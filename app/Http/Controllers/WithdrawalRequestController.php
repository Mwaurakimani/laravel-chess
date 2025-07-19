<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as HttpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class WithdrawalRequestController extends Controller
{
    public function view(Request $request, $id)
    {
        $userId = auth()->id();

        $withdrawalRequest = WithdrawalRequest::with(['initiatorUser', 'moderator', 'transaction'])->findOrFail($id);

        // Determine if the user is the initiator or the moderator
        $viewAs = $withdrawalRequest->moderator_account_id === $userId
            ? 'moderator'
            : 'requestor';

        // Tag the view_as field
        $withdrawalRequest->setAttribute('view_as', $viewAs);

        return Inertia::render('Player/wallet/WithdrawalDetails', [
            'withdrawalRequest' => $withdrawalRequest
        ]);
    }

    public function confirmReceipt($id): RedirectResponse
    {
        // Load the withdrawal request with its transaction
        $withdrawal = WithdrawalRequest::with('transaction')->findOrFail($id);

        // Only the request initiator can confirm
        if (Auth::id() !== $withdrawal->initiator) {
            abort(403, 'Unauthorized');
        }

        // If it’s already been completed, just redirect with success
        if ($withdrawal->request_status === 'completed'
            || ($withdrawal->transaction && $withdrawal->transaction->transaction_complete_status)
        ) {
            return redirect()
                ->route('wallet.withdrawal-details', $id)
                ->with('success', 'Withdrawal was already confirmed.');
        }

        DB::transaction(function () use ($withdrawal) {
            $transaction = $withdrawal->transaction;

            // 1) Update transaction to confirmed
            $transaction->update([
                'confirmation_status'         => true,
                'transaction_stage'           => 'confirmed',
                'transaction_complete_status' => true,
            ]);

            // 2) Mark the withdrawal request as completed
            $withdrawal->update(['request_status' => 'completed']);

            // 3) Adjust balances
            $initiator = User::findOrFail($transaction->transaction_origin);
            $moderator = User::findOrFail($transaction->transaction_destination);

            $initiator->balance -= $transaction->amount;
            $moderator->balance   += $transaction->amount;

            $initiator->save();
            $moderator->save();

            // 4) Notify the initiator
            $initNotif = HttpRequest::create('/notifications', 'POST', [
                'title'       => '✅ Withdrawal Completed',
                'message'     => "You confirmed receipt of KES {$transaction->amount}.",
                'type'        => 'withdrawal',
                'routeName'   => 'wallet.main',
                'routeParams' => [],
                'details'     => "Withdrawal ID: {$withdrawal->id}\nNew balance: KES {$initiator->balance}",
            ]);
            $initNotif->setUserResolver(fn() => $initiator);
            app(NotificationsController::class)->store($initNotif);

            // 5) Notify the moderator/peer
            $modNotif = HttpRequest::create('/notifications', 'POST', [
                'title'       => '📣 Withdrawal Confirmed by User',
                'message'     => "User {$initiator->name} confirmed your withdrawal of KES {$transaction->amount}.",
                'type'        => 'withdrawal',
                'routeName'   => 'wallet.main',
                'routeParams' => [],
                'details'     => "Withdrawal ID: {$withdrawal->id}",
            ]);
            $modNotif->setUserResolver(fn() => $moderator);
            app(NotificationsController::class)->store($modNotif);
        });

        return redirect()
            ->route('wallet.withdrawal-details', $id)
            ->with('success', 'Withdrawal confirmed and balances updated.');
    }

    public function markAsSent($id): RedirectResponse
    {
        $withdrawal = WithdrawalRequest::with('transaction')->findOrFail($id);

        if (auth()->id() !== $withdrawal->moderator_account_id) {
            abort(403, 'Unauthorized');
        }

        // 1) Update transaction status
        $withdrawal->transaction->update([
            'delivery_confirmation_status' => true,
            'transaction_stage'            => 'marked_sent',
        ]);

        // 2) Notify the initiator that funds were sent
        $initiator = $withdrawal->initiatorUser; // relationship

        $sentNotif = HttpRequest::create('/notifications', 'POST', [
            'title'       => '📤 Funds Sent',
            'message'     => "Your withdrawal #{$withdrawal->id} of KES {$withdrawal->transaction->amount} has been marked as sent.",
            'type'        => 'withdrawal',
            'routeName'   => 'wallet.withdrawal-details',
            'routeParams' => ['id' => $withdrawal->id],
            'details'     => "Sent by: {$withdrawal->moderator->name}",
        ]);
        $sentNotif->setUserResolver(fn() => $initiator);
        app(NotificationsController::class)->store($sentNotif);

        return redirect()->back()->with('success', 'Marked as sent.');
    }

}

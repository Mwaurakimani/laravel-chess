<?php

namespace App\Http\Controllers;

use App\Models\Notifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    // GET /notifications/all
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();   // use resolver
        $record = Notifications::firstOrCreate(
            ['user_id' => $user->id],
            ['notifications' => []]
        );

        // Grab only the first 10 entries
        $all   = $record->notifications;
        $first = array_slice($all, 0, 10);

        return response()->json($first);
    }


    // POST /notifications
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();   // <-- use resolver

        $request->validate([
            'title'       => 'required|string',
            'message'     => 'required|string',
            'type'        => 'required|string',
            'routeName'   => 'sometimes|string',
            'routeParams' => 'sometimes|array',
            'details'     => 'nullable|string',
        ]);

        $record = Notifications::firstOrCreate(
            ['user_id' => $user->id],
            ['notifications' => []]
        );

        $all = $record->notifications;
        $new = [
            'id'          => now()->timestamp,
            'title'       => $request->title,
            'message'     => $request->message,
            'type'        => $request->type,
            'timestamp'   => now(),
            'details'     => $request->details,
            'routeName'   => $request->routeName ?? null,
            'routeParams' => $request->routeParams ?? null,
        ];

        array_unshift($all, $new);
        $record->notifications = $all;
        $record->save();

        return response()->json($new, 201);
    }
}

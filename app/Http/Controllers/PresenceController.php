<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class PresenceController extends Controller
{
    public function chessOnline(Request $request): \Illuminate\Http\JsonResponse
    {
        // 1) Validate that 'username' is provided and exists in users.chess_com_link
        $data = $request->validate([
            'username' => ['required', 'string', 'exists:users,chess_com_link'],
        ]);

        $username = $data['username'];

        // 2) Call Chess.com API for full profile
        $resp = Http::get("https://api.chess.com/pub/player/{$username}");

        if (! $resp->successful()) {
            return response()->json([
                'online' => false,
                'error'  => 'Chess.com API error',
            ], 503);
        }

        $profile = $resp->json();

        // 3) Extract last_online (seconds since epoch)
        $lastOnline = $profile['last_online'] ?? null;

        if (! $lastOnline) {
            // No last_online field → treat as offline
            return response()->json(['online' => false]);
        }

        // 4) Compare against now (UTC)
        $nowUtc   = Carbon::now('UTC')->timestamp;
        $secondsSince = $nowUtc - intval($lastOnline);

        // If seen within last 120 seconds → online
        $isOnline = $secondsSince <= 120;

        return response()->json([
            'online' => $isOnline,
            'last_online_ago_seconds' => $secondsSince, // optional debug info
        ]);
    }
}

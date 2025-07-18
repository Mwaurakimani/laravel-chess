<?php

namespace App\Events;

use App\Models\Challenge;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChallengeCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Challenge $challenge;

    /**
     * Create a new event instance.
     */
    public function __construct(Challenge $challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * The channel this event should broadcast on.
     */
    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('online-users');
    }

    /**
     * The data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'          => $this->challenge->id,
            'user_id'     => $this->challenge->user_id,
            'stake'       => $this->challenge->stake,
            'time_control'=> $this->challenge->time_control,
        ];
    }

    /**
     * The event name for the frontend.
     */
    public function broadcastAs(): string
    {
        return 'ChallengeCreated';
    }
}

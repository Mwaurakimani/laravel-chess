<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed $user
 */
class Challenge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'opponent_id',
        'request_state',
        'position',
        'views',
        'challenge_status',
        'stake',
        'tokens',
        'platform_id',
        'time_control',
        'accepted_at',
        'rejected_at',
        'canceled_at',
    ];

    protected array $dates = [
        'accepted_at',
        'rejected_at',
        'canceled_at',
        'deleted_at',
    ];

    /**
     * The challenger (owner of the challenge).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The invited opponent.
     */


    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    /**
     * The platform where the challenge is to be played (e.g., Lichess, Chess.com).
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}

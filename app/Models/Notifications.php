<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifications extends Model
{
    protected $fillable = [
        'user_id',
        'notifications',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

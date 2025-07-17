<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChessMatchesResults extends Model
{
    protected $fillable = [
        'match_link',
        'match_type',
        'white',
        'black',
        'start_time',
        'end_time',
        'white_result',
        'black_result',
        'termination',
        'challenge_id',
    ];
}

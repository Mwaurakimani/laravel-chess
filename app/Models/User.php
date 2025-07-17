<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property mixed $chess_com_link
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "name",
        "email",
        "phone",
        "password",
        "balance",
        "token_balance",
        "lichess_link",
        "chess_com_link",
        "account_status",
        "roles",
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
        ];
    }

    // User.php

    public function withdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'initiator');
    }

    public function handledWithdrawals(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'moderator_account_id');
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transaction_origin');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transaction_destination');
    }

}

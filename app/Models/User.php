<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'pgsql'; // Central DB connection

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

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
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'home_currency_id');
    }

    public function agentTier()
    {
        return $this->belongsTo(AgentTier::class);
    }

    public function upline()
    {
        return $this->belongsTo(User::class, 'upline_id');
    }

    public function downlines()
    {
        return $this->hasMany(User::class, 'upline_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function walletTopups()
    {
        return $this->hasMany(WalletTopup::class);
    }

    public function walletWithdrawals()
    {
        return $this->hasMany(WalletWithdrawal::class);
    }

    // Cross-DB relationships
    public function investments()
    {
        return $this->hasMany(\App\Models\Tenant\Investment::class);
    }

    public function commissions()
    {
        return $this->hasMany(\App\Models\Tenant\Commission::class, 'recipient_user_id');
    }
}

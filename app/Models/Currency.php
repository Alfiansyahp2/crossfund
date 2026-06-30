<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $guarded = [];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'home_currency_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }
}

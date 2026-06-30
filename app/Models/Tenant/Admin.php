<?php

namespace App\Models\Tenant;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use \Laravel\Sanctum\HasApiTokens;

    protected $connection = 'tenant';
    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
}

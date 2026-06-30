<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $connection = 'tenant';
    protected $guarded = [];

    protected $hidden = [
        'password',
    ];
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Issuer extends Model
{
    protected $connection = 'tenant';
    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $connection = 'tenant';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'locked_until' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // Cross-DB relationship to Central DB
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $connection = 'tenant';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    // Cross-DB relationship to Central DB
    public function recipientUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'recipient_user_id');
    }
}

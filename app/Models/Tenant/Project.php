<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $connection = 'tenant';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'funding_end_date' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function issuer()
    {
        return $this->belongsTo(Issuer::class);
    }

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }
}

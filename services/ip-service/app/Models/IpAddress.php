<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAddress extends Model
{
    protected $fillable = [
        'subnet_id',
        'ip_address',
        'hostname',
        'mac_address',
        'status',
        'device_type',
        'description',
        'assigned_to',
        'last_seen',
        'label',
        'comment',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subnet_id' => 'integer',
        'last_seen' => 'datetime',
    ];

    public function subnet(): BelongsTo
    {
        return $this->belongsTo(Subnet::class);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ip_address', 'like', "%{$term}%")
              ->orWhere('hostname', 'like', "%{$term}%")
              ->orWhere('mac_address', 'like', "%{$term}%")
              ->orWhere('label', 'like', "%{$term}%");
        });
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
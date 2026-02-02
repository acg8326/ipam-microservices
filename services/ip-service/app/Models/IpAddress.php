<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class IpAddress extends Model
{
    protected $fillable = [
        'ip_address',
        'label',
        'comment',
        'created_by',
        'updated_by',
    ];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ip_address', 'like', "%{$term}%")
              ->orWhere('label', 'like', "%{$term}%");
        });
    }
}
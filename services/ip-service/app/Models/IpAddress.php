<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class IpAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'label',
        'comment',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];
    
    // This attribute is dynamically added and should not be persisted
    protected $appends = [];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('ip_address', 'like', "%{$term}%")
              ->orWhere('label', 'like', "%{$term}%");
        });
    }
    
    /**
     * Set created_by_name attribute without persisting it
     */
    public function setCreatedByNameAttribute($value)
    {
        $this->attributes['created_by_name'] = $value;
    }
    
    /**
     * Get created_by_name from attributes array (not database)
     */
    public function getCreatedByNameAttribute()
    {
        return $this->attributes['created_by_name'] ?? null;
    }
}
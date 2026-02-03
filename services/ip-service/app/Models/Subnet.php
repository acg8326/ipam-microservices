<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subnet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'network',
        'cidr',
        'gateway',
        'vlan_id',
        'description',
        'parent_id',
        'location_id',
    ];

    protected $casts = [
        'cidr' => 'integer',
        'vlan_id' => 'integer',
        'parent_id' => 'integer',
        'location_id' => 'integer',
    ];

    protected $appends = [
        'total_addresses',
        'used_addresses',
        'available_addresses',
        'utilization_percent',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subnet::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Subnet::class, 'parent_id');
    }

    public function ipAddresses(): HasMany
    {
        return $this->hasMany(IpAddress::class);
    }

    public function getTotalAddressesAttribute(): int
    {
        // Calculate total usable addresses based on CIDR
        // Subtract 2 for network and broadcast addresses (for /31 and /32 this is different)
        $totalIps = pow(2, 32 - $this->cidr);
        
        if ($this->cidr >= 31) {
            return $totalIps; // /31 and /32 are special cases
        }
        
        return max(0, $totalIps - 2); // Subtract network and broadcast
    }

    public function getUsedAddressesAttribute(): int
    {
        return $this->ipAddresses()
            ->whereIn('status', ['assigned', 'reserved', 'dhcp'])
            ->count();
    }

    public function getAvailableAddressesAttribute(): int
    {
        return max(0, $this->total_addresses - $this->used_addresses);
    }

    public function getUtilizationPercentAttribute(): float
    {
        if ($this->total_addresses === 0) {
            return 0;
        }
        
        return round(($this->used_addresses / $this->total_addresses) * 100, 1);
    }

    /**
     * Get the full CIDR notation (e.g., "10.0.0.0/24")
     */
    public function getCidrNotation(): string
    {
        return "{$this->network}/{$this->cidr}";
    }

    /**
     * Check if an IP address belongs to this subnet
     */
    public function containsIp(string $ipAddress): bool
    {
        $ip = ip2long($ipAddress);
        $network = ip2long($this->network);
        $mask = -1 << (32 - $this->cidr);
        
        return ($ip & $mask) === ($network & $mask);
    }
}

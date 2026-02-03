<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\IpAddress;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class IpAddressService
{
    public function list(?string $search = null): LengthAwarePaginator
    {
        $query = IpAddress::query()->orderBy('created_at', 'desc');

        if ($search) {
            $query->search($search);
        }

        $paginated = $query->paginate(15);
        
        // Fetch user names for created_by
        $userIds = $paginated->pluck('created_by')->unique()->filter()->values()->toArray();
        $userNames = $this->fetchUserNames($userIds);
        
        // Add created_by_name to each item
        $paginated->getCollection()->transform(function ($ip) use ($userNames) {
            $ip->created_by_name = $userNames[$ip->created_by] ?? 'Unknown';
            return $ip;
        });

        return $paginated;
    }

    public function find(int $id): IpAddress
    {
        $ipAddress = IpAddress::findOrFail($id);
        
        // Add created_by_name
        $userNames = $this->fetchUserNames([$ipAddress->created_by]);
        $ipAddress->created_by_name = $userNames[$ipAddress->created_by] ?? 'Unknown';
        
        return $ipAddress;
    }

    public function create(array $data, array $user, ?string $sessionId = null, ?string $clientIp = null): IpAddress
    {
        $ipAddress = IpAddress::create([
            'ip_address' => $data['ip_address'],
            'label' => $data['label'],
            'comment' => $data['comment'] ?? null,
            'created_by' => $user['id'],
        ]);

        AuditLog::log(
            'created',
            'ip_address',
            $ipAddress->id,
            null,
            $ipAddress->toArray(),
            $user['id'],
            $user['email'],
            $sessionId,
            $clientIp
        );

        $ipAddress->created_by_name = $user['name'] ?? $user['email'];
        
        return $ipAddress;
    }

    public function update(IpAddress $ipAddress, array $data, array $user, ?string $sessionId = null, ?string $clientIp = null): IpAddress
    {
        // Get a fresh model without appended attributes
        $ipAddress = IpAddress::findOrFail($ipAddress->id);
        $oldValues = $ipAddress->toArray();
        
        $updateData = [
            'label' => $data['label'],
            'updated_by' => $user['id'],
        ];
        
        if (array_key_exists('comment', $data)) {
            $updateData['comment'] = $data['comment'];
        }

        // Allow IP address change for admins OR for owners editing their own
        if (array_key_exists('ip_address', $data)) {
            $isAdmin = $user['role'] === 'admin';
            $isOwner = $ipAddress->created_by === (int) $user['id'];
            if ($isAdmin || $isOwner) {
                $updateData['ip_address'] = $data['ip_address'];
            }
        }
        
        $ipAddress->update($updateData);

        AuditLog::log(
            'updated',
            'ip_address',
            $ipAddress->id,
            $oldValues,
            $ipAddress->fresh()->toArray(),
            $user['id'],
            $user['email'],
            $sessionId,
            $clientIp
        );

        $fresh = $ipAddress->fresh();
        $userNames = $this->fetchUserNames([$fresh->created_by]);
        $fresh->created_by_name = $userNames[$fresh->created_by] ?? 'Unknown';
        
        return $fresh;
    }

    public function delete(IpAddress $ipAddress, array $user, ?string $sessionId = null, ?string $clientIp = null): void
    {
        $oldValues = $ipAddress->toArray();
        $id = $ipAddress->id;
        
        $ipAddress->delete();

        AuditLog::log(
            'deleted',
            'ip_address',
            $id,
            $oldValues,
            null,
            $user['id'],
            $user['email'],
            $sessionId,
            $clientIp
        );
    }

    public function canUserModify(array $user, IpAddress $ipAddress): bool
    {
        return $user['role'] === 'admin' || $ipAddress->created_by === (int) $user['id'];
    }

    public function canUserDelete(array $user): bool
    {
        return $user['role'] === 'admin';
    }
    
    private function fetchUserNames(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        
        try {
            $authServiceUrl = config('services.auth.url', 'http://auth-service');
            $response = Http::get("{$authServiceUrl}/api/users/names", [
                'ids' => implode(',', $userIds),
            ]);
            
            if ($response->successful()) {
                return $response->json('users', []);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::warning('Failed to fetch user names: ' . $e->getMessage());
        }
        
        return [];
    }
}

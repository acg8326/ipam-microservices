<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\IpAddress;
use Illuminate\Pagination\LengthAwarePaginator;

class IpAddressService
{
    public function list(?string $search = null): LengthAwarePaginator
    {
        $query = IpAddress::query();

        if ($search) {
            $query->search($search);
        }

        return $query->paginate(15);
    }

    public function find(int $id): IpAddress
    {
        return IpAddress::findOrFail($id);
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

        return $ipAddress;
    }

    public function update(IpAddress $ipAddress, array $data, array $user, ?string $sessionId = null, ?string $clientIp = null): IpAddress
    {
        $oldValues = $ipAddress->toArray();
        
        $data['updated_by'] = $user['id'];
        $ipAddress->update($data);

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

        return $ipAddress->fresh();
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
        return $user['role'] === 'admin' || $ipAddress->created_by === $user['id'];
    }

    public function canUserDelete(array $user): bool
    {
        return $user['role'] === 'admin';
    }
}

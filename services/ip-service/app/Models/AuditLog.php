<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'user_id',
        'user_email',
        'ip_address',
        'hash',
        'previous_hash',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public static function log(string $action, string $entityType, int $entityId, ?array $oldValues, ?array $newValues, int $userId, string $userEmail, ?string $ipAddress): self
    {
        $lastLog = self::latest('id')->first();
        $previousHash = $lastLog?->hash;

        $data = [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'ip_address' => $ipAddress,
            'previous_hash' => $previousHash,
            'created_at' => now(),
        ];

        $data['hash'] = self::generateHash($data, $previousHash);

        return self::create($data);
    }

    private static function generateHash(array $data, ?string $previousHash): string
    {
        $content = json_encode([
            'action' => $data['action'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'old_values' => $data['old_values'],
            'new_values' => $data['new_values'],
            'user_id' => $data['user_id'],
            'user_email' => $data['user_email'],
            'ip_address' => $data['ip_address'],
            'created_at' => $data['created_at']->toISOString(),
            'previous_hash' => $previousHash,
        ]);

        return hash('sha256', $content);
    }

    public static function verifyChain(): array
    {
        $logs = self::orderBy('id')->get();
        $results = ['valid' => true, 'errors' => []];

        foreach ($logs as $index => $log) {
            $expectedPreviousHash = $index > 0 ? $logs[$index - 1]->hash : null;

            if ($log->previous_hash !== $expectedPreviousHash) {
                $results['valid'] = false;
                $results['errors'][] = "Log #{$log->id}: Previous hash mismatch";
            }

            $expectedHash = self::generateHash([
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'user_id' => $log->user_id,
                'user_email' => $log->user_email,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
                'previous_hash' => $log->previous_hash,
            ], $log->previous_hash);

            if ($log->hash !== $expectedHash) {
                $results['valid'] = false;
                $results['errors'][] = "Log #{$log->id}: Hash mismatch - possible tampering";
            }
        }

        return $results;
    }
}
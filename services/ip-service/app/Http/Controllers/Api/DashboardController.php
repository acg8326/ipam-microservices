<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\IpAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        
        $totalIps = IpAddress::count();
        $myIps = IpAddress::where('created_by', $user['id'])->count();

        // Get recent activity from audit logs
        $recentActivity = AuditLog::where('entity_type', 'ip_address')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => str_replace(['created', 'updated', 'deleted'], ['create', 'update', 'delete'], $log->action),
                    'ip_address' => $log->new_values['ip_address'] ?? $log->old_values['ip_address'] ?? 'N/A',
                    'label' => $log->new_values['label'] ?? $log->old_values['label'] ?? 'N/A',
                    'user_name' => $log->user_email,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        return response()->json([
            'total_ips' => $totalIps,
            'my_ips' => $myIps,
            'recent_activity' => $recentActivity,
        ]);
    }
}

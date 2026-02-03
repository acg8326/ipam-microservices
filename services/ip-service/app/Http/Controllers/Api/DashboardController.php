<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpAddress;
use App\Models\Subnet;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalSubnets = Subnet::count();
        $totalIps = IpAddress::count();
        $assignedIps = IpAddress::where('status', 'assigned')->count();
        $reservedIps = IpAddress::where('status', 'reserved')->count();
        $availableIps = IpAddress::where('status', 'available')->count();
        $dhcpIps = IpAddress::where('status', 'dhcp')->count();

        $usedIps = $assignedIps + $reservedIps + $dhcpIps;
        $utilizationPercent = $totalIps > 0 
            ? round(($usedIps / $totalIps) * 100, 1) 
            : 0;

        // Get recent assignments
        $recentAssignments = IpAddress::where('status', 'assigned')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Get subnet utilization
        $subnetUtilization = Subnet::orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($subnet) {
                return [
                    'subnet_id' => $subnet->id,
                    'subnet_name' => $subnet->name,
                    'network' => "{$subnet->network}/{$subnet->cidr}",
                    'utilization_percent' => $subnet->utilization_percent,
                ];
            });

        return response()->json([
            'total_subnets' => $totalSubnets,
            'total_ips' => $totalIps,
            'assigned_ips' => $assignedIps,
            'reserved_ips' => $reservedIps,
            'available_ips' => $availableIps,
            'dhcp_ips' => $dhcpIps,
            'utilization_percent' => $utilizationPercent,
            'recent_assignments' => $recentAssignments,
            'subnet_utilization' => $subnetUtilization,
        ]);
    }
}

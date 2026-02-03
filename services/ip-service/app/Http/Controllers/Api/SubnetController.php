<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subnet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubnetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subnet::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('network', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Parent filter
        if ($request->has('parent_id')) {
            $parentId = $request->input('parent_id');
            if ($parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        // VLAN filter
        if ($request->has('vlan_id')) {
            $query->where('vlan_id', $request->input('vlan_id'));
        }

        $perPage = $request->input('per_page', 25);
        $subnets = $query->orderBy('network')->paginate($perPage);

        return response()->json($subnets);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'network' => 'required|ip',
            'cidr' => 'required|integer|min:0|max:32',
            'gateway' => 'nullable|ip',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:subnets,id',
            'location_id' => 'nullable|integer',
        ]);

        // Check for duplicate network/cidr
        $exists = Subnet::where('network', $validated['network'])
            ->where('cidr', $validated['cidr'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A subnet with this network and CIDR already exists.',
                'errors' => ['network' => ['This network/CIDR combination already exists.']]
            ], 422);
        }

        $subnet = Subnet::create($validated);

        return response()->json($subnet, 201);
    }

    public function show(int $id): JsonResponse
    {
        $subnet = Subnet::with(['children', 'parent'])->findOrFail($id);
        
        return response()->json($subnet);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subnet = Subnet::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'network' => ['sometimes', 'required', 'ip'],
            'cidr' => 'sometimes|required|integer|min:0|max:32',
            'gateway' => 'nullable|ip',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:subnets,id',
            'location_id' => 'nullable|integer',
        ]);

        // Check for duplicate if network or cidr changed
        if (isset($validated['network']) || isset($validated['cidr'])) {
            $network = $validated['network'] ?? $subnet->network;
            $cidr = $validated['cidr'] ?? $subnet->cidr;

            $exists = Subnet::where('network', $network)
                ->where('cidr', $cidr)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'A subnet with this network and CIDR already exists.',
                    'errors' => ['network' => ['This network/CIDR combination already exists.']]
                ], 422);
            }
        }

        // Prevent setting self as parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return response()->json([
                'message' => 'A subnet cannot be its own parent.',
                'errors' => ['parent_id' => ['A subnet cannot be its own parent.']]
            ], 422);
        }

        $subnet->update($validated);

        return response()->json($subnet);
    }

    public function destroy(int $id): JsonResponse
    {
        $subnet = Subnet::findOrFail($id);
        
        // Check if subnet has IP addresses
        if ($subnet->ipAddresses()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete subnet with assigned IP addresses.',
            ], 422);
        }

        $subnet->delete();

        return response()->json(null, 204);
    }

    public function tree(): JsonResponse
    {
        $subnets = Subnet::whereNull('parent_id')
            ->with('children.children')
            ->orderBy('network')
            ->get();

        return response()->json($subnets);
    }

    public function ipAddresses(Request $request, int $id): JsonResponse
    {
        $subnet = Subnet::findOrFail($id);
        
        $query = $subnet->ipAddresses();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        $ipAddresses = $query->orderByRaw('INET_ATON(ip_address)')->paginate($perPage);

        return response()->json($ipAddresses);
    }
}

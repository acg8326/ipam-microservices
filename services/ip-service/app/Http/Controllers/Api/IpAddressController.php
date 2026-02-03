<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIpAddressRequest;
use App\Http\Requests\UpdateIpAddressRequest;
use App\Services\IpAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpAddressController extends Controller
{
    public function __construct(
        private IpAddressService $ipAddressService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $ipAddresses = $this->ipAddressService->list($request->search);
        return response()->json($ipAddresses);
    }

    public function store(StoreIpAddressRequest $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $sessionId = $request->header('X-Session-Id');

        $ipAddress = $this->ipAddressService->create(
            $request->validated(),
            $user,
            $sessionId,
            $request->ip()
        );

        return response()->json([
            'message' => 'IP address created successfully.',
            'data' => $ipAddress
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $ipAddress = $this->ipAddressService->find($id);
        return response()->json($ipAddress);
    }

    public function update(UpdateIpAddressRequest $request, int $id): JsonResponse
    {
        $ipAddress = $this->ipAddressService->find($id);
        $user = $request->attributes->get('user');
        $sessionId = $request->header('X-Session-Id');

        if (!$this->ipAddressService->canUserModify($user, $ipAddress)) {
            return response()->json([
                'message' => 'Forbidden. You can only modify your own IP addresses.'
            ], 403);
        }

        $updated = $this->ipAddressService->update(
            $ipAddress,
            $request->validated(),
            $user,
            $sessionId,
            $request->ip()
        );

        return response()->json([
            'message' => 'IP address updated successfully.',
            'data' => $updated
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $ipAddress = $this->ipAddressService->find($id);
        $user = $request->attributes->get('user');
        $sessionId = $request->header('X-Session-Id');

        if (!$this->ipAddressService->canUserDelete($user)) {
            return response()->json([
                'message' => 'Forbidden. Only administrators can delete IP addresses.'
            ], 403);
        }

        $this->ipAddressService->delete($ipAddress, $user, $sessionId, $request->ip());

        return response()->json(['message' => 'IP address deleted successfully.']);
    }
}
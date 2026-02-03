<?php

namespace App\Http\Requests;

use App\Models\IpAddress;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIpAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ];

        // Check if user can modify IP address field
        $user = $this->attributes->get('user');
        $ipAddressId = $this->route('id');
        
        if ($user) {
            $ipAddress = IpAddress::find($ipAddressId);
            $isOwner = $ipAddress && $ipAddress->created_by === (int) $user['id'];
            $isAdmin = $user['role'] === 'admin';
            
            // Allow IP address change for admins OR for owners editing their own
            if ($isAdmin || $isOwner) {
                $rules['ip_address'] = 'sometimes|required|ip|unique:ip_addresses,ip_address,' . $ipAddressId;
            }
        }
        
        return $rules;
    }
}

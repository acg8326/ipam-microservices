<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIpAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip_address' => 'required|ip|unique:ip_addresses,ip_address',
            'label' => 'required|string|max:255',
            'comment' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => 'IP address is required.',
            'ip_address.ip' => 'Please enter a valid IPv4 or IPv6 address.',
            'ip_address.unique' => 'This IP address already exists in the system.',
            'label.required' => 'Label is required.',
            'label.max' => 'Label cannot exceed 255 characters.',
        ];
    }
}

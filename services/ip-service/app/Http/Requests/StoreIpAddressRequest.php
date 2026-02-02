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
}

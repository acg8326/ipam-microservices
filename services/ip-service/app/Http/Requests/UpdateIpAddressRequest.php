<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIpAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->attributes->get('user');
        $id = $this->route('id');

        if ($user['role'] === 'admin') {
            return [
                'ip_address' => 'sometimes|ip|unique:ip_addresses,ip_address,' . $id,
                'label' => 'sometimes|string|max:255',
                'comment' => 'nullable|string',
            ];
        }

        return [
            'label' => 'required|string|max:255',
        ];
    }
}

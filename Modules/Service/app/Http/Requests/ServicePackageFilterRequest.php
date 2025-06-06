<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServicePackageFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_service' => ['sometimes'],
            'customer_type' => ['sometimes', 'in:INDIVIDUAL,ORGANIZATION'],
            'billing_cycle' => ['sometimes', 'string', 'in:monthly,yearly,one-time'],
            'is_active' => ['sometimes'],
            'search' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
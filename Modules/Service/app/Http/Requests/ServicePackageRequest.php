<?php

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServicePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'type_service' => ['required', 'string', 'max:20', Rule::in(['SER_IHD', 'SER_CA', 'SER_EC'])],
            'customer_type' => ['required', 'string', 'max:20', Rule::in(['INDIVIDUAL', 'ORGANIZATION'])],
            'package_code' => ['required', 'string', 'max:20', 'unique:service_packages,package_code'],
            'package_name' => ['required', 'string', 'max:100'],
            'description' => ['sometimes', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:20'],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly', 'one-time'])],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['sometimes', 'numeric', 'min:0'],
            'features' => ['sometimes', 'array'],
            'features.*.feature_key' => ['required', 'string', 'max:50'],
            'features.*.feature_name' => ['required', 'string', 'max:100'],
            'features.*.feature_type' => ['required', 'string', Rule::in(['quantity', 'boolean'])],
            'features.*.unit' => ['nullable', 'string', 'max:20'],
            'features.*.limit_value' => ['nullable', 'numeric', 'min:0'],
            'features.*.is_optional' => ['required', 'boolean'],
            'features.*.is_customizable' => ['required', 'boolean'],
            'features.*.display_order' => ['required', 'integer', 'min:0'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['package_code'] = [
                'required',
                'string',
                'max:20',
                Rule::unique('service_packages', 'package_code')->ignore($this->route('service_package')),
            ];
        }

        return $rules;
    }
    public function attributes()
    {
        return [
            'type_service' => __('service::service.attributes.type_service'),
            'customer_type' => __('service::service.attributes.customer_type'),
            'package_code' => __('service::service.attributes.package_code'),
            'package_name' => __('service::service.attributes.package_name'),
            'description' => __('service::service.attributes.description'),
            'base_price' => __('service::service.attributes.base_price'),
            'currency' => __('service::service.attributes.currency'),
            'billing_cycle' => __('service::service.attributes.billing_cycle'),
            'is_active' => __('service::service.attributes.is_active'),
            'display_order' => __('service::service.attributes.display_order'),
            'features' => __('service::service.attributes.features'),
            'features.*.feature_key' => __('service::service.attributes.feature_key'),
            'features.*.feature_name' => __('service::service.attributes.feature_name'),
            'features.*.feature_type' => __('service::service.attributes.feature_type'),
            'features.*.unit' => __('service::service.attributes.unit'),
            'features.*.limit_value' => __('service::service.attributes.limit_value'),
            'features.*.is_optional' => __('service::service.attributes.is_optional'),
            'features.*.is_customizable' => __('service::service.attributes.is_customizable'),
            'features.*.display_order' => __('service::service.attributes.display_order'),
        ];
    }
}
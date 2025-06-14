<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_type' => ['required', Rule::in(['INDIVIDUAL', 'ORGANIZATION'])],
            'full_name' => 'nullable|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'gender' => ['nullable'], //, Rule::in(['male', 'female', 'other'])
            'date_of_birth' => 'nullable|date',
            'tax_code' => 'nullable|string|max:30',
            'industry' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'province_code' => 'nullable|max:20', // Assuming province_code is a string, adjust as necessary
            'identity_type' => ['nullable'], //, Rule::in(['CCCD', 'CMND', 'PP'])
            'identity_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'team_id' => 'nullable|uuid',
            'assigned_to' => 'nullable|uuid',
            // 'is_active' => 'boolean',
            'status' => ['nullable'],

            'contacts' => 'nullable|array',
            'contacts.*.contact_type' => ['required'], //, Rule::in(['phone', 'email'])
            'contacts.*.value' => 'required|string|max:255',
            'contacts.*.label' => 'nullable|string|max:20',
            'contacts.*.is_primary' => 'boolean',
            'contacts.*.note' => 'nullable|string|max:100',

            'representatives' => 'nullable|array',
            'representatives.*.full_name' => 'required|string|max:255',
            'representatives.*.position' => 'nullable|string|max:100',
            'representatives.*.phone' => 'nullable|string|max:30',
            'representatives.*.email' => 'nullable|email|max:255',
            'representatives.*.identity_type' => ['nullable'], //, Rule::in(['CCCD', 'CMND', 'PP'])
            'representatives.*.identity_number' => 'nullable|string|max:50',
            'representatives.*.note' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_type.required' => __('customer::validation.customer_type.required'),
            'customer_type.in' => __('customer::validation.customer_type.in'),
            'full_name.max' => __('customer::validation.full_name.max'),
            'contacts.*.contact_type.required' => __('customer::validation.contact_type.required'),
            'contacts.*.contact_type.in' => __('customer::validation.contact_type.in'),
            'contacts.*.value.required' => __('customer::validation.contact_value.required'),
            'representatives.*.full_name.required' => __('customer::validation.representative_name.required'),
        ];
    }
    public function attributes(): array
    {
        return [
            // Customer fields
            'customer_type' => __('customer::attributes.customer_type'),
            'full_name' => __('customer::attributes.full_name'),
            'short_name' => __('customer::attributes.short_name'),
            'gender' => __('customer::attributes.gender'),
            'date_of_birth' => __('customer::attributes.date_of_birth'),
            'tax_code' => __('customer::attributes.tax_code'),
            'industry' => __('customer::attributes.industry'),
            'address' => __('customer::attributes.address'),
            'province_code' => __('customer::attributes.province_code'),
            'identity_type' => __('customer::attributes.identity_type'),
            'identity_number' => __('customer::attributes.identity_number'),
            'position' => __('customer::attributes.position'),
            'website' => __('customer::attributes.website'),
            'team_id' => __('customer::attributes.team_id'),
            'assigned_to' => __('customer::attributes.assigned_to'),
            // 'is_active' => __('customer::attributes.is_active'),
            // 'status' => __('customer::attributes.status'),

            // Contact fields
            'contacts' => __('customer::attributes.contacts'),
            'contacts.*.contact_type' => __('customer::attributes.contact_type'),
            'contacts.*.value' => __('customer::attributes.contact_value'),
            'contacts.*.label' => __('customer::attributes.contact_label'),
            'contacts.*.is_primary' => __('customer::attributes.contact_is_primary'),
            'contacts.*.note' => __('customer::attributes.contact_note'),

            // Representative fields
            'representatives' => __('customer::attributes.representatives'),
            'representatives.*.full_name' => __('customer::attributes.representative_name'),
            'representatives.*.position' => __('customer::attributes.representative_position'),
            'representatives.*.phone' => __('customer::attributes.representative_phone'),
            'representatives.*.email' => __('customer::attributes.representative_email'),
            'representatives.*.identity_type' => __('customer::attributes.representative_identity_type'),
            'representatives.*.identity_number' => __('customer::attributes.representative_identity_number'),
            'representatives.*.note' => __('customer::attributes.representative_note'),
        ];
    }
}

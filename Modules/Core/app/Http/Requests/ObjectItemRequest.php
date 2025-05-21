<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ObjectItemRequest extends FormRequest
{
    public function authorize(): bool 
    {
        return true;
    }

    public function rules(): array
    {
        $objectItemId = $this->route('object') ?? $this->input('id');
        return [
            'object_type_id' => 'required|uuid|exists:object_types,id',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('objects', 'code')
                    ->ignore($objectItemId, 'id')
                    ->where(function ($query) {
                        // Thêm điều kiện object_type_id nếu cần
                        $query->where('object_type_id', $this->input('object_type_id'));
                        // Thêm tenant_id nếu hệ thống multi-tenant
                        if ($this->filled('tenant_id')) {
                            $query->where('tenant_id', $this->input('tenant_id'));
                        }
                        
                        return $query;
                    })
            ],
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'order' => 'nullable|integer',
            'tenant_id' => 'nullable|uuid',
            'meta' => 'nullable',
        ];
    }

    public function attributes(): array
    {
        return [
            'object_type_id' => __('core::object_item.attributes.object_type_id'),
            'code' => __('core::object_item.attributes.code'),
            'name' => __('core::object_item.attributes.name'),
            'status' => __('core::object_item.attributes.status'),
            'order' => __('core::object_item.attributes.order'),
            'tenant_id' => __('core::object_item.attributes.tenant_id'),
        ];
    }
}
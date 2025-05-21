<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ObjectTypeRequest extends FormRequest
{
    public function authorize(): bool 
    {
        return true;
    }

    public function rules(): array
    {
        // Lấy ID của object_type từ route hoặc input nếu có (dành cho update)
        $objectTypeId = $this->route('object_type') ?? $this->input('id');
        // Dùng Rule::unique với ignore để hỗ trợ cả insert và update
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('object_types', 'code')
                    ->ignore($objectTypeId, 'id') // Nếu là update thì bỏ qua chính nó
            ],
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'order' => 'nullable|integer',
            'tenant_id' => 'nullable|uuid',
            'parent_id' => 'nullable|uuid',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('core::object_type.attributes.code'),
            'name' => __('core::object_type.attributes.name'),
            'status' => __('core::object_type.attributes.status'),
            'order' => __('core::object_type.attributes.order'),
            'tenant_id' => __('core::object_type.attributes.tenant_id'),
            'parent_id' => __('core::object_type.attributes.parent_id'),
        ];
    }
}

<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ObjectMetaRequest extends FormRequest
{
    public function authorize(): bool 
    {
        return true;
    }

    public function rules(): array
    {
        $objectMetaId = $this->route('object_meta') ?? $this->input('id');
        return [
            'object_id' => 'required|uuid|exists:objects,id',
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('object_meta', 'key')
                    ->where('object_id', $this->input('object_id'))
                    ->ignore($objectMetaId)
            ],
            'value' => 'required|nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'object_id' => __('core::object_meta.attributes.object_id'),
            'key' => __('core::object_meta.attributes.key'),
            'value' => __('core::object_meta.attributes.value'),
        ];
    }
}
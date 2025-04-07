<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'parent_id' => 'nullable|uuid|exists:menus,id',
            'name' => 'required|string|max:100|unique:menus,name',
            'route' => 'nullable|string|max:255|unique:menus,route',
            'permission_name' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ];
    }
    public function messages(): array
    {
        return [
            'parent_id.uuid' => trans('validation.uuid', ['attribute' =>  trans('core::menus.attributes.parent_id')]),
            'parent_id.exists' => trans('validation.exists', ['attribute' =>  trans('core::menus.attributes.parent_id')]),
            'name.required' => trans('validation.required', ['attribute' => trans('core::menus.attributes.name')]),
            'name.string' => trans('validation.string', ['attribute' => trans('core::menus.attributes.name')]),
            'name.max' => trans('validation.max', ['attribute' => trans('core::menus.attributes.name'), 'max' => 100]),
            'name.unique' => trans('validation.unique', ['attribute' => trans('core::menus.attributes.name')]),
            'route.string' => trans('validation.string', ['attribute' =>  trans('core::menus.attributes.route')]),
            'route.unique' => trans('validation.unique', ['attribute' =>  trans('core::menus.attributes.route')]),
            'route.max' => trans('validation.max', ['attribute' =>  trans('core::menus.attributes.route'), 'max' => 255]),
            'permission_name.string' => trans('validation.string', ['attribute' =>  trans('core::menus.attributes.permission_name')]),
            'permission_name.max' => trans('validation.max', ['attribute' =>  trans('core::menus.attributes.permission_name'), 'max' => 255]),
            'icon.string' => trans('validation.string', ['attribute' =>  trans('core::menus.attributes.icon')]),
            'icon.max' => trans('validation.max', ['attribute' =>  trans('core::menus.attributes.icon'), 'max' => 50]),
            'sort_order.integer' => trans('validation.integer', ['attribute' =>  trans('core::menus.attributes.sort_order')]),
            'status.required' => trans('validation.required', ['attribute' =>  trans('core::menus.attributes.status')]),
            'status.in' => trans('validation.in', ['attribute' =>  trans('core::menus.attributes.status'), 'values' => 'active, inactive']),
        ];
    }

    // public function attributes(): array
    // {
    //     return trans('core::menus.attributes');
    // }
}

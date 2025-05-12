<?php

namespace Modules\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderCreateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|uuid|exists:folders,id',
            'description' => 'nullable|string'
        ];
    }
    public function messages()
    {
        return [
            'name.required' => __('filemanager::messages.folder.name_required'),
            'name.string' => __('filemanager::messages.folder.name_string'),
            'name.max' => __('filemanager::messages.folder.name_max'),
            'parent_id.uuid' => __('filemanager::messages.folder.parent_id_uuid'),
            'parent_id.exists' => __('filemanager::messages.folder.parent_id_exists'),
            'description.string' => __('filemanager::messages.folder.description_string')
        ];
    }
}
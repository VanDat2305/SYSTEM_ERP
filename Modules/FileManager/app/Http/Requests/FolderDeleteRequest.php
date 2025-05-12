<?php

namespace Modules\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderDeleteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|uuid|exists:folders,id'
        ];
    }
    public function messages()
    {
        return [
            'id.required' => __('filemanager::messages.folder.id_required'),
            'id.uuid' => __('filemanager::messages.folder.id_uuid'),
            'id.exists' => __('filemanager::messages.folder.id_exists')
        ];
    }
}
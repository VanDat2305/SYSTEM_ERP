<?php

namespace Modules\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileDeleteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|uuid|exists:files,id'
        ];
    }
    public function messages()
    {
        return [
            'id.required' => __('filemanager::messages.file.id_required'),
            'id.uuid' => __('filemanager::messages.file.id_uuid'),
            'id.exists' => __('filemanager::messages.file.id_exists')
        ];
    }
}
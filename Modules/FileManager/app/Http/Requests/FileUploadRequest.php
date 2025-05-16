<?php

namespace Modules\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'files' => 'required|array', // Max 10MB
            'folder_id' => 'nullable|uuid|exists:folders,id'
        ];
    }
    public function messages()
    {
        return [
            'files.required' => __('filemanager::messages.file.file_required'),
            'files.file' => __('filemanager::messages.file.file_file'),
            'files.max' => __('filemanager::messages.file.file_max'),
            'folder_id.uuid' => __('filemanager::messages.file.folder_id_uuid'),
            'folder_id.exists' => __('filemanager::messages.file.folder_id_exists')
        ];
    }
}
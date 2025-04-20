<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'in:active,inactive,pending,suspended,banned,deleted',
            'roles' => 'array',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function messages(): array
    {
        return [
            'name.required' => trans('validation.required', ['attribute' => trans('users::attr.users.name')]),
            'name.string' => trans('validation.string', ['attribute' => trans('users::attr.users.name')]),
            'name.max' => trans('validation.max.string', ['attribute' => trans('users::attr.users.name'), 'max' => 255]),
            'email.required' => trans('validation.required', ['attribute' => trans('users::attr.users.email')]),
            'email.email' => trans('validation.email', ['attribute' => trans('users::attr.users.email')]),
            'email.unique' => trans('validation.unique', ['attribute' => trans('users::attr.users.email')]),
            'password.required' => trans('validation.required', ['attribute' => trans('users::attr.users.password')]),
            'password.string' => trans('validation.string', ['attribute' => trans('users::attr.users.password')]),
            'password.min' => trans('validation.min.string', ['attribute' => trans('users::attr.users.password'), 'min' => 8]),
            'password.confirmed' => trans('validation.confirmed', ['attribute' => trans('users::attr.users.password')]),
            'status.in' => trans('validation.in', ['attribute' => trans('users::attr.users.status')]),
            'roles.array' => trans('validation.array', ['attribute' => trans('users::attr.roles.name')]),
        ];
    }
}

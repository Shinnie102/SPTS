<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateUserRequest
 * 
 * Validation request cho việc cập nhật user
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('userId');

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => [
                'sometimes', 
                'required', 
                'email', 
                'max:150', 
                Rule::unique('user', 'email')->ignore($userId, 'user_id')->whereNull('deleted_at')
            ],
            'username' => [
                'sometimes',
                'string', 
                'max:50', 
                Rule::unique('user', 'username')->ignore($userId, 'user_id')->whereNull('deleted_at')
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth' => ['nullable', 'date', 'before:today'],
            'gender_id' => ['nullable', 'integer', Rule::exists('gender_lookup', 'gender_id')],
            'major' => ['nullable', 'string', 'max:150'],
            'orientation_day' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên',
            'full_name.max' => 'Họ và tên không được quá 150 ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'birth.before' => 'Ngày sinh không hợp lệ',
        ];
    }
}
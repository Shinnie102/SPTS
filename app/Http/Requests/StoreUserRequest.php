<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreUserRequest
 * 
 * Validation request cho việc tạo user mới
 * Tuân thủ Single Responsibility Principle
 */
class StoreUserRequest extends FormRequest
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
        return [
            'role_id' => ['required', 'integer', Rule::exists('role', 'role_id')],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('user', 'email')->whereNull('deleted_at')],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('user', 'username')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:6'],
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
            'role_id.required' => 'Vui lòng chọn vai trò',
            'role_id.exists' => 'Vai trò không hợp lệ',
            'full_name.required' => 'Vui lòng nhập họ và tên',
            'full_name.max' => 'Họ và tên không được quá 150 ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không hợp lệ',
            'email.unique' => 'Email đã tồn tại trong hệ thống',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            'birth.before' => 'Ngày sinh không hợp lệ',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreFacultyRequest
 * 
 * Validation cho tạo Faculty mới
 */
class StoreFacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faculty_code' => [
                'required',
                'string',
                'min:2',
                'max:10',
                'regex:/^[A-Z0-9-]+$/',
            ],
            'faculty_name' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'faculty_status_id' => [
                'nullable',
                'integer',
                'exists:faculty_status,faculty_status_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_code.required' => 'Mã Khoa/Viện là bắt buộc',
            'faculty_code.min' => 'Mã Khoa/Viện phải có ít nhất 2 ký tự',
            'faculty_code.max' => 'Mã Khoa/Viện không được vượt quá 10 ký tự',
            'faculty_code.regex' => 'Mã Khoa/Viện chỉ được chứa chữ IN HOA, số và dấu gạch ngang',
            
            'faculty_name.required' => 'Tên Khoa/Viện là bắt buộc',
            'faculty_name.min' => 'Tên Khoa/Viện phải có ít nhất 5 ký tự',
            'faculty_name.max' => 'Tên Khoa/Viện không được vượt quá 150 ký tự',
            
            'faculty_status_id.exists' => 'Trạng thái không hợp lệ',
        ];
    }
}
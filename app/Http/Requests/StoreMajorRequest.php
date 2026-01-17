<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreMajorRequest
 * 
 * Validation cho tạo Major mới
 */
class StoreMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'major_code' => [
                'required',
                'string',
                'min:2',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'major_name' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'faculty_id' => [
                'required',
                'integer',
                'exists:faculty,faculty_id',
            ],
            'major_status_id' => [
                'nullable',
                'integer',
                'exists:major_status,major_status_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'major_code.required' => 'Mã Chuyên ngành là bắt buộc',
            'major_code.min' => 'Mã Chuyên ngành phải có ít nhất 2 ký tự',
            'major_code.max' => 'Mã Chuyên ngành không được vượt quá 10 ký tự',
            'major_code.regex' => 'Mã Chuyên ngành chỉ được chứa chữ IN HOA và số',
            
            'major_name.required' => 'Tên Chuyên ngành là bắt buộc',
            'major_name.min' => 'Tên Chuyên ngành phải có ít nhất 5 ký tự',
            'major_name.max' => 'Tên Chuyên ngành không được vượt quá 150 ký tự',
            
            'faculty_id.required' => 'Vui lòng chọn Khoa/Viện',
            'faculty_id.exists' => 'Khoa/Viện không tồn tại',
            
            'major_status_id.exists' => 'Trạng thái không hợp lệ',
        ];
    }
}
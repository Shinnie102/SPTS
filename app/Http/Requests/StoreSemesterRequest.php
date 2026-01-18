<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreSemesterRequest
 * 
 * Validation cho tạo Semester mới
 */
class StoreSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_year,academic_year_id',
            ],
            'semester_code' => [
                'required',
                'string',
                'max:50',
            ],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year_id.required' => 'Năm học là bắt buộc',
            'academic_year_id.exists' => 'Năm học không tồn tại',
            
            'semester_code.required' => 'Tên học kỳ là bắt buộc',
            'semester_code.max' => 'Tên học kỳ không được vượt quá 50 ký tự',
            
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ',
            
            'end_date.required' => 'Ngày kết thúc là bắt buộc',
            'end_date.date' => 'Ngày kết thúc không hợp lệ',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
        ];
    }
}
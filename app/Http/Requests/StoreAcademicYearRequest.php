<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreAcademicYearRequest
 * 
 * Validation cho tạo AcademicYear mới
 */
class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year_code' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
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
            'year_code.required' => 'Tên năm học là bắt buộc',
            'year_code.regex' => 'Tên năm học phải có định dạng YYYY-YYYY (VD: 2024-2025)',
            
            'start_date.required' => 'Ngày bắt đầu là bắt buộc',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ',
            
            'end_date.required' => 'Ngày kết thúc là bắt buộc',
            'end_date.date' => 'Ngày kết thúc không hợp lệ',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu',
        ];
    }
}
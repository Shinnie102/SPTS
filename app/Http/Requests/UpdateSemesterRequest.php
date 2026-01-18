<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateSemesterRequest
 * 
 * Validation cho cập nhật Semester
 */
class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
            ],
            'start_date' => [
                'sometimes',
                'required',
                'date',
                'date_format:Y-m-d',
            ],
            'end_date' => [
                'sometimes',
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
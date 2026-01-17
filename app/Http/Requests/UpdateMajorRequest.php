<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateMajorRequest
 * 
 * Validation cho cập nhật Major
 */
class UpdateMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'major_code' => [
                'nullable',
                'string',
                'min:2',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
            ],
            'major_name' => [
                'nullable',
                'string',
                'min:5',
                'max:150',
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
            'major_code.min' => 'Mã Chuyên ngành phải có ít nhất 2 ký tự',
            'major_code.max' => 'Mã Chuyên ngành không được vượt quá 10 ký tự',
            'major_code.regex' => 'Mã Chuyên ngành chỉ được chứa chữ IN HOA và số',
            
            'major_name.min' => 'Tên Chuyên ngành phải có ít nhất 5 ký tự',
            'major_name.max' => 'Tên Chuyên ngành không được vượt quá 150 ký tự',
            
            'major_status_id.exists' => 'Trạng thái không hợp lệ',
        ];
    }
}
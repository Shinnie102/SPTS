<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreCourseRequest
 * 
 * Validation cho tạo Course mới
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_code' => [
                'required',
                'string',
                'min:5',
                'max:10',
                'regex:/^[A-Z]{2,5}[0-9]{2,5}$/',
            ],
            'course_name' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'credit' => [
                'required',
                'integer',
                'min:1',
                'max:6',
            ],
            'major_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'major_ids.*' => [
                'integer',
                'exists:major,major_id',
            ],
            'grading_scheme_id' => [
                'nullable',
                'integer',
                'exists:grading_scheme,grading_scheme_id',
            ],
            'course_status_id' => [
                'nullable',
                'integer',
                'exists:course_status,course_status_id',
            ],
            'syllabus' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'course_code.required' => 'Mã Học phần là bắt buộc',
            'course_code.min' => 'Mã Học phần phải có ít nhất 5 ký tự',
            'course_code.max' => 'Mã Học phần không được vượt quá 10 ký tự',
            'course_code.regex' => 'Mã Học phần phải có định dạng: 2-5 chữ IN HOA + 2-5 số (VD: CS101, IT201)',
            
            'course_name.required' => 'Tên Học phần là bắt buộc',
            'course_name.min' => 'Tên Học phần phải có ít nhất 5 ký tự',
            'course_name.max' => 'Tên Học phần không được vượt quá 150 ký tự',
            
            'credit.required' => 'Số tín chỉ là bắt buộc',
            'credit.integer' => 'Số tín chỉ phải là số nguyên',
            'credit.min' => 'Số tín chỉ phải từ 1 đến 6',
            'credit.max' => 'Số tín chỉ phải từ 1 đến 6',
            
            'major_ids.required' => 'Vui lòng chọn ít nhất 1 Chuyên ngành',
            'major_ids.array' => 'Dữ liệu Chuyên ngành không hợp lệ',
            'major_ids.min' => 'Vui lòng chọn ít nhất 1 Chuyên ngành',
            'major_ids.*.exists' => 'Chuyên ngành không tồn tại',
            
            'grading_scheme_id.exists' => 'Cấu trúc điểm không hợp lệ',
            'course_status_id.exists' => 'Trạng thái không hợp lệ',
        ];
    }
}
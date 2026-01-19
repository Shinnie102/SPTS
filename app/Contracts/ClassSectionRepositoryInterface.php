<?php

namespace App\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface ClassSectionRepositoryInterface
 * 
 * Định nghĩa hợp đồng cho ClassSectionRepository
 * Tuân thủ Interface Segregation Principle (I in SOLID)
 * - Mỗi interface có mục đích rõ ràng
 * - Client chỉ phụ thuộc vào interface, không phụ thuộc vào implementation
 */
interface ClassSectionRepositoryInterface
{
    /**
     * Lấy danh sách lớp học với filter và pagination
     * 
     * @param array $filters Mảng filter (keyword, faculty_id, major_id, semester_id)
     * @param int $perPage Số bản ghi mỗi trang
     * @return LengthAwarePaginator
     */
    public function getPaginatedClassSections(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy chi tiết lớp học theo ID
     * 
     * @param int $classSectionId ID của lớp học
     * @return object|null
     */
    public function findById(int $classSectionId);

    /**
     * Lấy danh sách Khoa có môn học
     * 
     * @return array
     */
    public function getFacultiesWithCourses(): array;

    /**
     * Lấy danh sách Chuyên ngành của một Khoa
     * 
     * @param int $facultyId ID của Khoa
     * @return array
     */
    public function getMajorsByFaculty(int $facultyId): array;

    /**
     * Lấy danh sách Học kỳ
     * 
     * @return array
     */
    public function getSemesters(): array;

    /**
     * Lấy danh sách Trạng thái Lớp học
     * 
     * @return array
     */
    public function getStatuses(): array;
}

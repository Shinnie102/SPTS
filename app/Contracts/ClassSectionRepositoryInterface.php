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

    /**
     * Tạo mới một lớp học.
     *
     * @param array $data
     * @return \App\Models\ClassSection
     */
    public function create(array $data);

    /**
     * Kiểm tra mã lớp đã tồn tại chưa.
     *
     * @param string $classCode
     * @return bool
     */
    public function classCodeExists(string $classCode): bool;

    /**
     * Lưu danh sách buổi học (meetings) của lớp.
     *
     * @param array $meetings
     * @return void
     */
    public function createMeetings(array $meetings): void;

    /**
     * Thêm danh sách sinh viên vào lớp (Enrollment).
     *
     * @param int $classSectionId
     * @param array $studentIds
     * @param int $statusId
     * @return void
     */
    public function enrollStudents(int $classSectionId, array $studentIds, int $statusId = 1): void;

    /**
     * Danh sách giảng viên đang hoạt động.
     *
     * @return array
     */
    public function getActiveLecturers(): array;

    /**
     * Danh sách sinh viên đang hoạt động, hỗ trợ filter theo từ khóa.
     *
     * @param string|null $keyword
     * @return array
     */
    public function getActiveStudents(?string $keyword = null): array;

    /**
     * Danh sách phòng học còn hoạt động.
     *
     * @return array
     */
    public function getRooms(): array;

    /**
     * Danh sách ca học (time slots).
     *
     * @return array
     */
    public function getTimeSlots(): array;

    /**
     * Danh sách học phần (course versions).
     *
     * @return array
     */
    public function getCourseVersions(): array;

    /**
     * Danh sách năm học.
     *
     * @return array
     */
    public function getAcademicYears(): array;

    /**
     * Lấy danh sách học kỳ theo năm học.
     *
     * @param int $academicYearId
     * @return array
     */
    public function getSemestersByAcademicYear(int $academicYearId): array;

    /**
     * Lấy danh sách học phần (course versions) theo chuyên ngành.
     *
     * @param int $majorId
     * @return array
     */
    public function getCourseVersionsByMajor(int $majorId): array;
}

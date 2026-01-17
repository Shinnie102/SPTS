<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Models\CourseVersion;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class CourseService
 * 
 * Xử lý business logic liên quan đến Course
 * Tuân thủ Single Responsibility Principle (S in SOLID)
 */
class CourseService
{
    protected $courseRepository;

    public function __construct(CourseRepositoryInterface $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    /**
     * Kiểm tra mã học phần có tồn tại không
     */
    public function checkCodeExists(string $code): bool
    {
        return $this->courseRepository->codeExists($code);
    }

    /**
     * Lấy danh sách courses với phân trang
     */
    public function getCourses(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->courseRepository->getPaginatedCourses($filters, $perPage);
    }

    /**
     * Lấy chi tiết course
     */
    public function getCourseDetail(int $courseId): ?Course
    {
        return $this->courseRepository->findById($courseId);
    }

    /**
     * Tạo course mới (kèm course_version)
     */
    public function createCourse(array $data): array
    {
        // Validate major_ids
        if (empty($data['major_ids']) || !is_array($data['major_ids'])) {
            return [
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất 1 Chuyên ngành'
            ];
        }

        // Kiểm tra mã học phần đã tồn tại
        $existingCourse = $this->courseRepository->findByCode($data['course_code']);

        if ($existingCourse) {
            return [
                'success' => false,
                'message' => 'Mã Học phần đã tồn tại'
            ];
        }

        // Set default status
        if (empty($data['course_status_id'])) {
            $data['course_status_id'] = 1; // Active
        }

        try {
            // Tạo course
            $course = $this->courseRepository->create([
                'course_code' => $data['course_code'],
                'course_name' => $data['course_name'],
                'course_status_id' => $data['course_status_id'],
            ]);

            // Sync với majors
            $this->courseRepository->syncMajors($course->course_id, $data['major_ids']);

            // Tạo course_version (version_no = 1)
            $this->createCourseVersion($course->course_id, $data);

            return [
                'success' => true,
                'message' => 'Thêm Học phần thành công',
                'data' => $course
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cập nhật course
     */
    public function updateCourse(int $courseId, array $data): array
    {
        $course = $this->courseRepository->findById($courseId);

        if (!$course) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Học phần'
            ];
        }

        // Validate mã học phần (nếu thay đổi)
        if (!empty($data['course_code']) && $data['course_code'] !== $course->course_code) {
            if ($this->courseRepository->codeExists($data['course_code'], $courseId)) {
                return [
                    'success' => false,
                    'message' => 'Mã Học phần đã tồn tại'
                ];
            }
        }

        // Validate major_ids
        if (!empty($data['major_ids']) && !is_array($data['major_ids'])) {
            return [
                'success' => false,
                'message' => 'Dữ liệu Chuyên ngành không hợp lệ'
            ];
        }

        try {
            // Update course
            $this->courseRepository->update($courseId, [
                'course_code' => $data['course_code'] ?? $course->course_code,
                'course_name' => $data['course_name'] ?? $course->course_name,
                'course_status_id' => $data['course_status_id'] ?? $course->course_status_id,
            ]);

            // Sync majors nếu có
            if (!empty($data['major_ids'])) {
                $this->courseRepository->syncMajors($courseId, $data['major_ids']);
            }

            // Update course_version nếu có thay đổi credit hoặc grading_scheme
            if (!empty($data['credit']) || !empty($data['grading_scheme_id'])) {
                $this->updateLatestCourseVersion($courseId, $data);
            }

            return [
                'success' => true,
                'message' => 'Cập nhật Học phần thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Xóa course
     */
    public function deleteCourse(int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);

        if (!$course) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Học phần'
            ];
        }

        try {
            // Kiểm tra xem course có class_section không
            $versionIds = \DB::table('course_version')
                ->where('course_id', $courseId)
                ->pluck('course_version_id');

            if ($versionIds->isNotEmpty()) {
                $classSectionCount = \DB::table('class_section')
                    ->whereIn('course_version_id', $versionIds)
                    ->count();

                if ($classSectionCount > 0) {
                    return [
                        'success' => false,
                        'message' => 'Không thể xóa Học phần này vì còn ' . $classSectionCount . ' Lớp học phần. Vui lòng xóa các Lớp học phần trước.'
                    ];
                }
            }

            // Xóa tất cả course_versions
            \DB::table('course_version')->where('course_id', $courseId)->delete();

            // Detach course khỏi tất cả majors
            $course->majors()->detach();

            // Hard delete course
            $this->courseRepository->delete($courseId);

            return [
                'success' => true,
                'message' => 'Đã xóa Học phần thành công'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toggle lock/unlock course (chỉ ADMIN thấy khi locked)
     */
    public function toggleLock(int $courseId): array
    {
        $course = $this->courseRepository->findById($courseId);

        if (!$course) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy Học phần'
            ];
        }

        // Toggle status: 1 (Active) <-> 2 (Inactive/Locked)
        $newStatusId = $course->course_status_id == 1 ? 2 : 1;

        try {
            $this->courseRepository->toggleLock($courseId, $newStatusId);

            $statusText = $newStatusId == 1 ? 'mở khóa' : 'khóa';

            return [
                'success' => true,
                'message' => "Đã {$statusText} Học phần thành công",
                'new_status' => $newStatusId
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tạo course_version cho course
     */
    protected function createCourseVersion(int $courseId, array $data): void
    {
        // Lấy version_no cao nhất hiện tại
        $latestVersion = CourseVersion::where('course_id', $courseId)
            ->orderBy('version_no', 'desc')
            ->first();

        $versionNo = $latestVersion ? $latestVersion->version_no + 1 : 1;

        CourseVersion::create([
            'course_id' => $courseId,
            'version_no' => $versionNo,
            'credit' => $data['credit'] ?? 3,
            'syllabus' => $data['syllabus'] ?? null,
            'effective_from' => $data['effective_from'] ?? now(),
            'effective_to' => null,
            'status_id' => 1, // Active
            'created_at' => now(),
        ]);
    }

    /**
     * Update course_version mới nhất
     */
    protected function updateLatestCourseVersion(int $courseId, array $data): void
    {
        $latestVersion = CourseVersion::where('course_id', $courseId)
            ->where('status_id', 1)
            ->orderBy('version_no', 'desc')
            ->first();

        if ($latestVersion) {
            $updateData = [];
            
            if (!empty($data['credit'])) {
                $updateData['credit'] = $data['credit'];
            }
            
            if (!empty($data['syllabus'])) {
                $updateData['syllabus'] = $data['syllabus'];
            }

            if (!empty($updateData)) {
                $latestVersion->update($updateData);
            }
        }
    }
}
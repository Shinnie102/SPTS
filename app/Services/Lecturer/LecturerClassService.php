<?php

namespace App\Services\Lecturer;

use App\Models\ClassSection;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class LecturerClassService
{
    /**
     * @return array{classes: LengthAwarePaginator, currentClass: ?ClassSection, recommendedSelectedClassId: ?int}
     */
    public function getIndexViewData(int $lecturerId, ?int $selectedClassId, ?string $searchTerm, int $perPage = 15): array
    {
        $user = User::find($lecturerId);
        if (!$user || !$user->isLecturer()) {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        $query = ClassSection::with([
                'courseVersion.course',
                'status',
                'semester',
            ])
            ->withCount([
                'enrollments as valid_enrollments_count' => function ($query) {
                    $query->whereIn('enrollment_status_id', [1, 2]);
                },
            ])
            ->where('lecturer_id', $lecturerId);

        $searchTerm = $searchTerm !== null ? trim($searchTerm) : '';
        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('class_code', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('courseVersion.course', function ($query) use ($searchTerm) {
                        $query->where('course_code', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('course_name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        $classes = $query->orderBy('class_section_id', 'desc')->paginate($perPage);

        $currentClass = null;
        if ($selectedClassId) {
            $currentClass = ClassSection::with(['courseVersion.course'])
                ->where('class_section_id', $selectedClassId)
                ->where('lecturer_id', $lecturerId)
                ->first();
        }

        $recommendedSelectedClassId = null;
        if (!$currentClass && $classes->count() > 0) {
            $currentClass = $classes->first();
            if ($currentClass) {
                $recommendedSelectedClassId = (int) $currentClass->class_section_id;
            }
        }

        return compact('classes', 'currentClass', 'recommendedSelectedClassId');
    }

    /**
     * @return array{currentClass: ClassSection, classes: \Illuminate\Support\Collection}
     */
    public function getShowViewData(int $classSectionId, int $lecturerId): array
    {
        $currentClass = ClassSection::with(['courseVersion.course', 'status', 'semester'])
            ->where('class_section_id', $classSectionId)
            ->where('lecturer_id', $lecturerId)
            ->firstOrFail();

        $classes = ClassSection::with(['courseVersion.course'])
            ->where('lecturer_id', $lecturerId)
            ->orderBy('class_section_id', 'desc')
            ->get();

        return compact('currentClass', 'classes');
    }
}

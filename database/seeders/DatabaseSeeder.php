<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Announcement;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@spts.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create Teachers
        $teachers = [];
        for ($i = 1; $i <= 5; $i++) {
            $teachers[] = User::create([
                'name' => "Teacher $i",
                'email' => "teacher$i@spts.com",
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'phone' => "0901234$i$i$i",
                'gender' => $i % 2 == 0 ? 'female' : 'male',
                'is_active' => true,
            ]);
        }

        // Create Students
        $students = [];
        for ($i = 1; $i <= 30; $i++) {
            $students[] = User::create([
                'name' => "Student $i",
                'email' => "student$i@spts.com",
                'password' => Hash::make('password123'),
                'role' => 'student',
                'student_id' => sprintf('STU%05d', $i),
                'phone' => "0912345$i$i$i",
                'date_of_birth' => now()->subYears(20)->subDays($i * 10),
                'gender' => $i % 3 == 0 ? 'female' : ($i % 3 == 1 ? 'male' : 'other'),
                'is_active' => true,
            ]);
        }

        // Create Subjects
        $subjects = [
            ['code' => 'CS101', 'name' => 'Introduction to Computer Science', 'credits' => 3],
            ['code' => 'CS102', 'name' => 'Data Structures and Algorithms', 'credits' => 4],
            ['code' => 'CS201', 'name' => 'Database Systems', 'credits' => 3],
            ['code' => 'CS202', 'name' => 'Web Development', 'credits' => 3],
            ['code' => 'CS301', 'name' => 'Software Engineering', 'credits' => 4],
            ['code' => 'MATH101', 'name' => 'Calculus I', 'credits' => 3],
            ['code' => 'MATH201', 'name' => 'Linear Algebra', 'credits' => 3],
            ['code' => 'ENG101', 'name' => 'English Communication', 'credits' => 2],
        ];

        $subjectModels = [];
        foreach ($subjects as $subject) {
            $subjectModels[] = Subject::create([
                'code' => $subject['code'],
                'name' => $subject['name'],
                'description' => "Description for {$subject['name']}",
                'credits' => $subject['credits'],
                'is_active' => true,
            ]);
        }

        // Create Classes
        $classes = [];
        foreach ($subjectModels as $index => $subject) {
            $teacher = $teachers[$index % count($teachers)];
            
            $class = ClassModel::create([
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'code' => $subject->code . '-A1',
                'name' => $subject->name . ' - Section A1',
                'room' => 'Room ' . (101 + $index),
                'schedule' => 'Mon 8:00-10:00, Wed 13:00-15:00',
                'semester' => '2025-1',
                'max_students' => 30,
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->addMonths(4)->endOfMonth(),
                'is_active' => true,
            ]);

            $classes[] = $class;

            // Enroll random students (15-25 students per class)
            $enrollCount = rand(15, 25);
            $randomStudents = collect($students)->random($enrollCount);

            foreach ($randomStudents as $student) {
                $class->students()->attach($student->id, [
                    'enrolled_at' => now()->subDays(rand(1, 30)),
                    'status' => 'enrolled',
                ]);
            }

            // Create assignments for each class
            $assignmentTypes = ['homework', 'quiz', 'midterm', 'final', 'project'];
            
            for ($j = 1; $j <= 5; $j++) {
                $assignment = Assignment::create([
                    'class_id' => $class->id,
                    'title' => "Assignment $j - " . ucfirst($assignmentTypes[$j - 1]),
                    'description' => "Description for assignment $j",
                    'type' => $assignmentTypes[$j - 1],
                    'max_score' => 100,
                    'weight' => $assignmentTypes[$j - 1] === 'final' ? 30 : ($assignmentTypes[$j - 1] === 'midterm' ? 20 : 10),
                    'due_date' => now()->addDays($j * 7),
                    'is_published' => true,
                ]);

                // Create grades for enrolled students
                foreach ($class->students as $student) {
                    $hasSubmitted = rand(1, 10) > 2; // 80% submission rate
                    $isGraded = $hasSubmitted && rand(1, 10) > 3; // 70% of submissions are graded

                    Grade::create([
                        'assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                        'score' => $isGraded ? rand(60, 100) : null,
                        'feedback' => $isGraded ? 'Good work!' : null,
                        'submitted_at' => $hasSubmitted ? now()->subDays(rand(1, 5)) : null,
                        'graded_at' => $isGraded ? now()->subDays(rand(0, 3)) : null,
                        'graded_by' => $isGraded ? $teacher->id : null,
                    ]);
                }
            }

            // Create attendance records
            for ($day = 0; $day < 20; $day++) {
                $date = now()->subDays($day);
                
                // Only mark attendance on weekdays
                if ($date->isWeekday()) {
                    foreach ($class->students as $student) {
                        $statuses = ['present', 'absent', 'late', 'excused'];
                        $weights = [85, 5, 7, 3]; // 85% present, 5% absent, 7% late, 3% excused
                        
                        $status = $this->weightedRandom($statuses, $weights);

                        Attendance::create([
                            'class_id' => $class->id,
                            'student_id' => $student->id,
                            'date' => $date,
                            'status' => $status,
                            'note' => $status !== 'present' ? 'Note for ' . $status : null,
                            'marked_by' => $teacher->id,
                        ]);
                    }
                }
            }

            // Create announcements
            Announcement::create([
                'class_id' => $class->id,
                'created_by' => $teacher->id,
                'title' => 'Welcome to ' . $class->name,
                'content' => 'Welcome to the new semester! Please check the syllabus and assignment deadlines.',
                'priority' => 'high',
                'is_published' => true,
                'published_at' => now()->subDays(rand(1, 10)),
            ]);
        }

        // Create general announcements
        Announcement::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'System Maintenance Notice',
            'content' => 'The system will be under maintenance this weekend. Please plan accordingly.',
            'priority' => 'high',
            'is_published' => true,
            'published_at' => now()->subDays(3),
        ]);

        Announcement::create([
            'class_id' => null,
            'created_by' => $admin->id,
            'title' => 'Semester Schedule Update',
            'content' => 'The final exam schedule has been updated. Please check your respective class pages.',
            'priority' => 'normal',
            'is_published' => true,
            'published_at' => now()->subDays(7),
        ]);
    }

    /**
     * Select a random item based on weights
     */
    private function weightedRandom(array $items, array $weights): mixed
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($items as $index => $item) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $item;
            }
        }
        
        return $items[0];
    }
}

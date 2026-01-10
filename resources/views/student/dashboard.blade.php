@extends('layouts.app')

@section('title', 'Student Dashboard - SPTS')
@section('role', 'Student')

@section('nav-links')
    <a href="{{ route('student.dashboard') }}" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('student.classes') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        My Classes
    </a>
    <a href="{{ route('student.assignments') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Assignments
    </a>
    <a href="{{ route('student.grades') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Grades
    </a>
    <a href="{{ route('student.attendance') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Attendance
    </a>
    <a href="{{ route('student.announcements') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Announcements
    </a>
@endsection

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6">Student Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Enrolled Classes</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['enrolled_classes'] }}</p>
        </div>

        <div class="bg-green-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Assignments</h3>
            <p class="text-3xl font-bold text-green-600">{{ $stats['total_assignments'] }}</p>
        </div>

        <div class="bg-yellow-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Pending Submissions</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_submissions'] }}</p>
        </div>

        <div class="bg-purple-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Graded Assignments</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['graded_assignments'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upcoming Assignments -->
        <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Upcoming Assignments</h3>
            <div class="space-y-3">
                @forelse($upcomingAssignments as $assignment)
                    <div class="bg-white p-4 rounded shadow">
                        <h4 class="font-semibold">{{ $assignment->title }}</h4>
                        <p class="text-sm text-gray-600">{{ $assignment->class->name }}</p>
                        <p class="text-sm text-red-600">Due: {{ $assignment->due_date->format('M d, Y H:i') }}</p>
                        <a href="{{ route('student.assignments.show', $assignment) }}" class="text-blue-500 text-sm hover:underline">
                            View Details →
                        </a>
                    </div>
                @empty
                    <p class="text-gray-500">No upcoming assignments.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="bg-gray-50 p-6 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Recent Announcements</h3>
            <div class="space-y-3">
                @forelse($recentAnnouncements as $announcement)
                    <div class="bg-white p-4 rounded shadow">
                        <div class="flex justify-between items-start">
                            <h4 class="font-semibold">{{ $announcement->title }}</h4>
                            @if($announcement->priority === 'high')
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">High Priority</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($announcement->content, 100) }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $announcement->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No announcements.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- My Classes -->
    <div class="bg-gray-50 p-6 rounded-lg mt-6">
        <h3 class="text-lg font-semibold mb-4">My Classes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($enrolledClasses as $class)
                <div class="bg-white p-4 rounded shadow">
                    <h4 class="font-semibold">{{ $class->name }}</h4>
                    <p class="text-sm text-gray-600">{{ $class->subject->name }}</p>
                    <p class="text-sm text-gray-600">Lecturer: {{ $class->lecturer->name }}</p>
                    <p class="text-sm text-gray-600">Room: {{ $class->room }}</p>
                    <a href="{{ route('student.classes.show', $class) }}" class="text-blue-500 text-sm hover:underline mt-2 inline-block">
                        View Details →
                    </a>
                </div>
            @empty
                <p class="text-gray-500 col-span-3">No enrolled classes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

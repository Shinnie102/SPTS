@extends('layouts.app')

@section('title', 'Teacher Dashboard - SPTS')
@section('role', 'Teacher')

@section('nav-links')
    <a href="{{ route('teacher.dashboard') }}" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('teacher.classes') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        My Classes
    </a>
    <a href="{{ route('teacher.assignments') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Assignments
    </a>
    <a href="{{ route('teacher.announcements') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Announcements
    </a>
@endsection

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6">Teacher Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Classes</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['total_classes'] }}</p>
        </div>

        <div class="bg-green-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Active Classes</h3>
            <p class="text-3xl font-bold text-green-600">{{ $stats['active_classes'] }}</p>
        </div>

        <div class="bg-purple-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Students</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['total_students'] }}</p>
        </div>

        <div class="bg-yellow-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Pending Grading</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending_assignments'] }}</p>
        </div>
    </div>

    <!-- Recent Classes -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Recent Classes</h3>
        <div class="space-y-3">
            @forelse($recentClasses as $class)
                <div class="bg-white p-4 rounded shadow flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">{{ $class->name }}</h4>
                        <p class="text-sm text-gray-600">{{ $class->subject->name }} - {{ $class->code }}</p>
                    </div>
                    <a href="{{ route('teacher.classes.show', $class) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        View Details
                    </a>
                </div>
            @empty
                <p class="text-gray-500">No classes assigned yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

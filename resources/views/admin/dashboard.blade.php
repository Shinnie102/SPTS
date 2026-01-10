@extends('layouts.app')

@section('title', 'Admin Dashboard - SPTS')
@section('role', 'Admin')

@section('nav-links')
    <a href="{{ route('admin.dashboard') }}" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Dashboard
    </a>
    <a href="{{ route('admin.users') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Users
    </a>
    <a href="{{ route('admin.subjects') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Subjects
    </a>
    <a href="{{ route('admin.classes') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Classes
    </a>
    <a href="{{ route('admin.reports') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
        Reports
    </a>
@endsection

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6">Admin Dashboard</h2>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-blue-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Students</h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['total_students'] }}</p>
        </div>

        <div class="bg-green-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Lecturers</h3>
            <p class="text-3xl font-bold text-green-600">{{ $stats['total_lecturers'] }}</p>
        </div>

        <div class="bg-purple-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Classes</h3>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['total_classes'] }}</p>
        </div>

        <div class="bg-yellow-100 p-6 rounded-lg shadow">
            <h3 class="text-gray-600 text-sm font-medium">Total Subjects</h3>
            <p class="text-3xl font-bold text-yellow-600">{{ $stats['total_subjects'] }}</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.users.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Add New User
            </a>
            <a href="{{ route('admin.subjects.create') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                Add New Subject
            </a>
            <a href="{{ route('admin.classes.create') }}" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                Create New Class
            </a>
        </div>
    </div>
</div>
@endsection

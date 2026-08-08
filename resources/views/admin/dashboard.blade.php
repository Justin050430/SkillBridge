@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
<x-ui.page-header
    title="Dashboard"
    subtitle="Welcome back, Administrator." />
@endsection

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    <x-ui.stat-card
        title="Students"
        :value="$totalStudents">

        <x-slot:icon>
            <x-heroicon-o-academic-cap class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="Mentors"
        :value="$totalMentors">

        <x-slot:icon>
            <x-heroicon-o-user-group class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="Courses"
        :value="$totalCourses">

        <x-slot:icon>
            <x-heroicon-o-book-open class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="Enrollments"
        :value="$totalEnrollments">

        <x-slot:icon>
            <x-heroicon-o-clipboard-document-list class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

</div>

<div class="grid lg:grid-cols-3 gap-6 mt-8">

    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <h2 class="text-xl font-semibold mb-4">
            Recent Activities
        </h2>

        <div class="text-slate-500">
            No recent activities.
        </div>

    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <h2 class="text-xl font-semibold mb-4">
            Quick Actions
        </h2>

        <div class="space-y-3">

            <a href="{{ route('admin.users') }}"
               class="block bg-indigo-600 hover:bg-indigo-700 text-white text-center py-3 rounded-xl transition">

                Manage Users

            </a>

            <a
                href="{{ route('admin.courses.create') }}"
                class="block w-full bg-slate-100 hover:bg-slate-200 text-center py-3 rounded-xl transition">

                Add Course

            </a>

            <a
                href="{{ route('assessments.index') }}"
                class="block w-full bg-slate-100 hover:bg-slate-200 text-center py-3 rounded-xl transition">

                Manage Assessments

            </a>

            <button
                class="w-full bg-slate-100 hover:bg-slate-200 py-3 rounded-xl transition">

                View Reports

            </button>

        </div>

    </div>

</div>

@endsection

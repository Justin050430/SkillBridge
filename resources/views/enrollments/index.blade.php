@extends('layouts.admin')

@section('title', 'Enrollment Management')

@section('header')
<x-ui.page-header
    title="Enrollment Management"
    subtitle="Oversee student enrollments across the courses." />
@endsection

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">

            <x-text-input
                name="search"
                :value="request('search')"
                placeholder="Search by student or course..."
                class="w-full sm:w-80" />

            <select
                name="status"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Statuses</option>
                <option value="Active" @selected(request('status') === 'Active')>Active</option>
                <option value="Completed" @selected(request('status') === 'Completed')>Completed</option>
                <option value="Dropped" @selected(request('status') === 'Dropped')>Dropped</option>

            </select>

        </form>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full min-w-[820px]">

            <thead class="bg-slate-100">

                <tr>
                    <th class="text-left p-4">Student</th>
                    <th class="text-left p-4">Course</th>
                    <th class="text-left p-4">Mentor</th>
                    <th class="text-left p-4">Status</th>
                    <th class="text-left p-4">Enrolled</th>
                    <th class="text-center p-4">Action</th>
                </tr>

            </thead>

            <tbody>

                @forelse($enrollments as $enrollment)

                @php
                    $statusClasses = match ($enrollment->status) {
                        'Active' => 'bg-emerald-100 text-emerald-700',
                        'Completed' => 'bg-indigo-100 text-indigo-700',
                        default => 'bg-slate-200 text-slate-700',
                    };
                @endphp

                <tr class="border-t border-slate-200 hover:bg-slate-50">

                    <td class="p-4">
                        <p class="font-medium text-slate-900">
                            {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ $enrollment->student->email }}</p>
                    </td>

                    <td class="p-4">
                        <p class="text-slate-700">{{ $enrollment->course->title }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $enrollment->course->category->category_name }}</p>
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $enrollment->course->mentor->first_name }}
                        {{ $enrollment->course->mentor->last_name }}
                    </td>

                    <td class="p-4">
                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                            {{ $enrollment->status }}
                        </span>
                    </td>

                    <td class="p-4 text-slate-600">
                        {{ $enrollment->enrolled_at?->format('d M Y') }}
                    </td>

                    <td class="p-4 text-center">
                        <div class="flex justify-center gap-2">

                            @if($enrollment->status === 'Active')

                            <form method="POST" action="{{ route('enrollments.complete', $enrollment) }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">
                                    Complete
                                </button>
                            </form>

                            @endif

                            @if(in_array($enrollment->status, ['Active', 'Completed'], true))

                            <form
                                method="POST"
                                action="{{ route('enrollments.drop', $enrollment) }}"
                                onsubmit="return confirm('Drop this enrollment?')">

                                @csrf

                                <button
                                    type="submit"
                                    class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">
                                    Drop
                                </button>
                            </form>

                            @endif

                        </div>
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="6" class="py-16 text-center">
                        <x-heroicon-o-user-group class="w-12 h-12 mx-auto text-slate-300" />

                        <h3 class="mt-4 text-xl font-semibold text-slate-700">
                            No enrollments found
                        </h3>

                        <p class="mt-2 text-slate-500">
                            Enrollments will appear here once students join courses.
                        </p>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="p-6">
        {{ $enrollments->links() }}
    </div>

</div>

@endsection

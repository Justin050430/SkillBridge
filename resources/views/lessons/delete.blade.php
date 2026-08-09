@extends('layouts.admin')

@section('title', 'Delete Lesson')

@section('header')
<x-ui.page-header
    title="Delete Lesson"
    subtitle="Remove a lesson from {{ $course->title }}." />
@endsection

@section('content')

<div class="max-w-xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <h2 class="text-xl font-semibold text-red-600">
            Are you sure?
        </h2>

        <p class="mt-4 text-slate-600">
            You are about to delete
            <strong>{{ $lesson->title }}</strong>.
        </p>

        @if($lesson->assessments_count > 0 || $lesson->progresses_count > 0)

            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-700">
                This lesson has
                <strong>{{ $lesson->assessments_count }} assessment(s)</strong> and
                <strong>{{ $lesson->progresses_count }} progress record(s)</strong>.
                It cannot be deleted because deleting it would remove historical
                assessment and progress data.
            </div>

        @else

            <p class="mt-2 text-slate-500">
                Deleting a lesson removes it permanently. This action cannot be undone.
            </p>

        @endif

        <form
            method="POST"
            action="{{ route('courses.lessons.destroy', [$course, $lesson]) }}"
            class="mt-8 flex justify-end gap-3">

            @csrf
            @method('DELETE')

            <a
                href="{{ route('courses.lessons.index', $course) }}"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">

                Cancel

            </a>

            <button
                type="submit"
                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white {{ ($lesson->assessments_count > 0 || $lesson->progresses_count > 0) ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ ($lesson->assessments_count > 0 || $lesson->progresses_count > 0) ? 'disabled' : '' }}>

                Delete Lesson

            </button>

        </form>

    </div>

</div>

@endsection

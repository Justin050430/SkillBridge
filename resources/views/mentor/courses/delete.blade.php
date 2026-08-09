@extends('layouts.admin')

@section('title', 'Delete Course')

@section('header')
<x-ui.page-header
    title="Delete Course"
    subtitle="Remove a course from your teaching catalogue." />
@endsection

@section('content')

<div class="max-w-xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <h2 class="text-xl font-semibold text-red-600">Are you sure?</h2>

        <p class="mt-4 text-slate-600">
            You are about to delete <strong>{{ $course->title }}</strong>.
        </p>

        @if($course->lessons_count || $course->enrollments_count)
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">
                This course cannot be deleted because it has lessons or enrollments.
                Deleting it would remove historical learning data.
            </div>
        @else
            <p class="mt-2 text-slate-500">This action cannot be undone.</p>
        @endif

        <form
            method="POST"
            action="{{ route('mentor.courses.destroy', $course) }}"
            class="mt-8 flex justify-end gap-3">

            @csrf
            @method('DELETE')

            <a
                href="{{ route('mentor.courses') }}"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">
                Cancel
            </a>

            @if(!$course->lessons_count && !$course->enrollments_count)
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white">
                    Delete Course
                </button>
            @endif

        </form>

    </div>

</div>

@endsection

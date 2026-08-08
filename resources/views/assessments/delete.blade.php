@extends('layouts.admin')

@section('title', 'Delete Assessment')

@section('header')
<x-ui.page-header
    title="Delete Assessment"
    subtitle="This action cannot be undone." />
@endsection

@section('content')

<div class="max-w-xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <h2 class="text-xl font-semibold text-red-600">Are you sure?</h2>

        <p class="mt-4 text-slate-600">
            You are about to delete <strong>{{ $assessment->title }}</strong>
            from the lesson <strong>{{ $assessment->lesson->title }}</strong>.
        </p>

        @if($assessment->questions_count || $assessment->attempts_count)
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-800">
                This assessment has {{ $assessment->questions_count }} question(s) and
                {{ $assessment->attempts_count }} attempt(s). Deleting it will also remove
                those questions and attempts.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('assessments.destroy', $assessment) }}"
            class="mt-8 flex justify-end gap-3">

            @csrf
            @method('DELETE')

            <a
                href="{{ route('assessments.index') }}"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">
                Cancel
            </a>

            <button
                type="submit"
                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white">
                Delete Assessment
            </button>

        </form>

    </div>

</div>

@endsection

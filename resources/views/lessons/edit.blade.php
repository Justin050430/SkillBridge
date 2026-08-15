@extends('layouts.admin')

@section('title', 'Edit Lesson')

@section('header')
<x-ui.page-header
    title="Edit Lesson"
    subtitle="Modify the lesson content and settings." />
@endsection

@section('content')

<div class="max-w-4xl bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

    @include('lessons._form', [
        'buttonText' => 'Update Lesson',
        'action' => route('courses.lessons.update', [$course, $lesson]),
    ])

</div>

@endsection

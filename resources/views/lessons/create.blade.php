@extends('layouts.admin')

@section('title', 'Create Lesson')

@section('header')
<x-ui.page-header
    title="Create Lesson"
    subtitle="Add a new lesson to {{ $course->title }}." />
@endsection

@section('content')

<div class="max-w-4xl bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

    @include('lessons._form', [
        'buttonText' => 'Create Lesson',
        'action' => route('courses.lessons.store', $course),
    ])

</div>

@endsection

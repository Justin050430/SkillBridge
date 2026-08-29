@extends('layouts.admin')

@section('title', 'Edit Course')

@section('header')
<x-ui.page-header
    title="Edit Course"
    subtitle="Update the course details and publication status." />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('admin.courses.update', $course) }}">
            @csrf
            @method('PUT')

            @include('admin.courses._form', [
                'buttonText' => 'Update Course'
            ])
        </form>

    </div>

    @include('partials.assessment-analytics', ['analytics' => $analytics ?? null, 'course' => $course])

</div>

@endsection

@extends('layouts.admin')

@section('title', 'Create Course')

@section('header')
<x-ui.page-header
    title="Create Course"
    subtitle="Add a new course to your teaching catalogue." />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('mentor.courses.store') }}">

            @include('mentor.courses._form', [
                'buttonText' => 'Create Course'
            ])

        </form>

    </div>

</div>

@endsection

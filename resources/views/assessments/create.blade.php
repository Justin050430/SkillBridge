@extends('layouts.admin')

@section('title', 'Create Assessment')

@section('header')
<x-ui.page-header
    title="Create Assessment"
    subtitle="Add a new assessment to a lesson." />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('assessments.store') }}">
            @include('assessments._form', [
                'buttonText' => 'Create Assessment'
            ])
        </form>

    </div>

</div>

@endsection

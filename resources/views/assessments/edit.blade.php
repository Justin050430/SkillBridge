@extends('layouts.admin')

@section('title', 'Edit Assessment')

@section('header')
<x-ui.page-header
    title="Edit Assessment"
    subtitle="Update the assessment details and rules." />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('assessments.update', $assessment) }}">
            @method('PUT')
            @include('assessments._form', [
                'buttonText' => 'Update Assessment'
            ])
        </form>

    </div>

</div>

@endsection

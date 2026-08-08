@extends('layouts.admin')

@section('title', 'Add Question')

@section('header')
<x-ui.page-header
    title="Add Question"
    subtitle="{{ $assessment->title }}" />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('assessments.questions.store', $assessment) }}">
            @include('assessments.questions._form', [
                'buttonText' => 'Add Question'
            ])
        </form>

    </div>

</div>

@endsection

@extends('layouts.admin')

@section('title', 'Edit Question')

@section('header')
<x-ui.page-header
    title="Edit Question"
    subtitle="{{ $assessment->title }}" />
@endsection

@section('content')

<div class="max-w-4xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('assessments.questions.update', [$assessment, $question]) }}">
            @method('PUT')
            @include('assessments.questions._form', [
                'buttonText' => 'Update Question'
            ])
        </form>

    </div>

</div>

@endsection

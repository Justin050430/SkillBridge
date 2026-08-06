@extends('layouts.admin')

@section('title', 'Create User')

@section('header')
<x-ui.page-header
    title="Create User"
    subtitle="Create a new user account." />
@endsection

@section('content')

<div class="max-w-3xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <form method="POST" action="{{ route('admin.users.store') }}">

            @include('admin.users._form', [
            'buttonText' => 'Create User'
            ])

        </form>

    </div>

</div>

@endsection
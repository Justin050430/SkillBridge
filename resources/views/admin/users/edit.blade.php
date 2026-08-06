@extends('layouts.admin')

@section('title', 'Edit User')

@section('header')
<x-ui.page-header
    title="Edit User"
    subtitle="Modify the user's account information and permissions." />
@endsection

@section('content')

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

    <form
        method="POST"
        action="{{ route('admin.users.update', $user) }}">

        @csrf
        @method('PUT')

        @include('admin.users._form', [
        'buttonText' => 'Update User'
        ])

    </form>

</div>

@endsection
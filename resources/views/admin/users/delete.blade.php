@extends('layouts.admin')

@section('title', 'Delete User')

@section('header')
<x-ui.page-header
    title="Delete User"
    subtitle="Permanently remove the account (soft delete) without destroying its data." />
@endsection

@section('content')

<div class="max-w-xl">

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">

        <h2 class="text-xl font-semibold text-red-600">
            Are you sure?
        </h2>

        <p class="mt-4 text-slate-600">
            You are about to delete
            <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>.
        </p>

        <p class="mt-2 text-slate-500">
            The account will be marked as <strong>Deleted</strong> and can no
            longer sign in. The record, its ID and all historical data are
            preserved and can be reviewed using the status filter.
        </p>

        <p class="mt-2 text-slate-500">
            To simply suspend an account so it can be reactivated later, use
            <strong>Deactivate</strong> instead.
        </p>

        <form
            method="POST"
            action="{{ route('admin.users.destroy', $user) }}"
            class="mt-8 flex justify-end gap-3">

            @csrf
            @method('DELETE')

            <a
                href="{{ route('admin.users') }}"
                class="px-5 py-2 rounded-xl border border-slate-300 hover:bg-slate-100">

                Cancel

            </a>

            <button
                type="submit"
                class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white">

                Delete User

            </button>

        </form>

    </div>

</div>

@endsection
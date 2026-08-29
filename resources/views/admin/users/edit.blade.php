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

{{-- Enrollment statistics consumed from the Enrollment Management web service
     (GET /api/enrollments/user/{user}/stats) via App\Services\EnrollmentStatsClient. --}}
<div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <h2 class="text-lg font-semibold text-slate-900 mb-1">Enrollment Statistics</h2>
    <p class="text-sm text-slate-500 mb-6">
        Live enrolment activity consumed from the Enrollment Management module web service
        (<code class="text-xs">GET /api/enrollments/user/{{ $user->id }}/stats</code>).
    </p>

    @if (($enrollmentStats ?? null) === null)
        <div class="text-sm text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-4">
            Enrollment statistics are temporarily unavailable. The Enrollment Management web service could not be reached.
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-4">
                <div class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Total</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $enrollmentStats['total'] }}</div>
            </div>
            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                <div class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Active</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $enrollmentStats['active'] }}</div>
            </div>
            <div class="rounded-xl bg-sky-50 border border-sky-100 p-4">
                <div class="text-xs font-medium text-sky-600 uppercase tracking-wide">Completed</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $enrollmentStats['completed'] }}</div>
            </div>
            <div class="rounded-xl bg-rose-50 border border-rose-100 p-4">
                <div class="text-xs font-medium text-rose-600 uppercase tracking-wide">Dropped</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $enrollmentStats['dropped'] }}</div>
            </div>
        </div>
    @endif
</div>

@endsection
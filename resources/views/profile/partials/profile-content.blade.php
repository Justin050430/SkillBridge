{{-- Shared profile page content.

    Included by both the student shell (profile.edit -> x-app-layout) and the
    admin/mentor shell (profile.manage -> layouts.admin), so every role sees
    one consistent profile page showing their account information while still
    getting their usual navigation chrome.
--}}

@php
    $roleName = $user->role->role_name ?? 'User';

    $roleBadge = match ($roleName) {
        'Admin' => 'bg-red-100 text-red-700',
        'Mentor' => 'bg-indigo-100 text-indigo-700',
        default => 'bg-emerald-100 text-emerald-700',
    };

    $statusBadge = match ($user->status) {
        'Active' => 'bg-emerald-100 text-emerald-700',
        'Inactive' => 'bg-amber-100 text-amber-700',
        default => 'bg-red-100 text-red-700',
    };

    $initials = strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1));
@endphp

<div class="space-y-6">

    {{-- Profile header card: account information at a glance. --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="h-24 bg-gradient-to-r from-indigo-600 to-indigo-400"></div>

        <div class="px-6 pb-6">

            <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end">

                <div
                    class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white ring-4 ring-white">
                    {{ $initials }}
                </div>

                <div class="pb-1">

                    <h3 class="text-xl font-bold text-slate-900">
                        {{ $user->first_name }} {{ $user->last_name }}
                    </h3>

                    <p class="text-sm text-slate-500">
                        {{ $user->email }}
                    </p>

                </div>

            </div>

            <div class="mt-4 flex flex-wrap gap-2">

                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $roleBadge }}">
                    {{ $roleName }}
                </span>

                <span class="rounded-full px-3 py-1 text-sm font-medium {{ $statusBadge }}">
                    {{ $user->status }} account
                </span>

            </div>

        </div>

    </div>

    {{-- Update profile information --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    {{-- Update password --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    {{-- Delete account --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</div>

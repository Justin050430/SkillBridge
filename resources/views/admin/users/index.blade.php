@extends('layouts.admin')

@section('title', 'User Management')

@section('header')
<x-ui.page-header
    title="User Management"
    subtitle="Manage admins, mentors and students." />
@endsection

@section('content')

<div class="bg-white rounded-2xl shadow-sm border border-slate-200">

    <div class="p-6 flex justify-between items-center">

        <form method="GET" class="flex gap-3">

            <x-text-input
                name="search"
                :value="request('search')"
                placeholder="Search by name, email or role..."
                class="w-72"/>

            <select
                name="role"
                onchange="this.form.submit()"
                class="rounded-lg border-gray-300">

                <option value="">All Roles</option>

                @foreach($roles as $role)

                <option
                    value="{{ $role->id }}"
                    @selected(request('role') == $role->id)>

                    {{ $role->role_name }}

                </option>

                @endforeach

            </select>

        </form>

        <a
            href="{{ route('admin.users.create') }}"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl">

            + Add User

        </a>

    </div>
    
    <table class="w-full">

        <thead class="bg-slate-100">

            <tr>

                <th class="text-left p-4">Name</th>
                <th class="text-left p-4">Email</th>
                <th class="text-left p-4">Role</th>
                <th class="text-center p-4">Action</th>

            </tr>

        </thead>

        <tbody>

            @if($users->count())

            @foreach($users as $user)

            <tr class="border-t hover:bg-slate-50">

                <td class="p-4">
                    {{ $user->first_name }} {{ $user->last_name }}
                </td>

                <td class="p-4">
                    {{ $user->email }}
                </td>

                <td class="p-4">

                    @php
                    $badge = match($user->role->role_name) {
                    'Admin' => 'bg-red-100 text-red-700',
                    'Mentor' => 'bg-indigo-100 text-indigo-700',
                    default => 'bg-emerald-100 text-emerald-700',
                    };
                    @endphp

                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $badge }}">
                        {{ $user->role->role_name }}
                    </span>

                </td>

                <td class="p-4 text-center">

                    <div class="flex justify-center gap-2">

                        <a
                            href="{{ route('admin.users.edit',$user) }}"
                            class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100">

                            Edit

                        </a>

                        <a
                            href="{{ route('admin.users.delete',$user) }}"
                            class="px-3 py-1 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">

                            Delete

                        </a>

                    </div>

                </td>

            </tr>

            @endforeach

            @else

            <tr>

                <td colspan="4" class="py-16 text-center">

                    <div class="text-6xl mb-4">
                        👥
                    </div>

                    <h3 class="text-xl font-semibold text-slate-700">
                        No users found
                    </h3>

                    <p class="text-slate-500 mt-2">
                        Create your first user to get started.
                    </p>

                </td>

            </tr>

            @endif

        </tbody>

    </table>

    <div class="p-6">

        {{ $users->links() }}

    </div>

</div>

@endsection
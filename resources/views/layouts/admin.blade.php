<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'SkillBridge Admin')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    @php
        $unreadNotifications = Auth::user()->notifications()->where('is_read', false)->count();
    @endphp

    <x-ui.sidebar>

        @if(Auth::user()->isAdmin())

        <x-ui.sidebar-item
            :href="route('admin.dashboard')"
            :active="request()->routeIs('admin.dashboard')">

            <x-heroicon-o-home class="w-5 h-5"/>

            Dashboard

        </x-ui.sidebar-item>

        <x-ui.sidebar-item
            :href="route('admin.users')"
            :active="request()->routeIs('admin.users')">

            <x-heroicon-o-users class="w-5 h-5"/>

            User Management

        </x-ui.sidebar-item>

        <x-ui.sidebar-item
            :href="route('admin.courses')"
            :active="request()->routeIs('admin.courses')">

            <x-heroicon-o-book-open class="w-5 h-5"/>

            Course Management

        </x-ui.sidebar-item>

        @elseif(Auth::user()->isMentor())

        <x-ui.sidebar-item
            :href="route('mentor.dashboard')"
            :active="request()->routeIs('mentor.dashboard')">

            <x-heroicon-o-home class="w-5 h-5"/>

            Dashboard

        </x-ui.sidebar-item>

        @endif

        <x-ui.sidebar-item
            :href="route('assessments.index')"
            :active="request()->routeIs('assessments.*')">

            <x-heroicon-o-clipboard-document-list class="w-5 h-5"/>

            Assessment Management

        </x-ui.sidebar-item>

        <x-ui.sidebar-item
            :href="route('enrollments.index')"
            :active="request()->routeIs('enrollments.*')">

            <x-heroicon-o-user-group class="w-5 h-5"/>

            Enrollment Management

        </x-ui.sidebar-item>

        <x-ui.sidebar-item
            :href="route('notifications.index')"
            :active="request()->routeIs('notifications.*')">

            <x-heroicon-o-bell class="w-5 h-5"/>

            Notifications

            @if($unreadNotifications > 0)
                <span class="ml-auto inline-flex items-center justify-center rounded-full bg-red-500 px-2 text-xs font-bold text-white">
                    {{ $unreadNotifications }}
                </span>
            @endif

        </x-ui.sidebar-item>

    </x-ui.sidebar>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">

        <header class="bg-white shadow-sm">

            <div class="px-8 py-6">

                <h2 class="text-3xl font-bold text-slate-800">
                    @yield('header')
                </h2>

                <p class="text-slate-500 mt-1">
                    @yield('subtitle')
                </p>

            </div>

        </header>

        <div class="p-8">

            @yield('content')

        </div>

    </main>

</div>
    
    @if(session()->has('success') || session()->has('error') || session()->has('warning') || session()->has('info'))

    @php

    $type = 'success';
    $message = session('success');

    if(session()->has('error')){
    $type='error';
    $message=session('error');
    }

    if(session()->has('warning')){
    $type='warning';
    $message=session('warning');
    }

    if(session()->has('info')){
    $type='info';
    $message=session('info');
    }

    $styles = [
    'success' => 'bg-emerald-600',
    'error' => 'bg-red-600',
    'warning' => 'bg-amber-500',
    'info' => 'bg-blue-600',
    ];

    @endphp

    <div
        x-data="{ show:true }"
        x-show="show"
        x-transition
        x-init="setTimeout(()=>show=false,3000)"
        class="fixed top-6 right-6 z-50">

        <div class="{{ $styles[$type] }} text-white px-6 py-4 rounded-xl shadow-xl">

            {{ $message }}

        </div>

    </div>

    @endif

</body>

</html>

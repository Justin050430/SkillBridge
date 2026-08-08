@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
<x-ui.page-header
    title="Dashboard"
    subtitle="Welcome back, {{ Auth::user()->first_name }}. Here is an overview of your teaching activity." />
@endsection

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    <x-ui.stat-card
        title="My Courses"
        :value="$totalCourses">

        <x-slot:icon>
            <x-heroicon-o-book-open class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="My Lessons"
        :value="$totalLessons">

        <x-slot:icon>
            <x-heroicon-o-document-text class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="My Assessments"
        :value="$totalAssessments">

        <x-slot:icon>
            <x-heroicon-o-clipboard-document-list class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

    <x-ui.stat-card
        title="My Students"
        :value="$totalStudents">

        <x-slot:icon>
            <x-heroicon-o-academic-cap class="w-8 h-8"/>
        </x-slot:icon>

    </x-ui.stat-card>

</div>

<div class="grid lg:grid-cols-3 gap-6 mt-8">

    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <div class="flex items-center justify-between mb-4">

            <h2 class="text-xl font-semibold">
                Recent Assessments
            </h2>

            <a
                href="{{ route('assessments.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-700">

                View all →

            </a>

        </div>

        @if($recentAssessments->isEmpty())

            <div class="py-10 text-center text-slate-500">

                <x-heroicon-o-clipboard-document-list class="w-10 h-10 mx-auto text-slate-300" />

                <p class="mt-3">
                    No assessments yet. Create your first assessment to get started.
                </p>

            </div>

        @else

            <div class="divide-y divide-slate-100">

                @foreach($recentAssessments as $assessment)

                <div class="py-3 flex items-center justify-between gap-4">

                    <div class="min-w-0">

                        <p class="font-medium text-slate-900 truncate">
                            {{ $assessment->title }}
                        </p>

                        <p class="text-sm text-slate-500 truncate">
                            {{ $assessment->lesson->course->title }} — {{ $assessment->lesson->title }}
                        </p>

                    </div>

                    <div class="flex items-center gap-3 shrink-0">

                        @php
                            $statusClasses = $assessment->status === 'Published'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700';
                        @endphp

                        <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">
                            {{ $assessment->status }}
                        </span>

                        <a
                            href="{{ route('assessments.questions.index', $assessment) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-700">

                            Questions →

                        </a>

                    </div>

                </div>

                @endforeach

            </div>

        @endif

    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        <h2 class="text-xl font-semibold mb-4">
            Quick Actions
        </h2>

        <div class="space-y-3">

            <a
                href="{{ route('assessments.index') }}"
                class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center py-3 rounded-xl transition">

                Manage Assessments

            </a>

            <a
                href="{{ route('assessments.create') }}"
                class="block w-full bg-slate-100 hover:bg-slate-200 text-center py-3 rounded-xl transition">

                Add Assessment

            </a>

        </div>

    </div>

</div>

{{-- Assessment Analytics - consumes the Assessment module web service --}}
<div
    class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-6"
    x-data="assessmentStats()"
    x-init="loadStats()">

    <h2 class="text-xl font-semibold mb-1">
        Assessment Analytics
    </h2>

    <p class="text-sm text-slate-500 mb-4">
        Live statistics retrieved from the Assessment web service.
    </p>

    <div x-show="loading" class="text-sm text-slate-500">
        Loading statistics...
    </div>

    <div
        x-show="!loading && stats.length === 0"
        x-cloak
        class="text-sm text-slate-500">
        No submission statistics available yet.
    </div>

    <div
        x-show="!loading && stats.length"
        x-cloak
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

        <template x-for="stat in stats" :key="stat.assessment_id">

            <div class="rounded-xl border border-slate-200 p-4">

                <p class="font-medium text-slate-800" x-text="stat.assessment_title"></p>

                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">

                    <div>
                        <p class="text-slate-500">Attempts</p>
                        <p class="font-semibold" x-text="stat.attempt_count"></p>
                    </div>

                    <div>
                        <p class="text-slate-500">Avg score</p>
                        <p class="font-semibold" x-text="stat.average_score + '%'"></p>
                    </div>

                    <div>
                        <p class="text-slate-500">Pass rate</p>
                        <p class="font-semibold" x-text="stat.pass_rate + '%'"></p>
                    </div>

                    <div>
                        <p class="text-slate-500">Questions</p>
                        <p class="font-semibold" x-text="stat.question_count"></p>
                    </div>

                </div>

            </div>

        </template>

    </div>

</div>

<script>
    function assessmentStats() {
        return {
            assessments: @json(
                $recentAssessments->map(fn ($assessment) => [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                ])
            ),
            stats: [],
            loading: true,

            async loadStats() {
                const timestamp = new Date().toISOString();

                for (const assessment of this.assessments) {
                    try {
                        const response = await fetch(
                            '/api/assessments/' + assessment.id + '/stats?timestamp=' + encodeURIComponent(timestamp),
                            { headers: { Accept: 'application/json' } }
                        );

                        const payload = await response.json();

                        if (payload.status === 'S') {
                            this.stats.push(payload.data);
                        }
                    } catch (error) {
                        // Degrade gracefully if a single request fails.
                    }
                }

                this.loading = false;
            },
        };
    }
</script>

@endsection
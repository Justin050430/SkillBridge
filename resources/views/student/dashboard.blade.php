<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Student Dashboard</h2>
            <p class="mt-1 text-slate-500">Welcome back, {{ Auth::user()->first_name }}. Here is an overview of your learning activity.</p>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto p-6 space-y-8">

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Enrolled Courses</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $totalCourses }}</h2>
                    </div>
                    <x-heroicon-o-book-open class="h-8 w-8 text-indigo-600" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Available Assessments</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $availableAssessments->count() }}</h2>
                    </div>
                    <x-heroicon-o-clipboard-document-list class="h-8 w-8 text-indigo-600" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Assessments Taken</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $attemptsTaken }}</h2>
                    </div>
                    <x-heroicon-o-check-circle class="h-8 w-8 text-indigo-600" />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Lessons Completed</p>
                        <h2 class="mt-2 text-3xl font-bold text-slate-900">{{ $completedLessons }}</h2>
                    </div>
                    <x-heroicon-o-academic-cap class="h-8 w-8 text-indigo-600" />
                </div>
            </div>

        </div>

        <div class="grid gap-8 lg:grid-cols-3">

            <!-- Recent assessments -->
            <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-4 flex items-center justify-between">

                    <h2 class="text-xl font-semibold text-slate-900">
                        Continue Learning
                    </h2>

                    <a
                        href="{{ route('student.assessments.index') }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700">

                        View all →

                    </a>

                </div>

                @forelse($recentAssessments as $assessment)

                    <div class="flex items-center justify-between gap-4 border-t border-slate-100 py-4 first:border-t-0 first:pt-0">

                        <div class="min-w-0">

                            <p class="font-medium text-slate-900 truncate">
                                {{ $assessment->title }}
                            </p>

                            <p class="text-sm text-slate-500 truncate">
                                {{ $assessment->lesson->course->title }} — {{ $assessment->lesson->title }}
                            </p>

                        </div>

                        <div class="flex shrink-0 items-center gap-3">

                            <span class="hidden rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700 sm:inline">
                                {{ $assessment->passing_score }}% pass
                            </span>

                            <a
                                href="{{ route('student.assessments.take', $assessment) }}"
                                class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">

                                <x-heroicon-o-play-circle class="h-4 w-4" />

                                Start

                            </a>

                        </div>

                    </div>

                @empty

                    <div class="py-10 text-center text-slate-500">

                        <x-heroicon-o-clipboard-document-list class="mx-auto h-10 w-10 text-slate-300" />

                        <p class="mt-3">
                            No assessments available yet. Browse courses to start learning.
                        </p>

                    </div>

                @endforelse

            </div>

            <!-- Quick actions -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-xl font-semibold text-slate-900">
                    Quick Actions
                </h2>

                <div class="mt-4 space-y-3">

                    <a
                        href="{{ route('student.courses') }}"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">

                        <x-heroicon-o-book-open class="h-5 w-5" />

                        Browse Courses

                    </a>

                    <a
                        href="{{ route('student.assessments.index') }}"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">

                        <x-heroicon-o-clipboard-document-list class="h-5 w-5" />

                        My Assessments

                    </a>

                    <a
                        href="{{ route('student.progress') }}"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">

                        <x-heroicon-o-academic-cap class="h-5 w-5" />

                        My Progress

                    </a>

                </div>

            </div>

        </div>

    </div>
</x-app-layout>
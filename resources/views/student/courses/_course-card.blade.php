{{--
    Shared course card for the student catalogue.

    Alpine scope: expects `course` from the parent x-for loop.
    Blade param: $variant = 'my' | 'completed' | 'explore'
      - 'my'       : progress bar + lessons/assessments summary + Enrolled/Drop.
      - 'completed': Completed badge only (no actions).
      - 'explore'  : Enroll / Enroll Again / Enrollment Limit Reached.
--}}

<article class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
    <div class="flex h-40 shrink-0 items-center justify-center bg-indigo-50">
        <img
            x-show="course.thumbnail"
            :src="course.thumbnail"
            :alt="course.title"
            class="h-full w-full object-cover">

        <x-heroicon-o-book-open
            x-show="!course.thumbnail"
            class="h-12 w-12 text-indigo-300" />
    </div>

    <div class="flex flex-1 flex-col p-6">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-medium text-indigo-600" x-text="course.category"></p>

            @if ($variant === 'completed')
                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                    <x-heroicon-o-check-circle class="h-4 w-4" />
                    Completed
                </span>
            @endif
        </div>

        <h3 class="mt-2 text-xl font-semibold text-slate-900" x-text="course.title"></h3>
        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600" x-text="course.description"></p>

        @if ($variant === 'my')
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm font-medium">
                    <span class="text-slate-600">Progress</span>
                    <span class="text-indigo-600" x-text="progressPercent(course) + '%'"></span>
                </div>

                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-indigo-500 transition-all"
                        :style="'width: ' + progressPercent(course) + '%'">
                    </div>
                </div>

                <p class="mt-2 text-xs leading-5 text-slate-500" x-text="progressDetail(course)"></p>
            </div>
        @endif

        <div class="mt-auto pt-5">
            <div class="border-t border-slate-100 pt-4 text-sm text-slate-500">
                Mentor: <span class="font-medium text-slate-700" x-text="course.mentor"></span>
            </div>

            <div class="mt-4">

                @if ($variant === 'my')

                    <div class="flex items-center gap-2">
                        <span class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-700">
                            <x-heroicon-o-check-circle class="h-5 w-5"/>
                            Enrolled
                        </span>

                        <button
                            type="button"
                            x-on:click="drop(course)"
                            x-bind:disabled="busy"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60">
                            Drop
                        </button>
                    </div>

                    <a
                        :href="'/student/courses/' + course.slug + '/lessons'"
                        class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                        <x-heroicon-o-book-open class="h-5 w-5"/>
                        View Lessons
                    </a>

                @elseif ($variant === 'completed')

                    <span class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                        <x-heroicon-o-check-circle class="h-5 w-5"/>
                        Completed
                    </span>

                @else

                    {{-- Not enrolled --}}
                    <button
                        type="button"
                        x-show="enrollmentStatus(course) === null"
                        x-on:click="enroll(course)"
                        x-bind:disabled="busy"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        Enroll
                    </button>

                    {{-- Dropped but still allowed to re-enrol --}}
                    <button
                        type="button"
                        x-show="enrollmentStatus(course) === 'Dropped'"
                        x-on:click="enroll(course)"
                        x-bind:disabled="busy"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                        Enroll Again
                    </button>

                    {{-- Enrollment limit reached (3 drops) --}}
                    <div
                        x-show="enrollmentStatus(course) === 'LimitReached'"
                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700">
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5"/>
                        Enrollment Limit Reached
                    </div>

                @endif

            </div>
        </div>
    </div>
</article>

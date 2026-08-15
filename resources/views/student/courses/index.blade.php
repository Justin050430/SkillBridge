<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Browse Courses</h2>
            <p class="mt-1 text-slate-500">Explore the published courses available on SkillBridge.</p>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto p-6 space-y-10"
        x-data="courseCatalogue()"
        x-init="loadCourses()">

        <div x-show="loading" class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
            <template x-for="item in 3" :key="item">
                <div class="h-64 animate-pulse rounded-2xl bg-slate-200"></div>
            </template>
        </div>

        <div
            x-show="error"
            x-cloak
            class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700">
            <p class="font-semibold">Unable to load courses</p>
            <p class="mt-1 text-sm" x-text="error"></p>
        </div>

        <div
            x-show="message"
            x-cloak
            x-transition
            class="mb-6 rounded-2xl border p-4 text-sm font-medium"
            :class="messageType === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
            <p x-text="message"></p>
        </div>

        <div
            x-show="!loading && !error && courses.length === 0"
            x-cloak
            class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <x-heroicon-o-book-open class="mx-auto h-12 w-12 text-slate-300" />
            <h3 class="mt-4 text-xl font-semibold text-slate-700">No published courses yet</h3>
            <p class="mt-2 text-slate-500">Please check back when new courses are published.</p>
        </div>

        {{-- MY COURSES --}}
        <section x-show="!loading && !error && myCourses().length" x-cloak>
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                    <x-heroicon-o-academic-cap class="h-6 w-6"/>
                </span>
                <div>
                    <h3 class="text-xl font-bold text-slate-800">My Courses</h3>
                    <p class="text-sm text-slate-500" x-text="myCourses().length + ' active course' + (myCourses().length === 1 ? '' : 's')"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="course in myCourses()" :key="course.id">
                    @include('student.courses._course-card', ['variant' => 'my'])
                </template>
            </div>
        </section>

        {{-- My Courses empty state --}}
        <section x-show="!loading && !error && courses.length && myCourses().length === 0" x-cloak>
            <div class="rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/40 p-8 text-center">
                <x-heroicon-o-academic-cap class="mx-auto h-10 w-10 text-indigo-300"/>
                <h3 class="mt-3 text-lg font-semibold text-slate-700">No active courses</h3>
                <p class="mt-1 text-sm text-slate-500">Browse the available courses below to find something to learn.</p>
            </div>
        </section>

        {{-- COMPLETED COURSES --}}
        <section x-show="!loading && !error && completedCourses().length" x-cloak>
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <x-heroicon-o-check-badge class="h-6 w-6"/>
                </span>
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Completed Courses</h3>
                    <p class="text-sm text-slate-500" x-text="completedCourses().length + ' completed course' + (completedCourses().length === 1 ? '' : 's')"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="course in completedCourses()" :key="course.id">
                    @include('student.courses._course-card', ['variant' => 'completed'])
                </template>
            </div>
        </section>

        {{-- EXPLORE COURSES --}}
        <section x-show="!loading && !error && exploreCourses().length" x-cloak>
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <x-heroicon-o-book-open class="h-6 w-6"/>
                </span>
                <div>
                    <h3 class="text-xl font-bold text-slate-800">Explore Courses</h3>
                    <p class="text-sm text-slate-500" x-text="exploreCourses().length + ' course' + (exploreCourses().length === 1 ? '' : 's') + ' available'"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                <template x-for="course in exploreCourses()" :key="course.id">
                    @include('student.courses._course-card', ['variant' => 'explore'])
                </template>
            </div>
        </section>

    </div>

    <script>
        function courseCatalogue() {
            return {
                courses: [],
                loading: true,
                error: '',
                enrollmentStates: @json($enrollmentStates),
                courseProgress: @json($courseProgress),
                csrf: @json(csrf_token()),
                busy: false,
                message: '',
                messageType: 'success',
                _messageTimer: null,

                async loadCourses() {
                    try {
                        const response = await fetch('{{ route('api.courses.published', [], false) }}', {
                            headers: {
                                Accept: 'application/json',
                            },
                        });

                        const payload = await response.json();

                        if (!response.ok || payload.status !== 'S') {
                            throw new Error(payload.message || 'Please try again later.');
                        }

                        this.courses = payload.data;
                    } catch (error) {
                        this.error = error.message || 'Please try again later.';
                    } finally {
                        this.loading = false;
                    }
                },

                enrollmentStatus(course) {
                    return this.enrollmentStates[course.id] ?? null;
                },

                myCourses() {
                    return this.courses.filter((course) => this.enrollmentStatus(course) === 'Active');
                },

                completedCourses() {
                    return this.courses.filter((course) => this.enrollmentStatus(course) === 'Completed');
                },

                exploreCourses() {
                    return this.courses.filter((course) => {
                        const state = this.enrollmentStatus(course);
                        return state === null || state === 'Dropped' || state === 'LimitReached';
                    });
                },

                progressFor(course) {
                    return this.courseProgress[course.id] ?? {
                        percent: 0,
                        completed_lessons: 0,
                        total_lessons: 0,
                        assessments_passed: 0,
                        assessments_total: 0,
                    };
                },

                progressPercent(course) {
                    return this.progressFor(course).percent;
                },

                progressDetail(course) {
                    const p = this.progressFor(course);
                    const lessons = p.total_lessons === 1 ? '1 lesson' : p.total_lessons + ' lessons';
                    let detail = p.completed_lessons + ' of ' + lessons + ' completed';
                    if (p.assessments_total > 0) {
                        detail += ' · ' + p.assessments_passed + ' of ' + p.assessments_total + ' assessments passed';
                    }
                    return detail;
                },

                async enroll(course) {
                    await this.submitEnrollment(course, 'enroll');
                },

                async drop(course) {
                    await this.submitEnrollment(course, 'drop');
                },

                async submitEnrollment(course, action) {
                    if (this.busy) return;
                    this.busy = true;

                    try {
                        const response = await fetch('/student/courses/' + course.slug + '/' + action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({}),
                        });

                        const payload = await response.json();

                        if (!response.ok || payload.status !== 'S') {
                            throw new Error(payload.message || 'Unable to update your enrollment.');
                        }

                        if (action === 'drop') {
                            // Once the 3rd drop is reached the card locks to
                            // "Enrollment Limit Reached" (no enroll button).
                            this.enrollmentStates[course.id] = payload.limit_reached ? 'LimitReached' : 'Dropped';
                        } else {
                            this.enrollmentStates[course.id] = payload.enrollment_status;
                        }

                        this.showMessage(payload.message || 'Done.', 'success');
                    } catch (error) {
                        this.showMessage(error.message || 'Something went wrong. Please try again.', 'error');
                    } finally {
                        this.busy = false;
                    }
                },

                showMessage(message, type) {
                    this.message = message;
                    this.messageType = type;
                    clearTimeout(this._messageTimer);
                    this._messageTimer = setTimeout(() => {
                        this.message = '';
                    }, 3000);
                },
            };
        }
    </script>
</x-app-layout>

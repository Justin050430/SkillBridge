<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Browse Courses</h2>
            <p class="mt-1 text-slate-500">Explore the published courses available on SkillBridge.</p>
        </div>
    </x-slot>

    <div
        class="max-w-7xl mx-auto p-6"
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
            x-show="!loading && !error && courses.length === 0"
            x-cloak
            class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm">
            <x-heroicon-o-book-open class="mx-auto h-12 w-12 text-slate-300" />
            <h3 class="mt-4 text-xl font-semibold text-slate-700">No published courses yet</h3>
            <p class="mt-2 text-slate-500">Please check back when new courses are published.</p>
        </div>

        <div
            x-show="!loading && !error && courses.length"
            x-cloak
            class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">

            <template x-for="course in courses" :key="course.id">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex h-40 items-center justify-center bg-indigo-50">
                        <img
                            x-show="course.thumbnail"
                            :src="course.thumbnail"
                            :alt="course.title"
                            class="h-full w-full object-cover">

                        <x-heroicon-o-book-open
                            x-show="!course.thumbnail"
                            class="h-12 w-12 text-indigo-300" />
                    </div>

                    <div class="p-6">
                        <p class="text-sm font-medium text-indigo-600" x-text="course.category"></p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900" x-text="course.title"></h3>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600" x-text="course.description"></p>

                        <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">
                            Mentor: <span class="font-medium text-slate-700" x-text="course.mentor"></span>
                        </div>

                        <div class="mt-4">
                            <span
                                x-show="isEnrolled(course)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-700">

                                <x-heroicon-o-check-circle class="h-5 w-5"/>

                                Enrolled

                            </span>

                            <form
                                x-show="!isEnrolled(course)"
                                method="POST"
                                :action="'/student/courses/' + course.id + '/enroll'">

                                @csrf

                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">

                                    Enroll

                                </button>

                            </form>
                        </div>
                    </div>
                </article>
            </template>

        </div>

    </div>

    <script>
        function courseCatalogue() {
            return {
                courses: [],
                loading: true,
                error: '',
                enrolled: @json($enrolledCourseIds),

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

                isEnrolled(course) {
                    return this.enrolled.includes(course.id);
                },
            };
        }
    </script>
</x-app-layout>

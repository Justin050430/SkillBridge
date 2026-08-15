<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('student.progress') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                ← My Progress
            </a>
            <h2 class="mt-1 text-2xl font-bold text-slate-800">{{ $lesson->title }}</h2>
            <p class="mt-1 text-slate-500">
                {{ $course->title }} · Lesson {{ $lesson->order_number }}
                @if ($lesson->estimated_duration)
                    · {{ $lesson->estimated_duration }} min
                @endif
            </p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-8 p-6">

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Description --}}
        @if ($lesson->description)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-lg leading-relaxed text-slate-700">{{ $lesson->description }}</p>
            </div>
        @endif

        {{-- Rich content --}}
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-10">
            <div class="lesson-content">
                {!! $lesson->content !!}
            </div>
        </article>

        {{-- Materials / Resources --}}
        @php
            $documents = $lesson->resources->where('type', 'File');
            $images = $lesson->resources->where('type', 'Image');
            $videos = $lesson->resources->where('type', 'Video');
            $links = $lesson->resources->where('type', 'Link');
            $hasMaterials = $documents->isNotEmpty() || $images->isNotEmpty() || $videos->isNotEmpty() || $links->isNotEmpty();
        @endphp

        @if ($hasMaterials)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                        <x-heroicon-o-paper-clip class="h-6 w-6"/>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Materials</h3>
                        <p class="text-sm text-slate-500">Resources to help you with this lesson.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-8">

                    {{-- Documents --}}
                    @if ($documents->isNotEmpty())
                        <div>
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Documents</h4>
                            <ul class="space-y-2">
                                @foreach ($documents as $resource)
                                    @php
                                        $kb = (int) round(($resource->file_size ?? 0) / 1024);
                                        $size = $kb >= 1024 ? round($kb / 1024, 1).' MB' : $kb.' KB';
                                    @endphp
                                    <li>
                                        <a
                                            href="{{ route('student.lessons.resource.download', [$course, $lesson, $resource]) }}"
                                            class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-indigo-200 hover:bg-indigo-50">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm">
                                                <x-heroicon-o-document-text class="h-5 w-5"/>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate font-medium text-slate-800 group-hover:text-indigo-700">
                                                    {{ $resource->title }}
                                                </span>
                                                @if ($resource->description)
                                                    <span class="block truncate text-sm text-slate-500">{{ $resource->description }}</span>
                                                @endif
                                                <span class="block text-xs text-slate-400">
                                                    {{ $resource->file_name }} · {{ $size }}
                                                </span>
                                            </span>
                                            <x-heroicon-o-arrow-down-tray class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-indigo-600"/>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Images --}}
                    @if ($images->isNotEmpty())
                        <div>
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Images</h4>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                @foreach ($images as $resource)
                                    <a
                                        href="{{ route('student.lessons.resource.download', [$course, $lesson, $resource]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                        <img
                                            src="{{ route('student.lessons.resource.download', [$course, $lesson, $resource]) }}"
                                            alt="{{ $resource->title }}"
                                            class="h-32 w-full object-cover transition group-hover:scale-105"
                                            loading="lazy">
                                        <p class="truncate px-3 py-2 text-sm font-medium text-slate-700">
                                            {{ $resource->title }}
                                        </p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Videos --}}
                    @if ($videos->isNotEmpty())
                        <div>
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Videos</h4>
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                @foreach ($videos as $resource)
                                    <figure>
                                        <div class="aspect-video overflow-hidden rounded-xl bg-slate-900">
                                            @if ($resource->youtube_id)
                                                <iframe
                                                    class="h-full w-full"
                                                    src="https://www.youtube-nocookie.com/embed/{{ $resource->youtube_id }}"
                                                    title="{{ $resource->title }}"
                                                    frameborder="0"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                    allowfullscreen
                                                    loading="lazy"></iframe>
                                            @else
                                                <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer" class="flex h-full w-full items-center justify-center text-white">
                                                    <x-heroicon-o-play-circle class="h-10 w-10"/>
                                                </a>
                                            @endif
                                        </div>
                                        <figcaption class="mt-2 font-medium text-slate-800">{{ $resource->title }}</figcaption>
                                        @if ($resource->description)
                                            <p class="text-sm text-slate-500">{{ $resource->description }}</p>
                                        @endif
                                    </figure>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- External links --}}
                    @if ($links->isNotEmpty())
                        <div>
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">External Links</h4>
                            <ul class="space-y-2">
                                @foreach ($links as $resource)
                                    <li>
                                        <a
                                            href="{{ $resource->url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-indigo-200 hover:bg-indigo-50">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-500 shadow-sm">
                                                <x-heroicon-o-link class="h-5 w-5"/>
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate font-medium text-slate-800 group-hover:text-indigo-700">
                                                    {{ $resource->title }}
                                                </span>
                                                @if ($resource->description)
                                                    <span class="block truncate text-sm text-slate-500">{{ $resource->description }}</span>
                                                @endif
                                            </span>
                                            <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-indigo-600"/>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                </div>
            </section>
        @endif

        {{-- Related assessment --}}
        @if ($assessments->isNotEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                        <x-heroicon-o-clipboard-document-check class="h-6 w-6"/>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Assessment</h3>
                        <p class="text-sm text-slate-500">Test what you have learned in this lesson.</p>
                    </div>
                </div>

                <ul class="mt-4 space-y-2">
                    @foreach ($assessments as $assessment)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-800">{{ $assessment->title }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ $assessment->questions_count }} question{{ $assessment->questions_count === 1 ? '' : 's' }}
                                    · Pass mark {{ $assessment->passing_score }}%
                                </p>
                            </div>
                            <a
                                href="{{ route('student.assessments.take', $assessment) }}"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                <x-heroicon-o-play-circle class="h-5 w-5"/>
                                Take Assessment
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Progress action --}}
        <section class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $progress === 'Completed' ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                    <x-heroicon-o-check-circle class="h-6 w-6"/>
                </span>
                <div>
                    <p class="font-semibold text-slate-800">
                        {{ $progress === 'Completed' ? 'Lesson completed' : 'Lesson progress' }}
                    </p>
                    <p class="text-sm text-slate-500">
                        {{ $progress === 'Completed' ? 'You have finished this lesson.' : 'Mark this lesson as complete when you are done.' }}
                    </p>
                </div>
            </div>

            @if ($progress !== 'Completed')
                <form method="POST" action="{{ route('student.progress.update', $lesson) }}">
                    @csrf
                    <input type="hidden" name="status" value="Completed">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                        <x-heroicon-o-check class="h-5 w-5"/>
                        Mark as Complete
                    </button>
                </form>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-700">
                    <x-heroicon-o-check-circle class="h-5 w-5"/>
                    Completed
                </span>
            @endif
        </section>

        {{-- Previous / next --}}
        <nav class="flex items-center justify-between gap-4">
            @if ($previous)
                <a href="{{ route('student.lessons.show', [$course, $previous]) }}" class="group flex-1 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Previous</p>
                    <p class="mt-1 truncate font-semibold text-slate-800 group-hover:text-indigo-700">{{ $previous->title }}</p>
                </a>
            @else
                <div class="flex-1"></div>
            @endif

            @if ($next)
                <a href="{{ route('student.lessons.show', [$course, $next]) }}" class="group flex-1 rounded-2xl border border-slate-200 bg-white p-5 text-right shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Next</p>
                    <p class="mt-1 truncate font-semibold text-slate-800 group-hover:text-indigo-700">{{ $next->title }}</p>
                </a>
            @endif
        </nav>

    </div>
</x-app-layout>

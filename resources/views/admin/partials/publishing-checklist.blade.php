<section id="publishing-checklist" aria-labelledby="publishing-checklist-title"
         class="lg:col-span-2 min-w-0 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 scroll-mt-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 id="publishing-checklist-title" class="text-xl font-semibold text-slate-900">Publishing Checklist</h2>
            <p class="mt-1 text-sm text-slate-500">Review unfinished content and pick up where you left off.</p>
        </div>
        <span class="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
            {{ $checklistCounts['all'] }} {{ $checklistCounts['all'] === 1 ? 'draft' : 'drafts' }}
        </span>
    </div>

    <nav aria-label="Filter publishing checklist" class="mt-5 flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'course' => 'Courses', 'lesson' => 'Lessons', 'assessment' => 'Assessments'] as $type => $label)
            <a href="{{ route('admin.dashboard', ['checklist_type' => $type]) }}#publishing-checklist"
               @if($checklistType === $type) aria-current="page" @endif
               @class([
                   'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600',
                   'bg-indigo-600 text-white' => $checklistType === $type,
                   'bg-slate-100 text-slate-600 hover:bg-slate-200' => $checklistType !== $type,
               ])>
                {{ $label }} <span class="text-xs">{{ $checklistCounts[$type] }}</span>
            </a>
        @endforeach
    </nav>

    @if($checklistItems->isNotEmpty())
        <p class="mt-4 text-xs text-slate-500">Publishing blockers appear first. Drafts can stay unpublished until you are ready.</p>
        <ul class="mt-2 divide-y divide-slate-100">
            @foreach($checklistItems as $item)
                <li class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="font-medium uppercase tracking-wide text-slate-500">{{ ucfirst($item->type) }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 font-medium',
                                'bg-amber-50 text-amber-800' => $item->blocked,
                                'bg-blue-50 text-blue-700' => !$item->blocked && $item->missing,
                                'bg-slate-100 text-slate-600' => !$item->blocked && !$item->missing,
                            ])>{{ $item->label }}</span>
                        </div>
                        <h3 class="mt-1 break-words font-semibold text-slate-900">{{ $item->title }}</h3>
                        @if($item->context)
                            <p class="mt-0.5 break-words text-xs text-slate-500">{{ $item->type === 'lesson' ? 'Course' : 'Lesson' }}: {{ $item->context }}</p>
                        @endif
                        <p class="mt-1 text-sm text-slate-600">{{ $item->detail }}</p>
                    </div>
                    <a href="{{ $item->url }}" aria-label="{{ $item->action }}: {{ $item->title }}"
                       class="shrink-0 self-start rounded-lg border border-indigo-200 px-3 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:self-center">
                        {{ $item->action }} <span aria-hidden="true">&rarr;</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @elseif($checklistItems->total() > 0)
        <div class="py-10 text-center">
            <h3 class="font-semibold text-slate-900">No drafts on this page</h3>
            <a href="{{ $checklistItems->url(1) }}" class="mt-2 inline-block text-sm font-medium text-indigo-700 underline">Return to the first page</a>
        </div>
    @else
        <div class="py-10 text-center">
            <x-heroicon-o-check-circle class="mx-auto h-10 w-10 text-emerald-500" aria-hidden="true" />
            <h3 class="mt-3 font-semibold text-slate-900">{{ $checklistCounts['all'] === 0 ? 'No drafts to review' : 'No drafts in this category' }}</h3>
            <p class="mt-1 text-sm text-slate-500">Draft courses, lessons, and assessments will appear here. Archived courses are excluded.</p>
        </div>
    @endif

    @if($checklistItems->hasPages())
        <div class="border-t border-slate-100 pt-4">{{ $checklistItems->links() }}</div>
    @endif
</section>

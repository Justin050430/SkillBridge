@php
    $lessonResources = isset($lesson) && $lesson->exists
        ? $lesson->resources
        : collect();

    $submittedResources = old('resources');

    if (is_array($submittedResources)) {
        $lessonResourceRows = collect($submittedResources)
            ->map(function ($resource, $index) use ($lessonResources) {
                $existingResource = !empty($resource['id'])
                    ? $lessonResources->firstWhere('id', $resource['id'])
                    : null;

                return [
                    'id' => $resource['id'] ?? null,
                    'type' => $resource['type'] ?? 'File',
                    'title' => $resource['title'] ?? '',
                    'description' => $resource['description'] ?? '',
                    'url' => $resource['url'] ?? '',
                    'existing_file' => $existingResource?->file_name,
                    'key' => !empty($resource['id'])
                        ? 'existing-'.$resource['id']
                        : 'old-'.$index,
                ];
            })
            ->values()
            ->all();
    } else {
        $lessonResourceRows = $lessonResources
            ->map(fn ($resource) => [
                'id' => $resource->id,
                'type' => $resource->type,
                'title' => $resource->title,
                'description' => $resource->description,
                'url' => $resource->url,
                'existing_file' => $resource->file_name,
                'key' => 'existing-'.$resource->id,
            ])
            ->values()
            ->all();
    }
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="lessonEditor()">

    @csrf

    @if (isset($lesson) && $lesson->exists)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Lesson Information</h3>
        <p class="mt-1 text-sm text-slate-500">Set the lesson content, materials and publication status.</p>
        <hr class="mt-4">
    </div>

    <div class="md:col-span-2">
        <x-input-label for="course" :value="__('Course')" />

        <input
            id="course"
            type="text"
            value="{{ $course->title }}"
            class="block mt-1 w-full rounded-lg border-gray-300 bg-slate-100"
            disabled />

    </div>

    <div class="md:col-span-2">
        <x-input-label for="title" :value="__('Lesson Title')" />

        <x-text-input
            id="title"
            name="title"
            type="text"
            class="block mt-1 w-full"
            :value="old('title', $lesson->title ?? '')"
            placeholder="e.g. Introduction to PHP Security"
            maxlength="150"
            required />

        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Short Description / Instructions')" />

        <textarea
            id="description"
            name="description"
            rows="2"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="A one or two sentence summary shown at the top of the lesson."
            maxlength="2000">{{ old('description', $lesson->description ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="order_number" :value="__('Order')" />

        <x-text-input
            id="order_number"
            name="order_number"
            type="number"
            min="1"
            class="block mt-1 w-full"
            :value="old('order_number', $lesson->order_number ?? $nextOrder ?? 1)"
            required />

        <x-input-error :messages="$errors->get('order_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="estimated_duration" :value="__('Estimated Duration (minutes, optional)')" />

        <x-text-input
            id="estimated_duration"
            name="estimated_duration"
            type="number"
            min="1"
            max="600"
            class="block mt-1 w-full"
            :value="old('estimated_duration', $lesson->estimated_duration ?? '')"
            placeholder="e.g. 45" />

        <x-input-error :messages="$errors->get('estimated_duration')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="status" :value="__('Status')" />

        <select id="status" name="status" class="block mt-1 w-full rounded-lg border-gray-300" required>
            @foreach(['Draft', 'Published'] as $status)
                <option value="{{ $status }}" @selected(old('status', $lesson->status ?? 'Draft') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>

        <p class="mt-1 text-xs text-slate-500">
            Draft lessons are only visible to you. Publish for students to see them.
        </p>

        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    {{-- Rich content editor --}}
    <div class="md:col-span-2">
        <x-input-label for="content" :value="__('Lesson Content')" />
        <p class="mt-1 text-sm text-slate-500">Write the lesson body. Use the toolbar to add headings, lists, formatting and links.</p>

        <div class="mt-2 flex flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-slate-300 bg-slate-50 p-2">
            <button type="button" x-on:click="formatContent('h2')" class="rounded px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-200">Heading</button>
            <button type="button" x-on:click="formatContent('p')" class="rounded px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-200">Paragraph</button>
            <span class="mx-1 h-5 w-px bg-slate-300"></span>
            <button type="button" x-on:click="formatContent('strong')" class="rounded px-2 py-1 text-sm font-bold text-slate-700 hover:bg-slate-200">B</button>
            <button type="button" x-on:click="formatContent('em')" class="rounded px-2 py-1 text-sm italic text-slate-700 hover:bg-slate-200">I</button>
            <button type="button" x-on:click="formatContent('u')" class="rounded px-2 py-1 text-sm underline text-slate-700 hover:bg-slate-200">U</button>
            <span class="mx-1 h-5 w-px bg-slate-300"></span>
            <button type="button" x-on:click="formatContent('ul')" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-slate-200">• Bullet list</button>
            <button type="button" x-on:click="formatContent('ol')" class="rounded px-2 py-1 text-sm text-slate-700 hover:bg-slate-200">1. Numbered list</button>
            <button type="button" x-on:click="formatContent('a')" class="rounded px-2 py-1 text-sm text-indigo-600 hover:bg-slate-200">Link</button>
            <span class="flex-1"></span>
            <button
                type="button"
                x-on:click="openPreview()"
                x-bind:disabled="previewLoading"
                class="rounded px-3 py-1 text-sm font-medium text-indigo-600 hover:bg-indigo-100 disabled:opacity-60">
                <span x-show="!previewLoading">Preview</span>
                <span x-show="previewLoading" x-cloak>Previewing…</span>
            </button>
        </div>

        <textarea
            id="content"
            name="content"
            rows="12"
            class="block w-full rounded-b-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
            required>{{ old('content', $lesson->content ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('content')" class="mt-2" />
    </div>

    {{-- Materials / Resources --}}
    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Materials / Resources</h3>
        <p class="mt-1 text-sm text-slate-500">Attach documents, images, videos and external links for students to access.</p>
        @php
    $resourceErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) =>
            $key === 'resources' || str_starts_with($key, 'resources.')
        )
        ->flatten();
@endphp

@if ($resourceErrors->isNotEmpty())
    <div class="mt-3 rounded-lg border border-red-300 bg-red-50 p-3">
        <ul class="list-disc pl-5 text-sm text-red-700">
            @foreach ($resourceErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
        <hr class="mt-4">

        <div class="mt-4 space-y-4">
            <template x-for="(row, index) in rows" :key="row.key">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Type</label>
                                <select
                                    :name="'resources['+index+'][type]'"
                                    x-model="row.type"
                                    class="mt-1 w-full rounded-lg border-gray-300">
                                    <option value="File">Document</option>
                                    <option value="Image">Image</option>
                                    <option value="Video">Video (YouTube)</option>
                                    <option value="Link">External Link</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Title</label>
                                <input
                                    type="text"
                                    :name="'resources['+index+'][title]'"
                                    x-model="row.title"
                                    maxlength="150"
                                    placeholder="e.g. PHP Security Notes"
                                    class="mt-1 w-full rounded-lg border-gray-300">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Description (optional)</label>
                                <input
                                    type="text"
                                    :name="'resources['+index+'][description]'"
                                    x-model="row.description"
                                    maxlength="500"
                                    placeholder="Short note about this resource"
                                    class="mt-1 w-full rounded-lg border-gray-300">
                            </div>

                            <div class="sm:col-span-2" x-show="row.type === 'Link' || row.type === 'Video'">
                                <label class="block text-sm font-medium text-slate-700">
                                    <span x-text="row.type === 'Video' ? 'YouTube URL' : 'URL'"></span>
                                </label>
                                <input
                                    type="url"
                                    :name="'resources['+index+'][url]'"
                                    x-model="row.url"
                                    placeholder="https://..."
                                    class="mt-1 w-full rounded-lg border-gray-300">
                                <p x-show="row.type === 'Video'" class="mt-1 text-xs text-slate-500">
                                    Paste a YouTube watch, shorts or youtu.be link.
                                </p>
                            </div>

                            <div class="sm:col-span-2" x-show="row.type === 'File' || row.type === 'Image'">
                                <label class="block text-sm font-medium text-slate-700">File</label>
                                <p x-show="row.existing_file" class="mt-1 text-sm text-slate-600">
                                    Current: <span class="font-medium" x-text="row.existing_file"></span>
                                </p>
                      <input
    type="file"
    :name="'resources['+index+'][file]'"
    x-bind:accept="row.type === 'Image'
        ? 'image/jpeg,image/png,image/gif,image/webp'
        : '.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt,.md,.rtf,.mp4,.webm,.mov'"
    class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-600 hover:file:bg-indigo-100">
                                <p class="mt-1 text-xs text-slate-500">
                                    PDF, Word, PowerPoint, Excel, ZIP, images or MP4 up to 25 MB.
                                </p>
                            </div>

                            <input type="hidden" :name="'resources['+index+'][display_order]'" :value="index + 1">
                            <template x-if="row.id">
                                <input type="hidden" :name="'resources['+index+'][id]'" :value="row.id">
                            </template>

                        </div>

                        <button
                            type="button"
                            x-on:click="removeRow(row)"
                            class="shrink-0 rounded-lg px-2 py-1 text-sm text-red-600 hover:bg-red-50">
                            Remove
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex flex-wrap gap-2">
                <button type="button" x-on:click="addRow('File')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Document</button>
                <button type="button" x-on:click="addRow('Image')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Image</button>
                <button type="button" x-on:click="addRow('Video')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Video</button>
                <button type="button" x-on:click="addRow('Link')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">+ Link</button>
            </div>
        </div>
    </div>

</div>

<div class="flex justify-end gap-3 mt-8">
    <a
        href="{{ route('courses.lessons.index', $course) }}"
        class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
        Cancel
    </a>

    <x-ui.action-button type="submit">
        {{ $buttonText }}
    </x-ui.action-button>
</div>

{{-- Preview modal --}}
<div x-show="previewOpen" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50" x-on:click="previewOpen = false"></div>
    <div class="relative max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-semibold text-slate-800">Lesson Preview</h3>
            <button type="button" x-on:click="previewOpen = false" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100">Close</button>
        </div>
        <div class="lesson-content" x-html="previewHtml"></div>
    </div>
</div>

</form>

<script>
    function lessonEditor() {
        return {
            rows: @json($lessonResourceRows),
            previewOpen: false,
            previewHtml: '',
            previewLoading: false,

            addRow(type) {
                this.rows.push({
                    id: null,
                    type: type,
                    title: '',
                    description: '',
                    url: '',
                    existing_file: null,
                    key: 'new-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7),
                });
            },

            removeRow(row) {
                this.rows = this.rows.filter((r) => r !== row);
            },

            formatContent(tag) {
                const textarea = document.getElementById('content');
                if (!textarea) return;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selected = textarea.value.slice(start, end);

                let inserted;
                switch (tag) {
                    case 'a': {
                        const url = window.prompt('Link URL (https://...)');
                        if (!url) return;
                        const label = selected || url;
                        inserted = '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + label + '</a>';
                        break;
                    }
                    case 'ul':
                    case 'ol': {
                        const items = selected
                            .split('\n')
                            .map((line) => line.trim())
                            .filter(Boolean)
                            .map((line) => '<li>' + line + '</li>')
                            .join('');
                        inserted = '<' + tag + '>' + items + '</' + tag + '>';
                        break;
                    }
                    default:
                        inserted = '<' + tag + '>' + selected + '</' + tag + '>';
                }

                textarea.setRangeText(inserted, start, end, 'end');
                textarea.focus();
            },

            async openPreview() {
                const textarea = document.getElementById('content');
                const token = document.querySelector('form input[name="_token"]')?.value;
                if (!textarea || !token) return;

                this.previewLoading = true;
                try {
                    const response = await fetch('{{ route('courses.lessons.preview', $course) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ content: textarea.value }),
                    });

                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'S') {
                        throw new Error(payload.message || 'Preview unavailable.');
                    }

                    this.previewHtml = payload.html;
                    this.previewOpen = true;
                } catch (error) {
                    window.alert(error.message || 'Preview unavailable.');
                } finally {
                    this.previewLoading = false;
                }
            },
        };
    }
</script>

@csrf

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">

    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Lesson Information</h3>
        <p class="mt-1 text-sm text-slate-500">Set the lesson content and publication status.</p>
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
            placeholder="e.g. Introduction and Overview"
            required />

        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="content" :value="__('Content')" />

        <textarea
            id="content"
            name="content"
            rows="8"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            required>{{ old('content', $lesson->content ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('content')" class="mt-2" />
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

        <x-input-error :messages="$errors->get('status')" class="mt-2" />
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

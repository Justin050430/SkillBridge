@csrf

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">

    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Course Information</h3>
        <p class="mt-1 text-sm text-slate-500">Set up the course details and publication status.</p>
        <hr class="mt-4">
    </div>

    <div>
        <x-input-label for="category_id" :value="__('Category')" />

        <select id="category_id" name="category_id" class="block mt-1 w-full rounded-lg border-gray-300" required>
            <option value="">Select a category</option>

            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $course->category_id ?? '') == $category->id)>
                    {{ $category->category_name }}
                </option>
            @endforeach
        </select>

        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" :value="__('Course Title')" />

        <x-text-input
            id="title"
            name="title"
            type="text"
            class="block mt-1 w-full"
            :value="old('title', $course->title ?? '')"
            maxlength="150"
            required />

        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Description')" />

        <textarea
            id="description"
            name="description"
            rows="5"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            required>{{ old('description', $course->description ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="thumbnail" :value="__('Thumbnail URL (Optional)')" />

        <x-text-input
            id="thumbnail"
            name="thumbnail"
            type="text"
            class="block mt-1 w-full"
            :value="old('thumbnail', $course->thumbnail ?? '')" />

        <x-input-error :messages="$errors->get('thumbnail')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />

        <select id="status" name="status" class="block mt-1 w-full rounded-lg border-gray-300" required>
            @foreach(['Draft', 'Published'] as $status)
                <option value="{{ $status }}" @selected(old('status', $course->status ?? 'Draft') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>

        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

</div>

<div class="flex justify-end gap-3 mt-8">
    <a
        href="{{ route('mentor.courses') }}"
        class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
        Cancel
    </a>

    <x-ui.action-button type="submit">
        {{ $buttonText }}
    </x-ui.action-button>
</div>

@csrf

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">

    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Assessment Information</h3>
        <p class="mt-1 text-sm text-slate-500">Set the assessment details, rules and publication status.</p>
        <hr class="mt-4">
    </div>

    <div class="md:col-span-2">
        <x-input-label for="lesson_id" :value="__('Course Lesson')" />

        <select id="lesson_id" name="lesson_id" class="block mt-1 w-full rounded-lg border-gray-300" required>
            <option value="">Select a lesson</option>

            @foreach($lessons as $lesson)
                <option
                    value="{{ $lesson->id }}"
                    @selected(old('lesson_id', $assessment->lesson_id ?? '') == $lesson->id)>
                    {{ $lesson->course->title }} — {{ $lesson->title }}
                </option>
            @endforeach
        </select>

        <x-input-error :messages="$errors->get('lesson_id')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="title" :value="__('Assessment Title')" />

        <x-text-input
            id="title"
            name="title"
            type="text"
            class="block mt-1 w-full"
            :value="old('title', $assessment->title ?? '')"
            placeholder="e.g. Laravel Basics Quiz"
            required />

        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="__('Description (Optional)')" />

        <textarea
            id="description"
            name="description"
            rows="4"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Briefly describe what this assessment covers...">{{ old('description', $assessment->description ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="passing_score" :value="__('Passing Score (%)')" />

        <x-text-input
            id="passing_score"
            name="passing_score"
            type="number"
            min="0"
            max="100"
            class="block mt-1 w-full"
            :value="old('passing_score', $assessment->passing_score ?? 50)"
            required />

        <x-input-error :messages="$errors->get('passing_score')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="time_limit" :value="__('Time Limit (minutes, optional)')" />

        <x-text-input
            id="time_limit"
            name="time_limit"
            type="number"
            min="1"
            max="600"
            class="block mt-1 w-full"
            :value="old('time_limit', $assessment->time_limit ?? '')"
            placeholder="e.g. 30" />

        <x-input-error :messages="$errors->get('time_limit')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="max_attempts" :value="__('Max Attempts (optional)')" />

        <x-text-input
            id="max_attempts"
            name="max_attempts"
            type="number"
            min="1"
            max="10"
            class="block mt-1 w-full"
            :value="old('max_attempts', $assessment->max_attempts ?? '')"
            placeholder="e.g. 3" />

        <x-input-error :messages="$errors->get('max_attempts')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('Status')" />

        <select id="status" name="status" class="block mt-1 w-full rounded-lg border-gray-300" required>
            @foreach(['Draft', 'Published'] as $status)
                <option value="{{ $status }}" @selected(old('status', $assessment->status ?? 'Draft') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>

        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

</div>

<div class="flex justify-end gap-3 mt-8">
    <a
        href="{{ route('assessments.index') }}"
        class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
        Cancel
    </a>

    <x-ui.action-button type="submit">
        {{ $buttonText }}
    </x-ui.action-button>
</div>

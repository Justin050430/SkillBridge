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
            maxlength="150"
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

    <fieldset x-data="{ durationEnabled: {{ old('duration_enabled', ($assessment->time_limit ?? 0) > 0) ? 'true' : 'false' }} }">
        <legend class="text-sm font-medium text-gray-700">Assessment duration</legend>
        @php
            $duration = $assessment->time_limit ?? 0;
        @endphp
        <input type="hidden" name="duration_enabled" value="0">
        <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="duration_enabled" value="1"
                x-model="durationEnabled"
                @checked(old('duration_enabled', $duration > 0)) class="rounded border-gray-300 text-indigo-600">
            Set a time limit
        </label>
        <div class="mt-3 grid grid-cols-3 gap-2">
            @foreach(['hours' => [10, intdiv($duration, 3600)], 'minutes' => [59, intdiv($duration % 3600, 60)], 'seconds' => [59, $duration % 60]] as $part => [$maximum, $value])
                @php
                    $partValue = old('duration_'.$part, $value);
                    $partValue = is_scalar($partValue) ? $partValue : '';
                @endphp
                <div>
                    <x-input-label :for="'duration_'.$part" :value="ucfirst($part)" />
                    <input id="duration_{{ $part }}" name="duration_{{ $part }}" type="number" min="0" max="{{ $maximum }}" step="1"
                        :disabled="!durationEnabled" value="{{ $partValue }}" class="mt-1 w-full rounded-lg border-gray-300 text-center tabular-nums disabled:bg-slate-100 disabled:text-slate-400">
                    <x-input-error :messages="$errors->get('duration_'.$part)" class="mt-2" />
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-slate-500">Unchecked means no time limit. Timed assessments allow 1 second to 10 hours. Changes apply to new attempts.</p>
        <x-input-error :messages="$errors->get('duration_enabled')" class="mt-2" />
        <x-input-error :messages="$errors->get('time_limit')" class="mt-2" />
    </fieldset>

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

        @php
            $questionCount = $assessment->questions_count ?? 0;
        @endphp

        <select id="status" name="status" class="block mt-1 w-full rounded-lg border-gray-300" required>
            @foreach(['Draft', 'Published'] as $status)
                <option
                    value="{{ $status }}"
                    @selected(old('status', $assessment->status ?? 'Draft') === $status)
                    @disabled($status === 'Published' && $questionCount === 0)>
                    {{ $status }}
                </option>
            @endforeach
        </select>

        @if($questionCount === 0)
            <p class="mt-2 text-sm text-amber-600">
                Add questions before publishing.
            </p>
        @endif

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

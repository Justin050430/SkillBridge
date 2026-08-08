@csrf

<div
    x-data="{ type: '{{ old('question_type', $question->question_type ?? 'MCQ') }}' }"
    class="grid grid-cols-1 gap-6 md:grid-cols-2">

    <div class="md:col-span-2">
        <h3 class="text-lg font-semibold text-slate-800">Question Details</h3>
        <p class="mt-1 text-sm text-slate-500">Define the question, its type and the correct answer.</p>
        <hr class="mt-4">
    </div>

    <div class="md:col-span-2">
        <x-input-label for="question_text" :value="__('Question')" />

        <textarea
            id="question_text"
            name="question_text"
            rows="3"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            required>{{ old('question_text', $question->question_text ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="question_type" :value="__('Question Type')" />

        <select
            id="question_type"
            name="question_type"
            x-model="type"
            class="block mt-1 w-full rounded-lg border-gray-300"
            required>

            <option value="MCQ" @selected(old('question_type', $question->question_type ?? '') === 'MCQ')>Multiple Choice</option>
            <option value="TrueFalse" @selected(old('question_type', $question->question_type ?? '') === 'TrueFalse')>True / False</option>
            <option value="ShortAnswer" @selected(old('question_type', $question->question_type ?? '') === 'ShortAnswer')>Short Answer</option>

        </select>

        <x-input-error :messages="$errors->get('question_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="marks" :value="__('Marks')" />

        <x-text-input
            id="marks"
            name="marks"
            type="number"
            min="1"
            max="100"
            class="block mt-1 w-full"
            :value="old('marks', $question->marks ?? 1)"
            required />

        <x-input-error :messages="$errors->get('marks')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="order_number" :value="__('Order Number')" />

        <x-text-input
            id="order_number"
            name="order_number"
            type="number"
            min="1"
            class="block mt-1 w-full"
            :value="old('order_number', $question->order_number ?? $nextOrder)"
            required />

        <x-input-error :messages="$errors->get('order_number')" class="mt-2" />
    </div>

    <!-- MCQ options -->
    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6" x-show="type === 'MCQ'">

        <div class="md:col-span-2">
            <h4 class="font-semibold text-slate-700">Answer Options</h4>
            <p class="text-sm text-slate-500">Fill in the four choices and select the correct letter.</p>
        </div>

        @foreach(['a', 'b', 'c', 'd'] as $letter)

        <div>
            <x-input-label for="option_{{ $letter }}" :value="__('Option ' . strtoupper($letter))" />

            <x-text-input
                id="option_{{ $letter }}"
                name="option_{{ $letter }}"
                type="text"
                class="block mt-1 w-full"
                :value="old('option_' . $letter, $question->{'option_' . $letter} ?? '')" />

            <x-input-error :messages="$errors->get('option_' . $letter)" class="mt-2" />
        </div>

        @endforeach

        <div class="md:col-span-2">
            <x-input-label for="correct_answer_mcq" :value="__('Correct Answer (letter)')" />

            <select
                id="correct_answer_mcq"
                name="correct_answer_mcq"
                class="block mt-1 w-full rounded-lg border-gray-300">

                @foreach(['a', 'b', 'c', 'd'] as $letter)
                    <option
                        value="{{ strtoupper($letter) }}"
                        @selected(old('correct_answer_mcq', $question->correct_answer ?? '') === strtoupper($letter))>
                        {{ strtoupper($letter) }}
                    </option>
                @endforeach

            </select>

            <x-input-error :messages="$errors->get('correct_answer_mcq')" class="mt-2" />
        </div>

    </div>

    <!-- True / False -->
    <div class="md:col-span-2" x-show="type === 'TrueFalse'">

        <x-input-label for="correct_answer_tf" :value="__('Correct Answer')" />

        <select
            id="correct_answer_tf"
            name="correct_answer_tf"
            class="block mt-1 w-full rounded-lg border-gray-300">

            @foreach(['True', 'False'] as $option)
                <option value="{{ $option }}" @selected(old('correct_answer_tf', $question->correct_answer ?? '') === $option)>
                    {{ $option }}
                </option>
            @endforeach

        </select>

        <x-input-error :messages="$errors->get('correct_answer_tf')" class="mt-2" />
    </div>

    <!-- Short Answer -->
    <div class="md:col-span-2" x-show="type === 'ShortAnswer'">

        <x-input-label for="correct_answer_short" :value="__('Correct Answer (expected text)')" />

        <x-text-input
            id="correct_answer_short"
            name="correct_answer_short"
            type="text"
            class="block mt-1 w-full"
            :value="old('correct_answer_short', $question->correct_answer ?? '')" />

        <x-input-error :messages="$errors->get('correct_answer_short')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="explanation" :value="__('Explanation (optional, shown after grading)')" />

        <textarea
            id="explanation"
            name="explanation"
            rows="3"
            class="block mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Explain why the answer is correct...">{{ old('explanation', $question->explanation ?? '') }}</textarea>

        <x-input-error :messages="$errors->get('explanation')" class="mt-2" />
    </div>

</div>

<div class="flex justify-end gap-3 mt-8">
    <a
        href="{{ route('assessments.questions.index', $assessment) }}"
        class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
        Cancel
    </a>

    <x-ui.action-button type="submit">
        {{ $buttonText }}
    </x-ui.action-button>
</div>

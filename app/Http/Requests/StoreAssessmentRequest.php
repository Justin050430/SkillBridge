<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Requests;

use App\Models\Assessment;
use App\Models\Lesson;
use App\Support\ValidationRules;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of an assessment by an Admin or Mentor.
 *
 * Authorization (role:Admin,Mentor) is enforced by route middleware and the
 * mentor's course scope is enforced server-side in the controller
 * (ensureAccessible). The lesson-ownership check below is a field validation
 * rule that reports an error on lesson_id.
 */
class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $parts = [];
        foreach (['duration_hours', 'duration_minutes', 'duration_seconds'] as $field) {
            $value = $this->input($field);
            // Accept phone-style zero padding without accepting decimals or arrays.
            if (is_string($value) && preg_match('/^\d{1,3}$/D', $value)) {
                $parts[$field] = (int) $value;
            }
        }
        $this->merge($parts);
        $total = 0;
        foreach (['duration_hours' => 3600, 'duration_minutes' => 60, 'duration_seconds' => 1] as $field => $factor) {
            $value = $this->input($field);
            if (is_int($value) && $value >= 0 && $value <= 36000) {
                $total += $value * $factor;
            }
        }
        // Never accept a client-supplied total independently of the components.
        $this->merge(['time_limit' => $this->boolean('duration_enabled') ? $total : null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'exists:lessons,id', $this->lessonAccessRule()],
            'title' => ValidationRules::title(),
            'description' => ValidationRules::optionalLongText(5000),
            'passing_score' => 'required|integer|min:0|max:100',
            'duration_enabled' => 'sometimes|boolean',
            'duration_hours' => 'exclude_unless:duration_enabled,1|required|integer|min:0|max:10',
            'duration_minutes' => 'exclude_unless:duration_enabled,1|required|integer|min:0|max:59',
            'duration_seconds' => 'exclude_unless:duration_enabled,1|required|integer|min:0|max:59',
            'time_limit' => 'nullable|integer|min:1|max:36000',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'status' => 'required|in:Draft,Published',
        ];
    }

    /**
     * Enforce the invariant: an assessment must contain at least one question
     * before it can be Published. On create the assessment is brand new (0
     * questions), so requesting Published is rejected; on update the current
     * question count of the existing assessment is used.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'Published') {
                return;
            }

            if ($this->existingQuestionCount() === 0) {
                $validator->errors()->add(
                    'status',
                    'An assessment must contain at least one question before it can be published.'
                );
            }
        });
    }

    /**
     * Question count for the assessment being (un)published. On create there is
     * no assessment yet, so this is always 0.
     */
    protected function existingQuestionCount(): int
    {
        $assessment = $this->route('assessment');

        return $assessment instanceof Assessment
            ? $assessment->questions()->count()
            : 0;
    }

    /**
     * Mentors may only attach assessments to lessons inside their own courses.
     */
    protected function lessonAccessRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->user();

            if (! $user || ! $user->isMentor()) {
                return;
            }

            $lesson = Lesson::with('course')->find($value);

            if (! $lesson || (int) $lesson->course->mentor_id !== (int) $user->id) {
                $fail('You can only attach assessments to lessons in your own courses.');
            }
        };
    }
}

<?php

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
            'time_limit' => 'nullable|integer|min:1|max:600',
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

            if (!$user || !$user->isMentor()) {
                return;
            }

            $lesson = Lesson::with('course')->find($value);

            if (!$lesson || (int) $lesson->course->mentor_id !== (int) $user->id) {
                $fail('You can only attach assessments to lessons in your own courses.');
            }
        };
    }
}

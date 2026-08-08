<?php

/**
 * @author OOI JIAN XIN
 */

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    /**
     * The currently authenticated user, guaranteed to be an Admin or Mentor.
     */
    protected function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403, 'Unauthenticated.');

        return $user;
    }

    public function index(Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $questions = $assessment->questions()
            ->orderBy('order_number')
            ->get();

        return view('assessments.questions.index', compact('assessment', 'questions'));
    }

    public function create(Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $nextOrder = ($assessment->questions()->max('order_number') ?? 0) + 1;

        return view('assessments.questions.create', compact('assessment', 'nextOrder'));
    }

    public function store(Request $request, Assessment $assessment)
    {
        $this->ensureAccessible($assessment);

        $validated = $request->validate($this->rules());

        $data = $this->withCorrectAnswer($validated, $request);

        $assessment->questions()->create($data);

        return redirect()
            ->route('assessments.questions.index', $assessment)
            ->with('success', 'Question added successfully.');
    }

    public function edit(Assessment $assessment, Question $question)
    {
        $this->ensureAccessible($assessment);
        $this->ensureBelongsToAssessment($assessment, $question);

        return view('assessments.questions.edit', compact('assessment', 'question'));
    }

    public function update(Request $request, Assessment $assessment, Question $question)
    {
        $this->ensureAccessible($assessment);
        $this->ensureBelongsToAssessment($assessment, $question);

        $validated = $request->validate($this->rules());

        $data = $this->withCorrectAnswer($validated, $request);

        $question->update($data);

        return redirect()
            ->route('assessments.questions.index', $assessment)
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Assessment $assessment, Question $question)
    {
        $this->ensureAccessible($assessment);
        $this->ensureBelongsToAssessment($assessment, $question);

        $question->delete();

        return redirect()
            ->route('assessments.questions.index', $assessment)
            ->with('success', 'Question deleted successfully.');
    }

    /**
     * Validation rules for creating / updating a question.
     *
     * The correct answer is captured from a type-specific field
     * (correct_answer_mcq / correct_answer_tf / correct_answer_short)
     * and normalised into the question's correct_answer column.
     */
    protected function rules(): array
    {
        return [
            'question_text' => 'required|string',
            'question_type' => 'required|in:MCQ,TrueFalse,ShortAnswer',
            'option_a' => 'nullable|string',
            'option_b' => 'nullable|string',
            'option_c' => 'nullable|string',
            'option_d' => 'nullable|string',
            'correct_answer_mcq' => 'nullable|required_if:question_type,MCQ|string|max:255',
            'correct_answer_tf' => 'nullable|required_if:question_type,TrueFalse|string|max:255',
            'correct_answer_short' => 'nullable|required_if:question_type,ShortAnswer|string|max:255',
            'explanation' => 'nullable|string',
            'marks' => 'required|integer|min:1|max:100',
            'order_number' => 'required|integer|min:1',
        ];
    }

    /**
     * Resolve the single correct_answer column from the type-specific field.
     */
    protected function withCorrectAnswer(array $validated, Request $request): array
    {
        $validated['correct_answer'] = match ($validated['question_type']) {
            'MCQ' => (string) $request->input('correct_answer_mcq'),
            'TrueFalse' => (string) $request->input('correct_answer_tf'),
            default => (string) $request->input('correct_answer_short'),
        };

        return $validated;
    }

    /**
     * Mentors may only manage questions for assessments in their own courses.
     */
    protected function ensureAccessible(Assessment $assessment): void
    {
        if ($this->currentUser()->isMentor()) {
            $owns = $assessment->lesson->course->mentor_id === $this->currentUser()->id;
            abort_unless($owns, 403, 'You can only manage questions for your own assessments.');
        }
    }

    /**
     * Prevent questions being accessed through the wrong assessment.
     */
    protected function ensureBelongsToAssessment(Assessment $assessment, Question $question): void
    {
        abort_unless($question->assessment_id === $assessment->id, 404);
    }
}

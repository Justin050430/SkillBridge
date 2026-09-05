<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPublishingChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->user('Admin');
        $this->course = Course::create([
            'mentor_id' => $this->user('Mentor')->id,
            'category_id' => Category::create(['category_name' => 'Development'])->id,
            'title' => 'Web Development',
            'description' => 'Learn to build websites.',
            'status' => 'Draft',
        ]);
    }

    public function test_checklist_distinguishes_blockers_from_drafts_and_links_to_the_right_editor(): void
    {
        $lesson = $this->lesson();
        $assessment = $this->assessment($lesson);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Publishing Checklist')
            ->assertDontSee('Recent Activities')
            ->assertSee('Blocked')
            ->assertSee('At least one question is required to publish.')
            ->assertSee(route('admin.courses.edit', $this->course->slug), false)
            ->assertSee(route('courses.lessons.edit', [$this->course->slug, $lesson->id]), false)
            ->assertSee(route('assessments.questions.create', $assessment->slug), false)
            ->assertViewHas('checklistCounts', ['all' => 3, 'course' => 1, 'lesson' => 1, 'assessment' => 1]);

        $items = $response->viewData('checklistItems');
        $this->assertSame('assessment', $items->first()->type);
        $this->assertSame('Draft', $items->firstWhere('type', 'course')->label);

        foreach ($items as $item) {
            $this->get($item->url)->assertOk();
        }
    }

    public function test_missing_lessons_are_a_reminder_and_missing_lesson_content_is_a_blocker(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()->assertSee('Content reminder')->assertDontSee('>Blocked<', false)
            ->assertSee(route('courses.lessons.create', $this->course->slug), false);
        $this->assertEquals(0, $response->viewData('checklistItems')->first()->blocked);

        $this->lesson(['content' => '']);

        $response = $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Missing lesson content.')
            ->assertDontSee('No lessons added yet.');
        $this->assertSame('lesson', $response->viewData('checklistItems')->first()->type);
    }

    public function test_adding_questions_clears_blocker_and_publishing_removes_the_assessment(): void
    {
        $assessment = $this->assessment($this->lesson());
        $this->actingAs($this->admin);
        $this->get(route('admin.dashboard'))->assertSee('No questions added.');

        Question::create([
            'assessment_id' => $assessment->id,
            'question_text' => 'What does HTML stand for?',
            'question_type' => 'MCQ',
            'option_a' => 'HyperText Markup Language',
            'option_b' => 'Another answer',
            'option_c' => 'Another answer',
            'option_d' => 'Another answer',
            'correct_answer' => 'A',
            'marks' => 1,
            'order_number' => 1,
        ]);

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('No questions added.')
            ->assertSee(route('assessments.edit', $assessment->slug), false);

        $this->put(route('assessments.update', $assessment->slug), [
            'lesson_id' => $assessment->lesson_id,
            'title' => $assessment->title,
            'passing_score' => 50,
            'status' => 'Published',
        ])->assertSessionHasNoErrors()->assertRedirect(route('assessments.index'));

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee($assessment->title)
            ->assertViewHas('checklistCounts', ['all' => 2, 'course' => 1, 'lesson' => 1, 'assessment' => 0]);
    }

    public function test_published_items_and_archived_course_descendants_are_excluded(): void
    {
        $this->lesson(['status' => 'Published']);
        $this->course->update(['status' => 'Published']);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('No drafts to review');

        $this->assessment($this->lesson());
        $this->course->update(['status' => 'Archived']);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('No drafts to review')
            ->assertViewHas('checklistCounts', ['all' => 0, 'course' => 0, 'lesson' => 0, 'assessment' => 0]);
    }

    public function test_filters_and_pagination_keep_every_draft_reachable(): void
    {
        for ($number = 1; $number <= 6; $number++) {
            $this->lesson(['title' => 'Draft lesson '.$number]);
        }

        $url = route('admin.dashboard', ['checklist_type' => 'lesson']);
        $response = $this->actingAs($this->admin)->get($url)->assertOk()
            ->assertViewHas('checklistCounts', ['all' => 7, 'course' => 1, 'lesson' => 6, 'assessment' => 0]);
        $items = $response->viewData('checklistItems');
        $this->assertCount(5, $items);
        $this->assertSame(6, $items->total());
        $this->assertSame(['lesson'], $items->pluck('type')->unique()->all());
        $this->assertStringContainsString('checklist_type=lesson', $items->nextPageUrl());
        $this->assertStringContainsString('#publishing-checklist', $items->nextPageUrl());

        $next = $this->get($items->nextPageUrl())->assertOk()->viewData('checklistItems');
        $this->assertCount(1, $next);
        $this->assertFalse($items->pluck('id')->contains($next->first()->id));

        $this->get($url.'&checklist_page=3')->assertOk()
            ->assertSee('Return to the first page')->assertDontSee('No drafts in this category');

        $this->get(route('admin.dashboard', ['checklist_type' => 'assessment']))
            ->assertOk()->assertSee('No drafts in this category');
        $this->get(route('admin.dashboard', ['checklist_type' => 'invalid']))
            ->assertSessionHasErrors('checklist_type');
    }

    public function test_only_admins_can_access_the_checklist(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($this->user('Student'))->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->course->mentor)->get(route('admin.dashboard'))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['role_name' => $role])->id,
            'status' => 'Active',
        ]);
    }

    private function lesson(array $attributes = []): Lesson
    {
        return $this->course->lessons()->create(array_merge([
            'title' => 'Getting Started',
            'content' => 'An introduction to the course.',
            'order_number' => 1,
            'status' => 'Draft',
        ], $attributes));
    }

    private function assessment(Lesson $lesson): Assessment
    {
        return $lesson->assessments()->create([
            'title' => 'Knowledge Check',
            'passing_score' => 50,
            'status' => 'Draft',
        ]);
    }
}

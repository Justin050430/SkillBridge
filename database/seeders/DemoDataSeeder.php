<?php

/**
 * @author OOI JIAN XIN
 *
 * Seeds realistic SkillBridge demo content so a fresh database immediately
 * contains enough published courses, lessons, assessments and questions for a
 * newly registered student to browse and use the platform.
 *
 * No fake student accounts, enrollments, progress or answers are created.
 * Mentor accounts are required so courses have a valid mentor_id.
 *
 * Safe to run on a fresh database; idempotent (skips if already seeded).
 */

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /** @var array<int, string> */
    protected array $categoryNames = [
        'Programming',
        'Web Development',
        'Cybersecurity',
        'Mobile Development',
        'Data Science',
    ];

    /** @var array<int, array{first:string,last:string,email:string}> */
    protected array $mentors = [
        ['first' => 'Aisha', 'last' => 'Rahman', 'email' => 'aisha.rahman@demo.com'],
        ['first' => 'Daniel', 'last' => 'Lim', 'email' => 'daniel.lim@demo.com'],
        ['first' => 'Siti', 'last' => 'Tan', 'email' => 'siti.tan@demo.com'],
    ];

    /**
     * @var array<int, array{title:string,category:string,mentor:int,status:string,lessons:array<int,string>}>
     */
    protected array $courses = [
        [
            'title' => 'Introduction to Python Programming',
            'category' => 'Programming',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['Welcome to Python', 'Variables and Data Types', 'Control Flow', 'Functions and Modules', 'Hands-on Mini Project'],
        ],
        [
            'title' => 'Object-Oriented Programming in Java',
            'category' => 'Programming',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['Why Java?', 'Classes and Objects', 'Inheritance and Polymorphism', 'Interfaces and Abstract Classes'],
        ],
        [
            'title' => 'Data Structures and Algorithms',
            'category' => 'Programming',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['Complexity Analysis', 'Arrays and Linked Lists', 'Stacks and Queues', 'Trees and Graphs', 'Sorting and Searching'],
        ],
        [
            'title' => 'Version Control with Git and GitHub',
            'category' => 'Programming',
            'mentor' => 0,
            'status' => 'Draft',
            'lessons' => ['Getting Started with Git', 'Branching and Merging', 'Working with GitHub', 'Collaboration Workflows'],
        ],
        [
            'title' => 'Responsive Web Design with HTML and CSS',
            'category' => 'Web Development',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['HTML Foundations', 'CSS Styling', 'Flexbox and Grid', 'Responsive Layouts', 'Final Portfolio Project'],
        ],
        [
            'title' => 'JavaScript Essentials',
            'category' => 'Web Development',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['Variables and Functions', 'DOM Manipulation', 'Events and Forms', 'Asynchronous JavaScript'],
        ],
        [
            'title' => 'Full-Stack Web Development with Laravel',
            'category' => 'Web Development',
            'mentor' => 0,
            'status' => 'Published',
            'lessons' => ['Laravel Fundamentals', 'Routing and Controllers', 'Eloquent ORM', 'Authentication and Middleware', 'Building a Real Project'],
        ],
        [
            'title' => 'Building REST APIs with PHP',
            'category' => 'Web Development',
            'mentor' => 0,
            'status' => 'Draft',
            'lessons' => ['API Design Principles', 'RESTful Endpoints', 'Authentication for APIs', 'Testing and Documentation'],
        ],
        [
            'title' => 'Cybersecurity Fundamentals',
            'category' => 'Cybersecurity',
            'mentor' => 1,
            'status' => 'Published',
            'lessons' => ['Introduction to Security', 'Common Threats and Attacks', 'Risk Management', 'Security Controls', 'Defence in Depth'],
        ],
        [
            'title' => 'Ethical Hacking Basics',
            'category' => 'Cybersecurity',
            'mentor' => 1,
            'status' => 'Published',
            'lessons' => ['Ethics and Legalities', 'Reconnaissance', 'Scanning and Enumeration', 'Exploitation Basics', 'Reporting Findings'],
        ],
        [
            'title' => 'Network Security Essentials',
            'category' => 'Cybersecurity',
            'mentor' => 1,
            'status' => 'Published',
            'lessons' => ['Networking Fundamentals', 'Firewalls and IDS', 'VPNs and Secure Communication', 'Securing Wireless Networks'],
        ],
        [
            'title' => 'Web Application Security',
            'category' => 'Cybersecurity',
            'mentor' => 1,
            'status' => 'Published',
            'lessons' => ['OWASP Top 10', 'SQL Injection and XSS', 'Authentication Flaws', 'Secure Coding Practices'],
        ],
        [
            'title' => 'Digital Forensics Introduction',
            'category' => 'Cybersecurity',
            'mentor' => 1,
            'status' => 'Draft',
            'lessons' => ['Forensics Process', 'Evidence Handling', 'Disk and Memory Analysis', 'Forensic Reporting'],
        ],
        [
            'title' => 'Android App Development with Kotlin',
            'category' => 'Mobile Development',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['Kotlin Basics', 'Activities and Layouts', 'Recycler Views', 'Networking and Data', 'Publishing Your App'],
        ],
        [
            'title' => 'iOS Development with Swift',
            'category' => 'Mobile Development',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['Swift Fundamentals', 'View Controllers', 'Auto Layout', 'Data Persistence', 'Submitting to the App Store'],
        ],
        [
            'title' => 'Cross-Platform Apps with Flutter',
            'category' => 'Mobile Development',
            'mentor' => 2,
            'status' => 'Draft',
            'lessons' => ['Flutter and Dart', 'Widgets and Layouts', 'State Management', 'Platform Integration'],
        ],
        [
            'title' => 'Introduction to Data Science',
            'category' => 'Data Science',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['What is Data Science?', 'Working with Data', 'Statistics Basics', 'Building a Data Project'],
        ],
        [
            'title' => 'Machine Learning with Python',
            'category' => 'Data Science',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['ML Fundamentals', 'Supervised Learning', 'Unsupervised Learning', 'Model Evaluation', 'Capstone Project'],
        ],
        [
            'title' => 'Data Visualization with Python',
            'category' => 'Data Science',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['Introduction to Visualization', 'Matplotlib Basics', 'Pandas Plotting', 'Interactive Dashboards'],
        ],
        [
            'title' => 'SQL for Data Analysis',
            'category' => 'Data Science',
            'mentor' => 2,
            'status' => 'Published',
            'lessons' => ['SQL Basics', 'Querying and Filtering', 'Joins and Aggregations', 'Advanced SQL for Analysts'],
        ],
    ];

    public function run(): void
    {
        // Idempotency guard: skip when demo data already exists.
        if (Course::where('slug', 'introduction-to-python-programming')->exists()) {
            $this->command?->info('Demo data already seeded - skipping.');

            return;
        }

        DB::transaction(function (): void {
            $this->ensureRoles();
            $mentors = $this->ensureMentors();
            $categories = $this->ensureCategories();

            foreach ($this->courses as $index => $spec) {
                $course = Course::create([
                    'mentor_id' => $mentors[$spec['mentor']]->id,
                    'category_id' => $categories[$spec['category']]->id,
                    'title' => $spec['title'],
                    'description' => "A practical, project-based course that teaches {$spec['title']} step by step. "
                        .'You will learn through clear lessons, hands-on exercises and instant assessments.',
                    'status' => $spec['status'],
                ]);

                $lastLesson = null;

            foreach ($spec['lessons'] as $order => $lessonTitle) {
                    $lesson = Lesson::create([
                        'course_id' => $course->id,
                        'title' => $lessonTitle,
                        'content' => "Lesson content for {$lessonTitle} in {$course->title}. "
                            .'This section explains the key concepts with examples, and prepares you for the assessment.',
                        'order_number' => $order + 1,
                        'estimated_duration' => 20 + ($order * 10),
                        'status' => 'Published',
                    ]);

                    $lastLesson = $lesson;
                }

                // Give every published course one assessment on its final lesson.
                if ($course->status === 'Published' && $lastLesson) {
                    $this->createAssessment($course, $lastLesson);
                }
            }

            $this->command?->info('Demo data seeded: '.count($this->courses).' courses created.');
        });
    }

    protected function ensureRoles(): void
    {
        foreach (['Admin', 'Mentor', 'Student'] as $roleName) {
            Role::firstOrCreate(['role_name' => $roleName]);
        }
    }

    /**
     * @return array<int, User>
     */
    protected function ensureMentors(): array
    {
        $mentorRoleId = Role::where('role_name', 'Mentor')->value('id');

        return array_map(function (array $mentor) use ($mentorRoleId) {
            return User::firstOrCreate(
                ['email' => $mentor['email']],
                [
                    'role_id' => $mentorRoleId,
                    'first_name' => $mentor['first'],
                    'last_name' => $mentor['last'],
                    'password' => Hash::make('Mentor@123'),
                    'status' => 'Active',
                ]
            );
        }, $this->mentors);
    }

    /**
     * @return array<string, Category>
     */
    protected function ensureCategories(): array
    {
        $map = [];

        foreach ($this->categoryNames as $name) {
            $map[$name] = Category::firstOrCreate(
                ['category_name' => $name],
                ['description' => "Courses related to {$name}."]
            );
        }

        return $map;
    }

    protected function createAssessment(Course $course, Lesson $lesson): void
    {
        $assessment = Assessment::create([
            'lesson_id' => $lesson->id,
            'title' => "{$course->title} - Final Quiz",
            'description' => 'A short quiz to confirm your understanding of the key concepts in this course.',
            'passing_score' => 50,
            'time_limit' => 15,
            'max_attempts' => 3,
            'status' => 'Published',
        ]);

        $questions = [
            ['Which of the following best describes the main topic of this course?', 'MCQ', ['A', 'B', 'C', 'D'], 'A'],
            ['What is the correct first step when starting this course?', 'MCQ', ['Review the overview', 'Skip straight ahead', 'Ignore the lessons', 'Ask for the answers'], 'A'],
            ['Which statement about the course is true?', 'MCQ', ['Lessons build on each other', 'Lessons are unrelated', 'There are no assessments', 'Completion is not tracked'], 'A'],
            ['This course is designed to be completed in one sitting.', 'TrueFalse', null, 'False'],
            ['Name one key skill you expect to learn from this course.', 'ShortAnswer', null, ''],
        ];

        foreach ($questions as $order => $q) {
            [$text, $type, $options, $correct] = $q;

            $data = [
                'assessment_id' => $assessment->id,
                'question_text' => $text,
                'question_type' => $type,
                'marks' => 1,
                'order_number' => $order + 1,
                'correct_answer' => $correct,
            ];

            if ($options !== null) {
                $data['option_a'] = $options[0];
                $data['option_b'] = $options[1];
                $data['option_c'] = $options[2];
                $data['option_d'] = $options[3];
            }

            Question::create($data);
        }
    }
}

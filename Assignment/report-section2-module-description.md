# Section 2 – Module Description

**Module:** Assessment & Progress Management
**Member:** OOI JIAN XIN

---

This section describes the functionality of the **Assessment & Progress
Management** module that I oversaw. The module is separated below by the
functions it handles. Each function identifies who uses it, where it is
implemented (controller / service / model / view path) and the screenshot to be
used as evidence. All screenshots are taken from the live Azure deployment
unless stated otherwise.

---

## 2.1 Assessment Management

**What it does.** Admins and mentors create, view, edit and delete assessments,
each attached to a lesson of a course. The assessment list supports search and
status filtering, and each row shows the number of questions and attempts. A
business rule is enforced by the module: an assessment may only be **Published**
when it contains at least one question; an empty assessment remains in Draft
status and the interface shows an "Add questions before publishing" hint.

**Who uses it.** Administrators (all assessments system-wide) and Mentors
(only assessments belonging to lessons of their own courses). Mentor scoping is
enforced server-side on every request, so a mentor can never manage another
mentor's assessment.

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/AssessmentController.php` |
| Form Requests | `app/Http/Requests/StoreAssessmentRequest.php`, `app/Http/Requests/UpdateAssessmentRequest.php` |
| Model | `app/Models/Assessment.php` |
| Views | `resources/views/assessments/` (index, create, edit, delete, `_form`) |
| Routes | `routes/web.php` (assessments group, `role:Admin,Mentor`) |

> **[Figure 2.1 – Assessment management list]** Capture the `/assessments`
> page (logged in as Administrator) showing the assessment table with search,
> status filter, question/attempt counts and the "Add questions before
> publishing" hint on an empty assessment. Source: Azure. Supports Section 2.1.

> **[Figure 2.2 – Assessment create/edit form]** Capture the assessment edit
> page showing the form fields and the "Published" option disabled while the
> assessment has no questions. Source: Azure. Supports Section 2.1.

## 2.2 Question Management

**What it does.** Admins and mentors add, edit and delete questions under an
assessment. Three question types are supported: **Multiple-Choice (MCQ)**,
**True/False** and **Short Answer**. Each question stores its marks, display
order and an explanation, and the correct answer is normalised into a single
column regardless of type. Deleting the last question of a Published assessment
automatically reverts the assessment to Draft, preserving the "Published
assessment must contain at least one question" invariant.

**Who uses it.** Administrators and Mentors (scoped to their own courses).

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/QuestionController.php` |
| Form Requests | `app/Http/Requests/StoreQuestionRequest.php`, `app/Http/Requests/UpdateQuestionRequest.php` |
| Model | `app/Models/Question.php` |
| Views | `resources/views/assessments/questions/` (index, create, edit) |

> **[Figure 2.3 – Question management]** Capture the question list under an
> assessment, and one question create/edit form showing the question type
> selector (MCQ / True/False / Short Answer), marks and order. Source: Azure.
> Supports Section 2.2.

## 2.3 Student Assessment Flow (Take / Submit / Result)

**What it does.** A student enrolled in a published course sees the
assessments they are allowed to take. Each assessment is only visible when the
assessment **and** its parent lesson are Published and it contains at least one
question (enforced server-side, so a direct URL cannot bypass this). The take
page presents the questions in order; if the student starts an attempt but does
not submit it, the same attempt can be resumed later. On submission, the
assessment is graded automatically and the student is taken to a result page
showing the score, percentage, pass/fail outcome and per-question feedback.

**Who uses it.** Students.

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/Student/AssessmentController.php` |
| Form Request | `app/Http/Requests/SubmitAssessmentRequest.php` |
| Views | `resources/views/student/assessments/` (index, take, result) |
| Routes | `routes/web.php` (student group, `role:Student`) |

> **[Figure 2.4 – Student "My Assessments" list]** Capture the student
> assessment list showing only assessments of enrolled courses (with attempt
> status / resume / take buttons). Source: Azure. Supports Section 2.3.

> **[Figure 2.5 – Assessment attempt (take page)]** Capture the take page
> showing an MCQ, a True/False and a Short Answer question in order. Source:
> Azure. Supports Section 2.3.

> **[Figure 2.6 – Submit confirmation modal]** Capture the SkillBridge-styled
> submit-confirmation dialog on the take page. Source: Azure. Supports
> Section 2.3.

> **[Figure 2.7 – Assessment result page]** Capture the result page showing
> score, percentage, pass/fail and per-question feedback, including the
> "Explore More Courses" section populated from the Course module web service.
> Source: Azure. Supports Sections 2.3, 2.4 and 6.

## 2.4 Automatic Grading and Attempt Limits

**What it does.** When a student submits, each answer is compared against the
correct answer (with case- and whitespace-insensitive normalisation), marks are
awarded per correct question, and the percentage and pass/fail result are
computed against the assessment's passing score. The module enforces attempt
limits: only **submitted** attempts count towards the limit, and a
started-but-unsubmitted attempt is always resumable rather than consuming an
extra attempt. Each submission is recorded as an `AssessmentAttempt` with the
individual `StudentAnswer` rows.

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Grading / limits | `app/Http/Controllers/Student/AssessmentController.php` (`submit()`, `canTake()`, `isAnswerCorrect()`) |
| Models | `app/Models/AssessmentAttempt.php`, `app/Models/StudentAnswer.php` |

## 2.5 Progress Tracking

**What it does.** The student "My Progress" page shows their enrolled courses,
lessons and completion status. Lessons are **automatically marked as Completed**
when the student passes the lesson's assessment, through the Observer design
pattern's `ProgressUpdater` observer (Section 4). Progress is scoped to the
student's active enrollment, so a re-enrollment after dropping a course starts
from zero.

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/Student/ProgressController.php` |
| Model | `app/Models/Progress.php` |
| Observer | `app/Patterns/Observer/Observers/ProgressUpdater.php` |
| View | `resources/views/student/progress/` |

> **[Figure 2.8 – My Progress page]** Capture the student progress page showing
> courses, lessons and "Completed" statuses after passing an assessment.
> Source: Azure. Supports Section 2.5.

## 2.6 Administrative Reports and Analytics

**What it does.** Administrators can open a read-only Reports page that
aggregates the platform's data into an overview: content health (courses,
lessons, assessments, questions), platform activity (users and enrollments by
status), learner performance (total attempts, average score, pass rate), a bar
chart of courses by category, top courses by enrollment, and a per-assessment
performance table (attempts, average score and pass rate).

**Who uses it.** Administrators.

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/Admin/ReportController.php` |
| View | `resources/views/admin/reports/index.blade.php` |
| Route | `GET /admin/reports` (`routes/web.php`, `role:Admin`) |

> **[Figure 2.9 – Administrative Reports]** Capture the `/admin/reports` page
> showing the stat cards, bar charts and the per-assessment performance table.
> Source: Azure. Supports Section 2.6.

## 2.7 Assessment Analytics Web Service (Exposure)

**What it does.** The module exposes an **assessment analytics web service**
(`GET /api/assessments/{assessment}/stats`) that returns aggregate statistics
(attempt count, average score, pass rate, question count) for an assessment,
following the assignment's Interface Agreement (IFA). It is consumed by the
Mentor Dashboard "Assessment Analytics" widget and is available to any other
module. A course-level variant (`GET /api/assessments/course/{course}/stats`)
is also exposed and consumed by the Course Management module. This function is
described in detail in Section 6.

**Who uses it.** Mentors (dashboard widget) and other modules (e.g. the Course
Management module).

**Where it is implemented.**

| Layer | Path |
| --- | --- |
| Controller | `app/Http/Controllers/Api/AssessmentApiController.php` |
| Routes | `routes/api.php` |
| Consumer (browser) | `resources/views/mentor/dashboard.blade.php` (Alpine widget) |

> **[Figure 2.10 – Mentor dashboard analytics widget]** Capture the mentor
> dashboard showing the "Assessment Analytics" widget populated with data, with
> the browser Network tab showing the `GET /api/assessments/{id}/stats`
> request. Source: Azure. Supports Sections 2.7 and 6.

---

## Summary of Module Functions

| Function | Primary Users | Controller / Class Path | Screenshot |
| --- | --- | --- | --- |
| Assessment management | Admin, Mentor | `AssessmentController.php` | Fig. 2.1, 2.2 |
| Question management | Admin, Mentor | `QuestionController.php` | Fig. 2.3 |
| Student assessment flow | Student | `Student/AssessmentController.php` | Fig. 2.4–2.7 |
| Automatic grading & limits | Student | `Student/AssessmentController.php` | Fig. 2.7 |
| Progress tracking | Student | `Student/ProgressController.php` | Fig. 2.8 |
| Admin reports & analytics | Admin | `Admin/ReportController.php` | Fig. 2.9 |
| Assessment analytics web service | Mentor / other modules | `Api/AssessmentApiController.php` | Fig. 2.10 |

*The screenshots above are placeholders – each numbered figure should be
replaced with the actual screenshot (preferably from the Azure live
deployment) and given a descriptive caption in the final report.*

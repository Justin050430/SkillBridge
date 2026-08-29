# Section 6 – Web Services

**Module:** Assessment & Progress Management
**Member:** OOI JIAN XIN
**Protocol:** REST (JSON-based)

---

## 6.1 Overview of Web Service Technology Used in this Module

The Assessment & Progress module both **exposes** and **consumes** web services,
satisfying the requirement that each module interact with other modules over web
services and enabling efficient data exchange between system components.

The REST (JSON) protocol was chosen because it is the lightweight, modern and
flexible approach recommended for this system. Laravel provides first-class
support for building REST services (`response()->json()`) and for consuming them
(`Illuminate\Support\Facades\Http`), which keeps the payloads small and lets the
browser-based Mentor dashboard widget call the service directly.

All services in this module follow the **Interface Agreement (IFA)**:

- **Request** – every request must carry a `timestamp` **or** a unique `requestId`
  so the request can be tracked.
- **Response** – every response always includes a `status` field (`S` = Success,
  `F` = Fail, `E` = Error) and a `timestamp` showing when the response was generated.

---

## 6.2 Service Exposure – Assessment Statistics Web Service

This module exposes an Assessment Statistics web service that returns aggregate
analytics for a single assessment. It is consumed by the Mentor Dashboard
"Assessment Analytics" widget (and can be consumed by any other module that needs
assessment statistics).

### 6.2.1 Web Service Mechanism

| Field | Description |
| --- | --- |
| Protocol | RESTFUL |
| Function Description | Retrieves aggregate statistics for a single assessment (attempt count, average score, pass rate, question count) |
| Source Module | Assessment & Progress Management |
| Target Module | Mentor Dashboard (Analytics widget), Admin Dashboard, other modules requiring assessment analytics |
| URL | `GET /api/assessments/{assessment}/stats` |
| Function Name | `stats()` |

### 6.2.2 IFA – Request Parameter

| Field Name | Field Type | Mandatory / Optional | Description | Format |
| --- | --- | --- | --- | --- |
| `assessment` | Integer | Mandatory | Unique ID of the assessment whose statistics are requested | Path parameter in URL (`{assessment}`) |
| `timestamp` | String | Mandatory * | Time when the request was made | ISO 8601, e.g. `2026-08-22T10:30:00+08:00` |
| `requestId` | String | Mandatory * | Unique ID to track the request | UUID, e.g. `550e8400-e29b-41d4-a716-446655440000` |

\* At least one of `timestamp` or `requestId` is required. If neither is supplied,
the service rejects the request with `status = F` and HTTP 422.

### 6.2.3 IFA – Response Parameter

| Field Name | Field Type | Mandatory / Optional | Description | Format |
| --- | --- | --- | --- | --- |
| `status` | String | Mandatory | Status of the request | `S` : Success, `F` : Fail, `E` : Error |
| `requestId` | String | Mandatory | Echo of the request ID used for tracking | UUID string |
| `timestamp` | String | Mandatory | Time when the response was generated | ISO 8601 |
| `data` | Object | Mandatory | The requested statistics | Contains: `assessment_id`, `assessment_title`, `passing_score`, `question_count`, `attempt_count`, `average_score`, `pass_rate` |

### 6.2.4 Implementation (code snippet)

```php
// app/Http/Controllers/Api/AssessmentApiController.php

public function stats(Request $request, Assessment $assessment): JsonResponse
{
    $requestId = (string) $request->input('requestId', Str::uuid());

    // IFA: every request must be traceable via timestamp and/or requestId.
    if (! $request->filled('timestamp') && ! $request->filled('requestId')) {
        return response()->json([
            'status' => 'F',
            'requestId' => $requestId,
            'timestamp' => now()->toIso8601String(),
            'message' => 'A timestamp or requestId field is required.',
        ], 422);
    }

    // Access control: mentors may only view statistics for their own
    // assessments; admins may view all.
    $user = Auth::user();

    if ($user instanceof User && $user->isMentor()) {
        $owns = (int) $assessment->lesson->course->mentor_id === (int) $user->id;
        abort_unless($owns, 403, 'You can only view statistics for your own assessments.');
    }

    try {
        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->whereNotNull('submitted_at')
            ->get();

        $total = $attempts->count();

        $data = [
            'assessment_id' => $assessment->id,
            'assessment_title' => $assessment->title,
            'passing_score' => $assessment->passing_score,
            'question_count' => $assessment->questions()->count(),
            'attempt_count' => $total,
            'average_score' => $total > 0 ? round((float) $attempts->avg('percentage'), 2) : null,
            'pass_rate' => $total > 0 ? round($attempts->where('is_passed', true)->count() / $total * 100, 2) : null,
        ];

        return response()->json([
            'status' => 'S',
            'requestId' => $requestId,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ]);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json([
            'status' => 'E',
            'requestId' => $requestId,
            'timestamp' => now()->toIso8601String(),
            'message' => 'The assessment statistics are temporarily unavailable.',
        ], 500);
    }
}
```

**Description of the snippet:**
The `stats()` method is the exposed REST endpoint. It first enforces the IFA by
requiring a `timestamp` or `requestId`; without one, it returns `status = F` with
HTTP 422. It then applies access control (a mentor is only allowed to request
statistics for their own assessments). On success it computes the aggregates from
the `assessment_attempts` table through Eloquent ORM and returns `status = S`
together with `requestId`, `timestamp` and the `data` object. If anything fails,
it logs the exception internally and returns a generic `status = E` response so
no internal details leak to the client.

Route registration (`routes/api.php`):

```php
Route::get('/assessments/{assessment}/stats', [AssessmentApiController::class, 'stats'])
    ->middleware(['web', 'auth', 'role:Admin,Mentor', 'throttle:60,1'])
    ->name('api.assessments.stats');
```

---

## 6.3 Service Consumption – Course Catalogue Web Service

This module also **consumes** a web service provided by another team member
(**ZHI FOOK CHUA – Course Management**). The Course module exposes the published
course catalogue; the Assessment & Progress module consumes it on the student
**assessment result page** to display an "Explore More Courses" section, allowing
students to discover and enrol in more courses after completing an assessment.

### 6.3.1 Web Service Mechanism (consumed service)

| Field | Description |
| --- | --- |
| Protocol | RESTFUL |
| Function Description | Retrieves the list of published courses (id, title, slug, description, category, mentor) |
| Source Module | Course Management (ZHI FOOK CHUA) |
| Target Module | Assessment & Progress Management (this module) |
| URL | `GET /api/courses/published` |
| Function Name | `CourseApiClient::publishedCourses()` |

### 6.3.2 IFA – Request Parameter

| Field Name | Field Type | Mandatory / Optional | Description | Format |
| --- | --- | --- | --- | --- |
| `Accept` | Header | Mandatory | Requests a JSON response | `application/json` |
| `timeout` | Integer | Optional | Maximum time to wait for the service (5 s) | milliseconds / seconds |

The course service also follows the same IFA; this module passes a `requestId` and
`timestamp` where applicable and relies on the `status`/`timestamp` fields in the
response for tracking.

### 6.3.3 IFA – Response Parameter

| Field Name | Field Type | Mandatory / Optional | Description | Format |
| --- | --- | --- | --- | --- |
| `status` | String | Mandatory | Status of the request | `S` : Success, `F` : Fail, `E` : Error |
| `requestId` | String | Mandatory | Echo of the request ID | UUID string |
| `timestamp` | String | Mandatory | Time when the response was generated | ISO 8601 |
| `data` | Object (array) | Mandatory | The list of published courses | Each item: `id`, `title`, `slug`, `description`, `thumbnail`, `category`, `mentor` |

### 6.3.4 Implementation (code snippet)

```php
// app/Services/CourseApiClient.php

public function publishedCourses(): array
{
    try {
        $response = Http::timeout(5)
            ->acceptJson()
            ->get(route('api.courses.published'));

        $payload = $response->json();

        if (! $response->ok() || ($payload['status'] ?? '') !== 'S') {
            throw new \RuntimeException('Course API returned a non-success status.');
        }

        return $payload['data'] ?? [];
    } catch (Throwable $exception) {
        Log::channel('assessment')->warning('Course API consumption failed', [
            'timestamp' => now()->toIso8601String(),
            'endpoint' => 'api.courses.published',
            'message' => $exception->getMessage(),
        ]);

        return [];
    }
}
```

Where it is consumed (student result page controller):

```php
// app/Http/Controllers/Student/AssessmentController.php (result)

$courses = app(\App\Services\CourseApiClient::class)->publishedCourses();
// ... passed to the result view which renders the "Explore More Courses" section
```

**Description of the snippet:**
`publishedCourses()` consumes the Course module's REST service over HTTP. It
sends an HTTP `GET` with a 5-second timeout, parses the JSON payload, and only
accepts the result when the HTTP call succeeds **and** the IFA `status` field is
`S`. This demonstrates that the consuming side verifies the IFA status before
trusting the data. If the service is unavailable or returns a failure, the client
logs the incident to the `assessment` log channel and returns an empty array so
the student result page still renders gracefully instead of crashing.

---

## 6.4 Bidirectional Integration Summary

```text
   Assessment & Progress module                    Course Management module
   (this module)                                   (ZHI FOOK CHUA)
   ┌─────────────────────────────┐                 ┌──────────────────────────┐
   │  EXPOSE:                    │                 │                          │
   │  GET /api/assessments/{id}/stats              │                          │
   │  -> consumed by Mentor      │                 │                          │
   │     analytics widget        │                 │                          │
   │                             │                 │  EXPOSE:                 │
   │  CONSUME:                   │◄──── consume ───┤  GET /api/courses/published│
   │  CourseApiClient            │                 │                          │
   └─────────────────────────────┘                 └──────────────────────────┘
```

- **Direction 1 (expose):** this module exposes the assessment statistics service,
  consumed by the Mentor dashboard widget.
- **Direction 2 (consume):** this module consumes the course catalogue service
  exposed by the Course Management module.

Both directions follow the IFA (request `timestamp`/`requestId`; response
`status` + `timestamp`), are rate-limited (`throttle:60,1`) for robustness, and
degrade gracefully when the remote service is unavailable.

---

## 6.5 Testing Evidence

Web-service behaviour is covered by the automated test suite:

```bash
php artisan test --filter=WebServiceTest
```

`tests/Feature/WebServiceTest.php` (5 tests) verifies that the exposed statistics
endpoint enforces the IFA (missing `timestamp`/`requestId` is rejected), returns
`status`/`timestamp` on success, scopes mentors to their own assessments, and
that the `CourseApiClient` parses a valid IFA response and fails gracefully.

Full suite: **229 tests / 844 assertions green**.

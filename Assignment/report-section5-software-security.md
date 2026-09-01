# Section 5 – Software Security

**Module:** Assessment & Progress Management
**Member:** OOI JIAN XIN

---

Software security is a critical concern for the **Assessment & Progress
Management** module because it handles student answers, automatic grading,
assessment statistics and web-service data. This section identifies **two
potential threats** that could affect this module and describes the **secure
coding practice** implemented to prevent or mitigate each one.

Each practice below is **unique to this module** within the team: the other
team members address their own assigned threats (access control, output
encoding, session management and authentication hardening). Input validation
is applied throughout SkillBridge (for example, the Form Requests used by
assessment and question management), but as required, it is **not** counted as
one of the two mitigation strategies described here.

---

## 5.1 Threat 1 – Information Disclosure

### 5.1.1 Threat Description

Information disclosure occurs when a web application returns **internal system
details to the client** in error responses or API payloads. Typical examples
include PHP stack traces, SQL query text, database schema information, class
names, file paths, and session or account identifiers.

For the Assessment & Progress Management module, this is a real risk because the
module exposes a **web service** (assessment statistics) and handles sensitive
data such as question answers, grading results and student performance. If a
request fails — for example, a malformed call to the assessment statistics
service — a poorly configured error handler could return the raw exception to
the browser. An attacker could then use the leaked information (database table
names, query structure, framework paths) to plan further, more targeted attacks
against the application.

### 5.1.2 Secure Coding Practice – Sanitised Error Responses

To prevent information disclosure, the module never returns internal exception
details to the client. Instead, all JSON/API error responses are replaced with a
**generic, safe error payload** that contains only a `status`, a `timestamp` and
a neutral message.

The practice is centralised in `App\Services\SecurityService::safeErrorPayload()`
(`app/Services/SecurityService.php`), which builds a response from a whitelist
of generic messages and deliberately omits stack traces, SQL, class names and
account/session information:

```php
// app/Services/SecurityService.php
public static function safeErrorPayload(Throwable $exception, int $status = 500): array
{
    $messages = [
        403 => 'Forbidden.',
        404 => 'Not found.',
        419 => 'Page expired.',
        500 => 'An unexpected error occurred. Please try again later.',
    ];

    return [
        'status' => 'E',
        'timestamp' => now()->toIso8601String(),
        'message' => $messages[$status] ?? 'An unexpected error occurred. Please try again later.',
    ];
}
```

This helper is wired **globally** in `bootstrap/app.php`, so every JSON/API
error response in the application (4xx and 5xx, except 422 validation errors)
is sanitised — even when the application is running in a debug environment:

```php
// bootstrap/app.php — exception handler
$exceptions->render(function (Throwable $exception, Request $request) {
    // Authentication failures are handled by the framework (401/redirect).
    if ($exception instanceof AuthenticationException) {
        return;
    }

    // Validation failures (422) keep their normal field-error response.
    if ($exception instanceof \Illuminate\Validation\ValidationException) {
        return;
    }

    if ($request->expectsJson() || $request->is('api/*')) {
        $status = $exception instanceof HttpException
            ? $exception->getStatusCode()
            : 500;

        // Sanitise every JSON/API error (4xx and 5xx) so internal
        // details can never leak, even in a debug environment.
        if ($status >= 400 && $status !== 422) {
            return response()->json(
                SecurityService::safeErrorPayload($exception, $status),
                $status
            );
        }
    }
});
```

The web-service controller of this module also applies the same principle in
its own error handling: when the assessment statistics service catches an
unexpected exception, it logs the error internally and returns a generic
`status = E` response instead of the exception details.

**How this mitigates the threat.** Because the client can only ever receive the
generic message ("Forbidden.", "Not found.", "An unexpected error occurred…"),
no internal details are disclosed. Even if an attacker deliberately triggers
errors, the response reveals nothing about the database, the framework or the
server configuration.

> **[Figure 5.1 – Sanitised error response]** Capture a browser/Postman request
> to a protected endpoint without being authorised (e.g. a student calling an
> admin endpoint) showing the JSON response with only `status`, `timestamp` and
> a generic message — no stack trace. Source: Azure. Supports Section 5.1.

> **[Figure 5.2 – SecurityService in IDE]** Capture `app/Services/SecurityService.php`
> showing `safeErrorPayload()` in the editor. Source: VS Code. Supports
> Section 5.1.

---

## 5.2 Threat 2 – Lack of Audit Trail / Non-Repudiation

### 5.2.1 Threat Description

A lack of audit trail (also known as a security logging and monitoring failure)
means that **security-relevant events are not recorded**. In this module, the
most important events are **access-control failures**: attempts by a user to
reach a resource they are not allowed to access, such as a mentor trying to
manage another mentor's assessment, or a student trying to open an assessment of
a course they are not enrolled in.

Without a log of these failures, two problems arise. First, there is no
**evidence** that an attempt occurred, so a user can deny (repudiate) having
tried to access an unauthorised resource. Second, an attacker probing for
weaknesses (for example, trying to manipulate assessment URLs to reach another
course's assessment) would leave **no trace**, making the attack difficult or
impossible to detect. This is recognised in OWASP A09:2021 (Security Logging
and Monitoring Failures).

### 5.2.2 Secure Coding Practice – Logging Access-Control Failures

To provide a reliable audit trail, the module logs **every access-control
failure** to a dedicated `security` log channel. The logging is centralised in
`App\Services\SecurityService::logAccessControlFailure()`
(`app/Services/SecurityService.php`). The record captures only non-sensitive
information — the user id and role, IP address, HTTP method, path and reason —
and deliberately omits session identifiers, password hashes and credentials:

```php
// app/Services/SecurityService.php
public static function logAccessControlFailure(Request $request, string $reason = 'Forbidden'): void
{
    $user = Auth::user();

    Log::channel('security')->warning('Access control failure', [
        'timestamp' => now()->toIso8601String(),
        'user_id' => $user?->getAuthIdentifier(),
        'user_role' => $user instanceof User ? $user->role?->role_name : 'guest',
        'ip_address' => $request->ip(),
        'method' => $request->method(),
        'path' => $request->path(),
        'reason' => $reason,
    ]);
}
```

This helper is wired globally in `bootstrap/app.php`: whenever the application
returns a **403 (Forbidden)** response — for example from the role middleware or
from the module's own access-control guards (`ensureAccessible`,
`ensureEnrolled`) — an audit entry is written automatically:

```php
// bootstrap/app.php — exception handler
$exceptions->render(function (HttpException $exception, Request $request) {
    if ($exception->getStatusCode() === 403) {
        SecurityService::logAccessControlFailure(
            $request,
            $exception->getMessage() ?: 'Forbidden'
        );
    }
});
```

The dedicated `security` log channel is defined in `config/logging.php` and
writes to `storage/logs/security.log`:

```php
// config/logging.php
'security' => [
    'driver' => 'daily',
    'path' => storage_path('logs/security.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => env('LOG_DAILY_DAYS', 14),
    'replace_placeholders' => true,
],
```

In addition, the module records a complementary audit trail for every assessment
submission: the `SubmissionLogger` observer (part of the Observer design pattern
described in Section 4) writes a "Assessment submitted" record — assessment id,
student, score, percentage and pass/fail result — to the `assessment` log
channel whenever a student submits an assessment. This supports non-repudiation
of the grading process itself.

**How this mitigates the threat.** Because every denied access attempt is
logged with the user, role, IP, path and reason, the platform administrator has
a permanent, tamper-evident record of who attempted to access what and when.
This both deters malicious behaviour and provides the evidence needed to
investigate incidents, satisfying the need for an audit trail and
non-repudiation.

> **[Figure 5.3 – Security log evidence]** Capture `storage/logs/security-*.log`
> showing "Access control failure" entries with user id/role, IP, method, path
> and reason. Source: Azure or local storage. Supports Section 5.2.

> **[Figure 5.4 – Assessment submission audit log]** Capture `storage/logs/assessment-*.log`
> showing "Assessment submitted" records written by the `SubmissionLogger`
> observer. Source: Azure or local storage. Supports Section 5.2.

---

## Summary

| Threat | Secure coding practice | Implementation | Evidence |
| --- | --- | --- | --- |
| Information disclosure | Sanitised error responses | `SecurityService::safeErrorPayload()` wired in `bootstrap/app.php` | Fig. 5.1, 5.2 |
| Lack of audit trail / non-repudiation | Logging access-control failures | `SecurityService::logAccessControlFailure()` (+ `SubmissionLogger` audit trail) | Fig. 5.3, 5.4 |

Both practices are implemented within the **Assessment & Progress Management**
module, are distinct from the practices of the other team members, and do not
rely on input validation as a mitigation strategy.

# SkillBridge — Team Access Guide

A short, beginner-friendly guide for team members on how to access and use the
**deployed** SkillBridge system. This guide covers the live website, the
Laravel backend/API, the database, and the source code.

---

## 1. Frontend / Website (the live app)

1. Open **Chrome or Edge**.
2. Go to the live URL:

   ```
   https://skillbridge-cwcwcedpe7cdfqds.malaysiawest-01.azurewebsites.net
   ```

3. You will see the SkillBridge landing page (SDG 4 – Quality Education).
4. Use the **Log in** button to sign in.

### Test accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@skillbridge.com` | `Admin@123` |
| Mentor | `aisha.rahman@demo.com` | `Mentor@123` |
| Mentor | `daniel.lim@demo.com` | `Mentor@123` |
| Mentor | `siti.tan@demo.com` | `Mentor@123` |
| Student | *(register a new account — see step 5)* | — |

> There is no pre-seeded student account. To test as a Student, click
> **Register** on the login page and create an account — new registrations are
> automatically given the Student role.

### What each role can access

- **Student** — browse and enroll in published courses, read lessons, take and
  submit assessments, view results, and check progress.
- **Mentor** — create/manage their own courses and lessons, add assessments and
  questions, view the Assessment Analytics dashboard, and manage enrollments of
  their own courses.
- **Admin** — everything: manage all users, courses, lessons, assessments,
  questions, enrollments, notifications, and view the Reports/Analytics page.

---

## 2. Backend / Laravel & API

The Laravel backend runs on the **same Azure App Service** as the website —
there is no separate backend URL. API endpoints are reached by adding `/api/...`
to the live URL, e.g.:

```
https://skillbridge-cwcwcedpe7cdfqds.malaysiawest-01.azurewebsites.net/api/courses/published
```

### How to test the API

- **Easiest way:** open the endpoint in your browser while logged in (the
  browser sends your login session automatically).
- **Or with Postman:** send a `GET` request to the endpoint URL. For endpoints
  that require login, first log in on the website in your browser — Postman
  testing of protected endpoints is more advanced, so prefer the browser.
- All responses follow the project's **IFA** format: every request must include
  a `timestamp` **or** a `requestId`, and every response includes `status`
  (`S`/`F`/`E`) and `timestamp`.

### Important API endpoints

| Endpoint | What it does | Access |
| --- | --- | --- |
| `GET /api/courses/published` | Returns the list of published courses (catalogue) | Public (needs `?timestamp=…` or `?requestId=…`) |
| `GET /api/assessments/{id}/stats` | Aggregate stats for one assessment (attempts, avg score, pass rate) | Admin / Mentor |
| `GET /api/assessments/course/{course}/stats` | Assessment analytics for a whole course | Admin / Mentor (mentor: own courses only) |
| `GET /api/users/{user}/profile` | Public profile of a user (name, role, status) | Logged-in users |
| `GET /api/enrollments/user/{user}/stats` | A user's enrollment counts (active/completed/dropped) | Admin, or the user themself |
| `POST /api/notifications` | Creates a notification for a user | Admin / Mentor |

Example (public catalogue, copy-pasteable into a browser):

```
https://skillbridge-cwcwcedpe7cdfqds.malaysiawest-01.azurewebsites.net/api/courses/published?timestamp=2026-08-30T00%3A00%3A00%2B08%3A00
```

---

## 3. Database (Azure MySQL)

- The production database is **Azure Database for MySQL**, hosted in Azure —
  it is **not** the same as your local database.
- **Do NOT modify the production database unless the team agrees.** Testing in
  the live app (browsing, taking assessments, sending notifications) is safe —
  those are normal app actions. Directly editing or deleting data is **not**
  normal testing.
- If you genuinely need to inspect the data (read-only), use the **Azure Portal
  → your MySQL server → Query Editor**. (The project does not use phpMyAdmin.)

---

## 4. Source code (GitHub)

- The full source code is on GitHub:
  ```
  https://github.com/Justin050430/SkillBridge
  ```
- To view it, just open the link (browse the repo in your browser).
- To **clone** it locally (if you want to inspect/run code):

  ```bash
  git clone https://github.com/Justin050430/SkillBridge.git
  cd SkillBridge
  ```

### Live system vs local environment — important distinction

| | **Live (deployed)** | **Local (your computer)** |
| --- | --- | --- |
| URL | The Azure URL above | `http://127.0.0.1:8000` (after `php artisan serve`) |
| Database | Azure MySQL (production) | Local database configured on your machine |
| Changes | Deployed from GitHub `main` only | Your own edits, not live until pushed/deployed |
| When to use | Team demos, screenshots, final testing | Development, trying out code changes |

> Rule of thumb: **local changes do not affect the live site** until they are
> committed, pushed to GitHub `main`, and deployed by GitHub Actions. Only the
> team lead should trigger deployments.

---

## 5. Important warnings

**Safe for normal testing (won't damage production):**
- Browsing courses/lessons/assessments, enrolling a test student, taking and
  submitting an assessment, viewing results/progress, sending notifications,
  marking enrollments completed/dropped as Admin/Mentor.

**NOT safe — never do these against production unless the team agrees:**
- Running `php artisan migrate` / `php artisan migrate:fresh` / `php artisan db:seed`
  pointed at the production database.
- Deleting or editing rows directly in the Azure MySQL database.
- Re-running deployment commands or resetting the app service.

If in doubt, ask the team lead before touching anything that changes data or
configuration.

---

*Questions? Ask the team lead before making any changes to shared resources.*

# SkillBridge - E-Learning Management System

SkillBridge is a Laravel 12 learning management system (LMS) that connects learners with mentors through structured courses, interactive assessments and measurable progress. Built for the BMIT3173 Integrative Programming assignment, aligned with **SDG 4: Quality Education** - making quality education accessible to underserved communities.

## Features

- Role-based dashboards for **Admin**, **Mentor** and **Student** (a single sign-in dispatches by role).
- **User management**: admin CRUD, status-based soft deletion (Active / Inactive / Deleted), role assignment.
- **Course management**: mentors create and manage their own courses (Builder pattern); admins have full oversight; slug URLs.
- **Lesson management**: lessons nested under courses, ordered, published/draft.
- **Assessment & Question management**: MCQ / True-False / Short-Answer, auto-grading, publication rules (a published assessment must have a published lesson and at least one question).
- **Student learning flow**: browse and enrol in published courses, take auto-graded assessments, view results, track progress.
- **Enrollment management** (Strategy pattern): self-enrol, mark complete, drop.
- **Notifications** (Command pattern): role-specific inbox - enrollment confirmations, assessment results, mentor submission alerts, security alerts.
- **Secure coding**: sanitised error responses, access-control logging, central input validation, CSRF, hashed passwords, HTTPS enforcement.

## Technology Stack

- Laravel 12 (PHP 8.2)
- MySQL (production: Azure Database for MySQL) / SQLite (tests)
- Blade + Tailwind CSS + Alpine.js (Vite)

## Architecture (MVC)

Controllers (`app/Http/Controllers`) -> Models (`app/Models`) -> Views (`resources/views`). Eloquent ORM handles all persistence; Form Requests centralise validation.

## Design Patterns

| Pattern | Location | Used in |
| --- | --- | --- |
| Builder | `app/Builders/CourseBuilder.php` | Course creation |
| Factory Method | `app/Factories/UserAccountFactory.php` | Account creation |
| Strategy | `app/Patterns/Strategy/` | Enrollment lifecycle |
| Command | `app/Patterns/Command/` | Notification dispatch |
| Observer | `app/Patterns/Observer/` | Assessment submission side-effects |

## Secure Coding

- **Information-disclosure prevention**: sanitised API error responses (`SecurityService`).
- **Audit trail**: access-control failures are logged to a dedicated security channel.
- **Input validation**: central `ValidationRules` + Form Requests across every form.
- **Auth hardening**: CSRF, `Password::defaults()`, role middleware, HTTPS in production.

## Web Services

- **Expose**: `GET /api/courses/published`, `GET /api/assessments/{assessment}/stats` (IFA-compliant: `status` / `timestamp` / `requestId`).
- **Consume**: the student result page consumes the course catalogue service (`CourseApiClient`).

## Local Setup

```bash
git clone <repo-url> && cd SkillBridge
composer install
npm install && npm run build
cp .env.example .env      # configure your database
php artisan key:generate
php artisan migrate --seed # roles, categories, admin account + demo data
php artisan serve
```

## Testing

```bash
php artisan test
```

## Azure Deployment

- GitHub Actions (`.github/workflows/main_skillbridge.yml`) builds PHP + frontend assets and deploys to Azure App Service.
- Production database: Azure Database for MySQL (configured via App Service Application settings).
- Live: https://skillbridge-cwcwcedpe7cdfqds.malaysiawest-01.azurewebsites.net

## Email (Password Reset)

SkillBridge uses Laravel's built-in password-reset flow (`Password::sendResetLink` / `Password::reset`) with a SkillBridge-branded `ResetPassword` notification (`app/Notifications/ResetPassword.php`). Reset tokens are stored in the `password_reset_tokens` table, expire after 60 minutes, are single-use, and the new password is hashed with Laravel's default mechanism.

### Production (Azure App Service)

Set these **Environment Variables** in the Azure portal (Configuration → Application settings). Credentials are stored only in Azure, never in source code/GitHub:

| Name | Value |
| --- | --- |
| `APP_URL` | `https://skillbridge-cwcwcedpe7cdfqds.malaysiawest-01.azurewebsites.net` |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.sendgrid.net` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `apikey` |
| `MAIL_PASSWORD` | your SendGrid API key (secret) |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_FROM_ADDRESS` | a sender email verified with SendGrid |
| `MAIL_FROM_NAME` | `SkillBridge` |

`APP_URL` is what makes the generated reset link point to the deployed SkillBridge site.

### Local development

Set the same `MAIL_*` values in your local `.env` (with a real SMTP provider to receive real emails, or `MAIL_MAILER=log` to write the message to `storage/logs/laravel.log`). Use `php artisan config:clear` after changing `.env`.

### SendGrid setup
1. Create a SendGrid account and verify a **sender** (single sender verification or your own domain).
2. Create an API key with "Mail Send" permission (this is `MAIL_PASSWORD`).
3. Use `smtp.sendgrid.net:587`, username `apikey`, encryption `tls`.
4. Sender authentication must be verified or SendGrid will reject/flag the mail.

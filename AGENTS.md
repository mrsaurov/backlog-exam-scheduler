# AGENTS.md

This file provides guidance to coding agents when working with code in this repository.

It describes the current local working tree. Local UI and mail changes are not automatically deployed to cPanel; verify the deployed revision before assuming production has the same behavior.

## What this is

A Laravel 9 app for the CSE department at RUET. Students register for a backlog exam (up to five courses) and download a PDF application. Admins verify registrations, generate a clash-free exam schedule, post notices, assign teachers to courses and email those teachers. Everything is server-rendered Blade; there is no SPA and no external API consumer.

## Commands

```bash
composer install
php artisan migrate                           # local development database only
php artisan serve                              # http://127.0.0.1:8000
php artisan test                               # all tests
php artisan test --filter=ExampleTest          # one test class
php artisan test tests/Feature/ExampleTest.php # one file
./vendor/bin/pint                              # code style (default Laravel preset, no pint.json)
```

There is no front-end build step. Views load Bootstrap 5.3, Bootstrap Icons and the font from CDNs, and `public/css/app.css` and `public/js/app.js` directly. Vite (`npm run dev` / `npm run build`) is scaffolded but no view uses its output.

Setup facts the README gets wrong: there is no `.env.example`, no `npm run prod`, and `DatabaseSeeder` is empty. For a new local setup, create `.env` by hand, create a SQLite database file, set `DB_CONNECTION=sqlite` and an absolute `DB_DATABASE` path, and run `php artisan key:generate`. Preserve an existing environment file and key. An admin is a row in `users`; `HomeController@login` compares the password as plain text.

## Environment cautions

- **Feature tests use the configured database.** `phpunit.xml` has the in-memory SQLite override commented out. Without a separate testing environment or environment overrides, the feature test queries the database configured in `.env`. Point tests at a disposable database before running them. Only the two default example tests exist; the feature example requests `/` and queries exams and notices.
- **Mail can reach real people.** The local database may hold real teacher addresses and `.env` may hold working SMTP settings. To exercise mail, run against a copy of the SQLite file with the log mailer; real environment variables override `.env`:
  `DB_CONNECTION=sqlite DB_DATABASE=/path/to/copy.sqlite MAIL_MAILER=log php artisan serve --port=8001 --no-reload`
- **Newer PHP can print deprecation notices.** Laravel 9 predates PHP 8.4+. Keep diagnostics out of HTTP response bodies, especially PDF downloads; log them instead. Do not strip arbitrary bytes from a generated PDF to hide notices.
- **Production is not like local.** It uses cPanel PHP 8.3 and MySQL; migrations must work on MySQL as well as local SQLite. See [docs/CPANEL-DEPLOYMENT.md](docs/CPANEL-DEPLOYMENT.md) for deployment and recovery instructions.

## cPanel deployment

Paths below are relative to `/home/servicescserueta/`:

| Directory | Purpose |
| --- | --- |
| `repositories/backlog-exam-scheduler/` | cPanel-managed Git checkout used to fetch code; the website does not run from here. |
| `apps/backlog-scheduler/` | Deployed Laravel application, production `.env`, `vendor`, storage and notice uploads. |
| `public_html/` | Domain document root: customized `index.php`, `.htaccess`, public assets and existing separate websites. |
| `backups/backlog-reorganization-2026-10-06/` | Archived original deployment and entry-point backups. |
| `maintenance/` | Private reorganization script, logs and state. |

- Deployment currently uses manual code copying. This repository's `.cpanel.yml` clears the configuration cache, runs `migrate --force --no-interaction` against the deployed app, then clears route and view caches. It does not copy code, install dependencies or publish assets. Commit and publish this configuration before using cPanel's Deploy button; copy the release, including its migrations, into the app directory first. Full release automation still requires a deployment script. See the deployment document for the staging clone's existing local configuration change.
- Preserve the production `.env`, `storage`, `public/uploads` and existing dependencies unless deliberately updating them. Back up the production MySQL database before clicking Deploy HEAD Commit, because the new configuration applies pending migrations. A legacy SQLite file is not a production database backup.
- Publish public CSS, JavaScript, images and compiled assets into the matching `public_html` directories. The layout uses `public_path()` to version CSS and JavaScript, so update their copies in the deployed app's `public/` directory as well.
- Preserve `public_html/index.php`: it loads Laravel from `../apps/backlog-scheduler/`. The repository's default `public/index.php` has different paths and must not overwrite it. Preserve cPanel's PHP handler in `.htaccess`.
- Keep application code, `.env`, `vendor`, storage, Git metadata and backups outside the web root. Notice uploads stay at `apps/backlog-scheduler/public/uploads/notices` and are served by `/notice-file/{noticeid}`; do not publish them directly or symlink the whole app's `public/` directory into the web root.
- Do not replace all of `public_html`; preserve its `alumni/` and `remuneration.services.cse.ruet.ac.bd/` directories and hosting-managed account folders.
- `scripts/cpanel-reorganize.php` is a one-time layout migration, not a normal release deployment script.

## Architecture

Application routes are in `routes/web.php`, including the internal teacher JSON endpoint. `routes/api.php` retains the scaffolded Sanctum-protected `/api/user` route. Admin routes carry the `adminlogin` middleware (`AdminLoginCheck`), which only checks `session('name')`; app admin authentication does not use Laravel guards or policies. Registration, PDF download by exam and roll (`/download/{examid}/{roll}`), exam notices and `/notice-file/{noticeid}` are public.

Controllers are large and hold the business logic directly:

- `HomeController`: public pages, registration, PDF, login, the admin exam list, exam create/update/delete.
- `AdminController`: students (verify, edit, delete), courses, schedule and its CSV exports, notices.
- `TeacherController`: teachers and course assignments (assignments are saved by AJAX to `/teachers/assign-teacher`). `TeacherControllerOld` and `TeacherControllerNew` are unused.
- `MailController`: ready-made templates (defined in code), saved templates, sending, the mail log and resend.

The CRUD POST endpoints `/exams`, `/course`, `/notices`, `/teachers` and `/mail` dispatch on the `submit` field (`create`, `update` or `delete`). Student edits/deletes, teacher assignments, mail sends and resends use separate endpoints.

### Data model quirks

- `registered_students` stores course IDs in five nullable columns, `course1` to `course5`. There is no pivot table, so every "which courses" or "which students" computation loops over the five columns.
- Column naming is inconsistent. Older tables use `examid` / `courseid` with no foreign keys (`registered_students`, `course_exam_mappings`); newer ones use `exam_id` / `course_id` / `teacher_id` with foreign keys (`notices`, `course_teacher_assignments`, `mail_templates`, `mail_logs`).
- `course_exam_mappings` is the list of courses offered in an exam. It is deleted and rewritten whenever an exam is saved.
- A registration can point at a course that is no longer in the exam's mapping (unticked later), or at a course ID that no longer exists. Pages that list registrations must tolerate both: `AdminController::students` labels them `removed` / `deleted` in `course_states`, and `courseupdate` refuses to delete a course that any registration still uses. Never index a course map with a registered course ID without a fallback.
- `course_teacher_assignments` allows two teachers per course per exam. There is no position column: "Teacher 1" is the row with the lowest `id`.
- `teachers` are global, not per exam.
- `available_exams.deadline` is a date compared with `date('Y-m-d')`; registration is open through the deadline day. The home page lists exams whose deadline is in the future or within the last three months.

### Rules that recur across controllers

- **Course parity.** The first numeric sequence in `course_code` decides the kind of course: odd is theory, even is sessional. Scheduling and the clash graph use only odd-numbered courses; the Mail page's "Theory only" / "Sessional only" shortcuts use the same rule in JavaScript.
- **Active courses.** Teachers and Mail pages only deal with courses that at least one verified student registered for in that exam. This logic is copied in `TeacherController::index` and in `MailController` (`activeCourseIds()` plus older inline copies).
- **Schedule.** `AdminController::schedule` builds a graph where two courses are linked when one verified student registered for both, then colours it greedily in order of first appearance; a colour is an exam day. `exportScheduleCSV` repeats the colouring; `exportDependenciesCSV` repeats graph construction; `exportCoursesCSV` repeats course selection and counts. Review all four when changing these shared rules. The schedule page's course-count table includes both theory and sessional courses, while the course CSV includes only theory courses. The README's description (degree ordering, time slots) is not what the code does.
- **Mail.** `ct_marks` and `sessional_marks` go to Teacher 1 only; `question_manuscript` and `answer_script` go to both teachers. Placeholders are `[Teacher's Name]`, `[Exam Name]`, `[Course List]` and `[Deadline Date]`; supported substitutions vary by send path. The message is inserted into `emails/exam-notification` as raw HTML. Sending is synchronous within the request. Each recipient send goes through `MailController::sendAndLog`, which attempts to record a `mail_logs` row with status `sent` or `failed`; logging failures are caught separately and do not change the send result. A failed-mail resend reuses the stored recipient, subject and body, creates a new attempt linked by `resent_from_id`, and omits attachments because their files are not retained. Saved templates (`mail_templates`) can be previewed, and general ones can be loaded into the general mail dialog, but saved customized templates have no send path; customized sending uses the predefined templates.

### Files

Notice attachments are written to `public/uploads/notices` via `public_path()` (git-ignored) and served through `/notice-file/{noticeid}`. Mail attachments are temporary files under `storage/app/mail_attachments`, deleted after sending. The application PDF is `resources/views/application.blade.php` rendered by dompdf: it is a standalone document with limited CSS support and is not part of the site layout.

## UI conventions

- **Layouts.** Browser pages extend `layouts.master` (site chrome, shared dialog, CSS and JS) or `layouts.exam` (the admin workspace for one exam: exam header plus tabs). Email and PDF templates are standalone. A workspace page sets `@section('tab', 'students|schedule|notices|teachers|mail|settings')` and puts its content in `@section('exam-content')`. The layout reads `$exam` after the child view has run, so never reuse `$exam` as a loop variable in those views.
- **Theme.** `public/css/app.css` defines the components: `panel`, `panel-flush`, `panel-head`, `table-clean` (add `table-wide` for many columns), `toolbar`, `segmented`, `status-*`, `tag`, `sticky-bar`, `empty-state`, and the buttons `btn-primary`, `btn-quiet`, `btn-danger-quiet`, `btn-icon`. Navy is the only accent; green, amber and red are reserved for status labels and destructive confirmations. Reuse these rather than Bootstrap's coloured button and badge variants.
- **Dialogs.** `public/js/app.js` provides `confirmAction()`, `showNotice()` and `data-confirm` attributes for forms and buttons. Do not use `window.confirm` or `alert`.
- **Scripted submits.** Most forms contain a field named `submit`, which hides the form's own `submit()` method. Use `submitForm(form)` from `app.js`.
- **Flash messages.** PHPFlasher runs with `flash_bag` enabled, so `redirect()->with('success' | 'error' | 'warning' | 'info', ...)` is turned into a toast and removed from the session. A view cannot read `session('success')`. For data a view must render after a redirect, flash under a different key (the home page uses `application`).
- **Blade `@json`.** In Laravel 9 it breaks on expressions containing commas. Assign the value to a variable in `@php` first.
- **Page scripts.** Put them in `@section('scripts')`; it is rendered at the end of the body after jQuery, the Bootstrap bundle and `app.js`.

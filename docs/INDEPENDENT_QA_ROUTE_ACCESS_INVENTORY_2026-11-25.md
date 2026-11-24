# Route & Access Surface Inventory — Agent 2

- Repository: `zane0184/cbt_november`
- Audited source commit: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Audit branch: `audit/independent-qa-admin-guru-siswa-20261125`
- Date: 2026-11-25
- Scope: static route inventory only. This document does not claim runtime authorization tests passed.

## 1. Web route map

Source: `backend-laravel/routes/web.php` (blob `74b9c6ce75be962ccf7bd858943fb8e4edda1e1b`), `routes/teacher.php` (blob `d778f129240bb0d2edb4f5bfed992ce14ce1b653`).

### Public/auth/installer
- `/`: redirects by exactly one local role: ADMIN_MADRASAH, GURU, SISWA.
- `/login`, POST `/login`, POST `/logout`.
- License status/refresh.
- Installer wizard: authorize, requirements, database test/save, migrations run, Admin save, license activate/refresh, complete/finalize.

### Admin UI routes
Group middleware: `auth`, `active.user`, `tenant`, `license`.
- `/admin/dashboard`
- `/admin/school-profile`
- `/admin/branding-assets`
- `/admin/users`
- `/admin/students/import`
- `/admin/rombel-students`
- `/admin/academic`
- `/admin/teacher-assignments`
- `/admin/curriculum`
- `/admin/curriculum/registry`
- `/admin/proctor-assignments`

Static route observation: the route group itself does not specify an Admin role middleware. Authorization must therefore be enforced inside every Livewire component on mount/boot and on every mutation. This is a test target, not yet a confirmed vulnerability.

### Guru UI routes
Source `routes/teacher.php`; middleware:
`auth`, `active.user`, `tenant`, `license`, `EnsureTeacherPortalAccess`.
- `/guru/dashboard`
- `/guru/penugasan`
- `/guru/pembelajaran`
- `/guru/materi`
- `/guru/kurikulum`
- `/guru/profil`
- `/guru/bank-soal`
- `/guru/asesmen`
- Teacher question-media attach/detach/show routes.

Observation: route file imports `App\\Livewire\\Admin\\QuestionBank` and `Admin\\ExamManagement` under the Guru route group. Namespace naming is misleading but not itself a defect; actual component-level authorization and Teacher assignment scoping must be checked.

### Siswa UI routes
Group middleware: `auth`, `active.user`, `tenant`, `license`.
- `/student/exams`
- `/student/exams/{exam}`
- `/student/results/{attempt}`

Portal is implemented through `StudentExamController` + Blade, not a Student Livewire directory.

### Other Web routes requiring authorization audit
- `/exams/{exam}/monitoring`: closure calls `$user->cannot('monitor', $exam)`.
- `/exams/{exam}/results`, `/exams/{exam}/results/{attempt}`.
- Report export routes.
- Analytics dashboard/item analysis routes.

These routes share broad authenticated tenant middleware. Their policies/components must enforce role, teacher assignment coverage, student ownership, and tenant scope.

## 2. API v1 route map

Source: `backend-laravel/routes/api.php` (blob `3312ebd9603e50431faceeec3aaa1a5a847e4cbd`).

Global API v1:
- `GET /api/v1/health`.
- `POST /api/v1/auth/login`.
- Authenticated `/auth/me`, `/auth/logout`.
- Protected route group middleware: `auth:sanctum`, `active.user`, `tenant`, `license`.

Domains discovered:
1. Curriculum references: curricula, phases, grade-levels, elements, materials (read-only legacy API).
2. Question bank: questions CRUD, versions, publish, question-publications, media, question-version media attach/detach, tags.
3. Teacher assignments: teacher-subject-assignments.
4. Assessment & exam: assessments, exams, exam question snapshots, token, lock, close, assign-rombel, publish-results, participants.
5. Monitoring: summary, participants, attempt, events, heartbeat.
6. Student execution: execution, attempt create/show, answer list/update, submit.
7. Scoring: score, recalculate, score read, manual grading, finalize, reopen.
8. Reporting: exam results/summary/questions, PDF/Excel export, rombel/student/subject reports.
9. Analytics: curriculum analytics and exam analytics.
10. Additional curriculum learning outcome/learning path endpoints continue later in the route file and require individual policy review.

Route middleware is broad authenticated tenant context, not a substitute for controller authorization. Every API action must be traced to policy/service checks and tested with unauthorized roles, another tenant, another teacher's assignment, another student's attempt, and malformed IDs.

## 3. Route-level risks / audit targets (not yet FAIL)

| ID | Target | Why it needs deeper proof | Status |
|---|---|---|---|
| ROUTE-01 | Admin Livewire components | Admin route group has no explicit role middleware; verify every component mount/boot and every mutation reauthorizes exact Admin role + permission | NOT VERIFIED |
| ROUTE-02 | Teacher Livewire | EnsureTeacherPortalAccess is present; verify every action rechecks assignment scope, not just role/profile | NOT VERIFIED |
| ROUTE-03 | Student web routes | Generic auth/tenant middleware; verify StudentExamController checks exact SISWA role, assignment, ownership, schedule and result visibility | NOT VERIFIED |
| ROUTE-04 | Exam monitoring web route | Uses ExamPolicy::monitor; confirm Admin and temporary Proktor capabilities map to the intended authority | NOT VERIFIED |
| ROUTE-05 | Reports and analytics web routes | Broad auth/tenant middleware; verify teacher assignment scope and student/attempt ownership at component/controller level | NOT VERIFIED |
| ROUTE-06 | API v1 controllers | Broad auth/tenant/license middleware; inspect each method for explicit authorization and tenant scoping | NOT VERIFIED |
| ROUTE-07 | Admin versus Guru ownership | Exam/question Livewire classes are under Admin namespace but routed inside Guru group; confirm no Admin privilege leakage and no Guru approval dependency | NOT VERIFIED |
| ROUTE-08 | Legacy curriculum write APIs | Source comments say several legacy write routes are disabled/read-only; verify route methods match comments and no alternate write route bypass exists | NOT VERIFIED |

## 4. Next route audit actions

1. Build endpoint-to-policy matrix for every API controller method.
2. Build Livewire component/action authorization matrix for all Admin and Guru components.
3. Verify student controller and result route object ownership.
4. Verify exact official role set and ensure no Operator/Kamad portal route remains active.
5. Test negative authorization cases on disposable DB, including cross-tenant and stale assignment.
6. Verify all monitoring/report/analytics exports are scoped identically to their UI views.

## 5. Safety statement

No application source, route, middleware, migration, CI workflow, production database, VPS, or deployment was changed. This is a documentation-only audit artifact.

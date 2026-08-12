# Phase 2 — Authentication: Closure Report

**Status:** Complete
**Depends on:** Phase 0 (Foundation), Phase 1 (Database — unmodified, only additively extended)
**Governs:** `docs/ctf5.txt`, `docs/ctf9.txt` §5-6, §12, §27-28

---

## 1. Scope Delivered

- User registration, login, logout, current-user resolution
- Native PHP session management (no Redis)
- Authentication middleware (`AuthMiddleware`)
- Role-based authorization middleware (`RoleMiddleware`)
- Argon2id password hashing (bcrypt fallback if Argon2id unavailable)
- Session ID regeneration on login (fixation prevention)
- Secure `HttpOnly`/`SameSite=Lax` cookies (`Secure` in production)
- CSRF protection (synchronizer token pattern) for state-changing
  authenticated requests
- Database-backed login/register rate limiting (per IP and per identifier)
- Account status enforcement (only `active` accounts can authenticate)
- Server-side input validation
- Generic authentication error messages (no user-enumeration)
- Audit logging for all required authentication events

## 2. Database Changes

**Zero changes to any Phase 1 table.** One additive migration only:

```text
0025_create_auth_attempts_table.sql
```

`auth_attempts` supports rate limiting: `purpose` (login/register),
`identifier_hash`, `ip_hash`, `successful`, `created_at`. Only hashed
identifiers are stored — never plaintext usernames/emails/IPs.

Authentication events are written to the **existing** `audit_logs` table
from Phase 1 — no new audit table was introduced.

## 3. New Application Files

```text
app/Infrastructure/
├── Hash.php          — HMAC-based correlation hashing (IP/identifier)
├── Session.php        — native PHP session bootstrap + helpers
├── Csrf.php            — CSRF token issue/verify
└── Validator.php        — server-side input validation

app/Repositories/
├── UserRepository.php          — user CRUD + public-shape serialization
├── AuditLogRepository.php       — writes to Phase 1's audit_logs table
└── AuthAttemptRepository.php     — writes to auth_attempts

app/Services/
├── AuthService.php     — core register/login/logout/currentUser logic
├── RateLimiter.php      — rate-limit policy on top of AuthAttemptRepository
└── AuditLogger.php       — named event constants + audit_logs writer

app/Middleware/
├── AuthMiddleware.php     — requires an authenticated session
├── RoleMiddleware.php      — requires one of a set of allowed roles
└── CsrfMiddleware.php       — requires a valid X-CSRF-Token header

app/Controllers/
└── AuthController.php    — thin HTTP layer over AuthService
```

`routes/api.php` and `public/index.php` were extended (not rewritten) to
wire the four new routes and start sessions for `/api/` requests.

## 4. API Endpoints

| Method | Path | Auth required | CSRF required |
|---|---|---|---|
| POST | `/api/v1/auth/register` | No | No |
| POST | `/api/v1/auth/login` | No | No |
| POST | `/api/v1/auth/logout` | Yes | Yes |
| GET | `/api/v1/auth/me` | Yes | No (read-only) |

Response envelope matches `docs/ctf5.txt` §4 (`{success, data, message}`
/ `{success, error: {code, message}}`) in every case.

## 5. Security Decisions & Rationale

| Decision | Rationale |
|---|---|
| Role/status/user_id never read from request body | `docs/ctf9.txt` §5 — server is always authoritative |
| Registration sets `status='active'` immediately | No email verification pipeline exists in V1 (`docs/ctf9.txt` §6 defers email/password-reset infra); there is no other mechanism to ever reach `active` |
| Login error is always generic `INVALID_CREDENTIALS` | Applies identically to "no such user," "wrong password," and "inactive account" — prevents user enumeration (`docs/ctf9.txt` requirement #16) |
| `currentUser()` re-queries the DB on every call | A status change (e.g. suspension) takes effect immediately rather than trusting a cached session value |
| Session ID regenerated on login, not on every request | Standard fixation-prevention practice; regenerating on every request would break concurrent tabs |
| CSRF applies to logout but not to register/login | Register/login happen pre-session, where CSRF's threat model (an authenticated session being abused) doesn't yet apply; logout is the first state-changing authenticated action in Phase 2 |
| Rate limiting checks IP AND identifier independently | Matches `docs/ctf5.txt` §34 / `ctf7.txt` §3 — IP alone is an unreliable signal (shared campus Wi-Fi, NAT) |
| `RoleMiddleware` has no restricted endpoint to protect yet | No role-gated business functionality exists until later phases; the middleware is implemented and unit-tested now so later phases can compose it without rework |

## 6. Testing

### `php tests/phase2_validate.php` — **33 / 33 passed**

Runs against a real PHP dev server (spawned via `proc_open`) bound to a
dedicated test database, driven over real HTTP with `curl` (cookies,
headers, status codes) — not mocked. Covers every case listed in the
Phase 2 prompt: registration success/duplicate-username/duplicate-email/
invalid-input, password hashing (Argon2id/bcrypt, never plaintext), login
success/failure, session creation and ID rotation, logout + session
destruction, `/auth/me` auth requirement and correct payload,
`password_hash` never leaking in any response, inactive-account
rejection, CSRF enforcement (missing + wrong token), rate limiting
(empirically triggers a real 429), and every required audit event
(`USER_REGISTERED`, `LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOGOUT`,
`RATE_LIMIT_BLOCKED`, `AUTHORIZATION_FAILURE`). Role-based authorization
is verified as a direct unit test of `RoleMiddleware` since no
role-restricted endpoint exists yet.

### `php tests/phase1_validate.php` — **40 / 40 passed** (unchanged, rerun clean)

### `php tests/phase0_validate.php` — **47 / 47 passed**

One assertion (`No auth controller yet`) was retired as intentionally
obsolete now that Phase 2 has legitimately added `AuthController.php` —
same pattern as the Phase 1 migration-file retirement, noted inline in
the script rather than silently deleted.

### PHP syntax sweep

Every `.php` file in the repository — including all new Phase 2 files —
passes `php -l` with zero errors.

### Secrets hygiene

`.env` is not tracked by Git; `.gitignore` still excludes it; no dev
password/secret string appears in any file staged for commit.

## 7. A Real Bug Found & Fixed During Implementation

`UserRepository::findByIdentifier()` originally used the same named PDO
placeholder (`:identifier`) twice in one query
(`WHERE username = :identifier OR email = :identifier`). Under
`PDO::ATTR_EMULATE_PREPARES => false` (which this project uses
deliberately — real prepared statements, not client-side emulation),
MySQL's native protocol does not allow binding one placeholder name to
two positions, and login failed with `SQLSTATE[HY093]`. Caught via
manual smoke-testing before the automated suite was even written; fixed
by using two distinct placeholders (`:identifier1`, `:identifier2`) bound
to the same value.

## 8. Unresolved Issues / Deferred Items

- **No role-restricted business endpoint exists yet** to exercise
  `RoleMiddleware` over real HTTP — it's proven correct via direct unit
  test instead. This will get end-to-end HTTP coverage naturally once
  Phase 4+ adds admin-only endpoints.
- **Password reset remains deferred**, per `docs/ctf9.txt` §6 — not a
  Phase 2 gap, an explicit decision carried forward from Phase 1 planning.
- **Rate limiting is IP+identifier based, not distributed** — fine for a
  single-server V1 deployment; would need reconsideration if the
  platform ever runs behind multiple app servers without a shared store.
- **No CAPTCHA or progressive backoff** — the current rate limiter is a
  flat window/count; escalating delays are a reasonable future
  enhancement but were not required by this phase's scope.
- **CSRF token is not yet required on `register`/`login`** by design
  (see §5) — worth revisiting only if a future phase adds pre-session
  state-changing actions.

None of the above block Phase 3.

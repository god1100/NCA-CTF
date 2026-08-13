# NCA Batch 4 Private CTF Platform

Private, team-based Capture-the-Flag platform built for NCA Batch 4
cybersecurity training students. See `docs/` for the full specification
set (`ctf.txt` through `ctf9.txt`); `docs/ctf9.txt` is authoritative
where documents disagree.

**Current status: Phase 3 — Team Management (complete)**

Phase 0 established the project skeleton. Phase 1 added the database
schema. Phase 2 added authentication. Phase 3 adds team creation,
membership, captaincy, and secure token-based invitations. No challenge
CRUD, flag submission, leaderboard, admin dashboard, Docker
orchestration, or anti-cheat system exists yet — those are Phase 4+.
See `docs/PHASE1_REPORT.md`, `docs/PHASE2_REPORT.md`, and
`docs/PHASE3_REPORT.md` for full closure reports.

---

## 1. Project Description

The platform provides:

- Secure registration/login and role-based access control
- Team creation, invitations, and management
- Web / Pwn / Crypto / General challenge categories
- Server-authoritative flag submission and scoring
- A real-time leaderboard
- An admin control center for competition management
- An evidence-based, human-reviewed anti-cheat/integrity system
- Isolated Docker-based challenge infrastructure

The CTF application and the vulnerable challenge infrastructure are
architecturally separate systems (`docs/ctf6.txt`).

## 2. Current Phase

```text
Phase 0 — Foundation             complete
Phase 1 — Database                complete
Phase 2 — Authentication           complete
Phase 3 — Teams                     complete  ← YOU ARE HERE
Phase 4 — Challenges
Phase 5 — Flag Submission
Phase 6 — Leaderboard
Phase 7 — Admin
Phase 8 — Docker Infrastructure
Phase 9 — Anti-Cheat / Integrity
Phase 10 — Competition Controls
Phase 11 — Security Hardening
Phase 12 — Testing
Phase 13 — Deployment
Phase 14 — Documentation
```

Phase 0 implements only: project structure, a PHP front controller,
a minimal router, environment configuration, a single API health
endpoint, a static landing page, and placeholder directories for
admin/challenges/Docker. See `docs/ctf9.txt` §31 for the authoritative
phase sequencing and `docs/ctf8.txt` §8 for the Phase 0 task list.

## 3. Requirements

- PHP 8.1+ (developed against 8.3) with the `pdo` extension
- Composer (optional in Phase 0 — zero third-party dependencies are
  required; see §4 below)
- MySQL/MariaDB — **not required until Phase 1**

## 4. Local Setup

```bash
git clone <repository-url> nca-ctf
cd nca-ctf
cp .env.example .env
```

No `composer install` is strictly required for Phase 0: the app ships
with a tiny built-in PSR-4 autoloader (`app/Infrastructure/Autoloader.php`)
that is used automatically whenever `vendor/autoload.php` is absent. If
Composer is available in your environment, `composer install` will also
work against the same `psr-4` mapping and takes precedence if present.

## 5. Starting the Development Server

```bash
php -S localhost:8000 -t public
```

Then visit:

- `http://localhost:8000/` — landing page
- `http://localhost:8000/api/v1/health` — API health check

## 6. Project Structure

```text
nca-ctf/
├── app/
│   ├── Controllers/       # HTTP controllers (HealthController only, so far)
│   ├── Models/             # (empty — Phase 1+)
│   ├── Services/           # (empty — Phase 2+)
│   ├── Repositories/       # (empty — Phase 1+)
│   ├── Middleware/         # (empty — Phase 2+)
│   └── Infrastructure/     # Router, Env, JsonResponse, Autoloader
├── config/
│   └── app.php             # App-level config (name, env, debug, url)
├── database/
│   ├── migrations/         # (empty — Phase 1)
│   └── seeders/            # (empty — Phase 1)
├── public/
│   ├── index.php           # Front controller
│   └── assets/
│       ├── css/style.css   # Design tokens + landing page styles
│       └── js/main.js      # Health-check button behavior
├── resources/
│   ├── views/landing.php   # Landing page markup
│   ├── css/                # (reserved for future page-specific styles)
│   └── js/                 # (reserved for future page-specific scripts)
├── routes/
│   └── api.php             # Route table (health endpoint only)
├── admin/index.php         # Admin placeholder (no auth, no functionality)
├── challenges/README.md    # Challenge package layout — placeholder only
├── docker/README.md        # Infra separation doc — no orchestration yet
├── storage/
│   ├── logs/                # App error log target
│   ├── uploads/              # Reserved, outside webroot, non-executable
│   └── framework/
├── tests/
│   └── phase0_validate.php # Structural + smoke validation script
├── docs/                   # ctf.txt – ctf9.txt specifications
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

## 7. API Health Endpoint

```text
GET /api/v1/health
```

Response:

```json
{
    "success": true,
    "data": {
        "status": "ok",
        "phase": 0,
        "phase_label": "Foundation",
        "timestamp": "2026-08-10T12:00:00+00:00"
    },
    "message": "NCA Batch 4 CTF API is running."
}
```

This is the only implemented endpoint. It does not touch the database.

## 7a. Authentication API (Phase 2)

```text
POST /api/v1/auth/register   { username, email, password, full_name? }
POST /api/v1/auth/login      { identifier, password }
POST /api/v1/auth/logout     (requires session + X-CSRF-Token header)
GET  /api/v1/auth/me         (requires session)
```

- Sessions use native PHP sessions with `HttpOnly`, `SameSite=Lax`
  cookies (`Secure` added automatically when `APP_ENV=production`).
- `login` and `me` responses include a `csrf_token` — send it back as
  `X-CSRF-Token` on subsequent state-changing authenticated requests.
- Login failures are intentionally generic (`INVALID_CREDENTIALS`) and
  never reveal whether the identifier exists.
- Repeated failed attempts are rate-limited (429) per IP and per
  identifier — see `AUTH_RATE_LIMIT_MAX_ATTEMPTS` / `_WINDOW_SECONDS`
  in `.env.example`.
- New accounts are always created with role `participant` and status
  `active` server-side — the client cannot set role, status, or user ID.

## 7b. Team Management API (Phase 3)

```text
POST   /api/v1/teams                                { name }
GET    /api/v1/teams/me
GET    /api/v1/teams/me/members
DELETE /api/v1/teams/me/members/{user_id}
POST   /api/v1/teams/me/leave
POST   /api/v1/teams/me/transfer-captain             { user_id }
GET    /api/v1/teams/{id}
POST   /api/v1/teams/me/invitations                  { email }
GET    /api/v1/teams/me/invitations
POST   /api/v1/team-invitations/{token}/accept
POST   /api/v1/team-invitations/{token}/reject
```

- All endpoints require an authenticated session; all state-changing
  endpoints require `X-CSRF-Token`.
- The acting team is always resolved from the authenticated user's
  session-derived membership — never from a client-supplied `team_id`,
  which is what prevents a captain of Team A from touching Team B's data
  via ID manipulation.
- `team_min_size` / `team_max_size` come from the `system_settings` table
  (seeded in Phase 1), not hardcoded — see `docs/PHASE3_REPORT.md` for
  full rule details.
- Invitations are matched to their recipient by comparing the
  authenticated accepting/rejecting user's email against
  `team_invitations.invited_email` — no new database column was needed.
- The invitation token is returned in the `create invitation` response
  body exactly once (no email delivery yet — see `docs/PHASE3_REPORT.md`)
  and stored in the database only as a SHA-256 hash.
- A captain cannot leave a team with other active members without first
  transferring captaincy (`transfer-captain`).

## 8. Development Roadmap

See §2 above and `docs/ctf9.txt` §31 for the authoritative phase list.
Each phase is implemented, tested, and reviewed before the next begins —
no phase auto-expands into the next (`docs/ctf9.txt` §32).

## 9. Security Principles

Carried forward from Phase 0 into every later phase:

- No plaintext secrets in source control (`.env` is gitignored;
  `.env.example` documents keys only)
- No stack traces or internal paths exposed when `APP_DEBUG=false`
- `storage/uploads/` lives outside `public/` and is never directly
  executable or web-servable
- Baseline security headers (`X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, a minimal `Content-Security-Policy`)
  are set on every response
- All future database access must use prepared statements (PDO) —
  no query building from raw input
- The Docker socket and challenge infrastructure will only ever be
  reachable through a dedicated Infrastructure Manager, never directly
  from the participant-facing API (`docs/ctf6.txt` §43)

Full hardening (rate limiting, CSRF, RBAC enforcement, audit logging,
etc.) is Phase 11 and the security work embedded in each functional
phase — not complete or exhaustive here.

## 10. What Is Intentionally NOT Implemented Yet

Phase 0 does not include: authentication, registration, login, teams,
database schema, challenge CRUD, flag submission, leaderboard logic,
admin functionality, Docker challenge orchestration, or the anti-cheat/
integrity engine. The `admin/`, `challenges/`, and `docker/` directories
exist only as structural placeholders with explanatory `README`s — they
contain no working functionality by design.

**Challenge infrastructure in particular is intentionally absent.** No
vulnerable applications, containers, or exploitation targets exist in
this repository yet, and none should be added outside the controlled
Phase 8 process described in `docs/ctf6.txt`.

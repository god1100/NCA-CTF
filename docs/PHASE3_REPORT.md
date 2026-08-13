# Phase 3 — Team Management: Closure Report

**Status:** Complete
**Depends on:** Phase 0 (Foundation), Phase 1 (Database — unmodified),
Phase 2 (Authentication — unmodified, only its `AuditLogger` was
additively extended)
**Governs:** `docs/ctf4.txt`, `docs/ctf5.txt` §17-18, `docs/ctf9.txt`
§5, §7-8, §12-13

---

## 1. Architecture Implemented

Phase 3 follows the exact layered pattern established in Phase 2
(Routes → Middleware → Controllers → Services → Repositories → DB):

```text
app/Controllers/
├── TeamController.php              — /api/v1/teams/*
└── TeamInvitationController.php    — /api/v1/team-invitations/*, invitation sub-routes

app/Services/
├── TeamService.php                 — creation, membership, removal, leaving, captain transfer
└── TeamInvitationService.php       — invitation create/list/accept/reject

app/Repositories/
├── TeamRepository.php
├── TeamMemberRepository.php
├── TeamInvitationRepository.php
└── SystemSettingRepository.php     — reads team_min_size/team_max_size

app/Infrastructure/
├── Str.php     — slug generation (new)
├── Token.php   — secure random token + SHA-256 hashing (new)
└── Router.php  — extended with DELETE/PUT and {param} path segments
```

Controllers stay thin and only wire HTTP ↔ service calls, exactly as
`AuthController` did in Phase 2. All authorization and business-rule
decisions live in the service layer, keyed off the session-authenticated
user — never off any client-supplied ID.

### Router extension

The original Phase 0 `Router` explicitly anticipated this in its own doc
comment ("route parameters... added as later phases need them"). Phase 3
added `put()`/`delete()` and `{param}` path-segment support
(`/teams/me/members/{user_id}`, `/team-invitations/{token}/accept`) by
converting path segments to a capturing regex. Every existing Phase 0-2
route (exact paths, zero parameters) continues to work unchanged — a
literal path is just a pattern with zero captures.

## 2. API Endpoints

| Method | Path | Auth | CSRF | Notes |
|---|---|---|---|---|
| POST | `/api/v1/teams` | Yes | Yes | Creator becomes captain |
| GET | `/api/v1/teams/me` | Yes | No | Returns `{team: null}` cleanly if no team |
| GET | `/api/v1/teams/me/members` | Yes | No | 404 if caller has no team |
| DELETE | `/api/v1/teams/me/members/{user_id}` | Yes | Yes | Captain-only |
| POST | `/api/v1/teams/me/leave` | Yes | Yes | Blocked for captain unless sole member |
| POST | `/api/v1/teams/me/transfer-captain` | Yes | Yes | Captain-only |
| GET | `/api/v1/teams/{id}` | Yes | No | Limited public fields only (name/slug/status) |
| POST | `/api/v1/teams/me/invitations` | Yes | Yes | Captain-only; returns token once |
| GET | `/api/v1/teams/me/invitations` | Yes | No | Captain-only |
| POST | `/api/v1/team-invitations/{token}/accept` | Yes | Yes | |
| POST | `/api/v1/team-invitations/{token}/reject` | Yes | Yes | |

## 3. Team Rules Enforced

All 17 rules listed in the phase brief are implemented:

1-2. **One active team per user** — enforced with a row lock
(`SELECT ... FOR UPDATE`) on the user's active `team_members` row inside
a transaction during both team creation and invitation acceptance.
3. **`team_max_size` capacity** — checked (with a locking count) at both
invitation-creation time and invitation-acceptance time, since capacity
can change between the two.
4-5. **One captain per team** — `is_captain` is only ever set by
`transferCaptain()`, which flips exactly one bit off and one on inside a
single transaction.
6. **Creator becomes captain** — set atomically in the same transaction
as team creation.
7-8. **Captain manages membership / transfers captaincy** — enforced in
`TeamService`, not the controller.
9. **Captain cannot leave without transferring** — `leaveTeam()` checks
active member count; a sole-member captain leaving is still allowed
(there's no one to transfer to — the team simply empties).
10. **Non-captain member may leave normally** — no restriction.
11. **Removing/leaving never touches submissions/solves** — membership
rows transition to `removed`/`left` status; they are never deleted, and
`submissions`/`solves` reference `team_id`/`user_id` directly, not
`team_members` — verified live in the test suite.
12. **Cannot invite self** — compared against the captain's own email.
13. **User in an active team cannot accept another invite** — same
row-locking check as team creation.
14. **Invitations expire** — `expires_at`, opportunistically swept by
`expireOverdue()` before every token lookup.
15. **No invitation reuse** — status transitions are one-way
(`pending` → `accepted`/`declined`/`expired`); the accept/reject path
only proceeds if `status = 'pending'`.
16. **Duplicate pending invitations prevented** — checked per
`(team_id, invited_email)` before creating a new one.
17. **DB uniqueness respected** — team name/slug uniqueness (from Phase
1) is checked before insert and still backstopped by the DB constraint.

## 4. Invitation Flow

```text
Captain → POST /teams/me/invitations {email}
    ↓
TeamInvitationService validates: captain-only, not self, no duplicate
pending invite, team has capacity
    ↓
Token::generate() — 32 random bytes, hex-encoded (256 bits of entropy)
    ↓
Token::hash() — SHA-256 of the token — stored in team_invitations.token_hash
    ↓
Plaintext token returned in the API response body, ONCE
    ↓
Recipient → POST /team-invitations/{token}/accept (authenticated)
    ↓
Server re-hashes the supplied token, looks up + row-locks the invitation
    ↓
Validates: status=pending, not expired, invited_email matches the
authenticated caller's email, caller has no active team, team has capacity
    ↓
Transaction: INSERT team_members (active, non-captain) + UPDATE
team_invitations SET status='accepted', accepted_at=NOW()
```

No email delivery exists in Phase 3 (explicitly out of scope) — the
token is the API response itself. `docs/ctf9.txt` Phase 3 lists this as
acceptable ("invitation creation and token handling are enough").

**Design note on recipient identity:** the Phase 1 `team_invitations`
schema has `invited_email` but no `invited_user_id` column. Rather than
adding one (which the phase brief instructed against unless
"absolutely necessary"), Phase 3 ties an invitation to its recipient by
requiring the *authenticated* accepting/rejecting user's email to match
`invited_email`. This achieves the same security property — only the
intended recipient can act on the invitation — without a schema change.

## 5. Authorization Model

- **Team creation**: any authenticated, active user (regardless of
  global role).
- **View own team / leave / accept / reject invitation**: any active
  member of the relevant team, resolved from session — never from a
  request parameter.
- **Invite / remove member / transfer captaincy**: captain of the
  specific team only (`team_members.is_captain = 1` for that team),
  checked in `TeamService`/`TeamInvitationService`, not by a global role.
- **`challenge_admin`/`super_admin`**: given **no** special team
  permissions in Phase 3, per the phase brief's explicit instruction not
  to create unneeded broad permissions. Team authorization is entirely
  membership-based, not role-based, in this phase.

### IDOR prevention

Every team-scoped action resolves "my team" via
`TeamService::myTeam($user)`, which looks up the caller's own active
`team_members` row and derives the team from it. No endpoint accepts a
`team_id` from the client at all. A captain attempting to remove a user
who is not a member of *their own* team receives `404 NOT_FOUND` (the
lookup is scoped to the caller's team, not a global user search) —
verified live in the test suite by having Team A's captain attempt to
remove Team B's captain.

## 6. Database Changes

**None.** Phase 3 reuses `teams`, `team_members`, `team_invitations`,
`users`, `roles`, `system_settings`, and `audit_logs` exactly as Phase 1
defined them. No new migration was needed or created.

## 7. Security Controls

- Every state-changing endpoint requires both an authenticated session
  and a valid `X-CSRF-Token` (reusing Phase 2's `CsrfMiddleware`
  unchanged).
- All queries use prepared statements; no SQL string concatenation.
- Invitation tokens: 256-bit random, SHA-256 hashed at rest, never
  logged in plaintext (audit metadata stores `invitation_id`, not the
  token).
- Race-condition protection: `SELECT ... FOR UPDATE` row locks inside
  transactions for (a) one-active-team-per-user checks during both team
  creation and invitation acceptance, and (b) team-capacity counts
  during invitation acceptance.
- Client-supplied `team_id`, `user_id` (for authorization purposes),
  `role`, `is_captain`, and `status` are never trusted — every
  authorization decision re-derives state from the database via the
  authenticated session.
- No sensitive fields (`password_hash`, `token_hash`, session internals)
  appear in any Phase 3 API response — verified by scanning actual
  response bodies in the test suite, not just by code inspection.

## 8. Audit Events

All required events are implemented and were confirmed firing in the
live test run: `TEAM_CREATED`, `TEAM_INVITATION_CREATED`,
`TEAM_INVITATION_ACCEPTED`, `TEAM_INVITATION_REJECTED`,
`TEAM_MEMBER_REMOVED`, `TEAM_MEMBER_LEFT`, `CAPTAIN_TRANSFERRED`. All
write to the existing Phase 1 `audit_logs` table with `entity_type='team'`
and the relevant `team_id`; invitation events log `invitation_id`,
never the plaintext token.

`AuditLogger::log()` gained two optional trailing parameters
(`$entityType`, `$entityId`) so Phase 3 could log against `team`
entities instead of `user` entities — existing Phase 2 call sites are
unaffected since the new parameters default to the original behavior.

## 9. Test Results

### `php tests/phase3_validate.php` — **40 / 40 passed**

Boots a real PHP dev server against a dedicated test database and drives
it over real HTTP with `curl` (cookies, headers, status codes) — the
same approach as `phase2_validate.php`. Covers all 30 scenarios listed
in the phase brief, including the full invitation lifecycle, capacity
enforcement (via a temporary `team_max_size` override), the captain-
transfer sequence, the specific IDOR scenario (captain of Team A
attempting to remove a member of Team B), CSRF enforcement, all 7 audit
events, historical-data survival after membership changes, and a
response-body scan confirming no sensitive fields leak. Ends by running
`phase1_validate.php` and `phase2_validate.php` as subprocesses to
directly verify #29-30 (no regression), not just re-implementing
equivalent checks.

### `php tests/phase2_validate.php` — **33 / 33 passed** (unchanged, rerun clean)
### `php tests/phase1_validate.php` — **40 / 40 passed** (unchanged, rerun clean)
### `php tests/phase0_validate.php` — **47 / 47 passed** (unchanged, rerun clean)

### PHP syntax sweep

Every `.php` file in the repository, including all new Phase 3 files,
passes `php -l` with zero errors.

### Secrets hygiene

`.env` is not tracked by Git; `.gitignore` still excludes it; the only
match for a "secret-like" string in a full repo scan is the pre-existing
Phase 2 test fallback literal `test-secret-for-phase2-validation` inside
`tests/phase2_validate.php` — a non-production placeholder already
reviewed and accepted during Phase 2 closure, not a Phase 3 addition.

## 10. A Real Bug Found & Fixed During Implementation

`tests/phase3_validate.php` originally used `putenv('DB_DATABASE=...')`
to point the seed script at the test database, but never cleared that
override afterward. Since the same PHP process later shelled out to run
`phase1_validate.php` and `phase2_validate.php` as regression checks,
those child processes inherited the leftover `DB_DATABASE` value and
computed the wrong test-database name for themselves (appending `_test`
onto an already-`_test`-suffixed name), causing both regression checks
to fail with a connection error even though nothing was actually broken.
Fixed by calling `putenv('DB_DATABASE')` (clearing the override)
immediately after the seed step, before any subprocess call.

## 11. Files Added / Modified

**Added:**
`app/Controllers/TeamController.php`,
`app/Controllers/TeamInvitationController.php`,
`app/Services/TeamService.php`,
`app/Services/TeamInvitationService.php`,
`app/Repositories/TeamRepository.php`,
`app/Repositories/TeamMemberRepository.php`,
`app/Repositories/TeamInvitationRepository.php`,
`app/Repositories/SystemSettingRepository.php`,
`app/Infrastructure/Str.php`,
`app/Infrastructure/Token.php`,
`tests/phase3_validate.php`,
`docs/PHASE3_REPORT.md`

**Modified (additive only):**
`app/Infrastructure/Router.php` (added `put()`/`delete()` + path params),
`app/Services/AuditLogger.php` (added Phase 3 event constants + optional
entity-type override parameters),
`routes/api.php` (registered new routes),
`.env.example` (added `TEAM_INVITATION_TTL_HOURS`),
`README.md` (Phase 3 status + Teams API docs)

**Unchanged:** every Phase 0/1/2 file not listed above, including all
Phase 1 migrations and the entire `AuthService`/`AuthController` core
logic.

## 12. Known Limitations

- **No email delivery** — the invitation token is only ever returned in
  the API response body. This is explicit Phase 3 scope, not an
  oversight.
- **`invited_email` is not required to match an existing user account**
  at invitation-creation time — only at acceptance time does the
  accepting user's email need to match. This matches the schema as
  designed (email-based invites, not account-based) and is a deliberate
  choice to avoid a schema change.
- **No pagination on `GET /teams/me/members` or `GET /teams/me/invitations`**
  — acceptable given `team_max_size` defaults to 4; would need revisiting
  if team sizes grow substantially in a future configuration.
- **`GET /api/v1/teams/{id}` requires authentication** even though it
  only returns non-sensitive fields — kept consistent with "all
  endpoints require authentication" from the phase brief rather than
  carving out a public exception; worth revisiting if a future phase
  wants a public team directory.
- **Invitation TTL (`TEAM_INVITATION_TTL_HOURS`) lives in `.env`, not
  `system_settings`** — unlike `team_min_size`/`team_max_size`, which
  the phase brief explicitly named as settings-table values, there was
  no equivalent existing row for invitation TTL, so it follows the same
  pattern as other Phase 2/3 environment-based configuration
  (`AUTH_RATE_LIMIT_*`) instead.

## 13. Future Improvements (not Phase 3 scope)

- Email delivery for invitations (would use `invited_email` as-is).
- An admin override path for `super_admin` to manage any team
  (explicitly deferred — the phase brief said only implement this "where
  clearly required," and nothing in Phase 3 required it).
- Team-level settings (custom max size per team, etc.) — out of scope
  until a real need is identified.

---

None of the above block Phase 4.

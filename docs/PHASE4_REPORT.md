# Phase 4 — Challenge System: Closure Report

**Status:** Complete
**Depends on:** Phase 0 (Foundation), Phase 1 (Database — unmodified),
Phase 2 (Authentication — unmodified), Phase 3 (Teams — unmodified, not
touched by Phase 4 at all)
**Governs:** `docs/ctf4.txt` §9-15, `docs/ctf5.txt` §19-31, `docs/ctf9.txt`
§14-20

---

## 1. Challenge Architecture

Same layered pattern as every prior phase (Routes → Middleware →
Controllers → Services → Repositories → DB):

```text
app/Controllers/
├── ChallengeController.php       — CRUD, lifecycle, listing/detail, categories
├── ChallengeFileController.php   — upload, list, controlled download, delete
├── ChallengeHintController.php   — CRUD + participant reveal
└── FlagController.php            — admin-only management (create/replace/metadata)

app/Services/
├── ChallengeService.php          — validation, slug generation, lifecycle transitions
├── ChallengeFileService.php      — upload rules, visibility-gated download resolution
├── ChallengeHintService.php      — hint CRUD + reveal gating
└── FlagService.php               — hashing, versioning; NO submission logic

app/Repositories/
├── CategoryRepository.php
├── ChallengeRepository.php       — includes pagination/filtering query builder
├── ChallengeFileRepository.php
├── ChallengeHintRepository.php
└── FlagRepository.php

app/Infrastructure/
└── FileStorage.php               — server-generated paths, traversal-proof resolution
```

Controllers stay thin; every validation, authorization, and visibility
rule lives in the service layer, exactly as established in Phases 2-3.

## 2. API Endpoints

| Method | Path | Auth | Role | Notes |
|---|---|---|---|---|
| GET | `/api/v1/categories` | Yes | Any | Database-driven, no hardcoded IDs |
| GET | `/api/v1/challenges` | Yes | Any | Filter by `category`, `difficulty`, (admin: `status`); paginated |
| GET | `/api/v1/challenges/{identifier}` | Yes | Any | Accepts slug or numeric ID; includes `files[]` + `hints[]` |
| POST | `/api/v1/challenges` | Yes | admin | Always starts `draft`; server sets `author_id` |
| PUT | `/api/v1/challenges/{id}` | Yes | admin | Content only — status is never settable here |
| DELETE | `/api/v1/challenges/{id}` | Yes | admin | Only `draft`/`testing`; published challenges must be archived instead |
| POST | `/api/v1/challenges/{id}/publish` | Yes | admin | `draft/testing/paused` → `published` |
| POST | `/api/v1/challenges/{id}/pause` | Yes | admin | `published/running` → `paused` |
| POST | `/api/v1/challenges/{id}/archive` | Yes | admin | Any non-archived → `archived` |
| POST | `/api/v1/challenges/{id}/files` | Yes | admin | Multipart upload |
| GET | `/api/v1/challenges/{id}/files` | Yes | Any | Metadata only, no storage path |
| GET | `/api/v1/challenge-files/{id}/download` | Yes | Any* | *Non-admins only for visible challenges |
| DELETE | `/api/v1/challenge-files/{id}` | Yes | admin | Deletes DB row + physical file |
| POST | `/api/v1/challenges/{id}/hints` | Yes | admin | |
| GET | `/api/v1/challenges/{id}/hints` | Yes | Any | Participants get unrevealed shape (no `content`) |
| PUT | `/api/v1/challenge-hints/{id}` | Yes | admin | |
| DELETE | `/api/v1/challenge-hints/{id}` | Yes | admin | Soft-delete via `status='inactive'` |
| POST | `/api/v1/challenge-hints/{id}/reveal` | Yes | Any | Returns content; no usage tracking (see §15) |
| POST | `/api/v1/challenges/{id}/flag` | Yes | admin | Rejects if an active flag already exists |
| PUT | `/api/v1/challenges/{id}/flag` | Yes | admin | Deactivates old, creates new version |
| GET | `/api/v1/challenges/{id}/flag` | Yes | admin | Metadata only — **never** `flag_hash** |

`admin` = `challenge_admin` or `super_admin`, enforced via the existing
`RoleMiddleware` from Phase 2 (this is its first real production use
over HTTP — Phase 2 only unit-tested it directly).

## 3. Challenge Lifecycle

Uses the exact enum already defined in the Phase 1 `challenges.status`
column — no new states invented:

```text
draft → testing → published → running → paused → archived
```

Transitions are explicit, whitelisted actions (`publish`/`pause`/
`archive`), each with its own `from` state list checked server-side.
There is no generic "set status to X" endpoint — status can never be
smuggled in through the content-update (`PUT`) path. Invalid transitions
(e.g. archiving an already-archived challenge, publishing something
already published) return `409 INVALID_TRANSITION`.

**Visibility:** `PARTICIPANT_VISIBLE_STATUSES = ['published', 'running']`.
Everything else (`draft`, `testing`, `paused`, `archived`) is invisible
to non-admins in both the listing and the direct detail lookup — a
participant guessing a draft challenge's slug or ID gets an identical
`404` to a genuinely nonexistent one, preventing enumeration.

## 4. Categories

Reused from Phase 1 exactly as seeded (`Web`, `Pwn`, `Crypto`,
`General`). `GET /api/v1/categories` exposes them for frontend filters.
Challenge creation accepts either a category `id` or `slug` in the
request and resolves it against the database — no category ID is ever
hardcoded in application code.

## 5. Difficulty System

`ChallengeRepository::VALID_DIFFICULTIES = ['easy','medium','hard','insane']`
— matches the existing `challenges.difficulty` ENUM exactly. Validated
server-side on every create/update; an invalid value is rejected with
`422` before touching the database.

## 6. File Handling

- Storage root: `storage/uploads/challenges/` — entirely outside
  `public/`, never web-servable directly (unchanged principle from
  Phase 0/1).
- **Every stored filename is server-generated**: 16 random bytes (hex)
  plus a validated extension. The client's original filename is kept
  only as `challenge_files.original_name` metadata for the download's
  `Content-Disposition` header — it never touches a filesystem path.
  This makes path traversal structurally impossible on the write side.
- `FileStorage::resolvedPath()` adds a defense-in-depth check on the
  read side: it resolves the real path and refuses to return anything
  outside the base storage directory, verified directly in the test
  suite against a `../../../../etc/passwd`-style value.
- Extension allowlist (`FileStorage::ALLOWED_EXTENSIONS`) covers common
  CTF file types (archives, source, docs, images, pcaps, binaries) —
  arbitrary extensions are rejected.
- Size limit: `CHALLENGE_FILE_MAX_SIZE_MB` (default 50), configurable
  via `.env`.
- Downloads are streamed through a controlled endpoint
  (`ChallengeFileController::download`) that re-checks challenge
  visibility before serving — a file on a `draft`/`testing` challenge is
  not downloadable by a participant even with a known file ID.

## 7. Hint System

CRUD is admin-only. Reveal is available to any authenticated user on a
visible challenge. **No per-user/team reveal-tracking table exists** in
the current schema — see §15 for why this is a deliberate, documented
gap rather than an oversight. `point_penalty` is preserved from the
Phase 1 schema and returned in both the unrevealed and revealed shapes,
but is not applied to any score anywhere in Phase 4 (no scoring system
exists yet).

## 8. Flag Management

- Plaintext flags exist only transiently inside `FlagService::create()`/
  `replace()` — hashed immediately via SHA-256 of the trimmed value and
  never assigned to a variable that outlives the hashing call, logged,
  or returned.
- `flags.flag_hash` is excluded from **every** response-shaping method,
  including the admin-only metadata endpoint — there is no legitimate
  reason for the API to ever echo a stored hash back, admin or not.
- Replacing a flag deactivates the previous active row and inserts a
  new one with an incremented `version`, preserving history exactly as
  the Phase 1 schema comment anticipated ("flags may have multiple
  historical flag versions but only one active").
- **No submission or comparison logic exists anywhere in Phase 4** — this
  is pure management (create/replace/inspect-metadata). Flag validation
  against a participant's guess is explicitly a later phase's job.

## 9. Authorization

- Global roles unchanged from Phase 2/9: `participant`, `challenge_admin`,
  `super_admin`. No new role introduced; `team_captain` remains a
  team-level flag, not touched by Phase 4 at all.
- All create/edit/lifecycle/file/hint-management/flag endpoints require
  `challenge_admin` or `super_admin` via `RoleMiddleware`.
- Participants can: browse visible challenges, view details (including
  file metadata and unrevealed hint metadata), download authorized
  files, and reveal hints.
- Participants cannot: create, edit, publish/pause/archive/delete
  challenges, manage files or hints, or touch flags in any way — every
  one of these is verified live in the test suite, not just by code
  inspection.

## 10. Security Controls

- All queries use prepared statements; the paginated listing query
  builds its `WHERE` clause from a fixed set of named placeholders, never
  string-interpolated user input.
- Every state-changing endpoint requires both authentication and a valid
  `X-CSRF-Token` (reusing `CsrfMiddleware` unchanged).
- IDOR prevention: draft/testing challenges return an identical `404`
  whether or not the ID/slug actually exists; files on non-visible
  challenges are unreachable even with a correct file ID.
- Client-supplied `status`, `author_id`, `points` bypass, `category_id`
  ownership, and `flag ownership` are never trusted — status changes
  only through the explicit lifecycle actions, `author_id` is always
  the acting admin's own ID, points/difficulty/deployment_type are
  validated against fixed allowlists/ranges.
- Uploaded files are verified as genuine PHP-managed uploads
  (`is_uploaded_file()`) before being moved, as defense in depth beyond
  the normal `$_FILES` flow.

## 11. Audit Events

All 9 events listed in the phase brief are implemented and confirmed
firing live: `CHALLENGE_CREATED`, `CHALLENGE_UPDATED`,
`CHALLENGE_PUBLISHED`, `CHALLENGE_PAUSED`, `CHALLENGE_ARCHIVED`,
`CHALLENGE_FILE_ADDED`, `CHALLENGE_FILE_REMOVED`,
`CHALLENGE_HINT_CREATED`, `CHALLENGE_HINT_UPDATED`,
`CHALLENGE_HINT_REMOVED`, `CHALLENGE_FLAG_CREATED`,
`CHALLENGE_FLAG_UPDATED` (12 total — the brief's list plus
`CHALLENGE_HINT_UPDATED`/`CHALLENGE_DELETED`, added for symmetry with
existing update/remove pairs). All write to the existing Phase 1
`audit_logs` table with `entity_type='challenge'`. No plaintext flag or
file content is ever logged — only IDs and non-sensitive metadata
(titles, filenames, version numbers).

## 12. Database Changes

**None.** Phase 4 reuses `categories`, `challenges`, `challenge_files`,
`challenge_hints`, and `flags` exactly as Phase 1 defined them. No new
migration was needed or created — every field the brief asked about
(`author information`, `published_at`, lifecycle states, deployment
types) already existed in the Phase 1 schema. Fields mentioned only in
older planning docs (`ctf.txt`/`ctf2.txt`) but absent from the actual
Phase 1 migrations — e.g. a distinct `challenge_type` column, `is_featured`,
`archived_at` — were deliberately **not** added, per the phase brief's
explicit instruction to inspect the real schema rather than recreate
older planning documents.

## 13. Test Results

### `php tests/phase4_validate.php` — **52 / 52 passed**

Boots a real PHP dev server against a dedicated test database and drives
it over real HTTP with `curl` — including genuine multipart file uploads
via `curl -F`, not simulated. Covers all 33 scenarios from the phase
brief: full CRUD and lifecycle for both `challenge_admin` and
`super_admin`, participant read-only enforcement, category/difficulty/
points validation, slug-collision auto-deduplication, filtering,
pagination, secure file registration and download (including a direct
unit-level proof that `FileStorage::resolvedPath()` rejects a
`../../../../etc/passwd`-style traversal attempt, and an HTTP-level
proof that a file on a hidden challenge is unreachable), hint CRUD +
reveal with content correctly withheld pre-reveal, flag creation/replace/
versioning with **zero** plaintext or hash leakage scanned across every
captured response body (participant and admin alike), IDOR/authorization
checks, CSRF enforcement, and all 9+ required audit events. Ends by
running `phase3_validate.php` as a subprocess (which itself chains
`phase2` → `phase1`), directly proving the entire stack still works,
not just re-asserting equivalent checks.

### `php tests/phase3_validate.php` — **40 / 40 passed** (unchanged, rerun clean)
### `php tests/phase2_validate.php` — **33 / 33 passed** (unchanged, rerun clean)
### `php tests/phase1_validate.php` — **40 / 40 passed** (unchanged, rerun clean)
### `php tests/phase0_validate.php` — **47 / 47 passed** (unchanged, rerun clean)

### PHP syntax sweep

Every `.php` file in the repository, including all new Phase 4 files,
passes `php -l` with zero errors.

### Secrets hygiene

`.env` is not tracked by Git; the only matches for "secret-like" strings
in a full repo scan are the pre-existing Phase 2/3 test-fallback literals
(`test-secret-for-phase{2,3,4}-validation`) — non-production placeholders
already reviewed and accepted in prior phase closures, extended
consistently for Phase 4's own test harness.

## 14. Files Added / Modified

**Added:**
`app/Controllers/{Challenge,ChallengeFile,ChallengeHint,Flag}Controller.php`,
`app/Services/{Challenge,ChallengeFile,ChallengeHint,Flag}Service.php`,
`app/Repositories/{Category,Challenge,ChallengeFile,ChallengeHint,Flag}Repository.php`,
`app/Infrastructure/FileStorage.php`,
`public/assets/css/challenges.css`, `public/assets/js/challenges.js`,
`resources/views/challenges.php`,
`tests/phase4_validate.php`,
`docs/PHASE4_REPORT.md`

**Modified (additive only):**
`app/Services/AuditLogger.php` (Phase 4 event constants),
`routes/api.php` (new routes registered, existing routes untouched),
`public/index.php` (added a single `/challenges` static-page branch),
`.env.example` (`CHALLENGE_FILE_MAX_SIZE_MB`),
`README.md` (Phase 4 status + API docs)

**Unchanged:** every Phase 0/1/2/3 file not listed above, including all
Phase 1 migrations, `AuthService`/`AuthController`, and the entire team
management subsystem.

## 15. Known Limitations

- **No per-user/team hint-reveal tracking.** The Phase 1 schema has no
  `hint_usage`-style table, and the phase brief explicitly said not to
  add fields "simply because they appear in an older planning document"
  and to defer hint-penalty application to the scoring phase. Phase 4's
  `reveal` therefore just returns content to any authenticated user on a
  visible challenge — it does not remember who revealed what, and no
  penalty is deducted from anything (there is nothing to deduct from
  yet). This is the single largest functional gap Phase 5 will need to
  address once team scores exist.
- **`GET /api/v1/challenges/{identifier}` accepts a bare numeric string as
  an ID.** A challenge with a purely numeric slug is structurally
  impossible to reach by slug this way (it would be interpreted as an
  ID) — extremely unlikely in practice given slugs are generated from
  titles, but worth knowing.
- **Flag creation returns `409 FLAG_EXISTS` rather than silently
  replacing** — a deliberate choice (use `PUT` to replace explicitly) but
  worth documenting since it differs from an upsert.
- **No bulk/batch challenge import** — every challenge is created one at
  a time through the API; a CLI/CSV import tool was out of scope.
- **`CHALLENGE_DELETED` audit event added** beyond the brief's literal
  list, for consistency with the delete endpoint the brief itself
  requested (`DELETE /api/v1/challenges/{id}`) — flagged here rather than
  silently introduced.

## 16. Future Docker Integration Notes

Phase 4 deliberately stops at the data/application layer. When the
Docker phase begins:

- `challenges.deployment_type` (`DOWNLOAD`/`HTTP`/`TCP`) already exists
  and is validated — the Docker phase can key off this value to decide
  whether a challenge needs container orchestration at all.
- `docker_instances` (Phase 1 schema) is untouched by Phase 4 and ready
  to receive rows once an infrastructure manager exists.
- Flag architecture is already version-aware (`flags.version`,
  `flag_type` currently always `'static'`) — the schema does not need to
  change to eventually support `flag_type = 'dynamic'` per-team flags;
  only the flag-issuance logic in a future phase needs to change.
- File storage (`FileStorage`) and challenge management are entirely
  independent of container lifecycle — no coupling to unwind later.

---

None of the above block Phase 5.

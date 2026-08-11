# Phase 1 — Database Foundation: Closure Report

**Status:** Complete
**Depends on:** Phase 0 (Foundation)
**Governs:** `docs/ctf4.txt`, `docs/ctf9.txt` §5-28 (authoritative overrides)

---

## 1. Migration List

24 migrations, applied in filename order, each independently idempotent
(`CREATE TABLE IF NOT EXISTS`):

```text
0001_create_roles_table.sql
0002_create_users_table.sql
0003_create_teams_table.sql
0004_create_team_members_table.sql
0005_create_team_invitations_table.sql
0006_create_categories_table.sql
0007_create_challenges_table.sql
0008_create_challenge_files_table.sql
0009_create_challenge_hints_table.sql
0010_create_flags_table.sql
0011_create_submissions_table.sql
0012_create_solves_table.sql
0013_create_first_bloods_table.sql
0014_create_announcements_table.sql
0015_create_docker_instances_table.sql
0016_create_integrity_events_table.sql
0017_create_integrity_evidence_table.sql
0018_create_integrity_alerts_table.sql
0019_create_risk_scores_table.sql
0020_create_investigations_table.sql
0021_create_disciplinary_actions_table.sql
0022_create_account_relationships_table.sql
0023_create_audit_logs_table.sql
0024_create_system_settings_table.sql
```

A 25th table, `schema_migrations`, is created automatically by
`database/migrate.php` itself (tracking table; not a numbered `.sql`
file, to avoid a chicken-and-egg dependency).

## 2. Database Table List (25 total)

| Domain | Tables |
|---|---|
| Identity | `roles`, `users` |
| Teams | `teams`, `team_members`, `team_invitations` |
| Challenges | `categories`, `challenges`, `challenge_files`, `challenge_hints`, `flags` |
| Scoring | `submissions`, `solves`, `first_bloods`, `announcements` |
| Infrastructure | `docker_instances` |
| Integrity | `integrity_events`, `integrity_evidence`, `integrity_alerts`, `risk_scores`, `investigations`, `disciplinary_actions`, `account_relationships` |
| Administration | `audit_logs`, `system_settings` |
| Tooling | `schema_migrations` |

**No duplicate/competing tables** — verified: no `disqualifications`
table exists (superseded by `disciplinary_actions.action_type =
'disqualification'`, per `docs/ctf9.txt` §8); no `team_captain` global
role exists (superseded by `team_members.is_captain`, per
`docs/ctf9.txt` §5/§7).

## 3. Entity Relationship Summary

```text
roles ──< users ──< team_members >── teams ──< team_invitations
                       │
users ──< submissions >── challenges >── categories
users ──< solves >── challenges
users ──< first_bloods >── challenges
challenges ──< challenge_files
challenges ──< challenge_hints
challenges ──< flags
challenges ──< docker_instances >── teams (nullable)

integrity_events ──< integrity_evidence
integrity_events ──< integrity_alerts >── investigations ──< disciplinary_actions
users ──< account_relationships >── users (self-referencing pair)

users ──< audit_logs
users ──< announcements (created_by)
system_settings (standalone, key/value)
```

## 4. Important Foreign Keys

| Table.column | References | ON DELETE | Rationale |
|---|---|---|---|
| `users.role_id` | `roles.id` | RESTRICT | Can't delete a role still in use |
| `team_members.team_id` / `.user_id` | `teams.id` / `users.id` | CASCADE | Structural join row, not historical scoring data |
| `challenges.category_id` | `categories.id` | RESTRICT | Can't delete a category still in use |
| `challenges.author_id` | `users.id` | SET NULL | Preserve challenge if author account is removed |
| `challenge_files/hints/flags.challenge_id` | `challenges.id` | CASCADE | Dependent supporting data only |
| `submissions.team_id/challenge_id/submitted_by` | — | **RESTRICT** | Submission history must never silently vanish (`docs/ctf4.txt` §29) |
| `solves.team_id/challenge_id` | — | **RESTRICT** | Same — scoring history is protected |
| `first_bloods.challenge_id/team_id` | — | **RESTRICT** | Same |
| `integrity_*` nullable FKs | — | SET NULL | Event/alert/evidence rows must survive even if the related team/user/challenge is later removed |
| `investigations.alert_id` | `integrity_alerts.id` | RESTRICT | An alert under active investigation can't disappear |
| `account_relationships.user_a_id/user_b_id` | `users.id` | CASCADE | Relationship is meaningless without both accounts (both NOT NULL) |

Verified live: a team with existing `solves` **cannot be hard-deleted**
(RESTRICT actually fires) — confirmed in the Phase 1 validation suite.

## 5. Unique Constraints

| Table | Constraint |
|---|---|
| `roles.name` | unique |
| `users.username`, `users.email` | unique |
| `teams.name`, `teams.slug` | unique |
| `team_members(team_id, user_id)` | unique — one membership row per user per team |
| `team_invitations.token_hash` | unique |
| `categories.name`, `categories.slug` | unique |
| `challenges.slug` | unique |
| `solves(team_id, challenge_id)` | unique — **the** duplicate-solve guard |
| `first_bloods.challenge_id` | unique — only one first blood per challenge, ever |
| `account_relationships(user_a_id, user_b_id, relationship_type)` | unique |
| `system_settings.setting_key` | unique |
| `schema_migrations.migration` | unique — migration tracking |

Both `solves` and `first_bloods` uniqueness were verified live: a second
insert with the same key is rejected by MariaDB itself, not by
application logic.

## 6. Important Indexes

Beyond the unique constraints above: `users.role_id/status`,
`challenges.category_id/status/difficulty`, `submissions.team_id
/challenge_id/submitted_by/submitted_at/is_correct`,
`solves.challenge_id/solved_at`, `integrity_events.event_type/team_id
/challenge_id/created_at`, `integrity_alerts.team_id/user_id/severity
/status/created_at`, `audit_logs.user_id/action/created_at`, and a
composite `audit_logs(entity_type, entity_id)` for the polymorphic
reference lookup.

## 7. Seed Data

- **Roles:** `participant`, `challenge_admin`, `super_admin` (exactly the
  three from `docs/ctf9.txt` §5 — `team_captain` deliberately excluded)
- **Categories:** `Web`, `Pwn`, `Crypto`, `General`
- **System settings:** `team_min_size=1`, `team_max_size=4`,
  `competition_status=UPCOMING`, `hint_system_enabled=true`
- **Dev admin account:** optional, created only if `DEV_SEED_ADMIN_USERNAME`,
  `DEV_SEED_ADMIN_EMAIL`, and `DEV_SEED_ADMIN_PASSWORD` are all explicitly
  set in `.env`. No hardcoded password exists anywhere in the seed script.

No real flags, no production secrets, no vulnerable challenge content
seeded — per `docs/ctf9.txt` Phase 1 §33.

## 8. Database Setup Commands (local development)

```sql
CREATE DATABASE nca_ctf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE nca_ctf_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'nca_ctf_app'@'localhost' IDENTIFIED BY 'your-local-password';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
  ON nca_ctf.* TO 'nca_ctf_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
  ON nca_ctf_test.* TO 'nca_ctf_app'@'localhost';
FLUSH PRIVILEGES;
```

Then in `.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nca_ctf
DB_USERNAME=nca_ctf_app
DB_PASSWORD=your-local-password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

> The broader `CREATE/ALTER/DROP` grants above are for local development
> convenience running migrations. A production deployment should use a
> narrower runtime grant (`SELECT/INSERT/UPDATE/DELETE` only) plus a
> separate, more privileged account for running migrations — see
> `docs/ctf4.txt` §39.

## 9. Migration Commands

```bash
# Apply all pending migrations to the configured DB_DATABASE
php database/migrate.php

# Check status without applying anything
php database/migrate.php --status

# Target a different database (e.g. the test database)
php database/migrate.php --database=nca_ctf_test

# Seed core data (roles, categories, system settings)
php database/seed.php
```

## 10. Validation Results

**Phase 1 suite** (`php tests/phase1_validate.php`) — **40 / 40 passed**,
run against a live MariaDB 10.11 instance:
- All 25 tables created, all InnoDB + utf8mb4
- No `password` or `flag` plaintext columns anywhere; `password_hash`
  and `flag_hash` confirmed present
- Seed data counts correct; `team_captain` confirmed absent from roles
- **Functional** (not just structural) checks: duplicate username
  rejected, duplicate `(team_id, challenge_id)` solve rejected, duplicate
  `first_bloods.challenge_id` rejected, FK rejects a solve referencing a
  nonexistent team, and a team with existing solves cannot be
  hard-deleted

**Phase 0 regression suite** (`php tests/phase0_validate.php`) —
**48 / 48 passed**. One assertion (`no database migrations yet`) was
retired as intentionally obsolete now that Phase 1 exists by design —
noted inline in the script rather than silently deleted.

**PHP syntax check** — all `.php` files in the repository, including
every new Phase 1 file, pass `php -l` with zero errors.

**Secrets hygiene** — `.env` is not tracked by Git, `.gitignore`
excludes it, and no dev password string appears in any tracked file.

## 11. Unresolved Issues

- **Migration tooling is intentionally minimal.** `database/migrate.php`
  has no rollback/`down` mechanism — migrations are additive-only for
  Phase 1 (matches `docs/ctf9.txt`'s "reversible where practical" but
  doesn't implement it, since nothing has needed reverting yet). This
  should be revisited if a future phase needs to alter or drop a Phase 1
  table.
- **Single-active-flag enforcement is deferred to Phase 4/5.** MySQL/
  MariaDB has no native partial/conditional unique index, so "only one
  active flag per challenge" is documented as an application-level
  invariant rather than a DB constraint (see comment in
  `0010_create_flags_table.sql`).
- **Production DB grants are narrower than the dev setup above** —
  the dev setup grants `CREATE/ALTER/DROP` for migration convenience;
  a real deployment needs a two-account split (migration account vs.
  runtime account), not implemented as tooling yet.
- **No automated CI** runs these validation suites on every push yet —
  they're currently run manually. Worth revisiting once GitHub Actions
  or similar is in scope.

None of the above block Phase 2.

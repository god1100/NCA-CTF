# Docker Infrastructure — Placeholder

**Status: not implemented.** No orchestration code, Compose files, or
challenge containers exist yet. This directory documents the *planned*
separation only, per `docs/ctf6.txt` and `docs/ctf9.txt` §23-26.

## Planned architecture

```text
CTF Platform
     │
     ▼
Infrastructure Manager   (dedicated service, not the public API)
     │
     ▼
Docker
     │
     ├── Web challenge containers
     ├── Pwn challenge containers
     └── Other challenge services
```

## Non-negotiable boundaries (from docs/ctf6.txt §43-45, ctf9.txt §23)

- The participant-facing API **never** touches the Docker socket directly.
- Only a dedicated Infrastructure Manager component is permitted to talk
  to Docker.
- Challenge containers must not be able to reach:
  - the main CTF database
  - the host filesystem
  - the Docker socket
  - the admin API
  - other teams' containers (unless a specific challenge intentionally
    requires controlled access)
- No `--privileged` containers unless a specific challenge requires it
  and that requirement is documented.

## V1 strategy (docs/ctf9.txt §24-25)

- Shared containers first (not mandatory per-team instances).
- Dynamic/per-team containers are a planned future extension, not a
  Phase 0-8 requirement.

## When this gets built

Docker orchestration begins in **Phase 8**, strictly after Phases 0-7
(foundation through admin) are complete and tested. See `docs/ctf8.txt`
and `docs/ctf9.txt` §31 for the full phase order.

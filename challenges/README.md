# Challenges Directory — Placeholder

This directory will hold the portable challenge packages described in
`docs/ctf6.txt`.

**Status: not implemented.** No challenges — vulnerable or otherwise —
exist in this repository yet. This is intentional per the Phase 0 scope
in `docs/ctf8.txt` §8 and `docs/ctf9.txt` §31.

## Planned structure (future phases)

```text
challenges/
├── web/
│   └── <challenge-slug>/
│       ├── challenge.yaml
│       ├── README.md
│       ├── Dockerfile
│       ├── docker-compose.yml
│       ├── src/
│       ├── files/
│       └── private/
│           ├── solution.md
│           └── author-notes.md
├── pwn/
├── crypto/
└── general/
```

- `download` / `http` / `tcp` deployment types are defined in `docs/ctf6.txt` §6.
- Each challenge is isolated: its own container, its own network, its own
  database if needed — never the main CTF platform database.
- `private/` (solutions, author notes) must never be served to participants.

## When this gets built

Challenge infrastructure begins in **Phase 8 — Docker Challenge
Infrastructure**, only after the core platform (auth, teams, challenge
metadata CRUD, submissions, leaderboard, admin) is working. See
`docs/ctf9.txt` §31 for the full phase order.

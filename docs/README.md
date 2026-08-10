# Planning & Specification Documents

These documents are the complete design record for the NCA Batch 4
Private CTF Platform, in the order they were produced. **`ctf9.txt` is
authoritative** — where any earlier document conflicts with it, `ctf9.txt`
wins. See `ctf9.txt` §1 for the full precedence rule.

| File | Covers |
|---|---|
| [`ctf.txt`](./ctf.txt) | Project overview, objectives, scope, roles, high-level architecture |
| [`ctf2.txt`](./ctf2.txt) | Technical architecture blueprint, database table sketches, page map, dev order |
| [`ctf3.txt`](./ctf3.txt) | UI/UX master specification — design tokens, every page layout, component list |
| [`ctf4.txt`](./ctf4.txt) | Database architecture, ERD, full column definitions, indexes, migration order |
| [`ctf5.txt`](./ctf5.txt) | API & backend architecture — endpoints, auth, RBAC, rate limiting, response format |
| [`ctf6.txt`](./ctf6.txt) | Docker & challenge infrastructure — isolation, networking, deployment types |
| [`ctf7.txt`](./ctf7.txt) | Anti-cheat / integrity system — detection, evidence, risk scoring, investigation workflow |
| [`ctf8.txt`](./ctf8.txt) | Implementation roadmap — phased build order, MonkeyCode/AI-agent workflow |
| [`ctf9.txt`](./ctf9.txt) | **Authoritative decisions** — resolves every conflict between the docs above |

## How to use these when implementing

1. Read `ctf9.txt` first for the final decisions.
2. Use `ctf.txt`–`ctf8.txt` for the detailed reasoning and full context
   behind those decisions.
3. If you find a genuine gap that `ctf9.txt` doesn't resolve, stop and
   raise it rather than guessing — per `ctf9.txt` §38.

See the [repository root README](../README.md) for current build status
and the phase roadmap.

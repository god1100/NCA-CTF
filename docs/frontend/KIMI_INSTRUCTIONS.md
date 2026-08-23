# NCA-CTF Frontend — Kimi Implementation Instructions

## 1. ROLE

You are the frontend engineering agent for the NCA-CTF project.

Your responsibility is to design, implement, test, and refine the participant-facing frontend of the existing NCA-CTF Capture The Flag platform.

You are NOT responsible for replacing or redesigning the existing backend.

The project already contains a working PHP backend covering multiple completed development phases.

Your frontend must integrate with that backend rather than replacing it.

---

# 2. CRITICAL PROJECT RULE

This repository is an existing software project.

Before writing or modifying code:

1. Inspect the repository.
2. Understand the existing backend architecture.
3. Read the provided frontend specification documents.
4. Inspect existing routes/controllers/services/models/repositories.
5. Identify the actual API endpoints already implemented.
6. Identify the actual authentication mechanism.
7. Identify the actual response formats.
8. Identify the existing challenge structure.
9. Identify existing team functionality.
10. Identify anything already implemented for Phase 5.

DO NOT guess the backend architecture.

DO NOT invent API contracts when the existing code can be inspected.

If something is unclear, document the uncertainty instead of silently making architectural assumptions.

---

# 3. EXISTING PROJECT STATUS

The NCA-CTF project is being developed in phases.

Current status:

| Phase | Status |
|---|---|
| Phase 0 — Foundation | COMPLETE |
| Phase 1 — Database | COMPLETE |
| Phase 2 — Authentication | COMPLETE |
| Phase 3 — Teams | COMPLETE |
| Phase 4 — Challenge System | COMPLETE |
| Phase 5 — Flag Submission | IN PROGRESS |
| Phase 6 — Leaderboard | FUTURE |
| Phase 7 — Admin Panel | FUTURE |
| Phase 8 — Docker | FUTURE |
| Phase 9 — Integrity | FUTURE |
| Phase 10 — Competition Controls | FUTURE |
| Phase 11 — Security Hardening | FUTURE |
| Phase 12 — Final Testing | FUTURE |
| Phase 13 — Deployment | FUTURE |
| Phase 14 — Documentation | FUTURE |

Phase 4 has already been validated successfully:

```text
52 passed
0 failed




4. BACKEND MUST REMAIN INTACT

The existing PHP backend is authoritative.

Do not replace it with:

Node.js
Express
Next.js backend
Supabase
Firebase
Appwrite
Prisma
another ORM
another database
another authentication system

The frontend communicates with the existing PHP backend through its HTTP API.

5. FRONTEND LOCATION

The frontend must live in:

frontend/

at the root of the repository.

Expected high-level repository structure:

NCA-CTF/
│
├── app/                    # Existing PHP backend
├── config/                 # Existing configuration
├── database/               # Existing database/migrations
├── routes/                 # Existing backend routes
├── storage/                # Existing backend storage
├── tests/                  # Existing backend tests
│
├── docs/
│   ├── ...
│   └── frontend/
│
└── frontend/               # React frontend

Do NOT place React code inside:

app/
routes/
database/
storage/
tests/

The backend and frontend must remain clearly separated.

6. READ THESE DOCUMENTS FIRST

Before implementation, read:

docs/frontend/FRONTEND_MASTER_SPEC.md
docs/frontend/FRONTEND_ARCHITECTURE.md
docs/frontend/FRONTEND_UI_SPEC.md
docs/frontend/FRONTEND_API_CONTRACT.md
docs/frontend/FRONTEND_COMPONENTS.md
docs/frontend/FRONTEND_ROUTES.md

These documents define the frontend requirements.

If these documents conflict with assumptions you make from generic best practices, follow the project-specific documentation.

7. TECHNOLOGY

Preferred frontend stack:

React
TypeScript
Vite
Tailwind CSS

Use modern React patterns.

Use functional components.

Use TypeScript throughout the application.

Avoid unnecessary dependencies.

Do not introduce a large framework simply because it is convenient.

8. FRONTEND ARCHITECTURE

Maintain this conceptual architecture:

Page
 ↓
Domain Components
 ↓
Hooks / State
 ↓
API Services
 ↓
API Client
 ↓
PHP Backend

Do not allow components to contain scattered API calls.

Do not directly access the database.

9. API BOUNDARY

Frontend responsibilities:

displaying data
collecting user input
client-side validation
navigation
loading states
error states
accessibility
responsive presentation

Backend responsibilities:

authentication
authorization
session handling
team membership
challenge visibility
challenge ownership
flag verification
score calculation
first blood
rate limiting
submission storage
competition rules
security controls

The frontend is never trusted.

10. SECURITY RULE

Never implement security by relying solely on frontend behavior.

For example:

A hidden button does NOT prevent an unauthorized action.

A protected React route does NOT replace backend authorization.

A disabled form does NOT prevent API abuse.

The PHP backend must remain the final authority.

11. DO NOT EXPOSE SECRETS

Never place the following in frontend code:

database passwords
database connection strings
PHP secrets
private keys
signing secrets
flag plaintext
flag hashes
server filesystem paths
internal exception traces

Remember:

Anything bundled into a Vite frontend can potentially be inspected by the user.

12. MOCK DATA

Temporary mock data is allowed while backend phases are incomplete.

Mock data must live under:

src/mocks/

Do not mix mock data into API services.

Do not permanently hard-code fake production data.

The UI should be designed so mock data can later be replaced with real API responses with minimal changes.

13. API DISCOVERY

Before implementing an API service:

Inspect the actual backend.

For each endpoint determine:

HTTP method
path
authentication requirement
request body
query parameters
path parameters
response structure
error structure
HTTP status codes

Document discoveries in:

docs/frontend/FRONTEND_API_CONTRACT.md

Do not invent fields simply because they would be convenient for the frontend.

14. AUTHENTICATION

Inspect the existing authentication implementation before deciding how the frontend should authenticate.

Do not automatically use JWT.

If the existing backend uses PHP sessions/cookies, integrate with that mechanism.

The frontend should provide:

login
registration
session restoration
logout
current-user state
protected routes
authentication loading state

Create an authentication context or equivalent centralized mechanism.

15. COMPONENT DESIGN

Do not create huge components.

For example, avoid:

ChallengeDetailPage.tsx

containing the entire application logic.

Prefer:

ChallengeDetailPage
├── ChallengeHeader
├── ChallengeDescription
├── ChallengeFiles
├── ChallengeHints
└── FlagSubmission

However, also avoid excessive fragmentation.

Do not create a component for every trivial HTML element.

Use practical component boundaries.

16. UI QUALITY

The application should feel like a real cybersecurity competition platform.

It should be:

professional
modern
dark-first
technical
focused
competitive
readable
responsive

Avoid cliché "hacker" aesthetics.

Do not use:

excessive neon
Matrix rain everywhere
hacker hoodie stock imagery
glowing text everywhere
excessive animations
generic AI-dashboard aesthetics

Use subtle cybersecurity visual language instead.

17. RESPONSIVE DESIGN

The application must work on:

desktop
laptop
tablet
mobile

Desktop can use:

sidebar/navigation
multi-column layouts
challenge grids

Mobile should use:

drawer/hamburger navigation
stacked cards
full-width forms
responsive tables
mobile-friendly challenge pages
mobile-friendly flag submission

Do not treat mobile as an afterthought.

18. ACCESSIBILITY

Use:

semantic HTML
labels
keyboard navigation
visible focus states
accessible form errors
appropriate ARIA where needed
sufficient contrast
screen-reader-friendly status messages

Do not communicate state only through color.

For example:

✓ Solved

is preferable to showing only a green dot.

19. LOADING / ERROR / EMPTY STATES

Every API-driven interface should have:

loading state
error state
empty state

Examples:

Loading...
Something went wrong.

[ Try Again ]
No challenges found.

Try changing your filters.

Prefer skeleton states where appropriate.

20. PHASE SCOPE

The current frontend scope is the participant-facing platform.

Build the foundation for:

landing page
authentication
dashboard
challenges
challenge detail
challenge files
challenge hints
flag submission
leaderboard
team
profile
NCA contacts

Do NOT build the following yet:

admin dashboard
admin challenge management
flag management
user administration
cheat detection UI
integrity engine UI
Docker management UI
competition control panel
infrastructure management
challenge container management

Those belong to future phases.

21. NCA CONTACTS

The landing page must contain an NCA Contacts section.

Create a reusable component:

src/components/contacts/NCAContacts.tsx

It should be reusable from:

landing page
footer
future contact page

Do not invent real contact details.

Use clearly replaceable placeholders until official information is provided.

22. FLAG SUBMISSION

Flag submission is an important part of the frontend because Phase 5 is currently being implemented.

The UI must support states such as:

Idle
Submitting
Correct
Incorrect
Already Solved
Rate Limited
Server Error

Example:

Submit Flag

[ flag{................................} ]

[ SUBMIT FLAG ]

Correct:

✓ CORRECT

Challenge solved!

+250 points

First blood if reported by backend:

🏆 FIRST BLOOD

Incorrect:

✕ Incorrect flag

Try again.

Rate limited:

Too many attempts.

Please wait before trying again.

The frontend must NEVER determine whether a flag is correct.

23. ERROR HANDLING

Never display raw backend exceptions to users.

Do not display:

SQL errors
PHP stack traces
filesystem paths
database errors
internal service names
flag hashes

Map backend errors into safe user-facing messages.

24. ROUTING

Keep routing centralized.

Use:

src/routes/

with appropriate protected-route handling.

Public routes:

/
 /login
 /register

Protected routes:

/dashboard
/challenges
/challenges/:slug
/leaderboard
/team
/profile

System routes:

/403
/404
/500

Refer to:

FRONTEND_ROUTES.md

for the complete route specification.

25. DEVELOPMENT PROCESS

Follow this order.

Step 1

Inspect the repository.

Step 2

Read all frontend specification documents.

Step 3

Inspect existing backend APIs.

Step 4

Create frontend project.

Step 5

Create architecture and folders.

Step 6

Create design system.

Step 7

Create application layouts.

Step 8

Create public pages.

Step 9

Create authentication pages.

Step 10

Create protected application shell.

Step 11

Create dashboard.

Step 12

Create challenges interface.

Step 13

Create challenge detail.

Step 14

Create flag submission UI.

Step 15

Create leaderboard foundation.

Step 16

Create team and profile.

Step 17

Create NCA Contacts.

Step 18

Add loading/error/empty states.

Step 19

Connect actual backend APIs.

Step 20

Test and build the application.

26. PLUGINS / TOOLS

If your environment provides useful frontend tools or plugins, use them when they materially improve the implementation.

Examples include:

component/design tools
browser preview
visual inspection
screenshot comparison
accessibility tools
React development tools
testing tools
Git tools
icon libraries
UI component libraries

However:

DO NOT add dependencies simply because they exist.

Every dependency should have a clear reason.

Prefer a small, maintainable dependency set.

27. GIT SAFETY

Before major changes:

Inspect:

git status

Do not delete unrelated user work.

Do not reset the repository.

Do not force-push.

Do not rewrite existing backend history.

Do not overwrite Phase 0–4 implementation.

28. FILE MODIFICATION RULE

Before modifying an existing file:

Read it.
Understand its purpose.
Determine whether the modification is actually required.
Make the smallest appropriate change.

Prefer creating new frontend files over modifying backend files.

29. DOCUMENTATION

When frontend implementation changes significantly, update relevant documentation.

At minimum maintain:

docs/frontend/

Do not create unnecessary documentation files.

30. TESTING

Before declaring frontend work complete:

Run:

npm install
npm run build

and any available tests/linting.

Check:

TypeScript errors
build errors
broken imports
console errors
routing errors
API failures
responsive layout
form validation
accessibility basics
31. FINAL REPORT

At the end of each major implementation stage, report:

Files created

List them.

Files modified

List them.

Backend files modified

Clearly state whether any backend files were changed.

API endpoints discovered

List them.

API endpoints integrated

List them.

Mock data

List what remains mocked.

Remaining dependencies

List what still requires backend implementation.

Tests

Report:

Build:
Tests:
Lint:
Known issues

List anything unresolved.

32. MOST IMPORTANT RULE

When uncertain:

DO NOT GUESS.

Inspect the repository.

Inspect the existing implementation.

Inspect the specification.

If still unclear, stop and explain the ambiguity.

The goal is not merely to produce a visually impressive frontend.

The goal is to produce a maintainable frontend that correctly integrates with the existing NCA-CTF architecture without breaking completed work.

END OF KIMI INSTRUCTIONS

---

## Next file

After you've created that, I'll give you:

**FILE 2 — `FRONTEND_MASTER_SPEC.md`**

That one will define the actual **NCA-CTF product vision and frontend scope**, including the landing page, participant experience, NCA Contacts, competition flow, design direction, and what we want the finished platform to feel like.

We'll keep going **one file at a time**, so the final package is clean and Kimi can consume it without ambiguity.
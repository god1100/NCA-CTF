# NCA-CTF Frontend Architecture

## 1. Purpose

This document defines the technical architecture of the NCA-CTF frontend.

The frontend is a separate React application located inside:

frontend/

The existing PHP backend remains authoritative and MUST NOT be rewritten or modified as part of frontend development.

---

# 2. Technology Stack

Required:

- React
- TypeScript
- Vite
- Tailwind CSS
- React Router

Recommended supporting libraries:

- lucide-react for icons
- clsx for conditional class composition

Do not add large UI frameworks unless explicitly required.

Do not introduce:

- Next.js
- Laravel
- Vue
- Angular
- Redux unless a genuine requirement appears
- JWT authentication

The frontend should remain lightweight.

---

# 3. Application Boundary

The architecture is:

Browser
    ↓
React/Vite Frontend
    ↓
API Service Layer
    ↓
Central API Client
    ↓
PHP Backend
    ↓
Database

The frontend does NOT directly access:

- MySQL
- MariaDB
- PHP repositories
- PHP services
- filesystem storage
- challenge flag storage

---

# 4. Directory Boundary

The frontend lives entirely inside:

frontend/

The frontend MUST NOT modify:

app/
config/
database/
routes/
public/index.php
tests/
composer.json

Existing PHP-rendered views may remain untouched.

The new React application is independent of:

resources/views/

---

# 5. Final Folder Structure

Use this structure:

frontend/
├── public/
│   └── favicon.svg
│
├── src/
│   ├── main.tsx
│   ├── App.tsx
│   │
│   ├── api/
│   │   ├── client.ts
│   │   ├── auth.service.ts
│   │   ├── challenge.service.ts
│   │   ├── team.service.ts
│   │   ├── flag.service.ts
│   │   └── leaderboard.service.ts
│   │
│   ├── config/
│   │   ├── api.config.ts
│   │   └── contacts.config.ts
│   │
│   ├── contexts/
│   │   └── AuthContext.tsx
│   │
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useChallenges.ts
│   │   ├── useChallenge.ts
│   │   ├── useTeam.ts
│   │   ├── useFlagSubmission.ts
│   │   └── useLeaderboard.ts
│   │
│   ├── layouts/
│   │   ├── PublicLayout.tsx
│   │   └── AppLayout.tsx
│   │
│   ├── routes/
│   │   ├── index.tsx
│   │   └── ProtectedRoute.tsx
│   │
│   ├── pages/
│   │   ├── LandingPage.tsx
│   │   ├── LoginPage.tsx
│   │   ├── RegisterPage.tsx
│   │   ├── DashboardPage.tsx
│   │   ├── ChallengesPage.tsx
│   │   ├── ChallengeDetailPage.tsx
│   │   ├── LeaderboardPage.tsx
│   │   ├── TeamPage.tsx
│   │   ├── ProfilePage.tsx
│   │   ├── NotFoundPage.tsx
│   │   └── ForbiddenPage.tsx
│   │
│   ├── components/
│   │   ├── ui/
│   │   ├── layout/
│   │   ├── auth/
│   │   ├── challenges/
│   │   ├── team/
│   │   ├── leaderboard/
│   │   ├── dashboard/
│   │   └── contacts/
│   │
│   ├── mocks/
│   │   ├── data/
│   │   └── services/
│   │
│   ├── types/
│   │   ├── api.ts
│   │   ├── user.ts
│   │   ├── auth.ts
│   │   ├── challenge.ts
│   │   ├── team.ts
│   │   ├── flag.ts
│   │   └── leaderboard.ts
│   │
│   ├── utils/
│   │   ├── formatters.ts
│   │   ├── errors.ts
│   │   └── cn.ts
│   │
│   └── styles/
│       └── globals.css
│
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.js

---

# 6. API CLIENT

All backend communication MUST go through:

src/api/client.ts

Components MUST NOT directly call fetch().

Services MUST use apiClient().

The client is responsible for:

- credentials
- HTTP methods
- JSON serialization
- CSRF header injection
- response parsing
- API envelope handling
- authentication failures
- normalized errors

---

# 7. API BASE PATH

The frontend should use:

/api/v1/

Do not hard-code an absolute production hostname.

During development, Vite proxies:

/api/*

to the PHP backend.

---

# 8. HTTP CLIENT REQUIREMENTS

Use the native Fetch API unless there is a strong reason to introduce Axios.

Every request must use:

credentials: "include"

Example conceptual behavior:

GET:
    credentials included

POST:
    credentials included
    X-CSRF-Token included

PUT:
    credentials included
    X-CSRF-Token included

DELETE:
    credentials included
    X-CSRF-Token included

---

# 9. CSRF ARCHITECTURE

CSRF token is obtained from:

POST /api/v1/auth/login

or:

GET /api/v1/auth/me

The token is held in AuthContext memory.

It MUST NOT be stored in:

- localStorage
- sessionStorage
- cookies created by JavaScript
- URL parameters

The API client obtains the current token through the auth context/service architecture.

Avoid circular dependencies between api/client.ts and AuthContext.tsx.

A clean approach is to provide the API client with a CSRF token getter:

configureApiClient({
    getCsrfToken: () => currentToken
})

---

# 10. API RESPONSE ENVELOPE

Success:

{
    "success": true,
    "data": {},
    "message": "..."
}

Error:

{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "..."
    }
}

The API client should unwrap this into predictable TypeScript results.

Do not force every component to manually inspect:

response.data.success

---

# 11. ERROR HANDLING

Create a normalized ApiError type.

The frontend should distinguish:

- 400 validation
- 401 authentication
- 403 authorization
- 404 not found
- 409 conflict
- 422 validation/business error
- 429 rate limit
- 500 server error
- network failure

User-facing messages must remain safe.

Never display:

- SQL errors
- stack traces
- filesystem paths
- internal exception details

---

# 12. AUTHENTICATION ARCHITECTURE

AuthContext owns:

- current user
- csrf token
- authentication loading state
- authentication status

It exposes:

- login()
- logout()
- refresh()
- user
- isAuthenticated
- isLoading

On initial application startup:

1. Initialize loading state.
2. Request GET /api/v1/auth/me.
3. If successful:
   - store user
   - store csrf token
   - mark authenticated.
4. If 401:
   - mark unauthenticated.
5. Finish loading.

Do not assume the user is logged out merely because the browser was refreshed.

PHP session cookie remains authoritative.

---

# 13. LOGIN FLOW

Login:

POST /api/v1/auth/login

After successful response:

1. store returned user in memory
2. store csrf token in memory
3. navigate to intended protected route or dashboard

Do not store credentials.

Do not store password.

---

# 14. LOGOUT FLOW

Logout:

POST /api/v1/auth/logout

with CSRF token.

After success:

1. clear user
2. clear CSRF token
3. clear temporary auth state
4. redirect to login

---

# 15. SESSION EXPIRATION

If any authenticated API request returns 401:

1. clear auth state
2. clear CSRF token
3. redirect to login
4. preserve intended destination where practical

Avoid redirect loops.

---

# 16. ROUTING

Use React Router.

Public routes:

/
 /login
 /register

Protected routes:

/dashboard
/challenges
/challenges/:identifier
/leaderboard
/team
/profile

Error routes:

/403
/*

---

# 17. PROTECTED ROUTE

ProtectedRoute is only a frontend navigation guard.

It MUST NOT be considered security.

Backend remains authoritative.

If auth state is loading:

show a proper loading/splash state.

If unauthenticated:

redirect to /login.

If authenticated:

render requested route.

---

# 18. PAGE RESPONSIBILITIES

Pages coordinate:

- routing
- page-level data loading
- layout
- domain components

Pages should NOT contain large amounts of:

- API implementation
- reusable UI
- business logic

---

# 19. DOMAIN COMPONENT RESPONSIBILITIES

Challenge components handle challenge presentation.

Team components handle team presentation.

Leaderboard components handle ranking presentation.

Auth components handle login/register forms.

Components should remain reusable and focused.

---

# 20. UI COMPONENTS

Reusable primitives belong in:

src/components/ui/

Minimum set:

- Button
- Input
- Textarea
- Card
- Badge
- Modal
- Spinner
- Skeleton
- Alert
- Toast
- EmptyState
- ErrorState
- Pagination
- Table
- Select

Do not create dozens of unnecessary abstractions.

---

# 21. DESIGN TOKENS

Centralize:

- colors
- spacing
- border radius
- shadows
- typography
- transitions

Do not scatter arbitrary Tailwind values throughout every component.

The visual language should remain consistent.

---

# 22. LAYOUT SYSTEM

PublicLayout:

- Navbar
- main content
- Footer

AppLayout:

- Sidebar
- TopBar
- main content
- MobileDrawer

Authenticated pages should share the same application shell.

---

# 23. CHALLENGE ARCHITECTURE

Challenge service owns:

- categories
- challenge list
- challenge detail
- files
- hints
- hint reveal
- downloads

Components:

ChallengeCard
ChallengeList
ChallengeFilters
ChallengeFiles
ChallengeHints
FlagSubmission

---

# 24. CHALLENGE STATE

The frontend should support:

- published
- running
- paused

Participant-hidden states should never be fabricated.

Backend responses determine visibility.

---

# 25. SOLVED STATE

Phase 4 does not currently provide reliable solved state.

Therefore:

- do not fabricate solved challenges
- do not randomly mark challenges solved
- do not infer solves from localStorage
- do not infer solves from submissions

The UI may contain a solved-state slot.

When Phase 5 provides real solve information, it should populate naturally.

---

# 26. CHALLENGE FILTERING

Backend-supported:

- page
- per_page
- category
- difficulty

Client-side:

- solved filter only when reliable solve information exists
- local presentation filters

Never send unsupported query parameters to the backend.

---

# 27. FILE DOWNLOADS

Challenge file downloads are binary responses.

Do not attempt to parse them as JSON.

Use authenticated fetch/blob handling or another browser-safe mechanism.

Do not expose server filesystem paths.

---

# 28. HINT ARCHITECTURE

Hints are loaded from:

GET /api/v1/challenges/{id}/hints

Reveal:

POST /api/v1/challenge-hints/{id}/reveal

The frontend displays unrevealed/revealed state based on backend data.

The frontend must never decide whether a hint can be revealed.

---

# 29. TEAM ARCHITECTURE

Team service owns:

- current team
- members
- invitations
- creation
- leave
- remove member
- captain transfer

Team components:

TeamInfo
TeamMemberList
TeamInvitations
TeamActions

---

# 30. TEAM AUTHORIZATION

Frontend may hide captain-only controls when:

is_captain === false

However backend authorization remains authoritative.

A malicious user can still call the API manually, therefore backend validation remains mandatory.

---

# 31. INVITATION TOKEN

Team invitation creation may return a token once.

When returned:

- display it clearly
- provide copy button
- warn user that it may only be available once

Do not persist invitation tokens unnecessarily.

---

# 32. PROFILE

Profile is read-only until a backend profile-update endpoint exists.

Do not invent:

PUT /api/v1/auth/me

---

# 33. FLAG SUBMISSION ARCHITECTURE

Create:

src/api/flag.service.ts

and:

src/mocks/services/mockFlag.service.ts

The real service must initially fail clearly with a controlled "Phase 5 API unavailable" state.

Do not put fake correctness logic in production service code.

The mock service is development-only.

FlagSubmission component must support:

idle
submitting
correct
incorrect
already-solved
rate-limited
error

The component should receive a submission function or hook rather than embedding API logic.

---

# 34. MOCK FLAG SUBMISSION

Mock flag submission is allowed only for UI development.

It must be clearly marked as mock behavior in development.

Do not make the mock appear to be real scoring.

When Phase 5 becomes available:

- implement real API service
- switch configuration
- remove mock dependency

No UI redesign should be required.

---

# 35. LEADERBOARD ARCHITECTURE

Create:

src/api/leaderboard.service.ts

and:

src/mocks/services/mockLeaderboard.service.ts

Until Phase 6 exists:

- use mock data
- keep mock isolated
- do not modify backend
- do not invent leaderboard endpoints

Expected future shape:

{
    rank: number,
    team: string,
    score: number,
    solved_count: number,
    last_activity: string
}

---

# 36. DASHBOARD

Dashboard should prefer real information.

Currently available:

- current user
- team information
- challenge categories
- challenge list

Currently unavailable:

- authoritative team score
- authoritative leaderboard position
- solve count

Unavailable metrics should be:

- omitted
- or clearly marked as coming soon

Do not present fake competition statistics as real.

---

# 37. NCA CONTACTS

Store contact data in:

src/config/contacts.config.ts

Do not hard-code contact information into components.

Use placeholders until official information is supplied.

NCAContacts.tsx should consume the configuration.

---

# 38. MOCK CONFIGURATION

Create:

src/config/api.config.ts

Example conceptual configuration:

export const API_CONFIG = {
    useMockFlagSubmission: true,
    useMockLeaderboard: true
};

Do not expose secrets through this file.

This configuration only controls frontend development behavior.

---

# 39. ENVIRONMENT VARIABLES

Only public frontend configuration may use Vite environment variables.

Never put:

- database passwords
- GitHub tokens
- API secrets
- private keys
- backend credentials

into VITE_* variables.

Remember that VITE_* values are exposed to the browser.

---

# 40. STATE MANAGEMENT

Do not introduce Redux.

Use:

- React Context for authentication
- local component state for forms
- custom hooks for domain data
- service layer for API communication

Introduce a larger state library only if future complexity genuinely requires it.

---

# 41. DATA FETCHING

Use custom hooks.

Examples:

useChallenges()
useChallenge(identifier)
useTeam()
useLeaderboard()
useFlagSubmission()

Hooks manage:

- loading
- data
- error
- refresh

Components should remain primarily presentational.

---

# 42. LOADING STATES

Every API-driven page needs an intentional loading state.

Prefer skeletons for:

- challenge cards
- dashboard cards
- team information
- leaderboard rows

Use spinners for:

- button submissions
- small actions

---

# 43. EMPTY STATES

Examples:

No team:

"You are not currently part of a team."

No challenges:

"No challenges are currently available."

No invitations:

"No pending invitations."

No leaderboard:

"Leaderboard will be available when the competition begins."

---

# 44. RESPONSIVENESS

Minimum supported width:

320px.

Test:

- 320px
- 375px
- 768px
- 1024px
- 1280px
- 1440px+

Desktop should use sidebar navigation.

Mobile should use a navigation drawer.

---

# 45. ACCESSIBILITY

All interactive controls require:

- keyboard accessibility
- visible focus
- accessible labels
- semantic elements

Forms require:

- labels
- validation messages
- error associations

Status changes should be accessible to screen readers.

Respect prefers-reduced-motion.

---

# 46. SECURITY BOUNDARIES

The frontend MUST NOT:

- calculate real flag correctness
- store flags
- store flag hashes
- access databases
- bypass authorization
- modify PHP authentication
- modify CSRF implementation
- expose secrets
- trust client-side scores

The backend remains the security boundary.

---

# 47. FRONTEND SECURITY MODEL

Assume the browser is hostile.

Anything in the frontend can be inspected or modified by a participant.

Therefore:

Frontend:
    presentation + user interaction

Backend:
    authentication
    authorization
    validation
    scoring
    flag verification
    competition state
    integrity

---

# 48. DEVELOPMENT PROXY

Vite must proxy:

/api/

to the local PHP backend.

Do not hard-code localhost assumptions into application components.

Put development configuration in vite.config.ts.

---

# 49. PRODUCTION DEPLOYMENT ASSUMPTION

The preferred future architecture is same-origin:

example:

https://ctf.example.com/

frontend:

https://ctf.example.com/

API:

https://ctf.example.com/api/v1/

This minimizes:

- CORS complexity
- cookie problems
- CSRF complications

Do not implement production deployment now.

---

# 50. CODE QUALITY

Use:

- TypeScript strict mode
- meaningful names
- small components
- typed service functions
- centralized API logic
- no duplicated endpoint strings

Avoid:

- any unless absolutely necessary
- giant components
- deeply nested conditionals
- duplicated API logic
- inline business logic

---

# 51. COMMENTS

Comments should explain:

WHY something exists.

Do not write comments that merely restate obvious code.

---

# 52. FRONTEND/BACKEND CONTRACT

If the frontend discovers an unexpected backend response:

Do NOT modify the backend automatically.

Instead:

1. report the mismatch
2. identify the endpoint
3. identify expected vs actual response
4. wait for approval

---

# 53. EXISTING PHP FRONTEND

Existing files:

resources/views/
public/assets/

must remain untouched during initial React frontend implementation.

The React frontend is an additional frontend layer.

Do not delete the existing PHP frontend.

---

# 54. IMPLEMENTATION PHASES

Phase A:
Project initialization

Phase B:
API client

Phase C:
Authentication

Phase D:
Public pages

Phase E:
Application shell

Phase F:
Challenges

Phase G:
Team

Phase H:
Dashboard

Phase I:
Phase 5 submission UI

Phase J:
Leaderboard UI

Phase K:
Responsive/accessibility polish

Phase L:
Build/test

---

# 55. VALIDATION REQUIREMENTS

After implementation:

npm install

npm run build

TypeScript must compile without errors.

There must be no obvious runtime console errors.

Test:

- landing page
- login
- register
- session restoration
- logout
- challenge list
- filters
- challenge detail
- files
- hints
- team
- invitations
- profile
- flag mock
- leaderboard mock
- responsive navigation

---

# 56. GIT SAFETY

Before making frontend changes:

Confirm:

git status

The implementation should only create/modify frontend-related files.

After implementation:

git diff --stat

Verify backend files remain untouched.

Never commit:

.env
tokens
credentials
private keys

---

# 57. IMPLEMENTATION RULE

Kimi MUST implement incrementally.

Do not generate the entire frontend blindly in one step.

After each major stage:

1. implement
2. run build/type checks
3. inspect errors
4. fix
5. continue

---

# 58. FINAL ARCHITECTURAL PRINCIPLE

The architecture must remain:

UI
 ↓
Hooks
 ↓
Domain Services
 ↓
API Client
 ↓
PHP Backend

Never:

UI
 ↓
fetch()
 ↓
random endpoint

and never:

UI
 ↓
database

The frontend must remain maintainable as the NCA-CTF platform progresses through Phases 5–14.

# END OF FRONTEND ARCHITECTURE
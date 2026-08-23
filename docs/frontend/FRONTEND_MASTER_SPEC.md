# NCA-CTF Frontend — Master Product Specification

## Document Status

Status: APPROVED FOR FRONTEND IMPLEMENTATION

Project: NCA-CTF — NCA Batch 4 Private Capture The Flag Platform

Frontend: React + TypeScript + Vite + Tailwind CSS

Backend: Existing custom PHP backend

Current backend completion: Phase 0–4 complete

Current backend development: Phase 5 — Flag Submission

---

# 1. PROJECT OVERVIEW

NCA-CTF is a private Capture The Flag platform intended for NCA Batch 4 participants.

The platform provides a controlled environment where registered participants can:

- create or join teams
- browse available CTF challenges
- inspect challenge descriptions
- download challenge files
- reveal hints
- submit flags
- track solved challenges
- view scores
- view the leaderboard
- manage their team
- manage their profile

The platform will eventually support:

- challenge administration
- Docker-based challenges
- integrity and anti-cheating systems
- competition controls
- security hardening
- deployment infrastructure

Those future systems are outside the current frontend implementation scope.

---

# 2. FRONTEND OBJECTIVE

The objective of the frontend is to transform the existing backend into a polished, modern, professional CTF platform.

The frontend must not feel like:

- a generic CRUD dashboard
- a university assignment
- a default Bootstrap website
- an AI-generated SaaS dashboard
- a generic hacker template

It should feel like a serious private cybersecurity competition platform.

The UI should communicate:

- cybersecurity
- competition
- technical precision
- professionalism
- trust
- speed
- clarity

---

# 3. PRODUCT PHILOSOPHY

The primary principle is:

> The interface should help participants solve challenges, not distract them.

The participant should be able to understand:

1. What challenges are available.
2. Which challenges they have solved.
3. How many points each challenge is worth.
4. What category and difficulty each challenge belongs to.
5. What files are available.
6. What hints are available.
7. Where to submit a flag.
8. Their team's score.
9. Their leaderboard position.

The UI should minimize unnecessary interaction.

---

# 4. DESIGN DIRECTION

## 4.1 Overall Style

Use a modern cybersecurity-inspired dark interface.

The design should be:

- dark-first
- clean
- technical
- restrained
- professional
- high contrast
- information dense without being cluttered

Avoid excessive cyberpunk styling.

---

# 5. VISUAL LANGUAGE

Use subtle cybersecurity visual cues:

- terminal-inspired typography in selected areas
- command-line-inspired labels
- subtle grid patterns
- restrained technical icons
- status indicators
- category badges
- challenge difficulty indicators
- compact metadata rows

Do NOT use:

- Matrix rain backgrounds
- giant glowing text
- excessive neon
- skull graphics everywhere
- hacker silhouettes
- cliché stock photography
- animated binary backgrounds
- excessive scanline effects

Cybersecurity should be communicated through information architecture and subtle styling rather than gimmicks.

---

# 6. COLOR SYSTEM

The exact palette should be implemented as centralized design tokens.

Primary background:

```text
Very dark neutral / near-black


Secondary background:

Dark surface / card background

Primary text:

High contrast light neutral

Secondary text:

Muted neutral

Accent:

Professional NCA-inspired accent

Use semantic colors for:

Success
Warning
Error
Info

Do not hard-code colors throughout components.

Create reusable design tokens.

7. TYPOGRAPHY

Use a modern sans-serif font for the majority of the interface.

A monospace font may be used selectively for:

flags
code
technical identifiers
challenge IDs
terminal-like elements

Do not make the entire website monospace.

Typography must prioritize readability.

8. LAYOUT

The application should use two major layout modes.

Public Layout

Used for:

/
 /login
 /register

Features:

responsive navigation
NCA branding
call-to-action
footer
contact section
Participant Application Layout

Used for authenticated pages.

Desktop:

┌───────────────────────────────────────────────┐
│ Top bar / user information                    │
├──────────────┬────────────────────────────────┤
│ Sidebar      │                                │
│              │ Main content                   │
│ Dashboard    │                                │
│ Challenges   │                                │
│ Leaderboard  │                                │
│ Team         │                                │
│ Profile      │                                │
│              │                                │
│ Logout       │                                │
└──────────────┴────────────────────────────────┘

Mobile:

┌──────────────────────────────┐
│ NCA-CTF       ☰              │
├──────────────────────────────┤
│                              │
│ Main content                 │
│                              │
└──────────────────────────────┘
9. PUBLIC LANDING PAGE

Route:

/

The landing page should introduce the NCA-CTF platform.

Required sections
Hero

Include:

NCA-CTF branding
concise competition statement
primary CTA
secondary CTA

Example messaging direction:

NCA-CTF

Think. Exploit. Capture.

A private cybersecurity challenge platform for NCA Batch 4.

[ Enter the CTF ]
[ Explore Challenges ]

Do not copy this wording blindly if better copy can be created.

10. HERO DESIGN

The hero should immediately communicate:

cybersecurity
competition
technical challenge

Possible visual language:

subtle terminal panel
challenge statistics
technical grid
command-line motif
challenge category indicators

Keep it lightweight.

Avoid large decorative animations that hurt performance.

11. PLATFORM FEATURES SECTION

Introduce the core participant experience.

Suggested feature categories:

Challenge

Solve web, crypto, forensics, reverse engineering, pwn, OSINT and other challenges.

Competition

Track points, solves and leaderboard position.

Team

Collaborate with your registered team.

Learning

Use hints and challenge resources to develop practical security skills.

Do not claim categories that the backend does not support unless clearly marked as future functionality.

12. HOW IT WORKS

Provide a simple flow:

01 — Register
02 — Join or create a team
03 — Explore challenges
04 — Solve challenges
05 — Submit flags
06 — Climb the leaderboard

The section should be visually simple.

13. CATEGORIES

If category information is available through the backend, display real categories dynamically.

Do not hard-code categories that don't exist.

Possible categories include:

Web
Crypto
Forensics
Reverse Engineering
Pwn
OSINT
Misc

These should be treated as examples unless confirmed by the API.

14. COMPETITION STATISTICS

Where appropriate, display useful statistics such as:

Challenges
Participants
Teams
Points Available

Only show statistics that can be sourced reliably.

Do not invent numbers.

If an API is unavailable, use carefully labeled mock data during development.

15. NCA CONTACTS

The landing page MUST contain an NCA Contacts section.

This is a required feature.

Create:

src/components/contacts/NCAContacts.tsx

The component should support:

organization information
contact persons
email
Discord/community contact
website
social links where appropriate

However:

DO NOT invent real NCA contact details.

Until official information is provided, use clearly replaceable placeholders.

Example:

NCA Group

Official Contact
contact@example.com

Community
Discord — Coming Soon

Website
Official Website — Coming Soon

Structure the component so the actual information can be replaced easily.

16. FOOTER

The footer should include:

NCA-CTF branding
short description
navigation links
NCA Contacts
relevant social/community links
copyright
project/version information where appropriate

Avoid clutter.

17. AUTHENTICATION

Routes:

/login
/register

Authentication uses native PHP sessions.

Do NOT implement JWT.

18. LOGIN PAGE

The login page should include:

email/username field depending on backend requirements
password
submit button
loading state
validation
backend error handling
registration link

Example:

Welcome back.

Sign in to NCA-CTF.

[ Username or Email ]
[ Password ]

[ SIGN IN ]

Don't have an account?
Create one
19. REGISTER PAGE

Registration should use the actual backend validation requirements.

Do not invent additional mandatory fields.

Display:

validation errors
loading state
success state
backend errors

After successful registration, guide the participant toward login.

20. AUTHENTICATED DASHBOARD

Route:

/dashboard

The dashboard should provide an immediate overview.

Possible sections:

Welcome back, <name>

Team
Team Name
Team Score

Progress
Solved / Available

Recent Activity

Challenge Categories

Leaderboard Position

Quick Actions
[ View Challenges ]
[ View Leaderboard ]
[ Team ]

Do not display information that the backend cannot provide.

21. CHALLENGES PAGE

Route:

/challenges

This is one of the most important pages.

It should provide:

challenge list/grid
category filtering
difficulty filtering
search where practical
pagination
solved status
points
challenge status
22. CHALLENGE CARD

Each challenge card should communicate:

Challenge Name

Category
Difficulty

250 pts

[ SOLVED ]

Optional:

challenge identifier
solve count
deployment type
status

Do not expose internal administrative information.

23. CHALLENGE FILTERS

Provide filters for:

category
difficulty
solved status if supported client-side
search

The filter UI must work well on mobile.

Desktop:

Search       Category       Difficulty

Mobile:

[ Search ]
[ Filters ]
24. CHALLENGE DETAIL

Route:

/challenges/:slug

The challenge detail page should contain:

Header
Challenge Name
Category
Difficulty
Points
Description

Render challenge description safely.

Files

Display downloadable challenge files.

Hints

Display available hints.

Flag Submission

Prominent submission component.

25. CHALLENGE FILES

Each file should display:

filename
size if available
file type if useful
download action

Downloads must use authenticated backend requests.

Do not expose filesystem paths.

26. HINTS

Hints should initially appear locked/unrevealed where applicable.

Example:

Hint #1

This hint has not been revealed.

[ Reveal Hint ]

After reveal:

Hint #1

<revealed content>

The frontend must not decide whether a hint is available.

The backend remains authoritative.

27. FLAG SUBMISSION

The flag submission interface is required even though the backend endpoint is still under Phase 5 development.

The UI must be designed so the eventual API can be integrated without redesigning the component.

Component:

src/components/challenges/FlagSubmission.tsx

States:

Idle
Submitting
Correct
Incorrect
Already Solved
Rate Limited
Error

Example:

Submit Flag

[ flag{........................................} ]

[ SUBMIT FLAG ]

Correct:

✓ Correct Flag

Challenge solved.

+250 points

First Blood:

🏆 FIRST BLOOD

You were the first team to solve this challenge.

Incorrect:

✕ Incorrect Flag

Try again.

Rate limited:

Too many attempts.

Please wait before trying again.

The frontend must never locally determine flag correctness.

28. LEADERBOARD

Route:

/leaderboard

Phase 6 backend is not yet implemented.

Therefore the frontend should establish the page architecture but must not pretend the leaderboard backend already exists.

During development, mock data may be used.

Leaderboard should eventually show:

Rank
Team
Score
Solved
Last Activity

Potential visual treatment:

#1
Team Alpha
2450 pts

#2
Team Bravo
2300 pts

The user's team should be visually identifiable.

29. TEAM PAGE

Route:

/team

The page should use the existing team APIs.

Display:

team name
team slug where appropriate
team score if available
captain
members
joined dates
invitations
team actions

Actions should respect actual backend authorization.

For example:

captain-only operations must not be presented as universally available.
30. TEAM INVITATIONS

Where supported:

Display:

Pending Invitations

Actions:

Accept
Reject

Use backend APIs.

Provide clear success/error feedback.

31. PROFILE PAGE

Route:

/profile

Display information available through /api/v1/auth/me.

Do not create a fake profile-edit API.

If profile editing is not currently supported by the backend, make the page informational rather than pretending editing works.

32. NAVIGATION

Authenticated navigation should include:

Dashboard
Challenges
Leaderboard
Team
Profile

Logout should be clearly available.

Future/admin functionality should not appear in the participant navigation.

33. AUTHORIZATION-AWARE UI

The frontend may conditionally show UI based on user/team state.

However:

UI visibility is NOT authorization.

Every protected operation must still be validated by the backend.

34. RESPONSIVE BEHAVIOR

The entire platform must be responsive.

Desktop:

sidebar
multi-column dashboard
challenge grid
full leaderboard table

Tablet:

reduced columns
responsive navigation
adaptive cards

Mobile:

drawer navigation
stacked content
horizontally scrollable tables where necessary
full-width inputs
touch-friendly controls

Minimum target:

320px width
35. PERFORMANCE

Avoid unnecessary:

animations
large background images
huge JavaScript bundles
unnecessary API requests
duplicate API calls

Use:

lazy loading where appropriate
efficient rendering
pagination
caching where appropriate
36. SECURITY

Never trust frontend state.

Never store:

passwords
plaintext flags
flag hashes
private backend secrets

CSRF token should be held in memory and associated with the PHP session.

API calls must use credentials.

37. API INTEGRATION

Backend API base:

/api/v1/

API success envelope:

{
  "success": true,
  "data": {},
  "message": "..."
}

API error envelope:

{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message"
  }
}

The frontend API client must normalize this structure.

38. SESSION COOKIES

All API requests that depend on authentication must include credentials.

Using fetch:

credentials: "include"

or equivalent.

Do not replace the PHP session architecture with JWT.

39. CSRF

State-changing requests:

POST
PUT
DELETE

must include:

X-CSRF-Token

The token comes from the backend.

Do not hard-code a CSRF token.

Do not store it permanently in localStorage.

40. DEVELOPMENT API PROXY

During Vite development, configure a proxy so frontend requests can reach the PHP backend without requiring immediate backend CORS changes.

Conceptually:

Browser
   ↓
Vite
   ↓
/api/*
   ↓
PHP backend

The exact target must be determined from the developer environment.

Do not hard-code a production URL.

41. MOCK STRATEGY

Mock data is allowed only where the backend functionality does not yet exist.

Currently expected mock areas:

Phase 5:
Flag submission

Phase 6:
Leaderboard

Potentially:

Dashboard statistics

if required data is unavailable.

Mocks must be isolated.

Never mix mock logic directly into UI components.

42. MOCK DATA PRINCIPLE

Mock data should resemble the real backend structures as closely as possible.

When Phase 5 or Phase 6 APIs are implemented:

Mock API
    ↓
Real API

should be replaceable without rebuilding the UI architecture.

43. ERROR STATES

Every API-driven component must account for:

network error
authentication expiration
authorization failure
validation error
rate limit
server error
empty response

Provide useful user-facing messages.

Do not expose raw exceptions.

44. NOT FOUND

Provide a polished 404 page.

Example concept:

404

Challenge not found.

The page you're looking for does not exist.

[ Back to Challenges ]
45. FORBIDDEN

Provide a 403 page.

Example:

403

Access denied.

You don't have permission to access this resource.
46. SESSION EXPIRATION

If the backend returns an authentication failure:

clear frontend authentication state
redirect to login
preserve intended destination where practical
avoid infinite redirect loops
47. COMPONENT REUSABILITY

Create reusable components for common patterns:

Button
Input
Modal
Badge
Card
Table
Spinner
Skeleton
Toast
Alert
EmptyState
ErrorState
Pagination

Avoid rebuilding these patterns independently.

48. ICONS

Use a consistent icon library if required.

Do not mix random icon styles.

Icons should support the information rather than dominate the UI.

49. ANIMATION

Use animation sparingly.

Good uses:

page transitions
dropdowns
modals
loading states
subtle hover effects

Avoid:

continuous background animations
excessive motion
distracting effects

Respect reduced-motion preferences.

50. ACCESSIBILITY

The frontend must provide:

keyboard navigation
semantic HTML
accessible labels
focus indicators
readable contrast
accessible error messages
screen-reader-compatible status changes

Do not rely solely on color.

51. MOBILE NAVIGATION

Mobile navigation should not simply shrink the desktop sidebar.

Use a proper:

hamburger → navigation drawer

with:

Dashboard
Challenges
Leaderboard
Team
Profile
Logout
52. DATA OWNERSHIP

Backend owns:

users
teams
challenges
flags
solves
scores
submissions
competition state

Frontend owns:

presentation
temporary UI state
filters
modals
form state
loading state
client-side navigation state
53. FUTURE COMPATIBILITY

The architecture should leave room for future phases:

Phase 5 — Submission
Phase 6 — Leaderboard
Phase 7 — Admin
Phase 8 — Docker
Phase 9 — Integrity
Phase 10 — Competition Controls
Phase 11 — Security Hardening

Do not prematurely implement those systems.

The frontend architecture should simply avoid blocking them.

54. ADMIN SEPARATION

The participant frontend and future admin interface should remain logically separate.

Do not expose admin functionality in participant pages.

Future admin frontend may eventually live under a separate route/application area.

55. NCA BRANDING

The NCA-CTF platform should visually connect to NCA while maintaining its own competition identity.

Use:

NCA branding where appropriate
NCA-CTF identity
professional typography
consistent visual system

Do not invent official logos or brand guidelines.

If official logo assets are supplied later, integrate them properly.

56. CONTENT PRINCIPLES

UI copy should be:

concise
professional
clear
technically appropriate

Avoid:

"OMG YOU CRACKED IT!!!"

Prefer:

Challenge solved.

The platform should feel professional enough for an actual cybersecurity competition.

57. NO FAKE FUNCTIONALITY

Do not create buttons that appear functional but do nothing.

If functionality is not available yet:

disable it
show appropriate status
or implement a clearly isolated mock

Do not silently pretend an unavailable backend feature works.

58. FRONTEND IMPLEMENTATION GOAL

The final frontend should allow a participant to experience this flow:

Landing Page
      ↓
Register
      ↓
Login
      ↓
Dashboard
      ↓
Team
      ↓
Challenges
      ↓
Challenge Detail
      ↓
Read Description
      ↓
Download Files
      ↓
Reveal Hint
      ↓
Solve Challenge
      ↓
Submit Flag
      ↓
Challenge Solved
      ↓
Score Updated
      ↓
Leaderboard

The backend is authoritative at every step.

59. CURRENT IMPLEMENTATION BOUNDARY

Implement now:

Landing
Authentication
Application shell
Dashboard foundation
Challenges
Challenge detail
Files
Hints
Flag submission UI
Leaderboard foundation
Team
Profile
NCA Contacts
Responsive design
Error/loading states

Use mocks only where the backend is not yet available.

60. DO NOT IMPLEMENT NOW

Do NOT implement:

Admin dashboard
Admin challenge creation UI
Admin flag management
User administration
Docker management
Integrity dashboard
Cheat detection dashboard
Competition control panel
Infrastructure management
Container management

These belong to future phases.

61. SUCCESS CRITERIA

The frontend implementation will be considered successful when:

Architecture
React + TypeScript + Vite structure is clean
frontend is isolated from PHP backend
API layer is centralized
authentication is centralized
components are reusable
Functionality
registration works
login works
logout works
session restoration works
challenge list works
challenge filtering works
challenge detail works
files can be accessed through the backend
hints can be revealed
team information works
profile works
NCA Contacts exists
flag submission UI is ready for Phase 5
leaderboard foundation exists
UX
responsive
accessible
clear loading states
clear error states
clear success states
no broken routes
no obvious console errors
Security
no secrets exposed
no plaintext flags
no JWT replacing PHP sessions
CSRF handled correctly
API authorization remains backend-controlled
62. FINAL PRINCIPLE

The NCA-CTF frontend is not merely a visual layer.

It is the participant's primary interface to the competition.

Therefore it must combine:

Professional Design
        +
Clear UX
        +
Reliable API Integration
        +
Security Awareness
        +
Responsive Architecture
        +
Future Maintainability

The finished product should feel like a real cybersecurity competition platform built for NCA Batch 4 — not a generic generated dashboard.

END OF FRONTEND MASTER SPEC

---

## What to do with Kimi now

**Upload this file to Kimi alongside `KIMI_INSTRUCTIONS.md`.**

Then **do not tell Kimi to build yet**.

Send:

```text
I have now provided FRONTEND_MASTER_SPEC.md.

Do not implement anything yet.

Read and analyze:

1. KIMI_INSTRUCTIONS.md
2. FRONTEND_MASTER_SPEC.md
3. Your previous repository architecture assessment

Confirm that you understand the product requirements and identify any conflicts between the specification and the actual backend you inspected.

Pay particular attention to:

- PHP session authentication
- CSRF
- existing challenge API
- team API
- missing Phase 5 submission API
- missing Phase 6 leaderboard API
- existing PHP views
- Vite development proxy
- NCA Contacts
- participant vs future admin frontend

Do not create or modify code yet.

Return:
1. Requirements understood
2. Backend/frontend integration constraints
3. Conflicts or ambiguities
4. Recommended implementation order

Then wait.


Why we're doing this

We now have a two-layer control system:

KIMI_INSTRUCTIONS.md
        ↓
How Kimi must behave
        ↓
FRONTEND_MASTER_SPEC.md
        ↓
What Kimi must build
        ↓
Actual NCA-CTF backend
        ↓
What is technically possible
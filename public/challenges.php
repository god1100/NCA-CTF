<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenges — NCA Batch 4 CTF</title>
    <meta name="description" content="Browse NCA Batch 4 CTF challenges">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/challenges.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar__brand">
            <span class="brand__nca">NCA</span>
            <span class="brand__batch">BATCH 4</span>
            <span class="brand__ctf">CTF</span>
        </div>
        <span class="badge badge--info">Phase 4 &middot; Challenges</span>
    </header>

    <main class="challenges-page">
        <div id="auth-notice" class="challenges-page__notice" hidden>
            <p>You need to be logged in to browse challenges. Use the
            <code>/api/v1/auth/login</code> endpoint, or if you're
            testing manually, log in first and then reload this page.</p>
        </div>

        <div id="challenges-app" hidden>
            <h1 class="challenges-page__title">Challenges</h1>

            <div class="filters">
                <label class="filters__field">
                    Category
                    <select id="filter-category">
                        <option value="">All</option>
                    </select>
                </label>
                <label class="filters__field">
                    Difficulty
                    <select id="filter-difficulty">
                        <option value="">All</option>
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                        <option value="insane">Insane</option>
                    </select>
                </label>
            </div>

            <div id="challenge-grid" class="challenge-grid" aria-live="polite"></div>

            <p id="empty-state" class="empty-state" hidden>No challenges found. Try changing your filters.</p>

            <div class="pagination" id="pagination" hidden>
                <button class="btn" id="prev-page">Previous</button>
                <span id="page-indicator"></span>
                <button class="btn" id="next-page">Next</button>
            </div>
        </div>

        <div id="challenge-detail" class="challenge-detail" hidden></div>
    </main>

    <footer class="footer">
        <p>NCA Batch 4 Private CTF Platform &mdash; Internal training use only.</p>
    </footer>

    <script src="/assets/js/challenges.js"></script>
</body>
</html>

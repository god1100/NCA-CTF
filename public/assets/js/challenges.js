/**
 * NCA CTF — Challenges page behavior (Phase 4 backend integration).
 *
 * Vanilla JS only. Talks to /api/v1/categories, /api/v1/challenges/*,
 * /api/v1/challenge-files/*, /api/v1/challenge-hints/*.
 *
 * Uses the same session/CSRF mechanism as dashboard.js and home.js:
 * window.NCA_API (see api.js) owns the CSRF token and current-user
 * state; this file never manages its own session or auth headers.
 *
 * No flag submission, scoring, or solved-state fabrication happens
 * here — those features do not exist in the backend yet (see
 * docs/ctf9.txt phase sequencing). The "solved" field the backend
 * returns is currently always false and is not surfaced as if it
 * were meaningful.
 */
(function () {
    "use strict";

    var state = { page: 1, perPage: 20, category: "", difficulty: "" };

    // ---- DOM refs ----
    var authNotice = document.getElementById("auth-notice");
    var loadingState = document.getElementById("loading-state");
    var errorState = document.getElementById("error-state");
    var app = document.getElementById("challenges-app");
    var grid = document.getElementById("challenge-grid");
    var emptyState = document.getElementById("empty-state");
    var pagination = document.getElementById("pagination");
    var pageIndicator = document.getElementById("page-indicator");
    var detailView = document.getElementById("challenge-detail");
    var categorySelect = document.getElementById("filter-category");
    var difficultySelect = document.getElementById("filter-difficulty");

    // ---- API helper --------------------------------------------------
    // Reuses window.NCA_API's URL builder (handles BASE_URL / index.php
    // prefixing the same way every other page on the site does) and its
    // CSRF token, rather than re-implementing session handling.
    async function api(method, endpoint, data) {
        var headers = { "Content-Type": "application/json", "Accept": "application/json" };
        var token = window.NCA_API.getCsrfToken();
        if (token) headers["X-CSRF-Token"] = token;

        var options = { method: method, credentials: "include", headers: headers };
        if (data && (method === "POST" || method === "PUT" || method === "DELETE")) {
            options.body = JSON.stringify(data);
        }

        try {
            var response = await fetch(window.NCA_API.url(endpoint), options);
            var text = await response.text();
            var body = {};
            if (text && text.trim() !== "") {
                try { body = JSON.parse(text); } catch (_) { body = {}; }
            }
            return { status: response.status, ok: response.ok, body: body };
        } catch (_) {
            return { status: 0, ok: false, body: {}, networkError: true };
        }
    }

    function escapeHtml(str) {
        var div = document.createElement("div");
        div.textContent = str || "";
        return div.innerHTML;
    }

    function difficultyClass(d) {
        return "difficulty difficulty--" + (d || "");
    }

    // ---- View state helpers ------------------------------------------
    function showAuthRequired() {
        authNotice.hidden = false;
        loadingState.hidden = true;
        errorState.hidden = true;
        app.hidden = true;
        detailView.hidden = true;
    }

    function showLoading() {
        authNotice.hidden = true;
        errorState.hidden = true;
        app.hidden = true;
        detailView.hidden = true;
        loadingState.hidden = false;
    }

    function showError() {
        authNotice.hidden = true;
        loadingState.hidden = true;
        app.hidden = true;
        detailView.hidden = true;
        errorState.hidden = false;
    }

    function showApp() {
        authNotice.hidden = true;
        loadingState.hidden = true;
        errorState.hidden = true;
        detailView.hidden = true;
        app.hidden = false;
    }

    function showDetailView() {
        authNotice.hidden = true;
        loadingState.hidden = true;
        errorState.hidden = true;
        app.hidden = true;
        detailView.hidden = false;
    }

    // ---- Categories ----------------------------------------------------
    async function loadCategories() {
        var result = await api("GET", "/api/v1/categories");
        if (result.status !== 200) return;

        var categories = (result.body.data && result.body.data.categories) || [];
        categories.forEach(function (c) {
            var opt = document.createElement("option");
            opt.value = c.slug;
            opt.textContent = c.name;
            categorySelect.appendChild(opt);
        });
    }

    // ---- Challenge list --------------------------------------------------
    function renderGrid(challenges) {
        grid.innerHTML = "";
        challenges.forEach(function (c) {
            var card = document.createElement("div");
            card.className = "challenge-card";
            card.innerHTML =
                '<div class="challenge-card__category">' + escapeHtml(c.category || "") + "</div>" +
                '<h2 class="challenge-card__title">' + escapeHtml(c.title) + "</h2>" +
                '<div class="challenge-card__meta">' +
                '<span class="' + difficultyClass(c.difficulty) + '">' + escapeHtml(c.difficulty || "") + "</span>" +
                '<span class="points">' + (c.points != null ? c.points : "?") + " pts</span>" +
                "</div>";
            card.addEventListener("click", function () { showDetail(c.slug); });
            grid.appendChild(card);
        });
    }

    async function loadList() {
        showLoading();

        var params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.category) params.set("category", state.category);
        if (state.difficulty) params.set("difficulty", state.difficulty);

        var result = await api("GET", "/api/v1/challenges?" + params.toString());

        if (result.status === 401) {
            showAuthRequired();
            return;
        }

        if (result.status !== 200) {
            showError();
            return;
        }

        showApp();

        var challenges = (result.body.data && result.body.data.challenges) || [];
        emptyState.hidden = challenges.length > 0;
        grid.hidden = challenges.length === 0;
        renderGrid(challenges);

        var pg = (result.body.data && result.body.data.pagination) || { page: 1, total_pages: 1 };
        pagination.hidden = pg.total_pages <= 1;
        pageIndicator.textContent = "Page " + pg.page + " of " + pg.total_pages;
    }

    // ---- Challenge detail --------------------------------------------
    function renderFiles(files) {
        if (!files || files.length === 0) {
            return '<p class="hint-row__content">No files for this challenge.</p>';
        }
        return files.map(function (f) {
            var href = window.NCA_API.url("/api/v1/challenge-files/" + f.id + "/download");
            return '<div class="file-row"><span>' + escapeHtml(f.name) + " (" + f.size + " bytes)</span>" +
                '<a class="btn" href="' + href + '">Download</a></div>';
        }).join("");
    }

    function renderHints(hints) {
        if (!hints || hints.length === 0) {
            return '<p class="hint-row__content">No hints for this challenge.</p>';
        }
        return hints.map(function (h) {
            var revealed = typeof h.content !== "undefined";
            var row = '<div class="hint-row" data-hint-id="' + h.id + '">' +
                "<span>" + escapeHtml(h.title || "Hint") + " (&minus;" + h.point_penalty + " pts)</span>";
            if (revealed) {
                row += '<span class="hint-row__content">' + escapeHtml(h.content) + "</span>";
            } else {
                row += '<button class="btn reveal-btn" data-hint-id="' + h.id + '">Reveal</button>';
            }
            row += "</div>";
            return row;
        }).join("");
    }

    function flagSubmissionHtml() {
        return (
            '<div class="challenge-detail__section flag-section">' +
            "<h3>Flag Submission</h3>" +
            '<p class="flag-section__notice">' +
            '<i class="fas fa-clock"></i> Flag submission is coming soon. Scoring and the leaderboard are not available yet.' +
            "</p>" +
            "</div>"
        );
    }

    async function showDetail(slug) {
        showLoading();

        var result = await api("GET", "/api/v1/challenges/" + encodeURIComponent(slug));

        if (result.status === 401) {
            showAuthRequired();
            return;
        }

        if (result.status !== 200) {
            showError();
            return;
        }

        var c = result.body.data && result.body.data.challenge;
        if (!c) {
            showError();
            return;
        }

        showDetailView();

        detailView.innerHTML =
            '<button class="challenge-detail__back" id="back-btn">&larr; Back to challenges</button>' +
            '<div class="challenge-card__category">' + escapeHtml(c.category || "") + "</div>" +
            "<h1>" + escapeHtml(c.title) + "</h1>" +
            '<div class="challenge-card__meta">' +
            '<span class="' + difficultyClass(c.difficulty) + '">' + escapeHtml(c.difficulty || "") + "</span>" +
            '<span class="points">' + (c.points != null ? c.points : "?") + " pts</span></div>" +
            "<p>" + escapeHtml(c.description || "") + "</p>" +
            '<div class="challenge-detail__section"><h3>Files</h3>' + renderFiles(c.files) + "</div>" +
            '<div class="challenge-detail__section"><h3>Hints</h3>' + renderHints(c.hints) + "</div>" +
            flagSubmissionHtml();

        document.getElementById("back-btn").addEventListener("click", function () {
            loadList();
        });

        detailView.querySelectorAll(".reveal-btn").forEach(function (btn) {
            btn.addEventListener("click", async function () {
                var hintId = btn.getAttribute("data-hint-id");
                btn.disabled = true;

                var result = await api("POST", "/api/v1/challenge-hints/" + hintId + "/reveal");

                if (result.status === 401) {
                    showAuthRequired();
                    return;
                }

                if (result.status !== 200 || !result.body.data || !result.body.data.hint) {
                    btn.disabled = false;
                    var errRow = detailView.querySelector('.hint-row[data-hint-id="' + hintId + '"]');
                    var errMsg = document.createElement("span");
                    errMsg.className = "hint-row__error";
                    errMsg.textContent = "Could not reveal hint. Please try again.";
                    errRow.appendChild(errMsg);
                    return;
                }

                var row = detailView.querySelector('.hint-row[data-hint-id="' + hintId + '"]');
                var content = document.createElement("span");
                content.className = "hint-row__content";
                content.textContent = result.body.data.hint.content;
                row.appendChild(content);
                btn.remove();
            });
        });
    }

    // ---- Filters / pagination ------------------------------------------
    categorySelect.addEventListener("change", function () {
        state.category = categorySelect.value;
        state.page = 1;
        loadList();
    });
    difficultySelect.addEventListener("change", function () {
        state.difficulty = difficultySelect.value;
        state.page = 1;
        loadList();
    });
    document.getElementById("prev-page").addEventListener("click", function () {
        if (state.page > 1) { state.page--; loadList(); }
    });
    document.getElementById("next-page").addEventListener("click", function () {
        state.page++; loadList();
    });

    // ---- Init -----------------------------------------------------------
    // Mirrors dashboard.js/home.js: wait for api.js to attach window.NCA_API
    // before doing anything, then establish the session/CSRF state via
    // NCA_API.me() before making challenge-specific requests.
    async function init() {
        if (typeof window.NCA_API === "undefined") {
            setTimeout(init, 200);
            return;
        }

        showLoading();

        try {
            var meResult = await window.NCA_API.me();
            if (!meResult.ok || !meResult.success) {
                showAuthRequired();
                return;
            }
        } catch (_) {
            showAuthRequired();
            return;
        }

        await loadCategories();
        await loadList();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();

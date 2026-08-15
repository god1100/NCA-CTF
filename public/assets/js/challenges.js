// NCA Batch 4 CTF — Challenges page behavior (Phase 4).
// Vanilla JS only, no framework. Talks to /api/v1/challenges/*.

(function () {
    "use strict";

    const state = { page: 1, perPage: 20, category: "", difficulty: "" };

    const authNotice = document.getElementById("auth-notice");
    const app = document.getElementById("challenges-app");
    const grid = document.getElementById("challenge-grid");
    const emptyState = document.getElementById("empty-state");
    const pagination = document.getElementById("pagination");
    const pageIndicator = document.getElementById("page-indicator");
    const detailView = document.getElementById("challenge-detail");
    const categorySelect = document.getElementById("filter-category");
    const difficultySelect = document.getElementById("filter-difficulty");

    async function api(path, options) {
        const response = await fetch(path, Object.assign({ credentials: "same-origin" }, options || {}));
        const body = await response.json().catch(() => ({}));
        return { status: response.status, body: body };
    }

    async function loadCategories() {
        const { status, body } = await api("/api/v1/categories");
        if (status !== 200) return;
        (body.data.categories || []).forEach(function (c) {
            const opt = document.createElement("option");
            opt.value = c.slug;
            opt.textContent = c.name;
            categorySelect.appendChild(opt);
        });
    }

    function difficultyClass(d) {
        return "difficulty difficulty--" + d;
    }

    function renderGrid(challenges) {
        grid.innerHTML = "";
        challenges.forEach(function (c) {
            const card = document.createElement("div");
            card.className = "challenge-card";
            card.innerHTML =
                '<div class="challenge-card__category">' + (c.category || "") + '</div>' +
                '<h2 class="challenge-card__title">' + escapeHtml(c.title) + '</h2>' +
                '<div class="challenge-card__meta">' +
                '<span class="' + difficultyClass(c.difficulty) + '">' + c.difficulty + '</span>' +
                '<span class="points">' + c.points + ' pts</span>' +
                '</div>';
            card.addEventListener("click", function () { showDetail(c.slug); });
            grid.appendChild(card);
        });
    }

    function escapeHtml(str) {
        const div = document.createElement("div");
        div.textContent = str || "";
        return div.innerHTML;
    }

    async function loadList() {
        const params = new URLSearchParams({ page: state.page, per_page: state.perPage });
        if (state.category) params.set("category", state.category);
        if (state.difficulty) params.set("difficulty", state.difficulty);

        const { status, body } = await api("/api/v1/challenges?" + params.toString());

        if (status === 401) {
            authNotice.hidden = false;
            app.hidden = true;
            return;
        }

        authNotice.hidden = true;
        app.hidden = false;
        detailView.hidden = true;

        const challenges = body.data.challenges || [];
        emptyState.hidden = challenges.length > 0;
        grid.hidden = challenges.length === 0;
        renderGrid(challenges);

        const pg = body.data.pagination || { page: 1, total_pages: 1 };
        pagination.hidden = pg.total_pages <= 1;
        pageIndicator.textContent = "Page " + pg.page + " of " + pg.total_pages;
    }

    async function showDetail(slug) {
        const { status, body } = await api("/api/v1/challenges/" + encodeURIComponent(slug));
        if (status !== 200) return;

        const c = body.data.challenge;
        app.hidden = true;
        detailView.hidden = false;

        let filesHtml = (c.files || []).map(function (f) {
            return '<div class="file-row"><span>' + escapeHtml(f.name) + ' (' + f.size + ' bytes)</span>' +
                '<a class="btn" href="/api/v1/challenge-files/' + f.id + '/download">Download</a></div>';
        }).join("") || '<p class="hint-row__content">No files for this challenge.</p>';

        let hintsHtml = (c.hints || []).map(function (h) {
            return '<div class="hint-row" data-hint-id="' + h.id + '">' +
                '<span>' + escapeHtml(h.title || "Hint") + ' (&minus;' + h.point_penalty + ' pts)</span>' +
                '<button class="btn reveal-btn" data-hint-id="' + h.id + '">Reveal</button></div>';
        }).join("") || '<p class="hint-row__content">No hints for this challenge.</p>';

        detailView.innerHTML =
            '<button class="challenge-detail__back" id="back-btn">&larr; Back to challenges</button>' +
            '<div class="challenge-card__category">' + (c.category || "") + '</div>' +
            '<h1>' + escapeHtml(c.title) + '</h1>' +
            '<div class="challenge-card__meta">' +
            '<span class="' + difficultyClass(c.difficulty) + '">' + c.difficulty + '</span>' +
            '<span class="points">' + c.points + ' pts</span></div>' +
            '<p>' + escapeHtml(c.description || "") + '</p>' +
            '<div class="challenge-detail__section"><h3>Files</h3>' + filesHtml + '</div>' +
            '<div class="challenge-detail__section"><h3>Hints</h3>' + hintsHtml + '</div>';

        document.getElementById("back-btn").addEventListener("click", function () {
            detailView.hidden = true;
            app.hidden = false;
        });

        detailView.querySelectorAll(".reveal-btn").forEach(function (btn) {
            btn.addEventListener("click", async function () {
                const hintId = btn.getAttribute("data-hint-id");
                const meResp = await api("/api/v1/auth/me");
                const csrf = meResp.body.data && meResp.body.data.csrf_token;
                const { status, body } = await api("/api/v1/challenge-hints/" + hintId + "/reveal", {
                    method: "POST",
                    headers: { "X-CSRF-Token": csrf || "" },
                });
                if (status === 200) {
                    const row = detailView.querySelector('.hint-row[data-hint-id="' + hintId + '"]');
                    const content = document.createElement("span");
                    content.className = "hint-row__content";
                    content.textContent = body.data.hint.content;
                    row.appendChild(content);
                    btn.remove();
                }
            });
        });
    }

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

    loadCategories();
    loadList();
})();

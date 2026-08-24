// public/admin/assets/js/challenges.js
// Admin Challenge List

(function () {
    'use strict';

    const content = document.getElementById('adminContent');

    let allChallenges = [];
    let categories = [];

    let currentFilters = {
        search: '',
        category_id: '',
        difficulty: '',
        status: ''
    };

    let isLoading = false;

    // ============================================================
    // URL HELPERS
    // ============================================================

    function appUrl(path) {
        if (window.NCA_API && typeof window.NCA_API.url === 'function') {
            return window.NCA_API.url(path);
        }

        const base = window.NCA_CTF_BASE_URL || '/NCA-CTF/public';

        const cleanPath = path.startsWith('/')
            ? path.substring(1)
            : path;

        return `${base}/${cleanPath}`;
    }

    // ============================================================
    // MODAL HANDLING
    // ============================================================

    window.AdminModal = {

        open(title, message, confirmText, onConfirm) {

            const overlay = document.getElementById('adminModal');
            const titleEl = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('modalConfirmBtn');

            if (!overlay) return;

            titleEl.textContent = title || 'Confirm Action';
            messageEl.textContent = message || 'Are you sure?';
            confirmBtn.textContent = confirmText || 'Confirm';

            // Remove old listeners
            const newConfirm = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);

            newConfirm.addEventListener('click', function () {

                overlay.classList.remove('active');

                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });

            // Close when clicking outside modal
            overlay.addEventListener('click', function (e) {

                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }

            });

            overlay.classList.add('active');
        },

        close() {

            const overlay = document.getElementById('adminModal');

            if (overlay) {
                overlay.classList.remove('active');
            }

        }
    };

    // ============================================================
    // LOAD DATA
    // ============================================================

    async function loadData() {

        if (isLoading) return;

        isLoading = true;

        try {

            // ----------------------------------------------------
            // Load categories
            // ----------------------------------------------------

            await loadCategories();

            // ----------------------------------------------------
            // Build query string
            // ----------------------------------------------------

            const params = new URLSearchParams();

            params.set('limit', '100');

            if (currentFilters.search) {
                params.set('search', currentFilters.search);
            }

            if (currentFilters.category_id) {
                params.set('category_id', currentFilters.category_id);
            }

            if (currentFilters.difficulty) {
                params.set('difficulty', currentFilters.difficulty);
            }

            if (currentFilters.status) {
                params.set('status', currentFilters.status);
            }

            // ----------------------------------------------------
            // Request challenges
            // ----------------------------------------------------

            const response = await fetch(
                appUrl(`/api/v1/challenges?${params.toString()}`),
                {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to load challenges: ${response.status}`
                );
            }

            const data = await response.json();

            console.log('Challenges API response:', data);

            // ----------------------------------------------------
            // IMPORTANT:
            //
            // API response:
            //
            // {
            //     success: true,
            //     data: {
            //         challenges: [...]
            //         pagination: {...}
            //     }
            // }
            // ----------------------------------------------------

            allChallenges =
                data?.data?.challenges || [];

            // Safety check
            if (!Array.isArray(allChallenges)) {
                console.error(
                    'Invalid challenges response:',
                    data
                );

                allChallenges = [];
            }

            render();

        } catch (error) {

            console.error('Load error:', error);

            content.innerHTML = `
                <div class="admin-error">

                    <div class="error-icon">⚠️</div>

                    <div class="error-text">
                        Unable to load challenges
                    </div>

                    <div class="error-hint">
                        ${escapeHtml(
                error.message ||
                'Please try again later.'
            )}
                    </div>

                    <div class="error-actions">

                        <button
                            onclick="location.reload()"
                            class="btn btn-primary"
                        >
                            Retry
                        </button>

                    </div>

                </div>
            `;

        } finally {

            isLoading = false;

        }
    }

    // ============================================================
    // LOAD CATEGORIES
    // ============================================================

    async function loadCategories() {

        try {

            const response = await fetch(
                NCA_API.url('/api/v1/categories'),
                {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {

                throw new Error(
                    `Failed to load categories: ${response.status}`
                );

            }

            const data = await response.json();

            console.log('Categories API response:', data);

            categories =
                data?.data?.categories ||
                data?.data ||
                [];

            if (!Array.isArray(categories)) {
                categories = [];
            }

        } catch (error) {

            console.warn(
                'Could not load categories:',
                error
            );

            categories = [];
        }
    }

    // ============================================================
    // RENDER
    // ============================================================

    function render() {

        const challenges = Array.isArray(allChallenges)
            ? allChallenges
            : [];

        content.innerHTML = `

            <!-- Toolbar -->

            <div class="admin-toolbar">

                <div class="toolbar-left">

                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search challenges..."
                        value="${escapeHtml(currentFilters.search)}"
                    >

                    <select id="categoryFilter">

                        <option value="">
                            All Categories
                        </option>

                        ${categories.map(c => `

                            <option
                                value="${c.id}"
                                ${currentFilters.category_id == c.id
                ? 'selected'
                : ''
            }
                            >
                                ${escapeHtml(c.name)}
                            </option>

                        `).join('')}

                    </select>

                    <select id="difficultyFilter">

                        <option value="">
                            All Difficulties
                        </option>

                        <option
                            value="easy"
                            ${currentFilters.difficulty === 'easy'
                ? 'selected'
                : ''
            }
                        >
                            Easy
                        </option>

                        <option
                            value="medium"
                            ${currentFilters.difficulty === 'medium'
                ? 'selected'
                : ''
            }
                        >
                            Medium
                        </option>

                        <option
                            value="hard"
                            ${currentFilters.difficulty === 'hard'
                ? 'selected'
                : ''
            }
                        >
                            Hard
                        </option>

                    </select>

                    <select id="statusFilter">

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="draft"
                            ${currentFilters.status === 'draft'
                ? 'selected'
                : ''
            }
                        >
                            Draft
                        </option>

                        <option
                            value="published"
                            ${currentFilters.status === 'published'
                ? 'selected'
                : ''
            }
                        >
                            Published
                        </option>

                        <option
                            value="paused"
                            ${currentFilters.status === 'paused'
                ? 'selected'
                : ''
            }
                        >
                            Paused
                        </option>

                        <option
                            value="archived"
                            ${currentFilters.status === 'archived'
                ? 'selected'
                : ''
            }
                        >
                            Archived
                        </option>

                    </select>

                </div>

                <div class="toolbar-right">

                    <span
                        style="
                            font-size:13px;
                            color:var(--admin-text-muted);
                        "
                    >
                        ${challenges.length} challenges
                    </span>

                    <a
                        href="${appUrl('/admin/challenge.php')}"
                        class="btn btn-success"
                    >
                        + Create Challenge
                    </a>

                </div>

            </div>


            <!-- Challenge Table -->

            ${challenges.length === 0

                ? `

                    <div class="admin-empty-state">

                        <div class="empty-icon">
                            📭
                        </div>

                        <div class="empty-text">
                            No challenges found
                        </div>

                        <div class="empty-hint">

                            ${currentFilters.search ||
                    currentFilters.category_id ||
                    currentFilters.difficulty ||
                    currentFilters.status

                    ? 'Try adjusting your filters.'

                    : 'Create your first challenge to get started.'
                }

                        </div>

                        ${!currentFilters.search &&
                    !currentFilters.category_id &&
                    !currentFilters.difficulty &&
                    !currentFilters.status

                    ? `

                                <div style="margin-top:12px;">

                                    <a
                                        href="${appUrl('/admin/challenge.php')}"
                                        class="btn btn-success"
                                    >
                                        + Create Challenge
                                    </a>

                                </div>

                            `

                    : ''
                }

                    </div>

                `

                : `

                    <div class="admin-table-wrap">

                        <table class="admin-table">

                            <thead>

                                <tr>

                                    <th>Challenge</th>
                                    <th>Category</th>
                                    <th>Difficulty</th>
                                    <th>Points</th>
                                    <th>Status</th>
                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                ${challenges.map(c => `

                                    <tr>

                                        <td>

                                            <a
                                                href="${appUrl(
                    `/admin/challenge.php?id=${c.id}`
                )}"
                                                style="
                                                    color:var(--admin-text);
                                                    text-decoration:none;
                                                "
                                            >
                                                ${escapeHtml(c.title)}
                                            </a>

                                        </td>

                                        <td>
                                            ${c.category
                        ? escapeHtml(
                            typeof c.category === 'string'
                                ? c.category
                                : c.category.name
                        )
                        : '-'
                    }
                                        </td>

                                        <td>

                                            <span
                                                class="diff-${escapeHtml(
                        c.difficulty || ''
                    )}"
                                            >
                                                ${escapeHtml(
                        c.difficulty || '-'
                    )}
                                            </span>

                                        </td>

                                        <td>
                                            ${c.points || 0}
                                        </td>

                                        <td>

                                            <span
                                                class="
                                                    status-badge
                                                    status-${escapeHtml(
                        c.status || 'draft'
                    )}
                                                "
                                            >
                                                ${escapeHtml(
                        c.status || 'draft'
                    )}
                                            </span>

                                        </td>

                                        <td>

                                            <div class="col-actions">

                                                <a
                                                    href="${appUrl(
                        `/admin/challenge.php?id=${c.id}`
                    )}"
                                                    class="btn btn-outline btn-xs"
                                                >
                                                    Edit
                                                </a>

                                                ${getActionButtons(c)}

                                            </div>

                                        </td>

                                    </tr>

                                `).join('')}

                            </tbody>

                        </table>

                    </div>

                `
            }

        `;

        bindFilters();
    }

    // ============================================================
    // ACTION BUTTONS
    // ============================================================

    function getActionButtons(challenge) {

        const status = challenge.status || 'draft';
        const id = challenge.id;

        let buttons = [];

        switch (status) {

            case 'draft':

                buttons.push(`
                    <button
                        class="btn btn-success btn-xs"
                        onclick="ChallengeActions.publish(${id})"
                    >
                        Publish
                    </button>
                `);

                buttons.push(`
                    <button
                        class="btn btn-danger btn-xs"
                        onclick="ChallengeActions.deleteChallenge(${id})"
                    >
                        Delete
                    </button>
                `);

                break;


            case 'published':

                buttons.push(`
                    <button
                        class="btn btn-warning btn-xs"
                        onclick="ChallengeActions.pause(${id})"
                    >
                        Pause
                    </button>
                `);

                buttons.push(`
                    <button
                        class="btn btn-danger btn-xs"
                        onclick="ChallengeActions.archive(${id})"
                    >
                        Archive
                    </button>
                `);

                break;


            case 'paused':

                buttons.push(`
                    <button
                        class="btn btn-success btn-xs"
                        onclick="ChallengeActions.publish(${id})"
                    >
                        Publish
                    </button>
                `);

                buttons.push(`
                    <button
                        class="btn btn-danger btn-xs"
                        onclick="ChallengeActions.archive(${id})"
                    >
                        Archive
                    </button>
                `);

                break;


            case 'archived':
                break;
        }

        return buttons.join('');
    }

    // ============================================================
    // FILTERS
    // ============================================================

    function bindFilters() {

        const searchInput =
            document.getElementById('searchInput');

        const categoryFilter =
            document.getElementById('categoryFilter');

        const difficultyFilter =
            document.getElementById('difficultyFilter');

        const statusFilter =
            document.getElementById('statusFilter');


        const applyFilters = () => {

            currentFilters.search =
                searchInput
                    ? searchInput.value.trim()
                    : '';

            currentFilters.category_id =
                categoryFilter
                    ? categoryFilter.value
                    : '';

            currentFilters.difficulty =
                difficultyFilter
                    ? difficultyFilter.value
                    : '';

            currentFilters.status =
                statusFilter
                    ? statusFilter.value
                    : '';

            loadData();
        };


        if (searchInput) {

            let timeout;

            searchInput.addEventListener(
                'input',
                function () {

                    clearTimeout(timeout);

                    timeout = setTimeout(
                        applyFilters,
                        300
                    );

                }
            );

        }


        if (categoryFilter) {
            categoryFilter.addEventListener(
                'change',
                applyFilters
            );
        }


        if (difficultyFilter) {
            difficultyFilter.addEventListener(
                'change',
                applyFilters
            );
        }


        if (statusFilter) {
            statusFilter.addEventListener(
                'change',
                applyFilters
            );
        }
    }

    // ============================================================
    // CHALLENGE ACTIONS
    // ============================================================

    window.ChallengeActions = {

        async publish(id) {

            const challenge =
                allChallenges.find(
                    c => c.id == id
                );

            if (!challenge) return;

            AdminModal.open(
                'Publish Challenge',

                `Publish "${challenge.title}"?

Participants will be able to see and solve this challenge.`,

                'Publish Challenge',

                async () => {

                    await updateStatus(
                        id,
                        'published'
                    );

                }
            );
        },


        async pause(id) {

            const challenge =
                allChallenges.find(
                    c => c.id == id
                );

            if (!challenge) return;

            AdminModal.open(
                'Pause Challenge',

                `Pause "${challenge.title}"?

The challenge will no longer be active.`,

                'Pause Challenge',

                async () => {

                    await updateStatus(
                        id,
                        'paused'
                    );

                }
            );
        },


        async archive(id) {

            const challenge =
                allChallenges.find(
                    c => c.id == id
                );

            if (!challenge) return;

            AdminModal.open(
                'Archive Challenge',

                `Archive "${challenge.title}"?

The challenge will be archived and no longer active.`,

                'Archive Challenge',

                async () => {

                    await updateStatus(
                        id,
                        'archived'
                    );

                }
            );
        },


        async deleteChallenge(id) {

            const challenge =
                allChallenges.find(
                    c => c.id == id
                );

            if (!challenge) return;

            AdminModal.open(
                'Delete Challenge',

                `Delete "${challenge.title}"?

This action permanently removes the challenge and cannot be undone.`,

                'Delete Challenge',

                async () => {

                    try {

                        const csrf =
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.content || '';


                        const response =
                            await fetch(
                                appUrl(
                                    `/api/v1/challenges/${id}`
                                ),
                                {
                                    method: 'DELETE',

                                    credentials: 'include',

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-CSRF-TOKEN':
                                            csrf
                                    }
                                }
                            );


                        const data =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                data.message ||
                                'Delete failed'
                            );

                        }


                        await loadData();

                    }

                    catch (error) {

                        console.error(
                            'Delete error:',
                            error
                        );

                        alert(
                            `Error: ${error.message ||
                            'Unable to delete challenge.'
                            }`
                        );

                    }

                }
            );
        }
    };

    // ============================================================
    // UPDATE STATUS
    // ============================================================

    async function updateStatus(id, status) {

        try {

            const csrf =
                document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content || '';


            let endpoint = '';

            if (status === 'published') {
                endpoint = 'publish';
            }

            else if (status === 'paused') {
                endpoint = 'pause';
            }

            else if (status === 'archived') {
                endpoint = 'archive';
            }

            else {

                alert(
                    'Invalid status transition'
                );

                return;
            }


            const response =
                await fetch(
                    appUrl(
                        `/api/v1/challenges/${id}/${endpoint}`
                    ),
                    {
                        method: 'POST',

                        credentials: 'include',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrf
                        }
                    }
                );


            const data =
                await response.json();


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Status update failed'
                );

            }


            await loadData();

        }

        catch (error) {

            console.error(
                'Status update error:',
                error
            );

            alert(
                `Error: ${error.message ||
                'Unable to update challenge status.'
                }`
            );

        }
    }

    // ============================================================
    // HELPERS
    // ============================================================

    function escapeHtml(text) {

        if (
            text === null ||
            text === undefined
        ) {
            return '';
        }

        const div =
            document.createElement('div');

        div.textContent = String(text);

        return div.innerHTML;
    }

    // ============================================================
    // INIT
    // ============================================================

    function init() {

        if (
            window.Admin &&
            window.Admin.isAuthorized()
        ) {

            loadData();

        }

        else {

            document.addEventListener(
                'admin:authReady',
                function (e) {

                    if (
                        e.detail &&
                        e.detail.authorized
                    ) {

                        loadData();

                    }

                }
            );

        }
    }

    init();

})();
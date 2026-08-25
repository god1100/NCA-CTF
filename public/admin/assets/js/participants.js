/*
 * NCA CTF
 * Participants Administration
 */

(() => {
    'use strict';

    const tableBody = document.getElementById('participantsTableBody');
    const emptyState = document.getElementById('participantsEmpty');

    const searchInput = document.getElementById('participantSearch');
    const statusFilter = document.getElementById('participantStatus');


    let participants = [];


    /*
     * ------------------------------------------------------------
     * API
     * ------------------------------------------------------------
     */

    async function loadParticipants() {

        setLoading();

        try {

            const response = await fetch(
                '/NCA-CTF/public/api/v1/admin/participants',
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const data = await response.json();

            if (!response.ok || data.success !== true) {
                throw new Error(
                    data?.error?.message ||
                    'Unable to load participants.'
                );
            }

            participants = data.data?.participants || [];

            render();

        } catch (error) {

            console.error('Participants load failed:', error);

            tableBody.innerHTML = `
                <tr>
                    <td colspan="7">
                        Unable to load participants.
                    </td>
                </tr>
            `;
        }
    }


    async function performAction(userId, action) {

        let message;

        if (action === 'delete') {
            message = 'Delete this participant account? This action cannot be undone.';
        }

        if (action === 'suspend') {
            message = 'Suspend this participant account?';
        }

        if (action === 'unsuspend') {
            message = 'Unsuspend this participant account?';
        }

        if (!message) {
            return;
        }

        if (!window.confirm(message)) {
            return;
        }


        try {

            const csrfToken = getCsrfToken();

            const response = await fetch(
                `/NCA-CTF/public/api/v1/admin/participants/${userId}/${action}`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        ...(csrfToken
                            ? { 'X-CSRF-Token': csrfToken }
                            : {})
                    },
                    body: JSON.stringify({})
                }
            );


            const data = await response.json();


            if (!response.ok || data.success !== true) {
                throw new Error(
                    data?.error?.message ||
                    `Unable to ${action} participant.`
                );
            }


            await loadParticipants();

        } catch (error) {

            console.error(`Participant ${action} failed:`, error);

            window.alert(error.message);
        }
    }


    /*
     * ------------------------------------------------------------
     * Rendering
     * ------------------------------------------------------------
     */

    function render() {

        const search = searchInput.value
            .trim()
            .toLowerCase();

        const status = statusFilter.value;


        const filtered = participants.filter((participant) => {

            const matchesSearch =
                search === '' ||
                String(participant.id).includes(search) ||
                String(participant.username || '')
                    .toLowerCase()
                    .includes(search) ||
                String(participant.email || '')
                    .toLowerCase()
                    .includes(search) ||
                String(participant.full_name || '')
                    .toLowerCase()
                    .includes(search);


            const matchesStatus =
                status === 'all' ||
                participant.status === status;


            return matchesSearch && matchesStatus;
        });


        if (filtered.length === 0) {

            tableBody.innerHTML = '';

            emptyState.hidden = false;

            return;
        }


        emptyState.hidden = true;


        tableBody.innerHTML = filtered
            .map(renderParticipant)
            .join('');
    }


    function renderParticipant(participant) {

        const id = escapeHtml(participant.id);
        const username = escapeHtml(participant.username);
        const email = escapeHtml(participant.email);
        const fullName = escapeHtml(
            participant.full_name || 'No name provided'
        );

        const status = String(
            participant.status || 'unknown'
        ).toLowerCase();


        const createdAt = formatDate(participant.created_at);
        const lastLogin = formatDate(participant.last_login_at);


        let actions = '';


        if (status === 'active') {

            actions = `
                <button
                    type="button"
                    class="participant-action"
                    data-action="suspend"
                    data-user-id="${id}"
                >
                    Suspend
                </button>

                <button
                    type="button"
                    class="participant-action participant-action--danger"
                    data-action="delete"
                    data-user-id="${id}"
                >
                    Delete
                </button>
            `;

        } else if (status === 'suspended') {

            actions = `
                <button
                    type="button"
                    class="participant-action"
                    data-action="unsuspend"
                    data-user-id="${id}"
                >
                    Unsuspend
                </button>

                <button
                    type="button"
                    class="participant-action participant-action--danger"
                    data-action="delete"
                    data-user-id="${id}"
                >
                    Delete
                </button>
            `;

        } else {

            actions = `
                <button
                    type="button"
                    class="participant-action participant-action--danger"
                    data-action="delete"
                    data-user-id="${id}"
                >
                    Delete
                </button>
            `;
        }


        return `
            <tr>

                <td>
                    ${id}
                </td>

                <td>
                    <div class="participant-identity">
                        <span class="participant-username">
                            ${username}
                        </span>

                        <span class="participant-full-name">
                            ${fullName}
                        </span>
                    </div>
                </td>

                <td>
                    ${email}
                </td>

                <td>
                    <span
                        class="participant-status participant-status--${escapeHtml(status)}"
                    >
                        ${escapeHtml(status)}
                    </span>
                </td>

                <td>
                    ${createdAt}
                </td>

                <td>
                    ${lastLogin}
                </td>

                <td>
                    <div class="participant-actions">
                        ${actions}
                    </div>
                </td>

            </tr>
        `;
    }


    function setLoading() {

        emptyState.hidden = true;

        tableBody.innerHTML = `
            <tr class="participants-loading">
                <td colspan="7">
                    Loading participants...
                </td>
            </tr>
        `;
    }


    /*
     * ------------------------------------------------------------
     * Events
     * ------------------------------------------------------------
     */

    searchInput.addEventListener(
        'input',
        render
    );


    statusFilter.addEventListener(
        'change',
        render
    );


    tableBody.addEventListener(
        'click',
        (event) => {

            const button = event.target.closest(
                '[data-action][data-user-id]'
            );

            if (!button) {
                return;
            }

            performAction(
                button.dataset.userId,
                button.dataset.action
            );
        }
    );


    /*
     * ------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------
     */

    function formatDate(value) {

        if (!value) {
            return 'Never';
        }

        const date = new Date(
            String(value).replace(' ', 'T')
        );

        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleString();
    }


    function escapeHtml(value) {

        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function getCsrfToken() {

        /*
         * The application currently exposes the CSRF token
         * through the authenticated API response/session flow.
         *
         * This helper supports a meta tag if we add one later.
         */

        const meta = document.querySelector(
            'meta[name="csrf-token"]'
        );

        return meta
            ? meta.getAttribute('content')
            : null;
    }


    /*
     * ------------------------------------------------------------
     * Initial load
     * ------------------------------------------------------------
     */

    loadParticipants();

})();

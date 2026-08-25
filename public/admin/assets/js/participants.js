/*
 * NCA CTF
 * Participants Administration
 *
 * GET    /api/v1/admin/participants
 * PATCH  /api/v1/admin/participants/{id}/status
 * DELETE /api/v1/admin/participants/{id}
 */

(() => {

    'use strict';


    document.addEventListener('DOMContentLoaded', () => {


        /*
         * ------------------------------------------------------------
         * Elements
         * ------------------------------------------------------------
         */

        const tableBody =
            document.getElementById(
                'participantsTableBody'
            );


        const modal =
            document.getElementById(
                'participantActionModal'
            );


        const modalTitle =
            document.getElementById(
                'participantModalTitle'
            );


        const modalMessage =
            document.getElementById(
                'participantModalMessage'
            );


        const modalConfirm =
            document.getElementById(
                'participantModalConfirm'
            );


        if (!tableBody) {

            console.error(
                'Participants: #participantsTableBody was not found.'
            );

            return;
        }


        if (!modal || !modalConfirm) {

            console.error(
                'Participants: action modal was not found.'
            );

            return;
        }


        /*
         * ------------------------------------------------------------
         * Configuration
         * ------------------------------------------------------------
         */

        const API_BASE =
            '/NCA-CTF/public/api/v1/admin/participants';


        let participants = [];


        /*
         * Current modal action
         */

        let pendingAction = null;


        /*
         * ------------------------------------------------------------
         * CSRF
         * ------------------------------------------------------------
         */

        function getCsrfToken() {

            const meta =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            if (!meta) {

                console.error(
                    'CSRF token meta tag not found.'
                );

                return null;
            }


            return meta.getAttribute('content');
        }


        /*
         * ------------------------------------------------------------
         * Load Participants
         * ------------------------------------------------------------
         */

        async function loadParticipants() {

            setLoading();


            try {

                const response =
                    await fetch(
                        API_BASE,
                        {
                            method: 'GET',

                            credentials: 'same-origin',

                            headers: {
                                'Accept':
                                    'application/json'
                            }
                        }
                    );


                const data =
                    await parseJsonResponse(
                        response
                    );


                if (
                    !response.ok ||
                    data.success !== true
                ) {

                    throw new Error(
                        getApiErrorMessage(
                            data,
                            'Unable to load participants.'
                        )
                    );
                }


                participants =
                    Array.isArray(
                        data.data?.participants
                    )
                        ? data.data.participants
                        : [];


                render();


            } catch (error) {

                console.error(
                    'Participants load failed:',
                    error
                );


                renderError(
                    error.message ||
                    'Unable to load participants.'
                );
            }
        }


        /*
         * ------------------------------------------------------------
         * Render
         * ------------------------------------------------------------
         */

        function render() {

            if (participants.length === 0) {

                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8">
                            No participants found.
                        </td>
                    </tr>
                `;

                return;
            }


            tableBody.innerHTML =
                participants
                    .map(renderParticipant)
                    .join('');
        }


        /*
         * ------------------------------------------------------------
         * Render Participant
         * ------------------------------------------------------------
         */

        function renderParticipant(participant) {

            const id =
                escapeHtml(
                    participant.id
                );


            const username =
                escapeHtml(
                    participant.username
                );


            const fullName =
                participant.full_name
                    ? escapeHtml(
                        participant.full_name
                    )
                    : '—';


            const email =
                escapeHtml(
                    participant.email
                );


            const status =
                String(
                    participant.status ||
                    'unknown'
                ).toLowerCase();


            const createdAt =
                formatDate(
                    participant.created_at
                );


            const lastLogin =
                formatDate(
                    participant.last_login_at
                );


            let actions = '';


            /*
             * Active participant
             */

            if (status === 'active') {

                actions = `

                    <button
                        type="button"
                        class="participant-action participant-action--warning"
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
            }


            /*
             * Suspended participant
             */

            else if (status === 'suspended') {

                actions = `

                    <button
                        type="button"
                        class="participant-action participant-action--success"
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
            }


            /*
             * Other status
             */

            else {

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
                        <strong>
                            ${username}
                        </strong>
                    </td>

                    <td>
                        ${fullName}
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


        /*
         * ------------------------------------------------------------
         * Loading
         * ------------------------------------------------------------
         */

        function setLoading() {

            tableBody.innerHTML = `

                <tr class="participants-loading">

                    <td colspan="8">
                        Loading participants...
                    </td>

                </tr>

            `;
        }


        /*
         * ------------------------------------------------------------
         * Error
         * ------------------------------------------------------------
         */

        function renderError(message) {

            tableBody.innerHTML = `

                <tr>

                    <td colspan="8">
                        ${escapeHtml(message)}
                    </td>

                </tr>

            `;
        }


        /*
         * ------------------------------------------------------------
         * Open Modal
         * ------------------------------------------------------------
         */

        function openActionModal(
            userId,
            action,
            button
        ) {

            let title = '';
            let message = '';
            let confirmText = 'Confirm';


            /*
             * Suspend
             */

            if (action === 'suspend') {

                title =
                    'Suspend Participant';

                message =
                    'Are you sure you want to suspend this participant account?';

                confirmText =
                    'Suspend';
            }


            /*
             * Unsuspend
             */

            else if (action === 'unsuspend') {

                title =
                    'Unsuspend Participant';

                message =
                    'Are you sure you want to restore this participant account?';

                confirmText =
                    'Unsuspend';
            }


            /*
             * Delete
             */

            else if (action === 'delete') {

                title =
                    'Delete Participant';

                message =
                    'Are you sure you want to permanently delete this participant account? This action cannot be undone.';

                confirmText =
                    'Delete';
            }


            else {

                return;
            }


            pendingAction = {

                userId,

                action,

                button
            };


            modalTitle.textContent =
                title;


            modalMessage.textContent =
                message;


            modalConfirm.textContent =
                confirmText;


            /*
             * Delete gets danger styling.
             */

            modalConfirm.classList.toggle(
                'participant-modal__button--danger',
                action === 'delete'
            );


            modalConfirm.classList.toggle(
                'participant-modal__button--confirm',
                action !== 'delete'
            );


            modal.classList.add(
                'participant-modal--open'
            );


            modal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.body.classList.add(
                'modal-open'
            );
        }


        /*
         * ------------------------------------------------------------
         * Close Modal
         * ------------------------------------------------------------
         */

        function closeActionModal() {

            if (
                pendingAction &&
                pendingAction.button
            ) {

                pendingAction.button.disabled =
                    false;
            }


            pendingAction = null;


            modal.classList.remove(
                'participant-modal--open'
            );


            modal.setAttribute(
                'aria-hidden',
                'true'
            );


            document.body.classList.remove(
                'modal-open'
            );
        }


        /*
         * ------------------------------------------------------------
         * Execute Action
         * ------------------------------------------------------------
         */

        async function executeAction() {

            if (!pendingAction) {
                return;
            }


            const {
                userId,
                action,
                button
            } = pendingAction;


            let method = '';
            let url =
                API_BASE + '/' + userId;

            let body = null;


            /*
             * Suspend
             */

            if (action === 'suspend') {

                method = 'PATCH';

                url =
                    `${API_BASE}/${userId}/status`;

                body = {
                    status: 'suspended'
                };
            }


            /*
             * Unsuspend
             */

            else if (action === 'unsuspend') {

                method = 'PATCH';

                url =
                    `${API_BASE}/${userId}/status`;

                body = {
                    status: 'active'
                };
            }


            /*
             * Delete
             */

            else if (action === 'delete') {

                method = 'DELETE';

                url =
                    `${API_BASE}/${userId}`;
            }


            else {

                closeActionModal();

                return;
            }


            /*
             * CSRF
             */

            const csrfToken =
                getCsrfToken();


            if (!csrfToken) {

                closeActionModal();


                window.alert(
                    'CSRF token is missing. Please refresh the page and try again.'
                );


                return;
            }


            /*
             * Disable controls.
             */

            modalConfirm.disabled =
                true;


            modalConfirm.textContent =
                'Processing...';


            if (button) {

                button.disabled =
                    true;
            }


            try {

                const headers = {

                    'Accept':
                        'application/json',

                    'X-CSRF-Token':
                        csrfToken
                };


                /*
                 * PATCH uses JSON.
                 */

                if (method === 'PATCH') {

                    headers[
                        'Content-Type'
                    ] =
                        'application/json';
                }


                const requestOptions = {

                    method,

                    credentials:
                        'same-origin',

                    headers
                };


                if (method === 'PATCH') {

                    requestOptions.body =
                        JSON.stringify(body);
                }


                const response =
                    await fetch(
                        url,
                        requestOptions
                    );


                const data =
                    await parseJsonResponse(
                        response
                    );


                if (
                    !response.ok ||
                    data.success !== true
                ) {

                    throw new Error(
                        getApiErrorMessage(
                            data,
                            `Unable to ${action} participant.`
                        )
                    );
                }


                /*
                 * Success
                 */

                closeActionModal();


                await loadParticipants();


            } catch (error) {

                console.error(
                    `Participant ${action} failed:`,
                    error
                );


                /*
                 * Close our custom modal first.
                 */

                closeActionModal();


                /*
                 * We deliberately use alert ONLY for
                 * unexpected server errors.
                 */

                showError(
                    error.message ||
                    `Unable to ${action} participant.`
                );
            }

        }


        /*
         * ------------------------------------------------------------
         * Error Message
         * ------------------------------------------------------------
         *
         * This is intentionally separate from the confirmation modal.
         * We can later replace this with a toast without touching the
         * action confirmation flow.
         */

        function showError(message) {

            console.error(
                'Participant action error:',
                message
            );


            /*
             * Re-open the modal as an error dialog.
             */

            modalTitle.textContent =
                'Action Failed';


            modalMessage.textContent =
                message;


            modalConfirm.textContent =
                'Close';


            modalConfirm.disabled =
                false;


            modalConfirm.classList.remove(
                'participant-modal__button--danger'
            );


            modalConfirm.classList.add(
                'participant-modal__button--confirm'
            );


            pendingAction = null;


            modal.classList.add(
                'participant-modal--open'
            );


            modal.setAttribute(
                'aria-hidden',
                'false'
            );


            document.body.classList.add(
                'modal-open'
            );


            modalConfirm.onclick =
                () => {

                    modalConfirm.onclick =
                        executeAction;

                    closeActionModal();

                };
        }


        /*
         * ------------------------------------------------------------
         * Modal Events
         * ------------------------------------------------------------
         */

        modal.addEventListener(
            'click',
            (event) => {

                const closeButton =
                    event.target.closest(
                        '[data-modal-close]'
                    );


                if (!closeButton) {
                    return;
                }


                closeActionModal();
            }
        );


        /*
         * Confirm button.
         */

        modalConfirm.addEventListener(
            'click',
            () => {

                if (!pendingAction) {

                    closeActionModal();

                    return;
                }


                executeAction();
            }
        );


        /*
         * Escape key closes modal.
         */

        document.addEventListener(
            'keydown',
            (event) => {

                if (
                    event.key === 'Escape' &&
                    modal.classList.contains(
                        'participant-modal--open'
                    )
                ) {

                    closeActionModal();
                }
            }
        );


        /*
         * ------------------------------------------------------------
         * Table Event Delegation
         * ------------------------------------------------------------
         */

        tableBody.addEventListener(
            'click',
            (event) => {

                const button =
                    event.target.closest(
                        '[data-action][data-user-id]'
                    );


                if (!button) {
                    return;
                }


                const action =
                    button.dataset.action;


                const userId =
                    button.dataset.userId;


                if (
                    !userId ||
                    !action
                ) {

                    return;
                }


                /*
                 * IMPORTANT:
                 *
                 * No window.confirm().
                 *
                 * Everything goes through our custom modal.
                 */

                openActionModal(
                    userId,
                    action,
                    button
                );
            }
        );


        /*
         * ------------------------------------------------------------
         * API Helpers
         * ------------------------------------------------------------
         */

        async function parseJsonResponse(
            response
        ) {

            const text =
                await response.text();


            if (!text) {
                return {};
            }


            try {

                return JSON.parse(text);

            } catch (error) {

                console.error(
                    'Invalid JSON response:',
                    text
                );


                throw new Error(
                    `Server returned invalid JSON (HTTP ${response.status}).`
                );
            }
        }


        function getApiErrorMessage(
            data,
            fallback
        ) {

            if (
                data &&
                data.error &&
                typeof data.error.message === 'string'
            ) {

                return data.error.message;
            }


            if (
                data &&
                Array.isArray(data.errors) &&
                data.errors.length > 0
            ) {

                return data.errors.join(' ');
            }


            return fallback;
        }


        /*
         * ------------------------------------------------------------
         * Formatting
         * ------------------------------------------------------------
         */

        function formatDate(value) {

            if (!value) {
                return 'Never';
            }


            const normalized =
                String(value)
                    .replace(
                        ' ',
                        'T'
                    );


            const date =
                new Date(
                    normalized
                );


            if (
                Number.isNaN(
                    date.getTime()
                )
            ) {

                return escapeHtml(value);
            }


            return escapeHtml(
                date.toLocaleString()
            );
        }


        function escapeHtml(value) {

            return String(
                value ?? ''
            )

                .replaceAll(
                    '&',
                    '&amp;'
                )

                .replaceAll(
                    '<',
                    '&lt;'
                )

                .replaceAll(
                    '>',
                    '&gt;'
                )

                .replaceAll(
                    '"',
                    '&quot;'
                )

                .replaceAll(
                    "'",
                    '&#039;'
                );
        }


        /*
         * ------------------------------------------------------------
         * Initial Load
         * ------------------------------------------------------------
         */

        loadParticipants();

    });

})();
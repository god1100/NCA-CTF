document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
       ELEMENTS
       ============================================================ */

    const profileButton =
        document.getElementById('adminProfileButton');

    const profileDropdown =
        document.getElementById('adminProfileDropdown');

    const logoutButton =
        document.getElementById('adminLogoutButton');

    const logoutModal =
        document.getElementById('adminLogoutModal');

    const confirmLogout =
        document.getElementById('adminConfirmLogout');

    const closeLogoutElements =
        document.querySelectorAll('[data-close-logout]');


    /* ============================================================
       PROFILE DROPDOWN
       ============================================================ */

    function openProfileDropdown() {

        if (!profileButton || !profileDropdown) {
            return;
        }

        profileButton.setAttribute(
            'aria-expanded',
            'true'
        );

        profileDropdown.hidden = false;
    }


    function closeProfileDropdown() {

        if (!profileButton || !profileDropdown) {
            return;
        }

        profileButton.setAttribute(
            'aria-expanded',
            'false'
        );

        profileDropdown.hidden = true;
    }


    function toggleProfileDropdown() {

        if (!profileButton || !profileDropdown) {
            return;
        }

        const isOpen =
            profileButton.getAttribute('aria-expanded') === 'true';

        if (isOpen) {
            closeProfileDropdown();
        } else {
            openProfileDropdown();
        }
    }


    if (profileButton) {

        profileButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                toggleProfileDropdown();

            }
        );

    }


    /* ============================================================
       LOGOUT MODAL
       ============================================================ */

    function openLogoutModal() {

        if (!logoutModal) {
            return;
        }

        logoutModal.hidden = false;

        document.body.classList.add(
            'admin-modal-open'
        );

        /*
         * Put focus on Cancel initially so the modal
         * is keyboard accessible.
         */
        const cancelButton =
            logoutModal.querySelector(
                '[data-close-logout].btn'
            );

        if (cancelButton) {
            setTimeout(function () {
                cancelButton.focus();
            }, 50);
        }
    }


    function closeLogoutModal() {

        if (!logoutModal) {
            return;
        }

        logoutModal.hidden = true;

        document.body.classList.remove(
            'admin-modal-open'
        );
    }


    /* ============================================================
       OPEN LOGOUT MODAL
       ============================================================ */

    if (logoutButton) {

        logoutButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                closeProfileDropdown();

                openLogoutModal();

            }
        );

    }


    /* ============================================================
       CLOSE LOGOUT MODAL
       ============================================================ */

    closeLogoutElements.forEach(function (element) {

        element.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                closeLogoutModal();

            }
        );

    });


    /* ============================================================
       CONFIRM LOGOUT
       ============================================================ */

    if (confirmLogout) {

        confirmLogout.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                /*
                 * Backend logout will be connected here.
                 *
                 * For now, this proves that the confirmation
                 * button itself is working.
                 */

                confirmLogout.disabled = true;
                confirmLogout.textContent = 'Logging out...';

                /*
                 * TEMPORARY:
                 * Return to the login page.
                 *
                 * We will replace this with the real
                 * backend session logout once the admin
                 * authentication flow is cleaned up.
                 */

                window.location.href =
                    '/NCA-CTF/public/login.php';

            }
        );

    }


    /* ============================================================
       CLICK OUTSIDE PROFILE
       ============================================================ */

    document.addEventListener(
        'click',
        function (event) {

            if (!profileButton || !profileDropdown) {
                return;
            }

            if (
                !profileButton.contains(event.target) &&
                !profileDropdown.contains(event.target)
            ) {

                closeProfileDropdown();

            }

        }
    );


    /* ============================================================
       ESCAPE KEY
       ============================================================ */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }


            /* Close logout modal */

            if (
                logoutModal &&
                !logoutModal.hidden
            ) {

                closeLogoutModal();

                return;
            }


            /* Close profile dropdown */

            closeProfileDropdown();

        }
    );

});
<header class="admin-header">
    <div class="admin-header__left">

        <a
            href="/NCA-CTF/public/admin/index.php"
            class="admin-brand"
        >
            <span class="admin-brand__main">NCA</span>
            <span class="admin-brand__ctf">CTF</span>
        </a>

        <span class="admin-header__divider"></span>

        <span class="admin-header__section">
            Administration
        </span>

    </div>


    <div class="admin-header__right">

        <!-- Admin Profile -->
        <div class="admin-profile">

            <button
                type="button"
                class="admin-profile__button"
                id="adminProfileButton"
                aria-expanded="false"
                aria-haspopup="true"
            >
                <span class="admin-profile__name">
                    Admin
                </span>

                <span
                    class="admin-profile__arrow"
                    aria-hidden="true"
                >
                    ▼
                </span>
            </button>


            <!-- Profile Dropdown -->
            <div
                class="admin-profile__dropdown"
                id="adminProfileDropdown"
                hidden
            >

                <div class="admin-profile__dropdown-header">
                    <span class="admin-profile__dropdown-name">
                        Admin
                    </span>
                </div>

                <div class="admin-profile__dropdown-divider"></div>

                <button
                    type="button"
                    class="admin-profile__logout"
                    id="adminLogoutButton"
                >
                    Logout
                </button>

            </div>

        </div>

    </div>

</header>


<!-- ============================================================
     LOGOUT CONFIRMATION MODAL
     ============================================================ -->

<div
    class="admin-logout-modal"
    id="adminLogoutModal"
    hidden
>

    <!-- Modal backdrop -->
    <div
        class="admin-modal__backdrop"
        data-close-logout
        aria-hidden="true"
    ></div>


    <!-- Modal -->
    <div
        class="admin-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logoutModalTitle"
    >

        <!-- Modal Header -->
        <div class="admin-modal__header">

            <h2 id="logoutModalTitle">
                Confirm Logout
            </h2>

            <button
                type="button"
                class="admin-modal__close"
                data-close-logout
                aria-label="Close logout confirmation"
            >
                ×
            </button>

        </div>


        <!-- Modal Body -->
        <div class="admin-modal__body">

            <p>
                Are you sure you want to logout?
            </p>

        </div>


        <!-- Modal Footer -->
        <div class="admin-modal__footer">

            <button
                type="button"
                class="btn btn--secondary"
                data-close-logout
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn btn--danger"
                id="adminConfirmLogout"
            >
                Logout
            </button>

        </div>

    </div>

</div>
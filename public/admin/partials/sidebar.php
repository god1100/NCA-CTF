<aside class="admin-sidebar" id="adminSidebar">

    <div class="admin-sidebar__top">

        <div class="admin-sidebar__label">
            MAIN
        </div>

        <nav class="admin-sidebar__nav">

            <a
                href="/NCA-CTF/public/admin/index.php"
                class="admin-sidebar__link <?= ($adminPage ?? '') === 'dashboard' ? 'active' : '' ?>"
            >
                <span class="admin-sidebar__icon">⌂</span>
                <span>Dashboard</span>
            </a>

        </nav>

        <div class="admin-sidebar__label">
            CTF MANAGEMENT
        </div>

        <nav class="admin-sidebar__nav">

            <a
                href="/NCA-CTF/public/admin/challenges.php"
                class="admin-sidebar__link <?= ($adminPage ?? '') === 'challenges' ? 'active' : '' ?>"
            >
                <span class="admin-sidebar__icon">▣</span>
                <span>Challenges</span>
            </a>

        </nav>

        <div class="admin-sidebar__label">
            USERS
        </div>

        <nav class="admin-sidebar__nav">

            <a
                href="/NCA-CTF/public/admin/participants.php"
                class="admin-sidebar__link <?= ($adminPage ?? '') === 'participants' ? 'active' : '' ?>"
            >
                <span class="admin-sidebar__icon">♙</span>
                <span>Participants</span>
            </a>

        </nav>

    </div>

    <div class="admin-sidebar__bottom">
        <div class="admin-sidebar__version">
            NCA CTF
            <span>Administration Panel</span>
        </div>
    </div>

</aside>
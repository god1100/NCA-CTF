<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="csrf-token"
        content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
    >

    <title>Admin Dashboard - NCA CTF</title>

    <link
        rel="stylesheet"
        href="/NCA-CTF/public/assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/admin.css"
    >
</head>

<body class="admin-body">

    <!-- Sidebar -->
    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-sidebar-brand">

            <img
                src="/NCA-CTF/public/assets/images/NCA-logo.jpg"
                alt="NCA Logo"
            >

            <div class="admin-sidebar-brand-text">

                <span class="brand-nca">
                    NCA
                </span>

                <span class="brand-ctf">
                    CTF Administration
                </span>

            </div>

        </div>

        <nav class="admin-sidebar-nav">

            <div class="nav-label">
                Navigation
            </div>

            <a
                href="/NCA-CTF/public/admin/index.php"
                class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>"
            >
                <span class="nav-icon">📊</span>
                Dashboard
            </a>

            <a
                href="/NCA-CTF/public/admin/challenges.php"
                class="<?php echo $currentPage === 'challenges' ? 'active' : ''; ?>"
            >
                <span class="nav-icon">🏆</span>
                Challenges
            </a>

            <div class="nav-divider"></div>

            <a
                href="#"
                id="adminLogoutBtn"
                class="nav-logout"
            >
                <span class="nav-icon">🚪</span>
                Logout
            </a>

        </nav>

    </aside>


    <!-- Mobile overlay -->
    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>


    <!-- Mobile toggle -->
    <button
        class="admin-sidebar-toggle"
        id="adminSidebarToggle"
        aria-label="Toggle sidebar"
    >
        ☰
    </button>


    <!-- Main content -->
    <main class="admin-main">

        <header class="admin-header">

            <div class="admin-header-title">

                <h1>
                    Dashboard
                </h1>

                <p class="subtitle">
                    NCA CTF Administration
                </p>

            </div>


            <div class="admin-header-user">

                <span
                    class="user-role"
                    id="adminUserRole"
                >
                    Loading...
                </span>

                <span
                    class="user-name"
                    id="adminUserName"
                >
                    Loading...
                </span>

                <span
                    class="user-avatar"
                    id="adminUserAvatar"
                >
                    U
                </span>

            </div>

        </header>


        <div
            class="admin-content"
            id="adminContent"
        >

            <!-- Dashboard content will be rendered by JavaScript -->

            <div class="admin-loading">

                <div class="spinner"></div>

                Loading dashboard...

            </div>

        </div>

    </main>


    <script src="/NCA-CTF/public/assets/js/api.js"></script>

    <script src="/NCA-CTF/public/admin/assets/js/admin.js"></script>

    <script src="/NCA-CTF/public/admin/assets/js/dashboard.js"></script>

</body>
</html>
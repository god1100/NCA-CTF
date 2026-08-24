<?php
require_once __DIR__ . '/admin-check.php';

$pageTitle = 'Challenge';
$currentPage = 'challenges';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Admin Challenge - NCA CTF</title>
    <link rel="stylesheet" href="/NCA-CTF/public/assets/css/style.css">
    <link rel="stylesheet" href="/NCA-CTF/public/admin/assets/css/admin.css">
</head>
<body class="admin-body">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <img src="/NCA-CTF/public/assets/images/NCA-logo.jpg" alt="NCA Logo">
            <div class="admin-sidebar-brand-text">
                <span class="brand-nca">NCA</span>
                <span class="brand-ctf">CTF Administration</span>
            </div>
        </div>
        <nav class="admin-sidebar-nav">
            <div class="nav-label">Navigation</div>
            <a href="/NCA-CTF/public/admin/index.php">📊 Dashboard</a>
            <a href="/NCA-CTF/public/admin/challenges.php" class="active">🏆 Challenges</a>
            <div class="nav-divider"></div>
            <a href="#" id="adminLogoutBtn" class="nav-logout">🚪 Logout</a>
        </nav>
    </aside>

    <!-- Mobile overlay -->
    <div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

    <!-- Mobile toggle -->
    <button class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle sidebar">
        ☰
    </button>

    <!-- Main content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="admin-header-title">
                <h1 id="pageTitle">Create Challenge</h1>
                <p class="subtitle" id="pageSubtitle">Add a new challenge to the competition.</p>
            </div>
            <div class="admin-header-user">
                <span class="user-role" id="adminUserRole">Loading...</span>
                <span class="user-name" id="adminUserName">Loading...</span>
                <span class="user-avatar" id="adminUserAvatar">U</span>
            </div>
        </header>

        <div class="admin-content" id="adminContent">
            <div class="admin-loading">
                <div class="spinner"></div>
                Loading...
            </div>
        </div>
    </main>

    <!-- Modal Container -->
    <div class="admin-modal-overlay" id="adminModal">
        <div class="admin-modal">
            <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
            <p class="modal-message" id="modalMessage">Are you sure?</p>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="AdminModal.close()">Cancel</button>
                <button class="btn btn-danger" id="modalConfirmBtn">Confirm</button>
            </div>
        </div>
    </div>

    <script src="/NCA-CTF/public/assets/js/api.js"></script>
    <script src="/NCA-CTF/public/admin/assets/js/admin.js"></script>
    <script src="/NCA-CTF/public/admin/assets/js/challenge.js"></script>
</body>
</html>
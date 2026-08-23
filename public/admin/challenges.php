<?php
require_once __DIR__ . '/admin-check.php'; 
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: /login.php');
    exit;
}

$allowedRoles = ['challenge_admin', 'super_admin'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    // Not admin - redirect to dashboard
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <title>Admin Challenges - NCA CTF</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
</head>
<body class="admin-body">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <img src="/assets/images/NCA-logo.jpg" alt="NCA Logo">
            <div class="admin-sidebar-brand-text">
                <span class="brand-nca">NCA</span>
                <span class="brand-ctf">CTF Administration</span>
            </div>
        </div>
        <nav class="admin-sidebar-nav">
            <div class="nav-label">Navigation</div>
            <a href="/admin/index.php">📊 Dashboard</a>
            <a href="/admin/challenges.php" class="active">🏆 Challenges</a>
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
                <h1>Challenges</h1>
                <p class="subtitle">Manage challenges available in the competition.</p>
            </div>
            <div class="admin-header-user">
                <span class="user-role" id="adminUserRole">Loading...</span>
                <span class="user-name" id="adminUserName">Loading...</span>
                <span class="user-avatar" id="adminUserAvatar">U</span>
            </div>
        </header>

        <div class="admin-content" id="adminContent">
            <!-- Content rendered by JavaScript -->
            <div class="admin-loading">
                <div class="spinner"></div>
                Loading challenges...
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

    <script src="/assets/js/api.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
    <script src="/admin/assets/js/challenges.js"></script>
</body>
</html>
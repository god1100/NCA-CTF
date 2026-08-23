<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug - show session data
// Uncomment to see what's in session
// echo "<pre>"; print_r($_SESSION); echo "</pre>"; exit;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header('Location: /NCA-CTF/public/login.php');
    exit;
}

// role_id 2 = challenge_admin, 3 = super_admin
if (!in_array($_SESSION['role_id'], [2, 3])) {
    header('Location: /NCA-CTF/public/dashboard.php');
    exit;
}
?>
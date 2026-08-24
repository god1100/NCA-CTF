<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: /NCA-CTF/public/login.php');
    exit;
}

/*
 * Role IDs:
 * 2 = challenge_admin
 * 3 = super_admin
 */
$adminRoles = [2, 3];

if (!in_array((int) $_SESSION['role_id'], $adminRoles, true)) {
    header('Location: /NCA-CTF/public/dashboard.php');
    exit;
}
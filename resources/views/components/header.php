<?php
// Start session ONLY if not already active - MUST be at the VERY TOP before ANY HTML
if (session_status() === PHP_SESSION_NONE) {
    session_name('nca_ctf_session');
    session_start();
}

$baseUrl = $GLOBALS['baseUrl'] ?? '';
$currentPage = $currentPage ?? '';
?>
<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar__container">
        <div class="navbar__brand">
            <a href="<?= $baseUrl ?>/" class="navbar__logo-link">
                <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" class="navbar__logo" width="40" height="40">
                <span class="navbar__brand-text">NCA <span class="brand__ctf">CTF</span></span>
            </a>
        </div>

        <button class="navbar__toggle" id="navToggle" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar__toggle-icon"></span>
            <span class="navbar__toggle-icon"></span>
            <span class="navbar__toggle-icon"></span>
        </button>

        <div class="navbar__menu" id="navMenu">
            <ul class="navbar__links">
                <li><a href="<?= $baseUrl ?>/" <?= $currentPage === 'home' ? 'class="active"' : '' ?>>Home</a></li>
                <li><a href="<?= $baseUrl ?>/challenges.php" <?= $currentPage === 'challenges' ? 'class="active"' : '' ?>>Challenges</a></li>
                <li><a href="<?= $baseUrl ?>/leaderboard.php" <?= $currentPage === 'leaderboard' ? 'class="active"' : '' ?>>Leaderboard</a></li>
                <li><a href="<?= $baseUrl ?>/about.php" <?= $currentPage === 'about' ? 'class="active"' : '' ?>>About</a></li>
            </ul>
            <div class="navbar__actions" id="navActions">
                <!-- Populated by JavaScript based on auth state -->
            </div>
        </div>
    </div>
</nav>

<!-- ============================================================== -->
<!-- CHANGE PASSWORD MODAL (Global)                                 -->
<!-- ============================================================== -->
<div class="modal-overlay" id="changePasswordModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
            <button class="modal-close" id="closeModalBtn">&times;</button>
        </div>
        <div class="modal-body">
            <form id="changePasswordForm" novalidate>
                <div class="form-group">
                    <label for="currentPassword">Current Password <span class="required-star">*</span></label>
                    <input type="password" id="currentPassword" placeholder="Enter your current password" required>
                    <span class="error-message" id="currentPasswordError"></span>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password <span class="required-star">*</span></label>
                    <input type="password" id="newPassword" placeholder="Min 6 characters" required>
                    <span class="error-message" id="newPasswordError"></span>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password <span class="required-star">*</span></label>
                    <input type="password" id="confirmPassword" placeholder="Confirm your new password" required>
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>
                <div id="passwordFormMessage" class="form-message" role="alert" aria-live="polite"></div>
                <button type="submit" class="btn btn--primary btn--full" id="changePasswordSubmit">
                    <span class="btn-text">Update Password</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </form>
        </div>
    </div>
</div>
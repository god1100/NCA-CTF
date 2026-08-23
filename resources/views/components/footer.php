<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '';
?>
<footer class="footer">
    <div class="container footer__container">
        <div class="footer__brand">
            <img src="<?= $baseUrl ?>/assets/images/NCA-logo.jpg" alt="NCA CTF" width="28" height="28">
            <span class="footer__brand-text">NCA <span class="brand__ctf">CTF</span></span>
            <span class="footer__tagline">| Batch 4</span>
        </div>
        <div class="footer__copyright">
            &copy; <?= date('Y') ?> <strong>NCA@Nepal</strong>
        </div>
    </div>
</footer>
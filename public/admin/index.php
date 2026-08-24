<?php

declare(strict_types=1);

$adminPage = 'dashboard';

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NCA CTF — Admin Dashboard</title>

    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/admin-base.css"
    >

    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/admin-layout.css"
    >

</head>

<body>

<div class="admin-shell">

    <?php require __DIR__ . '/partials/header.php'; ?>

    <?php require __DIR__ . '/partials/sidebar.php'; ?>


    <main class="admin-main">

        <div class="admin-main__content">

            <!--
                Dashboard content will be built
                in the next step.

                For now we are testing only:
                - Header
                - Sidebar
                - Profile dropdown
                - Logout confirmation
            -->

        </div>

    </main>

</div>


<script
    src="/NCA-CTF/public/admin/assets/js/admin.js"
></script>

</body>
</html>
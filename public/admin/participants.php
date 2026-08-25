<?php

declare(strict_types=1);

$adminPage = 'participants';

require __DIR__ . '/admin-check.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NCA CTF — Participants</title>

    <!-- Universal Admin Styles -->
    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/admin-base.css"
    >

    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/admin-layout.css"
    >

    <!-- Participants Page Styles -->
    <link
        rel="stylesheet"
        href="/NCA-CTF/public/admin/assets/css/participants.css"
    >
</head>

<body>

<div class="admin-shell">

    <!-- Universal Admin Header -->
    <?php require __DIR__ . '/partials/header.php'; ?>

    <!-- Universal Admin Sidebar -->
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Admin Content -->
    <main class="admin-main">

        <div class="admin-main__content">

            <section class="participants-page">

                <div class="participants-page__header">
                    <div>
                        <h1>Participants</h1>

                        <p>
                            Manage registered participants of the NCA CTF.
                        </p>
                    </div>
                </div>

                <div class="participants-table-wrapper">

                    <table class="participants-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="participantsTableBody">

                            <tr>
                                <td colspan="8">
                                    Loading participants...
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

</div>

<!-- Universal Admin JavaScript -->
<script src="/NCA-CTF/public/admin/assets/js/admin.js"></script>

<!-- Participants Page JavaScript -->
<script src="/NCA-CTF/public/admin/assets/js/participants.js"></script>

</body>
</html>
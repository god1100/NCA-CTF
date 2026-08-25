<?php

declare(strict_types=1);

$adminPage = 'participants';

require __DIR__ . '/admin-check.php';

use App\Infrastructure\Csrf;

$csrfToken = Csrf::token();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="<?= htmlspecialchars(
            $csrfToken,
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
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


                <!-- Participants Table -->

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

                            <tr class="participants-loading">

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


<!-- ============================================================
     PARTICIPANT ACTION MODAL
============================================================ -->

<div
    id="participantActionModal"
    class="participant-modal"
    aria-hidden="true"
>

    <div
        class="participant-modal__backdrop"
        data-modal-close
    ></div>


    <div
        class="participant-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="participantModalTitle"
    >

        <div class="participant-modal__header">

            <h2 id="participantModalTitle">
                Confirm Action
            </h2>

            <button
                type="button"
                class="participant-modal__close"
                data-modal-close
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <div class="participant-modal__body">

            <p id="participantModalMessage">
                Are you sure you want to continue?
            </p>

        </div>


        <div class="participant-modal__footer">

            <button
                type="button"
                class="participant-modal__button participant-modal__button--cancel"
                data-modal-close
            >
                Cancel
            </button>

            <button
                type="button"
                id="participantModalConfirm"
                class="participant-modal__button participant-modal__button--confirm"
            >
                Confirm
            </button>

        </div>

    </div>

</div>


<!-- Universal Admin JavaScript -->

<script src="/NCA-CTF/public/admin/assets/js/admin.js"></script>


<!-- Participants JavaScript -->

<script src="/NCA-CTF/public/admin/assets/js/participants.js"></script>

</body>

</html>
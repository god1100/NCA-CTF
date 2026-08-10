<?php

declare(strict_types=1);

/**
 * Admin panel placeholder.
 *
 * Intentionally contains NO authentication, authorization, or admin
 * functionality. The full admin control center (users, teams, challenge
 * manager, submission monitor, integrity center, containers, audit logs,
 * settings) is specified in docs/ctf3.txt §31-45 and implemented in
 * Phase 7 (docs/ctf9.txt §31).
 *
 * This file exists only so the approved project structure is present
 * from Phase 0 onward.
 */

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NCA Batch 4 CTF — Admin (Not Yet Implemented)</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        body {
            background: #0B0F14;
            color: #F3F4F6;
            font-family: system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .box {
            border: 1px solid #25303B;
            background: #151C24;
            border-radius: 10px;
            padding: 2rem;
            max-width: 420px;
        }
        code { color: #22C55E; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Admin Control Center</h1>
        <p>The administrator panel has not been implemented yet.</p>
        <p>Admin functionality (users, teams, challenge management, integrity
        center, audit logs, settings) is planned for <code>Phase 7</code>.</p>
    </div>
</body>
</html>

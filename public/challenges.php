<?php

$baseUrl = $GLOBALS['baseUrl'] ?? '/NCA-CTF/public';
$currentPage = 'challenges';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenges — NCA Batch 4 CTF</title>
    <meta name="description" content="Browse NCA Batch 4 CTF challenges">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <script>
        window.NCA_CTF_BASE_URL = '<?= $baseUrl ?>';
    </script>

    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/home.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/challenges.css">
</head>
<body>

    <!-- Include Header -->
    <?php include __DIR__ . '/../resources/views/components/header.php'; ?>

    <main class="challenges-page">
        <div class="container">
            <h1 class="challenges-page__title">Challenges</h1>

            <div class="filters">
                <label class="filters__field">
                    Category
                    <select id="filter-category">
                        <option value="">All</option>
                    </select>
                </label>
                <label class="filters__field">
                    Difficulty
                    <select id="filter-difficulty">
                        <option value="">All</option>
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                        <option value="insane">Insane</option>
                    </select>
                </label>
            </div>

            <div id="challenge-grid" class="challenge-grid" aria-live="polite"></div>

            <p id="empty-state" class="empty-state" hidden>No challenges found. Try changing your filters.</p>

            <div class="pagination" id="pagination" hidden>
                <button class="btn" id="prev-page">Previous</button>
                <span id="page-indicator"></span>
                <button class="btn" id="next-page">Next</button>
            </div>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include __DIR__ . '/../resources/views/components/footer.php'; ?>

    <script src="<?= $baseUrl ?>/assets/js/api.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/home.js"></script>
    <script src="<?= $baseUrl ?>/assets/js/challenges.js"></script>
</body>
</html>
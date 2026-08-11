<?php

declare(strict_types=1);

/**
 * Seed entry point. Runs every seeder file in database/seeders/ in
 * filename order.
 *
 * Usage:
 *   php database/seed.php
 */

$root = dirname(__DIR__);

$seeders = glob($root . '/database/seeders/*.php');
sort($seeders);

if ($seeders === false || count($seeders) === 0) {
    fwrite(STDERR, "No seeder files found in database/seeders/\n");
    exit(1);
}

foreach ($seeders as $seeder) {
    echo 'Running seeder: ' . basename($seeder) . "\n";
    require $seeder;
    echo "\n";
}

-- Challenge metadata only. No flag data lives here -- see 0010_create_flags_table.
-- Schema only in Phase 1; CRUD logic arrives in Phase 4 (docs/ctf4.txt §12).
CREATE TABLE IF NOT EXISTS challenges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    description TEXT NULL,
    difficulty ENUM('easy', 'medium', 'hard', 'insane') NOT NULL,
    points INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('draft', 'testing', 'published', 'running', 'paused', 'archived') NOT NULL DEFAULT 'draft',
    deployment_type ENUM('DOWNLOAD', 'HTTP', 'TCP') NOT NULL,
    author_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    UNIQUE KEY uq_challenges_slug (slug),
    KEY idx_challenges_category_id (category_id),
    KEY idx_challenges_status (status),
    KEY idx_challenges_difficulty (difficulty),
    CONSTRAINT fk_challenges_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_challenges_author
        FOREIGN KEY (author_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

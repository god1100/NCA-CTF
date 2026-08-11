-- Optional per-challenge hints with a point penalty. Reveal logic and
-- per-team reveal tracking arrive later (docs/ctf4.txt §14, ctf9.txt §18).
CREATE TABLE IF NOT EXISTS challenge_hints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NULL,
    content TEXT NOT NULL,
    point_penalty INT UNSIGNED NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_challenge_hints_challenge_id (challenge_id),
    CONSTRAINT fk_challenge_hints_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

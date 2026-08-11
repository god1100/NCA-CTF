-- UNIQUE(challenge_id) guarantees only one first blood can ever exist per
-- challenge, race-condition safe at the database level (docs/ctf4.txt §19,
-- ctf9.txt §16).
CREATE TABLE IF NOT EXISTS first_bloods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    bonus_points INT UNSIGNED NOT NULL DEFAULT 0,
    solved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_first_bloods_challenge (challenge_id),
    KEY idx_first_bloods_team_id (team_id),
    CONSTRAINT fk_first_bloods_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_first_bloods_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_first_bloods_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Successful solves. UNIQUE(team_id, challenge_id) is the database-level
-- guarantee against duplicate scoring, independent of any application-layer
-- check (docs/ctf4.txt §17, ctf9.txt §16/§21).
CREATE TABLE IF NOT EXISTS solves (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    challenge_id BIGINT UNSIGNED NOT NULL,
    first_solved_by BIGINT UNSIGNED NULL,
    points_awarded INT NOT NULL DEFAULT 0,
    solved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_solves_team_challenge (team_id, challenge_id),
    KEY idx_solves_challenge_id (challenge_id),
    KEY idx_solves_solved_at (solved_at),
    CONSTRAINT fk_solves_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_solves_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_solves_first_solved_by
        FOREIGN KEY (first_solved_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

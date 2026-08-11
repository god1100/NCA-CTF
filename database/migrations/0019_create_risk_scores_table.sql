-- Dynamic risk scores. An investigation aid only -- never an automatic
-- punishment score (docs/ctf7.txt §11, ctf9.txt §9). Calculation logic is
-- NOT implemented in this phase; calculation_version lets future scoring
-- changes be tracked against historical scores.
CREATE TABLE IF NOT EXISTS risk_scores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    calculation_version VARCHAR(20) NOT NULL DEFAULT 'unversioned',
    calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_risk_scores_team_id (team_id),
    KEY idx_risk_scores_user_id (user_id),
    KEY idx_risk_scores_calculated_at (calculated_at),
    CONSTRAINT fk_risk_scores_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_risk_scores_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

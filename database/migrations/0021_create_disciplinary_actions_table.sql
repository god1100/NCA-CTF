-- Canonical disciplinary record. Per docs/ctf9.txt §8: this is the ONLY
-- disciplinary table -- there is deliberately no separate
-- `disqualifications` table. A disqualification is simply an action_type
-- value here (docs/ctf7.txt §46).
CREATE TABLE IF NOT EXISTS disciplinary_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    investigation_id BIGINT UNSIGNED NULL,
    action_type ENUM('warning', 'score_penalty', 'temporary_ban', 'disqualification') NOT NULL,
    reason TEXT NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_disciplinary_actions_team_id (team_id),
    KEY idx_disciplinary_actions_user_id (user_id),
    KEY idx_disciplinary_actions_action_type (action_type),
    KEY idx_disciplinary_actions_created_at (created_at),
    CONSTRAINT fk_disciplinary_actions_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_disciplinary_actions_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_disciplinary_actions_investigation
        FOREIGN KEY (investigation_id) REFERENCES investigations(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_disciplinary_actions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

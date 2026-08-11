-- Every flag submission attempt, correct or not. This is historical/
-- integrity-relevant data -- team_id/challenge_id/submitted_by use
-- ON DELETE RESTRICT so a submission history can never silently vanish
-- via a parent delete (docs/ctf4.txt §29, ctf9.txt §21). Submitted flag
-- is stored only as a hash, never plaintext.
CREATE TABLE IF NOT EXISTS submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    challenge_id BIGINT UNSIGNED NOT NULL,
    submitted_by BIGINT UNSIGNED NOT NULL,
    submitted_flag_hash VARCHAR(255) NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    ip_hash CHAR(64) NULL,
    user_agent_hash CHAR(64) NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_submissions_team_id (team_id),
    KEY idx_submissions_challenge_id (challenge_id),
    KEY idx_submissions_submitted_by (submitted_by),
    KEY idx_submissions_submitted_at (submitted_at),
    KEY idx_submissions_is_correct (is_correct),
    CONSTRAINT fk_submissions_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_submissions_submitted_by
        FOREIGN KEY (submitted_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

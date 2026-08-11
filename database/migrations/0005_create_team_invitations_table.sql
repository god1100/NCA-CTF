-- Team invitations. Token is stored only as a hash, never plaintext
-- (docs/ctf4.txt §10, ctf9.txt §13).
CREATE TABLE IF NOT EXISTS team_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    invited_by BIGINT UNSIGNED NULL,
    invited_email VARCHAR(255) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    UNIQUE KEY uq_team_invitations_token_hash (token_hash),
    KEY idx_team_invitations_team_id (team_id),
    KEY idx_team_invitations_status (status),
    CONSTRAINT fk_team_invitations_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_team_invitations_invited_by
        FOREIGN KEY (invited_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

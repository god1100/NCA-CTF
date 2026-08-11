-- Many-to-many relationship between users and teams. The application-level
-- rule "one active team per user" is enforced in a later phase, not here
-- (docs/ctf4.txt §9, ctf9.txt §8 Phase 1 scope).
CREATE TABLE IF NOT EXISTS team_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_captain BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('active', 'removed', 'left') NOT NULL DEFAULT 'active',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_members_team_user (team_id, user_id),
    KEY idx_team_members_team_id (team_id),
    KEY idx_team_members_user_id (user_id),
    CONSTRAINT fk_team_members_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_team_members_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

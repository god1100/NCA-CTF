-- Raw competition-relevant signals/events. Detection algorithms are NOT
-- implemented here -- this table only stores what happened
-- (docs/ctf7.txt §5-8, ctf9.txt §8/§20). Nullable FKs use SET NULL so the
-- event record itself is never lost if a related entity is later removed.
CREATE TABLE IF NOT EXISTS integrity_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(60) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    team_id BIGINT UNSIGNED NULL,
    challenge_id BIGINT UNSIGNED NULL,
    ip_hash CHAR(64) NULL,
    user_agent_hash CHAR(64) NULL,
    metadata JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_integrity_events_event_type (event_type),
    KEY idx_integrity_events_team_id (team_id),
    KEY idx_integrity_events_challenge_id (challenge_id),
    KEY idx_integrity_events_created_at (created_at),
    CONSTRAINT fk_integrity_events_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_integrity_events_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_integrity_events_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

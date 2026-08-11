-- Admin-facing alerts derived from integrity events (docs/ctf7.txt §9-11,
-- ctf9.txt §8). A single suspicious signal alone must never auto-punish --
-- this table only surfaces alerts for human review (docs/ctf7.txt §2).
CREATE TABLE IF NOT EXISTS integrity_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    integrity_event_id BIGINT UNSIGNED NULL,
    alert_type VARCHAR(60) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'low',
    status ENUM('open', 'reviewing', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    evidence JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by BIGINT UNSIGNED NULL,
    KEY idx_integrity_alerts_team_id (team_id),
    KEY idx_integrity_alerts_user_id (user_id),
    KEY idx_integrity_alerts_severity (severity),
    KEY idx_integrity_alerts_status (status),
    KEY idx_integrity_alerts_created_at (created_at),
    CONSTRAINT fk_integrity_alerts_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_integrity_alerts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_integrity_alerts_event
        FOREIGN KEY (integrity_event_id) REFERENCES integrity_events(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_integrity_alerts_resolved_by
        FOREIGN KEY (resolved_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

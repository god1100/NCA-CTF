-- Administrator review workflow for an alert (docs/ctf7.txt §40,
-- ctf9.txt §8). RESTRICT on alert_id: an alert under active investigation
-- should not simply disappear via a parent delete.
CREATE TABLE IF NOT EXISTS investigations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alert_id BIGINT UNSIGNED NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    status ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
    notes TEXT NULL,
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_investigations_alert_id (alert_id),
    KEY idx_investigations_status (status),
    CONSTRAINT fk_investigations_alert
        FOREIGN KEY (alert_id) REFERENCES integrity_alerts(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_investigations_assigned_to
        FOREIGN KEY (assigned_to) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

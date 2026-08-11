-- Structured evidence attached to an integrity event (docs/ctf7.txt §9,
-- ctf9.txt §21). Cascades with its parent event since evidence has no
-- independent meaning.
CREATE TABLE IF NOT EXISTS integrity_evidence (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    integrity_event_id BIGINT UNSIGNED NOT NULL,
    evidence_type VARCHAR(60) NOT NULL,
    evidence_data JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_integrity_evidence_event_id (integrity_event_id),
    CONSTRAINT fk_integrity_evidence_event
        FOREIGN KEY (integrity_event_id) REFERENCES integrity_events(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

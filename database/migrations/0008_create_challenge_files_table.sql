-- Challenge downloadable attachments. Metadata only -- upload handling
-- and non-executable storage enforcement arrive in a later phase
-- (docs/ctf4.txt §13, ctf9.txt §17).
CREATE TABLE IF NOT EXISTS challenge_files (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    sha256_checksum CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_challenge_files_challenge_id (challenge_id),
    CONSTRAINT fk_challenge_files_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

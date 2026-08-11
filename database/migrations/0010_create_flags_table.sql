-- Flag validation data. NEVER a plaintext `flag` column -- only flag_hash
-- (docs/ctf4.txt §15, ctf9.txt §14/§20). flag_type defaults to 'static' for
-- V1 while remaining structurally compatible with future dynamic flags.
--
-- NOTE: enforcing "only one active flag per challenge" is left to
-- application logic in a later phase, not a DB constraint here -- MySQL/
-- MariaDB has no native partial/conditional unique index, and a
-- trigger-based approach is unnecessary complexity for Phase 1 schema-only
-- scope.
CREATE TABLE IF NOT EXISTS flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT UNSIGNED NOT NULL,
    flag_hash VARCHAR(255) NOT NULL,
    flag_type ENUM('static', 'dynamic') NOT NULL DEFAULT 'static',
    version INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_flags_challenge_id (challenge_id),
    KEY idx_flags_challenge_status (challenge_id, status),
    CONSTRAINT fk_flags_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

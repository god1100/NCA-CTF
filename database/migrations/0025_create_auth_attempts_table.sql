-- Phase 2: database-backed rate limiting for authentication endpoints.
-- Additive to the Phase 1 schema -- does not modify or replace any
-- existing table. No Redis introduced (docs/ctf9.txt §28, Phase 2 scope).
-- Only hashed identifiers are stored, never plaintext IP/username.
CREATE TABLE IF NOT EXISTS auth_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purpose ENUM('login', 'register') NOT NULL,
    identifier_hash CHAR(64) NULL,
    ip_hash CHAR(64) NOT NULL,
    successful BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auth_attempts_purpose_created (purpose, created_at),
    KEY idx_auth_attempts_ip_hash (ip_hash),
    KEY idx_auth_attempts_identifier_hash (identifier_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

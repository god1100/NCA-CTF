-- Evidence-based relationships between accounts (docs/ctf7.txt §20/§26,
-- ctf9.txt §10). No invasive device fingerprinting -- relationship_type
-- is restricted at the application layer to non-invasive signals such as
-- same_ip, same_submission_pattern, team_relationship.
CREATE TABLE IF NOT EXISTS account_relationships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_a_id BIGINT UNSIGNED NOT NULL,
    user_b_id BIGINT UNSIGNED NOT NULL,
    relationship_type VARCHAR(60) NOT NULL,
    evidence JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_account_relationships (user_a_id, user_b_id, relationship_type),
    KEY idx_account_relationships_user_a (user_a_id),
    KEY idx_account_relationships_user_b (user_b_id),
    CONSTRAINT fk_account_relationships_user_a
        FOREIGN KEY (user_a_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_account_relationships_user_b
        FOREIGN KEY (user_b_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

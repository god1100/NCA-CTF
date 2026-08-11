-- Configurable competition settings (docs/ctf4.txt §26, ctf9.txt §28).
-- Values such as team_min_size / team_max_size are seeded as rows here,
-- never hardcoded into application logic or the schema itself.
CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') NOT NULL DEFAULT 'string',
    description VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_system_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

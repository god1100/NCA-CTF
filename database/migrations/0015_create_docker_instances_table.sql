-- Database representation of deployed challenge containers ONLY. Docker
-- itself remains the runtime source of truth; no orchestration logic is
-- implemented here (docs/ctf4.txt §21, ctf9.txt §19).
CREATE TABLE IF NOT EXISTS docker_instances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    container_identifier VARCHAR(255) NULL,
    status ENUM('created', 'starting', 'running', 'stopping', 'stopped', 'failed') NOT NULL DEFAULT 'created',
    host VARCHAR(255) NULL,
    host_port INT UNSIGNED NULL,
    internal_port INT UNSIGNED NULL,
    last_health_check DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_docker_instances_challenge_id (challenge_id),
    KEY idx_docker_instances_team_id (team_id),
    KEY idx_docker_instances_status (status),
    CONSTRAINT fk_docker_instances_challenge
        FOREIGN KEY (challenge_id) REFERENCES challenges(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_docker_instances_team
        FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

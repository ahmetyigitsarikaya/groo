<?php
declare(strict_types=1);

final class Install_002_users_and_roles
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS um_users (
                id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
                username     VARCHAR(64)  NOT NULL,
                email        VARCHAR(191) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                status       VARCHAR(32) NOT NULL DEFAULT "active",
                remember_token VARCHAR(100) NULL DEFAULT NULL,
                created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_email    (email),
                UNIQUE KEY uniq_username (username),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS um_roles (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name        VARCHAR(64)  NOT NULL,
                color       VARCHAR(7)   NOT NULL DEFAULT "#99aab5",
                permissions JSON         NOT NULL,
                created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS um_user_roles (
                user_id INT UNSIGNED NOT NULL,
                role_id INT UNSIGNED NOT NULL,
                assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, role_id),
                CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES um_users (id) ON DELETE CASCADE,
                CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES um_roles  (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS um_api_keys (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id    INT UNSIGNED NOT NULL,
                api_key    VARCHAR(64)  NOT NULL,
                label      VARCHAR(128) NOT NULL DEFAULT "",
                is_active  TINYINT(1)  NOT NULL DEFAULT 1,
                last_used_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_api_key (api_key),
                KEY idx_user (user_id),
                CONSTRAINT fk_ak_user FOREIGN KEY (user_id) REFERENCES um_users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $pdo->exec('
            CREATE TABLE IF NOT EXISTS um_login_attempts (
                id int(10) unsigned NOT NULL AUTO_INCREMENT,
                ip_address varchar(45) NOT NULL,
                email varchar(191) NOT NULL,
                attempts int(10) unsigned NOT NULL DEFAULT 1,
                locked_until timestamp NULL DEFAULT NULL,
                created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ip_email (ip_address, email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS um_login_attempts');
        $pdo->exec('DROP TABLE IF EXISTS um_api_keys');
        $pdo->exec('DROP TABLE IF EXISTS um_user_roles');
        $pdo->exec('DROP TABLE IF EXISTS um_roles');
        $pdo->exec('DROP TABLE IF EXISTS um_users');
    }
}

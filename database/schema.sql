-- Matrix Bochum – Datenbankschema (MySQL 8 / MariaDB 10.4+)

CREATE TABLE IF NOT EXISTS users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    last_login_at  DATETIME NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(200) NOT NULL,
    slug            VARCHAR(220) NOT NULL,
    subtitle        VARCHAR(255) NULL,
    category        ENUM('konzert','party','sonstiges') NOT NULL DEFAULT 'konzert',
    category_label  VARCHAR(80) NULL COMMENT 'Bei Sonstiges: Art (z. B. Lesung). Sonst: Genre/Motto',
    description     TEXT NULL,
    starts_at       DATETIME NOT NULL,
    doors_at        TIME NULL,
    ends_at         DATETIME NULL,
    room            VARCHAR(80) NULL,
    image           VARCHAR(80) NULL,
    image_focus     ENUM('top','center','bottom') NOT NULL DEFAULT 'center',
    ticket_url      VARCHAR(500) NULL,
    price_presale   VARCHAR(60) NULL,
    price_box       VARCHAR(60) NULL,
    age_rating      VARCHAR(40) NULL,
    ticket_status   ENUM('available','few','boxoffice','free','soldout','postponed','cancelled') NOT NULL DEFAULT 'available',
    status          ENUM('published','draft') NOT NULL DEFAULT 'published',
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_events_slug (slug),
    KEY idx_events_start (status, starts_at),
    KEY idx_events_cat (category, starts_at),
    CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip            VARCHAR(45) NOT NULL,
    email         VARCHAR(190) NULL,
    attempted_at  DATETIME NOT NULL,
    KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

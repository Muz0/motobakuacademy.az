<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$db = app('db');

if (!$db) {
    echo "Database connection is not configured.\n";
    exit(1);
}

$statements = [
    <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title_az VARCHAR(255) NOT NULL,
    title_ru VARCHAR(255) NULL,
    title_en VARCHAR(255) NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    summary_az TEXT NULL,
    summary_ru TEXT NULL,
    summary_en TEXT NULL,
    content_az LONGTEXT NOT NULL,
    content_ru LONGTEXT NULL,
    content_en LONGTEXT NULL,
    cover_image_az VARCHAR(255) NULL,
    cover_image_ru VARCHAR(255) NULL,
    cover_image_en VARCHAR(255) NULL,
    graphic_content_az VARCHAR(255) NULL,
    graphic_content_ru VARCHAR(255) NULL,
    graphic_content_en VARCHAR(255) NULL,
    author_name VARCHAR(191) NULL,
    accepts_comments TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_posts_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS post_category (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    CONSTRAINT fk_post_category_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_category_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    parent_comment_id INT UNSIGNED DEFAULT NULL,
    author_name VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_post_created_at (post_id, created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_comment (parent_comment_id),
    CONSTRAINT fk_comments_post
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_comments_parent
        FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS team_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    role VARCHAR(191) NOT NULL,
    description TEXT NULL,
    photo_url VARCHAR(255) NOT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_team_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
CREATE TABLE IF NOT EXISTS team_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
    description TEXT NULL,
    about_title_az VARCHAR(255) NULL,
    about_title_ru VARCHAR(255) NULL,
    about_title_en VARCHAR(255) NULL,
    about_content_az LONGTEXT NULL,
    about_content_ru LONGTEXT NULL,
    about_content_en LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_title_az VARCHAR(255) NULL;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_title_ru VARCHAR(255) NULL;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_title_en VARCHAR(255) NULL;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_content_az LONGTEXT NULL;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_content_ru LONGTEXT NULL;
SQL,

    <<<SQL
ALTER TABLE team_settings ADD COLUMN IF NOT EXISTS about_content_en LONGTEXT NULL;
SQL,
];


try {
    foreach ($statements as $sql) {
        $db->exec($sql);
    }
    echo "Database tables are ready.\n";
} catch (Throwable $exception) {
    echo "Migration failed: " . $exception->getMessage() . "\n";
    exit(1);
}

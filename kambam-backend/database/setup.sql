DROP DATABASE IF EXISTS kanbandb;
CREATE DATABASE kanbandb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kanbandb;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB;

CREATE TABLE boards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(80) NOT NULL,
    description TEXT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE columns (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    board_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(40) NOT NULL,
    `order` INT NOT NULL,
    wip_limit INT DEFAULT 999,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE cards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    board_id BIGINT UNSIGNED NOT NULL,
    column_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NULL,
    position INT DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
    FOREIGN KEY (column_id) REFERENCES columns(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE move_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    card_id BIGINT UNSIGNED NOT NULL,
    from_column_id BIGINT UNSIGNED NULL,
    to_column_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    FOREIGN KEY (from_column_id) REFERENCES columns(id) ON DELETE SET NULL,
    FOREIGN KEY (to_column_id) REFERENCES columns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB;

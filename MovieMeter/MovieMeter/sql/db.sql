-- Disable foreign key checks (MyISAM ignores them anyway)
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they already exist
DROP TABLE IF EXISTS mm_watchlist_items;
DROP TABLE IF EXISTS mm_watchlists;
DROP TABLE IF EXISTS mm_movie_categories;
DROP TABLE IF EXISTS mm_ratings;
DROP TABLE IF EXISTS mm_comments;
DROP TABLE IF EXISTS mm_movie_media;
DROP TABLE IF EXISTS mm_deleted_movies_log;
DROP TABLE IF EXISTS mm_admin_logs;
DROP TABLE IF EXISTS mm_movies;
DROP TABLE IF EXISTS mm_categories;
DROP TABLE IF EXISTS mm_users;
DROP TABLE IF EXISTS mm_roles;

SET FOREIGN_KEY_CHECKS = 1;

-- 1) ROLES
CREATE TABLE mm_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=MyISAM;

-- 2) USERS
CREATE TABLE mm_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) NULL,
    account_status ENUM('active','suspended') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM;

-- 3) CATEGORIES
CREATE TABLE mm_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM;

-- 4) MOVIES
CREATE TABLE mm_movies (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    creator_id INT NOT NULL,
    deleted_by INT NULL,
    title VARCHAR(200) NOT NULL,
    short_description VARCHAR(500) NOT NULL,
    full_description TEXT NOT NULL,
    release_date DATE NULL,
    poster_image VARCHAR(255) NULL,
    trailer_url VARCHAR(255) NULL,
    status ENUM('draft','published','deleted') DEFAULT 'draft',
    view_count INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    is_api_imported TINYINT(1) DEFAULT 0,
    external_api_source VARCHAR(100) NULL,
    external_api_id VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    published_at DATETIME NULL,
    deleted_reason VARCHAR(255) NULL
) ENGINE=MyISAM;

-- 5) MOVIE MEDIA
CREATE TABLE mm_movie_media (
    media_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    media_type ENUM('image','video','audio') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM;

-- 6) COMMENTS
CREATE TABLE mm_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    deleted_by INT NULL,
    comment_text TEXT NOT NULL,
    comment_status ENUM('visible','hidden','deleted') DEFAULT 'visible',
    deleted_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM;

-- 7) RATINGS
CREATE TABLE mm_ratings (
    movie_id INT NOT NULL,
    user_id INT NOT NULL,
    rating_value INT NOT NULL,
    rated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (movie_id, user_id),
    CHECK (rating_value BETWEEN 1 AND 5)
) ENGINE=MyISAM;

-- 8) MOVIE_CATEGORIES
CREATE TABLE mm_movie_categories (
    movie_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (movie_id, category_id)
) ENGINE=MyISAM;

-- 9) WATCHLISTS
CREATE TABLE mm_watchlists (
    watchlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    watchlist_name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watchlist_user_name (user_id, watchlist_name)
) ENGINE=MyISAM;

-- 10) WATCHLIST ITEMS
CREATE TABLE mm_watchlist_items (
    watchlist_id INT NOT NULL,
    movie_id INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (watchlist_id, movie_id)
) ENGINE=MyISAM;

-- 11) ADMIN LOGS
CREATE TABLE mm_admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    target_id INT NULL,
    target_table VARCHAR(100) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_note TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM;

-- 12) DELETED MOVIES LOG
CREATE TABLE mm_deleted_movies_log (
    deleted_log_id INT AUTO_INCREMENT PRIMARY KEY,
    deleted_by INT NOT NULL,
    deleted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    creator_id INT NULL,
    movie_id INT NULL,
    movie_title VARCHAR(200) NOT NULL,
    deleted_reason VARCHAR(255) NULL
) ENGINE=MyISAM;
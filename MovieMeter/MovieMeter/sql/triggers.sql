-- ALL TRIGGERS

DELIMITER $$

-- 1) Validate user data before inserting a new user.
DROP TRIGGER IF EXISTS trg_before_insert_user_check_role$$
CREATE TRIGGER trg_before_insert_user_check_role
BEFORE INSERT ON mm_users
FOR EACH ROW
BEGIN
    IF (SELECT COUNT(*) FROM mm_roles WHERE role_id = NEW.role_id) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid role_id';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.full_name)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Full name cannot be empty';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.username)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Username cannot be empty';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.email)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Email cannot be empty';
    END IF;
END$$


-- 2) Validate movie data before inserting a new movie.
DROP TRIGGER IF EXISTS trg_before_insert_movie_check_creator$$
CREATE TRIGGER trg_before_insert_movie_check_creator
BEFORE INSERT ON mm_movies
FOR EACH ROW
BEGIN
    IF (SELECT COUNT(*)
        FROM mm_users u
        JOIN mm_roles r ON u.role_id = r.role_id
        WHERE u.user_id = NEW.creator_id
          AND u.account_status = 'active'
          AND r.role_name IN ('creator','admin')) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'creator_id must belong to an active creator/admin';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.title)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Movie title cannot be empty';
    END IF;

    IF NEW.status = 'published' AND NEW.published_at IS NULL THEN
        SET NEW.published_at = NOW();
    END IF;
END$$


-- 3) Validate movie data when updating movie information or status.
DROP TRIGGER IF EXISTS trg_before_update_movie_manage_status$$
CREATE TRIGGER trg_before_update_movie_manage_status
BEFORE UPDATE ON mm_movies
FOR EACH ROW
BEGIN
    IF CHAR_LENGTH(TRIM(NEW.title)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Movie title cannot be empty';
    END IF;

    IF NEW.status = 'published' AND OLD.status <> 'published' THEN
        SET NEW.published_at = NOW();
    END IF;

    IF NEW.status = 'deleted' AND OLD.status <> 'deleted' THEN
        IF NEW.deleted_at IS NULL THEN
            SET NEW.deleted_at = NOW();
        END IF;
    END IF;
END$$


-- 4) Log deleted movies after status changes to deleted.
DROP TRIGGER IF EXISTS trg_after_update_movie_log_status$$
CREATE TRIGGER trg_after_update_movie_log_status
AFTER UPDATE ON mm_movies
FOR EACH ROW
BEGIN
    IF OLD.status <> 'deleted' AND NEW.status = 'deleted' THEN
        INSERT INTO mm_deleted_movies_log
        (deleted_by, deleted_at, creator_id, movie_id, movie_title, deleted_reason)
        VALUES
        (NEW.deleted_by, NOW(), NEW.creator_id, NEW.movie_id, NEW.title, NEW.deleted_reason);
    END IF;
END$$


-- 5) Validate movie media before inserting media for a movie.
DROP TRIGGER IF EXISTS trg_before_insert_movie_media_check_movie$$
CREATE TRIGGER trg_before_insert_movie_media_check_movie
BEFORE INSERT ON mm_movie_media
FOR EACH ROW
BEGIN
    IF (SELECT COUNT(*)
        FROM mm_movies
        WHERE movie_id = NEW.movie_id
          AND status <> 'deleted') = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid movie_id for media';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.file_path)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'file_path cannot be empty';
    END IF;
END$$


-- 6) Prevent adding a rating for a deleted or unpublished movie.
DROP TRIGGER IF EXISTS trg_before_insert_rating_check_movie$$
CREATE TRIGGER trg_before_insert_rating_check_movie
BEFORE INSERT ON mm_ratings
FOR EACH ROW
BEGIN
    IF (SELECT COUNT(*)
        FROM mm_movies
        WHERE movie_id = NEW.movie_id
          AND status = 'published') = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ratings allowed only for published movies';
    END IF;

    IF NEW.rating_value < 1 OR NEW.rating_value > 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Rating must be between 1 and 5';
    END IF;
END$$


-- 7) Update movie average rating and rating count after inserting a rating.
DROP TRIGGER IF EXISTS trg_after_insert_rating_update_movie$$
CREATE TRIGGER trg_after_insert_rating_update_movie
AFTER INSERT ON mm_ratings
FOR EACH ROW
BEGIN
    UPDATE mm_movies
    SET average_rating = (
            SELECT IFNULL(AVG(rating_value), 0)
            FROM mm_ratings
            WHERE movie_id = NEW.movie_id
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM mm_ratings
            WHERE movie_id = NEW.movie_id
        )
    WHERE movie_id = NEW.movie_id;
END$$


-- 8) Update movie average rating and rating count after updating a rating.
DROP TRIGGER IF EXISTS trg_after_update_rating_update_movie$$
CREATE TRIGGER trg_after_update_rating_update_movie
AFTER UPDATE ON mm_ratings
FOR EACH ROW
BEGIN
    UPDATE mm_movies
    SET average_rating = (
            SELECT IFNULL(AVG(rating_value), 0)
            FROM mm_ratings
            WHERE movie_id = NEW.movie_id
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM mm_ratings
            WHERE movie_id = NEW.movie_id
        )
    WHERE movie_id = NEW.movie_id;
END$$


-- 9) Update movie average rating and rating count after deleting a rating.
DROP TRIGGER IF EXISTS trg_after_delete_rating_update_movie$$
CREATE TRIGGER trg_after_delete_rating_update_movie
AFTER DELETE ON mm_ratings
FOR EACH ROW
BEGIN
    UPDATE mm_movies
    SET average_rating = (
            SELECT IFNULL(AVG(rating_value), 0)
            FROM mm_ratings
            WHERE movie_id = OLD.movie_id
        ),
        rating_count = (
            SELECT COUNT(*)
            FROM mm_ratings
            WHERE movie_id = OLD.movie_id
        )
    WHERE movie_id = OLD.movie_id;
END$$


-- 10) Prevent adding comments to deleted or unpublished movies.
DROP TRIGGER IF EXISTS trg_before_insert_comment_check_movie$$
CREATE TRIGGER trg_before_insert_comment_check_movie
BEFORE INSERT ON mm_comments
FOR EACH ROW
BEGIN
    IF (SELECT COUNT(*)
        FROM mm_movies
        WHERE movie_id = NEW.movie_id
          AND status = 'published') = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Comments allowed only for published movies';
    END IF;

    IF CHAR_LENGTH(TRIM(NEW.comment_text)) = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Comment cannot be empty';
    END IF;
END$$


-- 11) Increase movie comment count after adding a new comment.
DROP TRIGGER IF EXISTS trg_after_insert_comment_update_count$$
CREATE TRIGGER trg_after_insert_comment_update_count
AFTER INSERT ON mm_comments
FOR EACH ROW
BEGIN
    UPDATE mm_movies
    SET comment_count = comment_count + 1
    WHERE movie_id = NEW.movie_id;
END$$


-- 12) Decrease movie comment count after deleting a comment.
DROP TRIGGER IF EXISTS trg_after_delete_comment_update_count$$
CREATE TRIGGER trg_after_delete_comment_update_count
AFTER DELETE ON mm_comments
FOR EACH ROW
BEGIN
    UPDATE mm_movies
    SET comment_count = GREATEST(comment_count - 1, 0)
    WHERE movie_id = OLD.movie_id;
END$$


-- 13) Prevent duplicate watchlist entries for the same watchlist and movie.
DROP TRIGGER IF EXISTS trg_before_insert_watchlist_item_prevent_duplicate$$
CREATE TRIGGER trg_before_insert_watchlist_item_prevent_duplicate
BEFORE INSERT ON mm_watchlist_items
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1
        FROM mm_watchlist_items
        WHERE watchlist_id = NEW.watchlist_id
          AND movie_id = NEW.movie_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Movie already exists in watchlist';
    END IF;
END$$

-- 14) Prevent adding deleted or unpublished movies to the watchlist.
DROP TRIGGER IF EXISTS trg_before_insert_watchlist_item_check_movie$$
CREATE TRIGGER trg_before_insert_watchlist_item_check_movie
BEFORE INSERT ON mm_watchlist_items
FOR EACH ROW
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM mm_movies
        WHERE movie_id = NEW.movie_id
          AND status = 'published'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only published movies can be added to watchlist';
    END IF;
END$$


-- 15) Set updated_at automatically when movie data changes.
DROP TRIGGER IF EXISTS trg_before_update_movie_set_updated_at$$
CREATE TRIGGER trg_before_update_movie_set_updated_at
BEFORE UPDATE ON mm_movies
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END$$


-- 16) Log admin deletions of comments for moderation tracking.
DROP TRIGGER IF EXISTS trg_after_delete_comment_log_admin$$
CREATE TRIGGER trg_after_delete_comment_log_admin
AFTER DELETE ON mm_comments
FOR EACH ROW
BEGIN
    INSERT INTO mm_admin_logs
    (admin_id, target_id, target_table, action_type, action_note, created_at)
    VALUES
    (OLD.deleted_by, OLD.comment_id, 'mm_comments', 'DELETE', 'Comment deleted by admin', NOW());
END$$


DELIMITER ;

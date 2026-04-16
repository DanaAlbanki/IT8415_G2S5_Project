DELIMITER $$

DROP PROCEDURE IF EXISTS sp_get_most_popular_movies $$
CREATE PROCEDURE sp_get_most_popular_movies(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT
        m.movie_id,
        m.title,
        m.release_date,
        m.view_count,
        m.average_rating,
        m.rating_count,
        m.comment_count,
        u.full_name AS creator_name,
        u.username AS creator_username
    FROM mm_movies m
    INNER JOIN mm_users u ON m.creator_id = u.user_id
    WHERE m.status = 'published'
      AND (
            (p_start_date IS NULL AND p_end_date IS NULL)
            OR (
                m.published_at IS NOT NULL
                AND DATE(m.published_at) BETWEEN COALESCE(p_start_date, DATE(m.published_at))
                                            AND COALESCE(p_end_date, DATE(m.published_at))
            )
          )
    ORDER BY
        m.view_count DESC,
        m.average_rating DESC,
        m.rating_count DESC,
        m.comment_count DESC,
        m.title ASC;
END $$


DROP PROCEDURE IF EXISTS sp_get_movies_by_creator $$
CREATE PROCEDURE sp_get_movies_by_creator(
    IN p_creator_id INT
)
BEGIN
    SELECT
        m.movie_id,
        m.title,
        m.short_description,
        m.release_date,
        m.status,
        m.poster_image,
        m.trailer_url,
        m.view_count,
        m.average_rating,
        m.rating_count,
        m.comment_count,
        m.created_at,
        m.published_at
    FROM mm_movies m
    WHERE m.creator_id = p_creator_id
    ORDER BY
        m.created_at DESC,
        m.title ASC;
END $$


DROP PROCEDURE IF EXISTS sp_get_movie_rating_summary $$
CREATE PROCEDURE sp_get_movie_rating_summary(
    IN p_movie_id INT
)
BEGIN
    SELECT
        m.movie_id,
        m.title,
        COALESCE(AVG(r.rating_value), 0) AS calculated_average_rating,
        COUNT(r.user_id) AS calculated_rating_count,
        (
            SELECT COUNT(*)
            FROM mm_comments c
            WHERE c.movie_id = m.movie_id
              AND c.comment_status = 'visible'
        ) AS visible_comment_count,
        m.view_count,
        m.average_rating AS stored_average_rating,
        m.rating_count AS stored_rating_count,
        m.comment_count AS stored_comment_count
    FROM mm_movies m
    LEFT JOIN mm_ratings r ON m.movie_id = r.movie_id
    WHERE m.movie_id = p_movie_id
    GROUP BY
        m.movie_id,
        m.title,
        m.view_count,
        m.average_rating,
        m.rating_count,
        m.comment_count;
END $$


DROP PROCEDURE IF EXISTS sp_get_user_watchlist $$
CREATE PROCEDURE sp_get_user_watchlist(
    IN p_user_id INT
)
BEGIN
    SELECT
        w.watchlist_id,
        m.movie_id,
        m.title,
        m.short_description,
        m.release_date,
        m.poster_image,
        m.trailer_url,
        wi.added_at
    FROM mm_watchlists w
    INNER JOIN mm_watchlist_items wi
        ON w.watchlist_id = wi.watchlist_id
    INNER JOIN mm_movies m
        ON wi.movie_id = m.movie_id
    WHERE w.user_id = p_user_id
      AND m.status = 'published'
    ORDER BY
        wi.added_at DESC,
        m.title ASC;
END $$


DROP PROCEDURE IF EXISTS sp_get_movies_by_category $$
CREATE PROCEDURE sp_get_movies_by_category(
    IN p_category_id INT
)
BEGIN
    SELECT
        c.category_id,
        c.category_name,
        m.movie_id,
        m.title,
        m.short_description,
        m.release_date,
        m.poster_image,
        m.trailer_url,
        m.status,
        m.view_count,
        m.average_rating,
        m.rating_count,
        m.comment_count
    FROM mm_categories c
    INNER JOIN mm_movie_categories mc
        ON c.category_id = mc.category_id
    INNER JOIN mm_movies m
        ON mc.movie_id = m.movie_id
    WHERE c.category_id = p_category_id
      AND m.status = 'published'
    ORDER BY
        m.release_date DESC,
        m.title ASC;
END $$

DELIMITER ;
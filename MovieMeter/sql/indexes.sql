-- Indexes
 
-- USERS
CREATE INDEX idx_users_role_status ON mm_users(role_id, account_status);

-- MOVIES
CREATE INDEX idx_movies_title ON mm_movies(title);
CREATE INDEX idx_movies_release_date ON mm_movies(release_date);
CREATE INDEX idx_movies_creator_id ON mm_movies(creator_id);
CREATE INDEX idx_movies_view_count ON mm_movies(view_count);
CREATE INDEX idx_movies_average_rating ON mm_movies(average_rating);
CREATE INDEX idx_movies_status ON mm_movies(status);
CREATE INDEX idx_users_full_name ON mm_users(full_name);
CREATE INDEX idx_movies_status_created_at ON mm_movies(status, created_at);
CREATE INDEX idx_movies_status_release_date ON mm_movies(status, release_date);
CREATE INDEX idx_movies_deleted_by ON mm_movies(deleted_by);
CREATE INDEX idx_movies_published_at ON mm_movies(published_at);
CREATE INDEX idx_movies_external_api_id ON mm_movies(external_api_id);

-- MOVIE MEDIA
CREATE INDEX idx_movie_media_movie_primary ON mm_movie_media(movie_id, is_primary);
CREATE INDEX idx_movie_media_movie_type ON mm_movie_media(movie_id, media_type);

-- COMMENTS
CREATE INDEX idx_comments_movie_status_created ON mm_comments(movie_id, comment_status, created_at);
CREATE INDEX idx_comments_deleted_by ON mm_comments(deleted_by);

-- RATINGS
CREATE INDEX idx_ratings_user_id ON mm_ratings(user_id);
CREATE INDEX idx_ratings_user_rated_at ON mm_ratings(user_id, rated_at);

-- MOVIE CATEGORIES
CREATE INDEX idx_movie_categories_category_id ON mm_movie_categories(category_id);

-- WATCHLIST ITEMS
CREATE INDEX idx_watchlist_items_movie_id ON mm_watchlist_items(movie_id);

-- ADMIN LOGS
CREATE INDEX idx_admin_logs_admin_created ON mm_admin_logs(admin_id, created_at);
CREATE INDEX idx_admin_logs_target ON mm_admin_logs(target_table, target_id);

-- DELETED MOVIES LOG
CREATE INDEX idx_deleted_movies_log_movie_id ON mm_deleted_movies_log(movie_id);
CREATE INDEX idx_deleted_movies_log_deleted_by ON mm_deleted_movies_log(deleted_by);
CREATE INDEX idx_deleted_movies_log_creator_id ON mm_deleted_movies_log(creator_id);
CREATE INDEX idx_deleted_movies_log_deleted_at ON mm_deleted_movies_log(deleted_at);

ALTER TABLE mm_movies
ADD FULLTEXT INDEX ft_movies_search (title, short_description, full_description);
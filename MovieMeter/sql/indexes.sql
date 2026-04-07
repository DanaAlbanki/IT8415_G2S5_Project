-- Indexes
 
CREATE INDEX idx_movies_creator_id ON mm_movies(creator_id);
CREATE INDEX idx_movies_status ON mm_movies(status);
CREATE INDEX idx_movies_release_date ON mm_movies(release_date);
CREATE INDEX idx_movies_view_count ON mm_movies(view_count);
 
CREATE INDEX idx_comments_movie_id ON mm_comments(movie_id);
CREATE INDEX idx_comments_user_id ON mm_comments(user_id);
 
CREATE INDEX idx_watchlists_user_id ON mm_watchlists(user_id);
 
-- Full-text search
CREATE FULLTEXT INDEX ft_movies_title_short_full
ON mm_movies(title, short_description, full_description);
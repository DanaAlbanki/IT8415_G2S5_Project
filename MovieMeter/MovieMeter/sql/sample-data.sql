-- Insert Data
-- ROLES
INSERT INTO mm_roles (role_name) VALUES
('admin'),
('creator'),
('viewer');
  
-- USERS
INSERT INTO mm_users (role_id, full_name, username, email, password_hash, account_status, created_at)
VALUES
(1, 'Ahmed Mohammed', 'admin_ahmed', 'ahmed@moviemeter.com', 'hash', 'active', NOW()),
(2, 'Fatema Ali', 'fatema_creator', 'fatema@moviemeter.com', 'hash', 'active', NOW()),
(2, 'Mohsen Mohamed', 'mohsen_creator', 'mohsen@moviemeter.com', 'hash', 'active', NOW()),
(3, 'Sara Hussain', 'sara_user', 'sara@gmail.com', 'hash', 'active', NOW()),
(3, 'Mohammed Ali', 'mohammed_user', 'mohammed@gmail.com', 'hash', 'active', NOW()),
(3, 'Zainab Ahmed', 'zainab_user', 'zainab@gmail.com', 'hash', 'active', NOW());

-- CATEGORIES
INSERT INTO mm_categories (category_name, description, created_at) VALUES
('Action', 'High energy movies', NOW()),
('Drama', 'Emotional storytelling', NOW()),
('Horror', 'Scary movies', NOW()),
('Comedy', 'Funny movies', NOW()),
('Mystery', 'Investigation stories', NOW());
 
-- MOVIES
INSERT INTO mm_movies 
(creator_id, title, short_description, full_description, release_date, status, created_at)
VALUES
(2,'John Wick','Hitman revenge','Legendary assassin returns','2014-10-24','published',NOW()),
(2,'Mad Max Fury Road','Desert chase','Post-apocalyptic survival','2015-05-15','published',NOW()),
(3,'Gladiator','Roman revenge','General becomes gladiator','2000-05-05','published',NOW()),
(2,'The Dark Knight','Batman vs Joker','Chaos in Gotham','2008-07-18','published',NOW()),
(3,'Avengers Endgame','Final battle','Heroes save universe','2019-04-26','published',NOW()),
(2,'Inception','Dream world','Dream manipulation','2010-07-16','published',NOW()),
(3,'Interstellar','Space travel','Saving humanity','2014-11-07','published',NOW()),
(2,'Titanic','Love story','Ship tragedy','1997-12-19','published',NOW()),
(3,'The Matrix','Virtual reality','Truth about world','1999-03-31','published',NOW()),
(2,'Parasite','Social class','Family infiltration','2019-05-30','published',NOW()),
(3,'Joker','Villain origin','Descent into madness','2019-10-04','published',NOW()),
(2,'Black Panther','Wakanda king','Protecting nation','2018-02-16','published',NOW()),
(3,'The Godfather','Mafia family','Crime dynasty','1972-03-24','published',NOW()),
(2,'Fight Club','Underground fight','Identity crisis','1999-10-15','published',NOW()),
(3,'The Prestige','Magic rivalry','Two magicians compete','2006-10-20','published',NOW()),
(2,'Whiplash','Music pressure','Drummer training','2014-10-10','published',NOW()),
(3,'La La Land','Love and dreams','Artists in LA','2016-12-09','published',NOW()),
(2,'The Revenant','Survival','Man seeks revenge','2015-12-25','published',NOW()),
(3,'Dune','Desert world','Future prophecy','2021-10-22','published',NOW()),
(2,'Avatar','Alien planet','Pandora story','2009-12-18','published',NOW());
 
-- MOVIE MEDIA
INSERT INTO mm_movie_media (movie_id, media_type, file_path, file_name, is_primary, uploaded_at)
VALUES
(1,'image','uploads/johnwick.jpg','johnwick.jpg',1,NOW()),
(2,'image','uploads/madmax.jpg','madmax.jpg',1,NOW()),
(3,'image','uploads/gladiator.jpg','gladiator.jpg',1,NOW()),
(4,'image','uploads/darkknight.jpg','darkknight.jpg',1,NOW()),
(5,'image','uploads/endgame.jpg','endgame.jpg',1,NOW()),
(6,'image','uploads/inception.jpg','inception.jpg',1,NOW()),
(7,'image','uploads/interstellar.jpg','interstellar.jpg',1,NOW()),
(8,'image','uploads/titanic.jpg','titanic.jpg',1,NOW());
 
-- MOVIE CATEGORIES
INSERT INTO mm_movie_categories (movie_id, category_id) VALUES
(1,1),(2,1),(3,1),(4,1),(5,1), -- Action (5 required)
(6,5),(7,5),
(8,2),(9,5),
(10,2),(11,2),
(12,1),(13,2),
(14,2),(15,5),
(16,2),(17,4),
(18,1),(19,5),(20,1);
 
-- COMMENTS
INSERT INTO mm_comments (movie_id, user_id, comment_text, comment_status, created_at)
VALUES
(1,4,'Amazing action movie','visible',NOW()),
(2,5,'Great visuals','visible',NOW()),
(3,6,'Very emotional','visible',NOW()),
(4,4,'Best Batman movie','visible',NOW()),
(5,5,'Epic ending','visible',NOW()),
(6,6,'Mind blowing','visible',NOW()),
(7,4,'Loved the science','visible',NOW()),
(8,5,'Very sad story','visible',NOW());
 
-- RATINGS
INSERT INTO mm_ratings (movie_id, user_id, rating_value, rated_at)
VALUES
(1,4,5,NOW()),
(1,5,4,NOW()),
(2,6,5,NOW()),
(3,4,4,NOW()),
(4,5,5,NOW()),
(5,6,5,NOW()),
(6,4,5,NOW()),
(7,5,4,NOW());
 
-- WATCHLISTS
INSERT INTO mm_watchlists (user_id, watchlist_name, created_at)
VALUES
(4,'Favorites',NOW()),
(5,'Watch Later',NOW());
 
-- WATCHLIST ITEMS
INSERT INTO mm_watchlist_items (watchlist_id, movie_id, added_at)
VALUES
(1,1,NOW()),
(1,2,NOW()),
(1,3,NOW()),
(2,4,NOW()),
(2,5,NOW());

-- ADMIN LOGS
INSERT INTO mm_admin_logs 
(admin_id, target_id, target_table, action_type, action_note, created_at)
VALUES
(1,1,'mm_movies','DELETE','Removed inappropriate movie',NOW()),
(1,4,'mm_comments','DELETE','Removed offensive comment',NOW()),
(1,2,'mm_users','WARNING','User violated guidelines',NOW()),
(1,3,'mm_movies','UPDATE','Updated movie details',NOW());
 
-- DELETED MOVIES LOG
INSERT INTO mm_deleted_movies_log 
(deleted_by, deleted_at, creator_id, movie_id, movie_title, deleted_reason)
VALUES
(1,NOW(),2,21,'Removed Movie','Violation of rules'),
(1,NOW(),3,22,'Duplicate Entry','Duplicate movie'),
(1,NOW(),2,23,'Low Quality Upload','Content not suitable');
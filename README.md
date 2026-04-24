# 🎬 MovieMeter

MovieMeter is a data-driven web application where users can browse movies, search content, rate movies, add comments, and manage a personal watchlist. The system supports multiple user roles and delivers an interactive, responsive experience.

---

## 📌 Project Overview

MovieMeter is a movie review and rating platform where users can:

- Browse and search movies
- View detailed movie information
- Rate movies and add comments
- Manage a personal watchlist
- Interact based on their assigned role

---

## 👥 Roles

- **Viewer (Visitor)** — Browse and search movies, view details, read reviews and ratings
- **Content Creator** — Add and manage movies, write reviews, upload media
- **Administrator** — Full system control: manage users, approve/delete content, generate reports

---

## ✨ Required Features

### 🔐 Authentication & Roles
- User registration and login
- Password encryption
- Session management
- Role-based access control

### 🏠 Home Page
- Display latest movies (newest first)
- Show title, description, image, and media
- View details page per movie

### 🔍 Search System
- Search by title
- Search by date or date range
- Search by creator
- Search by popularity

### ⭐ Rating & Comments
- Add rating
- Add comments (logged-in users)
- View comments
- Admin can delete comments

### 🎬 Creator Panel
- Add movies
- Edit movies
- Upload media (images/videos)
- Publish movies
- View own movies

### 🛠️ Admin Panel
- Manage users
- Manage all movies
- Delete content
- Manage comments
- Generate reports

### 📊 Reporting
- Most popular movies within a date range
- Movies created by specific users


# 🚀 Advanced Features

## 1. Recommendation Movies ⭐
Used to suggest movies based on user preferences.

**Assigned To:** Fatema

---

## 2. Actors in Each Movie ⭐
Display actor information for each movie.

**Assigned To:** Fatema

---

## 3. Advanced UI Responsive ⭐
- Responsive design
- Clean and modern layout

**Assigned To:** All (Maram, Zainab, Fatema, Dana)

---

## 4. Pagination ⭐
- Display movies in pages
- Improve performance
- Better user experience

**Assigned To:** Fatema, Maram

---

## 5. Watchlist ⭐
- Add/remove movies
- Personal movie list

**Assigned To:** Fatema, Maram

---

## 6. Triggers (Database) ⭐
Used to automate database operations.
- Automatically update average rating
- Maintain comment count
- Prevent duplicate ratings
- Log deleted data

**Assigned To:** Fatema, Maram

---

## 7. External API Integration ⭐
- Fetch movies from external API

**Assigned To:** Maram, Fatema

---

## 8. Trailer ⭐
- Display movie trailers
- Play trailers directly on the movie page
- Handle video loading errors

**Assigned To:** Fatema, Maram

---

## 9. Handle Missing Images ⭐
- Show fallback image when movie poster is missing
- Prevent broken image links in UI
- Maintain consistent layout

**Assigned To:** Fatema, Maram

---

## 10. "For You" Section ⭐
- Recommend movies based on user activity
- Display personalized movie suggestions
- Update dynamically as user interacts with the site

**Assigned To:** Maram

---

## 11. Profiles & Edit Profile ⭐
User profile management and editing functionality.

**Assigned To:** Fatema

---

## 12. Image Upload (Profile Pic) ⭐
Upload and manage user profile pictures.

**Assigned To:** Fatema

---

## 13. View Count Changes ⭐
Track and update movie view counts.

**Assigned To:** Fatema

---

## 14. AJAX ⭐
Dynamic content loading and updates without page refresh.

**Assigned To:** Maram

---

## 15. jQuery ⭐
DOM manipulation and event handling.

**Assigned To:** Fatema

---

## 16. Database Procedures ⭐
Create and manage stored procedures for database operations.

**Assigned To:** Maram

---
## 👥 Final Task Distribution

### Team Members

| ID | Name |
|----|------|
| 202304661 | Fatema Maitham |
| 202305590 | Maram Shubbar |
| 202200316 | Dana Albanki |
| 202200277 | Zainab Mahdi |

---

## Roles & Tasks

| Developer | Role | Dev Type | Primary Responsibilities |
|---|---|---|---|
| **Maram Shubbar** | Frontend & UI/UX | Frontend | ERD Design, Home Page, Search System, Rating/Comments (Frontend), Watchlist (UI), "For You" Section, Pagination (Frontend), Triggers (Frontend), External API (Frontend), Trailer (Frontend), Missing Images, AJAX, jQuery, Database Procedures |
| **Zainab Mahdi** | Content & Creator Panel | Full-stack | Database Tables Creation, Creator Panel (Add/Edit/Publish), Media Upload (Images/Videos), Movie Validation, Image Preview Before Upload, Connect to Database, Edit/Delete Logic with Permissions |
| **Fatema Maitham** | Backend & API | Backend | Database Tables Creation, Authentication & Roles, Recommendation System, Actors, Pagination (Backend), Watchlist (Backend), Database Triggers (Backend), Trailer (Backend), Handle Missing Images (Backend), User Profiles, Profile Picture Upload, View Count Changes |
| **Dana Albanki** | Admin & Reports | Backend | ERD Design, Admin Panel, Reporting System (Most Popular, By Creator), Comment Management, Prepared Statements (Secure Queries) |

---

## 💻 Technologies Used

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript, jQuery |
| Backend | PHP |
| Database | MySQL |
| Tools | XAMPP, MAMP/ phpMyAdmin / NetBeans  |

---

## 📁 Project Structure

```
MovieMeter/
│
├── index.php                  # Home page: latest movies + search
├── login.php                  # User login & creates session after authentication
├── register.php               # User signup page
├── logout.php                 # Destroy session and log user out
├── discover.php               # Discover movies page
├── categories.php             # Movies by category
├── search.php                 # Search results page
├── movie.php                  # Movie details page (rating, comments, trailer)
├── profile.php                # User profile page
├── watchlist.php              # User watchlist page
├── foryou.php                 # Personalized recommendations
├── 404.php                    # Not found page
│
├── config/
│   ├── DBConn.php             # Database connection
│   ├── config.php             # Application configuration (database credentials, constants)
│
├── includes/
│   ├── admin_nav.php          # Admin navigation bar
│   ├── admin_footer.php       # Admin footer
│   ├── creator_footer.php     # Creator footer
│   ├── auth_check.php         # Session check & restrict access to logged-in users
│   ├── config_setup.php       # Initial config setup
│
├── admin/
│   ├── dashboard.php          # Admin dashboard
│   ├── manage-users.php       # Manage users
│   ├── add-user.php           # Add user
│   ├── edit-user.php          # Edit user
│   ├── delete-user.php        # Delete user
│   ├── manage-movies.php      # Manage all movies
│   ├── add-movie.php          # Add movie
│   ├── edit-movie.php         # Edit movie
│   ├── delete-movie.php       # Delete movie
│   ├── manage-comments.php    # Manage comments
│   ├── delete-comment.php     # Delete comment
│   ├── reports.php            # Reports main page
│   ├── popular-report.php     # Most popular movies
│   ├── creator-report.php     # Movies by creator
│
├── creator/
│   ├── dashboard.php          # Creator dashboard
│   ├── add-movie.php          # Add new movie
│   ├── edit-movie.php         # Edit movie
│   ├── delete-movie.php       # Delete movie
│   ├── my-movie.php           # Creator movies list
│   ├── movie_details.php      # Movie details (creator view)
│   ├── import-movies.php      # Import from API
│   ├── publish.php            # Publish movie
│   ├── upload-media.php       # Upload images/videos
│   ├── profile.php            # Creator profile
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Main styles
│   │   ├── admin.css          # Admin styles
│   │   ├── discover.css       # Discover page styles
│   │   ├── categories.css     # Categories styles
│   │   ├── movie.css          # Movie card styles
│   │   ├── movie-details.css  # Movie details page
│   │   ├── profile.css        # Profile styles
│   │   ├── login.css          # Login page styles
│   │   ├── register.css       # Register page styles
│   │   ├── watchlist.css      # Watchlist styles
│   │   ├── foryou.css         # For You styles
│   │
│   ├── js/
│   │   ├── api.js            # TMDB API configuration: base URL and image path
│   │   ├── main.js            # Main logic (navigation)
│   │   ├── home.js            # Home interactions (slider)
│   │   ├── movie.js           # Movie page logic (rating/comments)
│   │   ├── watchlist.js       # Watchlist logic
│   │   ├── foryou.js          # Recommendation logic
│   │   ├── categories.js      # Category filtering
│   │   ├── auth.js            # Auth handling
│   │   ├── edit-profile.js    # Profile update logic
│   │
│   ├── images/               # Images & assets
│
├── sql/
│   ├── db.sql                # Tables structure
│   ├── sample-data.sql       # Sample data
│   ├── procedures.sql        # Stored procedures
│   ├── triggers.sql          # Database triggers
│   ├── indexes.sql           # Indexes
│
├── uploads/                  # Uploaded files (images/videos)
│
├── .htaccess                 # Routing & security rules
├── .gitignore                # Ignored files
```
---

## 🧪 Testing

The system is tested to ensure:

- All features work correctly
- Validation is handled properly
- Role permissions are enforced
- Reports generate correct results
- Advanced features function as expected

---

## 🎯 Project Purpose

MovieMeter demonstrates professional full-stack web development skills including server-side programming, relational database design, secure coding practices, role-based access control, and thorough testing.

---

## 📜 Academic Note

This project was developed as part of **IT8415 – Database Programming 2 Group Project**.

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

---

## 🚀 Advanced Features

### 1. AJAX ⭐
Used to update parts of the page dynamically without reloading.
- Add comments without page reload
- Update ratings instantly
- Live search results

### 2. Prepared Statements ⭐
Used to secure database operations and prevent SQL injection.
- Secure login queries
- Secure insert/update/delete operations

### 3. jQuery ⭐
Used to simplify JavaScript operations.
- Event handling
- Form validation
- AJAX requests

### 4. Advanced UI (Bootstrap) ⭐
- Responsive design
- Clean and modern layout
- UI components (cards, navbar, modals)

### 5. Pagination ⭐
- Display movies in pages
- Improve performance
- Better user experience

### 6. Watchlist ⭐
- Add/remove movies
- Personal movie list

### 7. Database Triggers ⭐
Used to automate database operations.
- Automatically update average rating
- Maintain comment count
- Prevent duplicate ratings
- Log deleted data

### 8. External API Integration ⭐
- Fetch movies from external API
- Save selected movies into database

### 9. Dark Theme UI ⭐
- Consistent dark interface design

### 10. API Testing (Postman)
- Test API endpoints
- Verify requests and responses

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

| Name | Role | Dev Type | Tasks |
|---|---|---|---|
| **Maram Shubbar** | Frontend & UI/UX Developer | Frontend | ERD, Home Page (UI + layout), Search System (AJAX + filters), Rating & Comments (AJAX frontend), jQuery integration (events + AJAX handling), API consumption (connect frontend to backend endpoints), Watchlist (UI + AJAX add/remove), Dark/light theme toggle |
| **Zainab Mahdi** | Content & Creator Panel Developer | Full-stack | ERD, Creator Panel (add/edit/publish movies), Media Upload (images/videos), Movie Validation (forms + logic), Image Preview Before Upload (JavaScript), Connect creator panel with database, Edit/Delete logic with permissions |
| **Fatema Maitham** | Backend & API Developer | Backend | Database Table Creation, Authentication (login/register), Session Management, Roles & Permissions, External API Integration (fetch & save movies), API endpoints (for AJAX requests), Watchlist API (add/remove backend logic), Pagination, Database Triggers |
| **Dana Albanki** | Admin & Reports Developer | Backend | Database Table Creation, Admin Panel (manage users & movies), Manage Comments (admin control), Reporting System, Prepared Statements (secure queries) |

---

## 💻 Technologies Used

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, JavaScript, Bootstrap, jQuery |
| Backend | PHP |
| Database | MySQL |
| Tools | XAMPP, MAMP/ phpMyAdmin / NetBeans / Postman |

---

## 📁 Project Structure

```
MovieMeter/
│
├── index.php                     # Home page: latest movies, search bar
├── login.php                     # User login page
├── register.php                  # User signup page
├── logout.php                    # Logout and destroy session
├── search.php                    # Search results page
├── movie-details.php             # Movie page: details, rating, comments, trailer
├── profile.php                   # Logged-in user profile
├── not-found.php                 # 404 page
│
├── config/
│   ├── config.php                # App settings, constants, API key, base URL
│   ├── database.php              # Database connection
│   └── app.php                   # Global app configuration
│
├── includes/
│   ├── header.php                # Common header + Bootstrap CSS
│   ├── footer.php                # Common footer + JS files
│   ├── navbar.php                # Navigation bar
│   ├── session.php               # Session helpers
│   ├── auth.php                  # Login/role protection helpers
│   ├── functions.php             # General helper functions
│   ├── flash.php                 # Success/error messages
│   └── pagination.php            # Pagination UI/helper
│
├── classes/
│   ├── User.php                  # User operations: register, login, update, delete
│   ├── Movie.php                 # Movie operations: add, edit, delete, fetch, search
│   ├── Rating.php                # Rating operations: add/update/get average
│   ├── Comment.php               # Comment operations: add/get/delete/approve
│   ├── Category.php              # Category operations
│   ├── Report.php                # Reports for admin
│   └── Auth.php                  # Authentication logic
│
├── admin/
│   ├── dashboard.php             # Admin dashboard
│   ├── manage-users.php          # View/edit/delete users
│   ├── add-user.php              # Add new user manually
│   ├── edit-user.php             # Edit user data
│   ├── delete-user.php           # Delete user
│   ├── manage-movies.php         # View/edit/delete all movies
│   ├── add-movie.php             # Add movie as admin
│   ├── edit-movie.php            # Edit any movie
│   ├── delete-movie.php          # Delete any movie
│   ├── manage-comments.php       # View/delete inappropriate comments
│   ├── reports.php               # Reports page
│   ├── popular-report.php        # Most popular movies by date range
│   ├── creator-report.php        # Movies by specific creator
│   └── settings.php              # Admin settings
│
├── creator/
│   ├── dashboard.php             # Creator dashboard
│   ├── add-movie.php             # Add new movie
│   ├── edit-movie.php            # Edit own movie
│   ├── delete-movie.php          # Delete own movie if allowed
│   ├── my-movies.php             # List creator's own movies
│   ├── upload-media.php          # Upload poster/trailer/media
│   └── publish-movie.php         # Publish movie
│
├── viewer/
│   ├── dashboard.php             # Viewer dashboard
│   ├── favorites.php             # Watchlist movies
│   ├── ratings.php               # Movies rated by viewer
│   └── comments.php              # Comments written by viewer
│
├── api/
│   ├── fetch-movies.php          # Fetch movies from external API
│   ├── import-movies.php         # Save selected API movies into DB
│   ├── save-movie.php            # Save single API movie into DB
│   ├── rate.php                  # AJAX rating endpoint
│   ├── comment.php               # AJAX comment insert endpoint
│   ├── delete-comment.php        # AJAX comment delete endpoint
│   ├── search-movies.php         # AJAX live search endpoint
│   └── fetch-reports.php         # AJAX report data endpoint
│
├── assets/
│   ├── css/
│   │   ├── style.css             # Main custom styles
│   │   ├── admin.css             # Admin page styles
│   │   ├── forms.css             # Form styles
│   │   └── movie.css             # Movie cards/details styles
│   ├── js/
│   │   ├── validation.js         # Client-side form validation
│   │   ├── rating.js             # Star rating logic
│   │   ├── comments.js           # Comment submit/load/delete
│   │   ├── search.js             # Search and filter logic
│   │   ├── ajax.js               # Shared AJAX helpers
│   │   └── app.js                # Global JS
│   └── images/
│       ├── logo.png
│       ├── default-movie.jpg
│       └── uploads/
│
├── uploads/
│   ├── posters/
│   ├── trailers/
│   ├── thumbnails/
│   └── temp/
│
├── sql/
│   ├── db.sql                    # CREATE TABLE statements
│   ├── sample-data.sql           # Sample data
│   ├── procedures.sql            # Stored procedures
│   ├── triggers.sql              # Database triggers
│   └── indexes.sql               # Full-text/index definitions
│
├── tests/
│   ├── screenshots/
│   ├── functional-tests.txt
│   └── advanced-features.txt
│
├── docs/
│   ├── erd.png
│   ├── report.pdf
│   ├── test-plan.pdf
│   └── github-link.txt
│
├── .htaccess                     # URL/security rules (Apache)
└── README.md                     # Project overview and setup
```

---

## 🧪 Testing

The system is tested to ensure:

- All features work correctly
- Validation is handled properly
- Role permissions are enforced
- Reports generate correct results
- Advanced features function as expected
- API endpoints verified via Postman

---

## 🎯 Project Purpose

MovieMeter demonstrates professional full-stack web development skills including server-side programming, relational database design, secure coding practices, role-based access control, and thorough testing.

---

## 📜 Academic Note

This project was developed as part of **IT8415 – Database Programming 2 Group Project**.

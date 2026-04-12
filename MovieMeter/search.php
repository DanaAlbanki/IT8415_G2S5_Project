<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . "/config/DBConn.php");

$conn = getConnection();
mysqli_set_charset($conn, "utf8mb4");

// Get filters
$title      = isset($_GET["title"]) ? trim($_GET["title"]) : "";
$creator    = isset($_GET["creator"]) ? trim($_GET["creator"]) : "";
$date_from  = isset($_GET["date_from"]) ? trim($_GET["date_from"]) : "";
$date_to    = isset($_GET["date_to"]) ? trim($_GET["date_to"]) : "";
$popularity = isset($_GET["popularity"]) ? trim($_GET["popularity"]) : "";

// Base query
$sql = "
SELECT 
    m.movie_id,
    m.title,
    m.short_description,
    m.release_date,
    m.poster_image,
    m.average_rating,
    m.rating_count,
    m.comment_count,
    m.view_count,
    m.published_at,
    u.full_name AS creator_name
FROM mm_movies m
INNER JOIN mm_users u ON m.creator_id = u.user_id
WHERE m.status = 'published'
";

$conditions = [];
$params = [];
$types = "";

// Search by title
if ($title !== "") {
    $conditions[] = "m.title LIKE ?";
    $params[] = "%" . $title . "%";
    $types .= "s";
}

// Search by creator
if ($creator !== "") {
    $conditions[] = "u.full_name LIKE ?";
    $params[] = "%" . $creator . "%";
    $types .= "s";
}

// Search by date range
if ($date_from !== "" && $date_to !== "") {
    $conditions[] = "m.release_date BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
} elseif ($date_from !== "") {
    $conditions[] = "m.release_date >= ?";
    $params[] = $date_from;
    $types .= "s";
} elseif ($date_to !== "") {
    $conditions[] = "m.release_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

// Add conditions
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

// Sort by popularity
switch ($popularity) {
    case "rating_high":
        $sql .= " ORDER BY m.average_rating DESC, m.rating_count DESC, m.title ASC";
        break;

    case "rating_count":
        $sql .= " ORDER BY m.rating_count DESC, m.average_rating DESC, m.title ASC";
        break;

    case "views":
        $sql .= " ORDER BY m.view_count DESC, m.title ASC";
        break;

    case "comments":
        $sql .= " ORDER BY m.comment_count DESC, m.title ASC";
        break;

    case "newest":
        $sql .= " ORDER BY m.release_date DESC, m.created_at DESC";
        break;

    case "oldest":
        $sql .= " ORDER BY m.release_date ASC, m.created_at ASC";
        break;

    default:
        $sql .= " ORDER BY m.created_at DESC";
        break;
}

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Movies - MovieMeter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fb;
        }
        .search-box {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
        }
        .movie-card {
            background: #fff;
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            height: 100%;
        }
        .movie-card img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            background: #eee;
        }
        .poster-placeholder {
            width: 100%;
            height: 320px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-weight: 600;
        }
        .meta {
            font-size: 14px;
            color: #666;
        }
        .desc {
            min-height: 72px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Search Movies</h1>
        <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
    </div>

    <div class="search-box mb-4">
        <form method="GET" action="search.php" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search by Title</label>
                <input type="text" name="title" class="form-control"
                       value="<?php echo htmlspecialchars($title); ?>"
                       placeholder="Enter movie title">
            </div>

            <div class="col-md-6">
                <label class="form-label">Search by Creator</label>
                <input type="text" name="creator" class="form-control"
                       value="<?php echo htmlspecialchars($creator); ?>"
                       placeholder="Enter creator name">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Popularity</label>
                <select name="popularity" class="form-select">
                    <option value="">Default</option>
                    <option value="rating_high" <?php echo ($popularity === "rating_high") ? "selected" : ""; ?>>Highest Rating</option>
                    <option value="rating_count" <?php echo ($popularity === "rating_count") ? "selected" : ""; ?>>Most Rated</option>
                    <option value="views" <?php echo ($popularity === "views") ? "selected" : ""; ?>>Most Viewed</option>
                    <option value="comments" <?php echo ($popularity === "comments") ? "selected" : ""; ?>>Most Commented</option>
                    <option value="newest" <?php echo ($popularity === "newest") ? "selected" : ""; ?>>Newest Release</option>
                    <option value="oldest" <?php echo ($popularity === "oldest") ? "selected" : ""; ?>>Oldest Release</option>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <label class="form-label invisible">Search</label>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <?php
    $hasFilters = ($title !== "" || $creator !== "" || $date_from !== "" || $date_to !== "" || $popularity !== "");
    ?>

    <div class="mb-3">
        <h4 class="mb-0">
            <?php
            if ($hasFilters) {
                echo "Search Results";
            } else {
                echo "All Published Movies";
            }
            ?>
        </h4>
    </div>

    <div class="row g-4">
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($movie = mysqli_fetch_assoc($result)) { ?>
                <div class="col-md-6 col-lg-4">
                    <div class="movie-card">
                        <?php if (!empty($movie["poster_image"])) { ?>
                            <img src="uploads/posters/<?php echo htmlspecialchars($movie["poster_image"]); ?>" alt="Poster">
                        <?php } else { ?>
                            <div class="poster-placeholder">No Poster</div>
                        <?php } ?>

                        <div class="p-3">
                            <h5><?php echo htmlspecialchars($movie["title"]); ?></h5>

                            <p class="meta mb-2">
                                <strong>Creator:</strong> <?php echo htmlspecialchars($movie["creator_name"]); ?><br>
                                <strong>Release Date:</strong>
                                <?php echo !empty($movie["release_date"]) ? htmlspecialchars($movie["release_date"]) : "N/A"; ?>
                            </p>

                            <p class="desc">
                                <?php echo htmlspecialchars($movie["short_description"]); ?>
                            </p>

                            <p class="meta mb-3">
                                ⭐ <?php echo number_format((float)$movie["average_rating"], 2); ?>
                                | Ratings: <?php echo (int)$movie["rating_count"]; ?>
                                | Comments: <?php echo (int)$movie["comment_count"]; ?>
                                | Views: <?php echo (int)$movie["view_count"]; ?>
                            </p>

                            <a href="movie.php?id=<?php echo (int)$movie["movie_id"]; ?>" class="btn btn-outline-primary w-100">
                                View More
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="alert alert-warning">
                    No movies found matching your search.
                </div>
            </div>
        <?php } ?>
    </div>
</div>

</body>
</html>
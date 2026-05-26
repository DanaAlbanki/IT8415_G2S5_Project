<?php
// Fetches and displays a list of all user comments, including the associated user and movie, with options to edit or delete each record.
require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    mysqli_query($conn,
    "DELETE FROM mm_comments WHERE comment_id = $id");

    header("Location: manage-comments.php");
    exit();
}

$result = mysqli_query($conn,"
SELECT c.comment_id, c.comment_text, u.full_name, m.title
FROM mm_comments c
JOIN mm_users u ON c.user_id=u.user_id
JOIN mm_movies m ON c.movie_id=m.movie_id
ORDER BY c.comment_id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<?php include("../includes/admin_nav.php"); ?>

<div class="admin-container">

<h1 class="admin-title">Manage Comments</h1>

<table class="admin-table">
<tr>
<th>User</th>
<th>Movie</th>
<th>Comment</th>
<th>Actions</th>
</tr>

<?php while($row=mysqli_fetch_assoc($result)){ ?>
<tr>
<td><?php echo $row["full_name"]; ?></td>
<td><?php echo $row["title"]; ?></td>
<td><?php echo $row["comment_text"]; ?></td>
<td>
<a href="edit-comment.php?id=<?php echo $row["comment_id"]; ?>" class="btn btn-edit">Edit</a>
<a href="manage-comments.php?delete=<?php echo $row["comment_id"]; ?>" class="btn btn-delete" onclick="return confirm('Delete comment?')">Delete</a>
</td>
</tr>
<?php } ?>

</table>

</div>
<?php include("../includes/admin_footer.php"); ?>
</body>
</html>
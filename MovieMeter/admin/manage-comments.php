<?php
// lists all visible comments with edit and delete options

require_once(__DIR__ . "/../includes/auth_check.php");
require_once(__DIR__ . "/../config/DBConn.php");

if ($_SESSION["role_name"] !== "admin") die("Access denied.");

$conn = getConnection();

$result = mysqli_query($conn,"
SELECT c.comment_id, c.comment_text, c.movie_id, u.full_name, m.title
FROM mm_comments c
JOIN mm_users u ON c.user_id=u.user_id
JOIN mm_movies m ON c.movie_id=m.movie_id
WHERE c.comment_status = 'visible'
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
<td><?php echo htmlspecialchars($row["full_name"]); ?></td>
<td><?php echo htmlspecialchars($row["title"]); ?></td>
<td><?php echo htmlspecialchars($row["comment_text"]); ?></td>
<td>
    <a href="edit-comment.php?id=<?php echo $row["comment_id"]; ?>" class="btn btn-edit">Edit</a>

    <form method="POST" action="../delete-comment.php" style="display:inline" onsubmit="return confirm('Delete comment?')">
        <input type="hidden" name="comment_id" value="<?php echo $row['comment_id']; ?>">
        <input type="hidden" name="movie_id" value="<?php echo $row['movie_id']; ?>">
        <input type="hidden" name="redirect_to" value="admin">
        <button type="submit" class="btn btn-delete">Delete</button>
    </form>
</td>
</tr>
<?php } ?>

</table>

</div>

<?php include("../includes/admin_footer.php"); ?>

</body>
</html>
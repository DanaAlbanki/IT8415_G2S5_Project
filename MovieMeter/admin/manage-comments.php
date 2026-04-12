<?php
require_once("../config/database.php");

$result = $conn->query("SELECT * FROM mm_comments");
?>

<h2>Manage Comments</h2>

<table border="1">
<tr>
<th>ID</th>
<th>Movie</th>
<th>User</th>
<th>Text</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['comment_id']; ?></td>
<td><?php echo $row['movie_id']; ?></td>
<td><?php echo $row['user_id']; ?></td>
<td><?php echo $row['comment_text']; ?></td>
<td><?php echo $row['comment_status']; ?></td>
<td>
<a href="delete-comment.php?id=<?php echo $row['comment_id']; ?>">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>
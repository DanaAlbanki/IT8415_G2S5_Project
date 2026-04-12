<?php
require_once("../config/database.php");

$result = $conn->query("SELECT * FROM mm_movies");
?>

<h2>Manage Movies</h2>

<table border="1">
<tr>
<th>ID</th>
<th>Title</th>
<th>Status</th>
<th>Views</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['movie_id']; ?></td>
<td><?php echo $row['title']; ?></td>
<td><?php echo $row['status']; ?></td>
<td><?php echo $row['view_count']; ?></td>
<td>
<a href="delete-movie.php?id=<?php echo $row['movie_id']; ?>">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>
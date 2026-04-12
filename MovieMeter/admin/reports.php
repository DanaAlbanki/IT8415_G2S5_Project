<?php
require_once("../config/database.php");

$popular = $conn->query("
SELECT title, view_count 
FROM mm_movies 
WHERE status='published'
ORDER BY view_count DESC 
LIMIT 5
");

$creators = $conn->query("
SELECT creator_id, COUNT(*) as total 
FROM mm_movies 
GROUP BY creator_id
");
?>

<h2>Most Popular Movies</h2>

<table border="1">
<tr><th>Title</th><th>Views</th></tr>
<?php while($row = $popular->fetch_assoc()): ?>
<tr>
<td><?php echo $row['title']; ?></td>
<td><?php echo $row['view_count']; ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Movies by Creator</h2>

<table border="1">
<tr><th>Creator ID</th><th>Total Movies</th></tr>
<?php while($row = $creators->fetch_assoc()): ?>
<tr>
<td><?php echo $row['creator_id']; ?></td>
<td><?php echo $row['total']; ?></td>
</tr>
<?php endwhile; ?>
</table>
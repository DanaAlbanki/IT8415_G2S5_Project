<?php
require_once("../config/database.php");

$result = $conn->query("SELECT * FROM mm_users");
?>

<h2>Manage Users</h2>

<table border="1">
<tr>
<th>ID</th>
<th>Name</th>
<th>Username</th>
<th>Email</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['user_id']; ?></td>
<td><?php echo $row['full_name']; ?></td>
<td><?php echo $row['username']; ?></td>
<td><?php echo $row['email']; ?></td>
<td><?php echo $row['account_status']; ?></td>
<td>
<a href="delete-user.php?id=<?php echo $row['user_id']; ?>">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>
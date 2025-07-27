<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id = $id");
    header("Location: manage_users.php");
    exit();
}

// Fetch users
$result = $conn->query("SELECT * FROM users");
?>

<h2>Manage Users (Admins Only)</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th><th>Username</th><th>Role</th><th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['username'] ?></td>
        <td><?= $row['role'] ?></td>
        <td>
            <a href="edit_user.php?id=<?= $row['id'] ?>">Edit</a> |
            <a href="manage_users.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<h3>Add New User</h3>
<form method="post" action="add_user.php">
    Username: <input type="text" name="username" required><br>
    Password: <input type="text" name="password" required><br>
    Role:
    <select name="role">
        <option value="admin">Admin</option>
        <option value="doctor">Doctor</option>
        <option value="nurse">Nurse</option>
        <option value="receptionist">Receptionist</option>
    </select><br>
    <input type="submit" value="Add User">
</form>

<?php
require_once('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $password, $role])) {
        echo "User successfully added.";
    } else {
        echo "Error adding user.";
    }
}
?>
<form method="POST">
  <input type="text" name="username" placeholder="Username" required><br>
  <input type="password" name="password" placeholder="Password" required><br>
  <select name="role" required>
    <option value="admin">Admin</option>
    <option value="doctor">Doctor</option>
    <option value="lab">Lab</option>
  </select><br>
  <button type="submit">Add User</button>
</form>

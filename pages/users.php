<?php
require '../config/db.php';

$message = "";

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetchColumn() > 0) {
            $message = "<div class='alert alert-danger'>❗ Username already exists. Please choose another one.</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $role, $full_name, $email, $phone]);

            $user_id = $pdo->lastInsertId();

           if ($role === 'doctor') {
    $specialty = $_POST['specialty'] ?? NULL;
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : NULL;
    $room_id = !empty($_POST['room_id']) ? $_POST['room_id'] : NULL;
    $status = 'available';

    $stmt2 = $pdo->prepare("INSERT INTO doctor (id, name, specialty, department_id, phone, email, room_id, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt2->execute([$user_id, $full_name, $specialty, $department_id, $phone, $email, $room_id, $status]);
}

            $message = "<div class='alert alert-success'>✅ User created successfully.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>❗ Error: " . $e->getMessage() . "</div>";
    }
}

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_users'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
        $message = "<div class='alert alert-success'>✅ User updated successfully.</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>❗ Update failed: " . $e->getMessage() . "</div>";
    }
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_users'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<script>alert('❗ Cannot delete user: Please unlink them from other records first.');</script>";
        } else {
            echo "<script>alert('An error occurred while deleting the user.');</script>";
        }
    }
}

$users = $pdo->query("SELECT id, username, role FROM users")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM department")->fetchAll();
$rooms = $pdo->query("SELECT id, room_number FROM room")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>User Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
    </div>
<div class="row mb-3">
  <div class="col-md-3">
    <select class="form-select" id="columnSelect">
    </select>
  </div>
  <div class="col-md-9">
    <input type="text" class="form-control" id="columnSearch" placeholder="Enter value to search...">
  </div>
</div>
    <?= $message ?>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $row): ?>
        <form method="post">
        <tr>
            <td><input type="hidden" name="id" value="<?= $row['id'] ?>"> <?= $row['id'] ?></td>
            <td><input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($row['username']) ?>" readonly></td>
            <td><input type="text" name="role" class="form-control form-control-sm" value="<?= htmlspecialchars($row['role']) ?>" readonly></td>
            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-warning btn-edit" onclick="editRow(this)">Edit</button>
                    <button type="submit" class="btn btn-sm btn-success btn-confirm" name="update_users" style="display:none;">Confirm</button>
                    <button type="button" class="btn btn-sm btn-secondary btn-cancel" onclick="cancelEdit(this)" style="display:none;">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger" name="delete_users" onclick="return confirm('Delete this user?')">Delete</button>
                </div>
            </td>
        </tr>
        </form>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add New User</h5></div>
        <div class="modal-body">
            <input type="hidden" name="add_user" value="1">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    <option value="admin">Admin</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="doctor">Doctor</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" required>
            </div>

            <!-- Doctor-only fields -->
            <div class="mb-3">
                <label class="form-label">Specialty (Only for Doctors)</label>
                <input type="text" name="specialty" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Department (Only for Doctors)</label>
                <select name="department_id" class="form-control">
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Room (Only for Doctors)</label>
                <select name="room_id" class="form-control">
                    <option value="">-- Select Room --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success">Save</button></div>
    </form>
  </div>
</div>


<script>document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('columnSearch');
    const columnSelect = document.getElementById('columnSelect');
    const table = document.querySelector("table");
    const headers = table.querySelectorAll("thead th");

    // Build column options
    headers.forEach((th, i) => {
      const option = document.createElement("option");
      option.value = i;
      option.textContent = th.textContent.trim();
      columnSelect.appendChild(option);
    });

    // Filter table by selected column
    searchInput.addEventListener('input', function () {
      const colIndex = columnSelect.value;
      const filter = searchInput.value.toLowerCase();
      const rows = table.querySelectorAll("tbody tr");

      rows.forEach(row => {
        const cells = row.querySelectorAll("td");
        const target = cells[colIndex]?.textContent.toLowerCase() || "";
        row.style.display = target.includes(filter) ? "" : "none";
      });
    });
  });</script>

<?php include '../partials/footer.php'; ?>
</body>
</html>

<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

// Add new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO patient (name, gender, birth_date, phone, address, status) VALUES (?, ?, ?, ?, ?, ?)");

   $stmt->execute([$name, $gender, $birth_date, $phone, $address, $status]);
}

// Update patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patients'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE patient SET name=?, gender=?, birth_date=?, phone=?, address=?, status=? WHERE id=?");
    $stmt->execute([$name, $gender, $birth_date, $phone, $address, $status, $id]);
}

// Delete patient with safety message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patients'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM patient WHERE id=?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<script>alert('❗ Cannot delete patient: Please unassign the patient from the room first.');</script>";
        } else {
            echo "<script>alert('An error occurred while deleting the patient.');</script>";
        }
    }
}

// Fetch all patients
$patients = $pdo->query("SELECT * FROM patient")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patients - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Patient Records</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">Add Patient</button>
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
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Gender</th>
                <th>birth_date</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($patients as $row): ?>
        <form method="post">
        <tr>
            <td><input type="hidden" name="id" value="<?= $row['id'] ?>"> <?= $row['id'] ?></td>
            <td data-editable><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($row['name']) ?>" readonly></td>
            <td data-editable><input type="text" name="gender" class="form-control form-control-sm" value="<?= htmlspecialchars($row['gender']) ?>" readonly></td>
            <td data-editable><input type="text" name="birth_date" class="form-control form-control-sm" value="<?= htmlspecialchars($row['birth_date']) ?>" readonly></td>
            <td data-editable><input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($row['phone']) ?>" readonly></td>
            <td data-editable><input type="text" name="address" class="form-control form-control-sm" value="<?= htmlspecialchars($row['address']) ?>" readonly></td>
            <td data-editable><input type="text" name="status" class="form-control form-control-sm" value="<?= htmlspecialchars($row['status']) ?>" readonly></td>
            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-warning btn-edit" onclick="editRow(this)">Edit</button>
                    <button type="submit" class="btn btn-sm btn-success btn-confirm" name="update_patients" style="display:none;">Confirm</button>
                    <button type="button" class="btn btn-sm btn-secondary btn-cancel" onclick="cancelEdit(this)" style="display:none;">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger" name="delete_patients" onclick="return confirm('Delete this record?')">Delete</button>
                </div>
            </td>
        </tr>
        </form>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="addPatientModal" tabindex="-1" aria-labelledby="addPatientLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="addPatientLabel">Add New Patient</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="add_patient" value="1">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-control" required>
                    <option value="">Choose...</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="birth_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="admitted">Admitted</option>
                    <option value="discharged">Discharged</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editRow(btn) {
    const row = btn.closest("tr");
    row.querySelectorAll("input").forEach(i => i.removeAttribute("readonly"));
    row.querySelector(".btn-edit").style.display = "none";
    row.querySelector(".btn-confirm").style.display = "inline-block";
    row.querySelector(".btn-cancel").style.display = "inline-block";
    row.querySelector("[name='delete_patients']").style.display = "none";
}
function cancelEdit(btn) {
    const row = btn.closest("tr");
    row.querySelectorAll("input").forEach(i => i.setAttribute("readonly", true));
    row.querySelector(".btn-edit").style.display = "inline-block";
    row.querySelector(".btn-confirm").style.display = "none";
    row.querySelector(".btn-cancel").style.display = "none";
    row.querySelector("[name='delete_patients']").style.display = "inline-block";
}
  document.addEventListener('DOMContentLoaded', function () {
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
  });

</script>
<?php include '../partials/footer.php'; ?>
</body>
</html>

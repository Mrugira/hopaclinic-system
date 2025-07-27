<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

// Add new doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $phone = $_POST['phone'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO doctor (name, specialty, phone, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $specialty, $phone, $status]);
}

// Update doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctors'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $phone = $_POST['phone'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE doctor SET name=?, specialty=?, phone=?, status=? WHERE id=?");
    $stmt->execute([$name, $specialty, $phone, $status, $id]);
}

// Delete doctor with safety message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doctors'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM doctor WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<script>alert('❗ Cannot delete doctor: Please unassign them from appointments or schedules first.');</script>";
        } else {
            echo "<script>alert('An error occurred while deleting the doctor.');</script>";
        }
    }
}

// Fetch all doctors
$doctors = $pdo->query("SELECT * FROM doctor")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctors - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Doctor Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">Add Doctor</button>
    </div>

    <div class="mb-3 text-end">
        <button class="btn btn-success" onclick="exportTableToCSV()">⬇️ Export to CSV</button>
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
                <th>Specialty</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($doctors as $row): ?>
        <form method="post">
        <tr>
            <td><input type="hidden" name="id" value="<?= $row['id'] ?>"> <?= $row['id'] ?></td>
            <td data-editable><input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($row['name']) ?>" readonly></td>
            <td data-editable><input type="text" name="specialty" class="form-control form-control-sm" value="<?= htmlspecialchars($row['specialty']) ?>" readonly></td>
            <td data-editable><input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($row['phone']) ?>" readonly></td>
            <td data-editable>
    <select name="status" class="form-select form-select-sm" >
        <option value="available" <?= $row['status'] === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="unavailable" <?= $row['status'] === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
    </select>
</td>

            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-warning btn-edit" onclick="editRow(this)">Edit</button>
                    <button type="submit" class="btn btn-sm btn-success btn-confirm" name="update_doctors" style="display:none;">Confirm</button>
                    <button type="button" class="btn btn-sm btn-secondary btn-cancel" onclick="cancelEdit(this)" style="display:none;">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger" name="delete_doctors" onclick="return confirm('Delete this record?')">Delete</button>
                </div>
            </td>
        </tr>
        </form>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="addDoctorLabel">Add New Doctor</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="add_doctor" value="1">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Specialty</label>
                <input type="text" name="specialty" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
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
    row.querySelector("[name^='delete_']").style.display = "none";
}
function cancelEdit(btn) {
    const row = btn.closest("tr");
    row.querySelectorAll("input").forEach(i => i.setAttribute("readonly", true));
    row.querySelector(".btn-edit").style.display = "inline-block";
    row.querySelector(".btn-confirm").style.display = "none";
    row.querySelector(".btn-cancel").style.display = "none";
    row.querySelector("[name^='delete_']").style.display = "inline-block";
}
function exportTableToCSV() {
    const table = document.querySelector("table");
    let csv = [];
    const rows = table.querySelectorAll("tr");

    for (let row of rows) {
        let cols = Array.from(row.querySelectorAll("th, td")).map(cell =>
            '"' + cell.innerText.replace(/"/g, '""') + '"'
        );
        csv.push(cols.join(","));
    }

    const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    const downloadLink = document.createElement("a");
    downloadLink.download = "doctors_export.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
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

<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = $user['role'];
$message = "";

// ADD PRESCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    $stmt = $pdo->prepare("INSERT INTO prescription (patient_id, doctor_id, medication_name, dosage, date_prescribed)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['patient_id'],
        $_POST['doctor_id'],
        $_POST['medicine'],
        $_POST['dosage'],
        $_POST['date_prescribed']
    ]);
    $message = "<div class='alert alert-success'>✅ Prescription added successfully.</div>";
}

// UPDATE PRESCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prescription'])) {
    $stmt = $pdo->prepare("SELECT doctor_id FROM prescription WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $owner = $stmt->fetchColumn();

    if ($role === 'admin' || $user_id == $owner) {
        $pdo->prepare("UPDATE prescription SET medication_name = ?, dosage = ?, date_prescribed = ? WHERE id = ?")
            ->execute([
                $_POST['medicine'],
                $_POST['dosage'],
                $_POST['date_prescribed'],
                $_POST['id']
            ]);
        $message = "<div class='alert alert-success'>✅ Updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⛔ You are not allowed to update this record.</div>";
    }
}

// DELETE PRESCRIPTION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_prescription'])) {
    $stmt = $pdo->prepare("SELECT doctor_id FROM prescription WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $owner = $stmt->fetchColumn();

    if ($role === 'admin' || $user_id == $owner) {
        $pdo->prepare("DELETE FROM prescription WHERE id = ?")->execute([$_POST['id']]);
        $message = "<div class='alert alert-success'>🗑️ Prescription deleted.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⛔ You are not allowed to delete this record.</div>";
    }
}

// FETCH DATA
$prescriptions = $pdo->query("SELECT pr.id, pr.doctor_id, p.name AS patient_name, d.name AS doctor_name, 
                                      pr.medication_name, pr.dosage, pr.date_prescribed
                               FROM prescription pr
                               JOIN patient p ON pr.patient_id = p.id
                               JOIN doctor d ON pr.doctor_id = d.id")->fetchAll();

$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctor")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescriptions - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-3">Prescriptions</h2>
    <?= $message ?>
    <div class="mb-3 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">➕ Add Prescription</button>
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
                <th>Patient</th>
                <th>Doctor</th>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($prescriptions as $row): ?>
            <tr>
                <form method="post">
                    <td><?= $row['id'] ?><input type="hidden" name="id" value="<?= $row['id'] ?>"></td>
                    <td><?= htmlspecialchars($row['patient_name']) ?></td>
                    <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                    <td><input name="medicine" class="form-control form-control-sm" value="<?= htmlspecialchars($row['medication_name']) ?>"></td>
                    <td><input name="dosage" class="form-control form-control-sm" value="<?= htmlspecialchars($row['dosage']) ?>"></td>
                    <td><input name="date_prescribed" type="date" class="form-control form-control-sm" value="<?= $row['date_prescribed'] ?>"></td>
                    <td>
                        <?php if ($role === 'admin' || $user_id == $row['doctor_id']): ?>
                            <button type="submit" name="update_prescription" class="btn btn-sm btn-outline-success">💾 Update</button>
                            <button type="submit" name="delete_prescription" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this?')">🗑️Delete</button>
                        <?php else: ?>
                            <span class="text-muted">No access</span>
                        <?php endif; ?>
                    </td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1" aria-labelledby="addPrescriptionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Prescription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="add_prescription" value="1">
        <div class="mb-3">
          <label>Patient</label>
          <select name="patient_id" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label>Doctor</label>
          <select name="doctor_id" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($doctors as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label>Medicine</label>
          <input type="text" name="medicine" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Dosage</label>
          <input type="text" name="dosage" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Date</label>
          <input type="date" name="date_prescribed" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
      </div>
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

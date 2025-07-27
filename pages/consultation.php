<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['id'];

// ADD consultation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consultation'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $reason = $_POST['reason'];
    $diagnosis = $_POST['diagnosis'];
    $consultation_date = $_POST['consultation_date'];

    $stmt = $pdo->prepare("INSERT INTO consultation (patient_id, doctor_id, reason, diagnosis, consultation_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $reason, $diagnosis, $consultation_date]);
}

// UPDATE consultation inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inline_update_consultation'])) {
    $id = $_POST['consultation_id'];
    $reason = $_POST['reason'];
    $diagnosis = $_POST['diagnosis'];

    $stmt = $pdo->prepare("SELECT doctor_id FROM consultation WHERE id = ?");
    $stmt->execute([$id]);
    $con = $stmt->fetch();

    if ($con && ($role === 'admin' || $user_id == $con['doctor_id'])) {
        $update = $pdo->prepare("UPDATE consultation SET reason = ?, diagnosis = ? WHERE id = ?");
        $update->execute([$reason, $diagnosis, $id]);
    }
}

// DELETE consultation
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT doctor_id FROM consultation WHERE id = ?");
    $stmt->execute([$delete_id]);
    $con = $stmt->fetch();

    if ($con && ($role === 'admin' || $user_id == $con['doctor_id'])) {
        $delete = $pdo->prepare("DELETE FROM consultation WHERE id = ?");
        $delete->execute([$delete_id]);
    }
    header("Location: consultation.php");
    exit;
}

$consultations = $pdo->query("SELECT c.id, c.doctor_id, p.name AS patient_name, d.name AS doctor_name, c.reason, c.diagnosis, c.consultation_date
                               FROM consultation c
                               JOIN patient p ON c.patient_id = p.id
                               JOIN doctor d ON c.doctor_id = d.id")->fetchAll();
$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctor")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Consultations - Hopaclinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Consultations</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsultationModal">Add Consultation</button>
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
  <form method="post">
  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Doctor</th>
        <th>Reason</th>
        <th>Diagnosis</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($consultations as $con): ?>
        <tr>
          <td><?= $con['id'] ?></td>
          <td><?= htmlspecialchars($con['patient_name']) ?></td>
          <td><?= htmlspecialchars($con['doctor_name']) ?></td>
          <?php if ($role === 'admin' || $user_id == $con['doctor_id']): ?>
            <td><input type="text" name="reason" value="<?= htmlspecialchars($con['reason']) ?>" class="form-control"></td>
            <td><input type="text" name="diagnosis" value="<?= htmlspecialchars($con['diagnosis']) ?>" class="form-control"></td>
            <td><?= $con['consultation_date'] ?></td>
            <td>
              <input type="hidden" name="consultation_id" value="<?= $con['id'] ?>">
              <button type="submit" name="inline_update_consultation" class="btn btn-sm btn-success">Save</button>
              <a href="?delete_id=<?= $con['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
            </td>
          <?php else: ?>
            <td><?= htmlspecialchars($con['reason']) ?></td>
            <td><?= htmlspecialchars($con['diagnosis']) ?></td>
            <td><?= $con['consultation_date'] ?></td>
            <td>-</td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </form>
</div>

<!-- Modal -->
<div class="modal fade" id="addConsultationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add New Consultation</h5></div>
      <div class="modal-body">
        <input type="hidden" name="add_consultation" value="1">
        <div class="mb-3">
          <label class="form-label">Patient</label>
          <select name="patient_id" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($patients as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Doctor</label>
          <select name="doctor_id" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($doctors as $d): ?>
              <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Diagnosis</label>
          <textarea name="diagnosis" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Consultation Date</label>
          <input type="date" name="consultation_date" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../partials/footer.php'; ?>
</body>
</html>

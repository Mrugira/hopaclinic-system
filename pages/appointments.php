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

// Add appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $stmt = $pdo->prepare("INSERT INTO appointment (patient_id, doctor_id, appointment_date, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['patient_id'], $_POST['doctor_id'], $_POST['appointment_date'], $_POST['status']]);
}

// Update appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $date = $_POST['appointment_date'];

    $stmt = $pdo->prepare("SELECT doctor_id FROM appointment WHERE id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();

    if ($appointment && ($role === 'admin' || ($role === 'doctor' && $appointment['doctor_id'] == $user_id))) {
        $pdo->prepare("UPDATE appointment SET appointment_date = ?, status = ? WHERE id = ?")
            ->execute([$date, $status, $id]);
    }
}

// Delete appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("SELECT doctor_id FROM appointment WHERE id = ?");
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();

    if ($appointment && ($role === 'admin' || ($role === 'doctor' && $appointment['doctor_id'] == $user_id))) {
        $pdo->prepare("DELETE FROM appointment WHERE id = ?")->execute([$id]);
    }
}

$appointments = $pdo->query("SELECT a.id, p.name AS patient_name, d.name AS doctor_name, a.appointment_date, a.status, a.doctor_id
                             FROM appointment a
                             JOIN patient p ON a.patient_id = p.id
                             JOIN doctor d ON a.doctor_id = d.id")->fetchAll();
$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctor")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointments - Hopaclinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Appointments</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAppointmentModal">Add Appointment</button>
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
        <th>Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
   <tbody>
  <?php foreach ($appointments as $a): ?>
    <tr>
      <td><?= $a['id'] ?></td>
      <td><?= htmlspecialchars($a['patient_name']) ?></td>
      <td><?= htmlspecialchars($a['doctor_name']) ?></td>
      <td>
        <form method="post" class="d-flex">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="datetime-local" name="appointment_date"
                 value="<?= date('Y-m-d\TH:i', strtotime($a['appointment_date'])) ?>"
                 class="form-control form-control-sm me-2" required>
      </td>
      <td>
          <select name="status" class="form-select form-select-sm" required>
            <option value="scheduled" <?= $a['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="completed" <?= $a['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $a['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
      </td>
      <td class="d-flex gap-1">
        <?php if ($role === 'admin' || ($role === 'doctor' && $a['doctor_id'] == $user_id)): ?>
          <button type="submit" name="update_appointment" class="btn btn-sm btn-outline-success">Update</button>
          <button type="submit" name="delete_appointment" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this appointment?')">Delete</button>
        <?php else: ?>
          <span class="text-muted">no access</span>
        <?php endif; ?>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</tbody>
  </table>
</div>

<!-- Add Appointment Modal -->
<div class="modal fade" id="addAppointmentModal" tabindex="-1" aria-labelledby="addAppointmentLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Schedule Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="add_appointment" value="1">
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
          <label class="form-label">Date & Time</label>
          <input type="datetime-local" name="appointment_date" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-control" required>
            <option value="scheduled">Scheduled</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Save</button></div>
    </form>
  </div>
</div>


<?php include '../partials/footer.php'; ?>
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
</body>
</html>

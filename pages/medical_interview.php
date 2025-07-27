<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

$user = $_SESSION['user'];
$role = $user['role'];
$logged_doctor_id = $user['id'] ?? null;

// Add interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_interview'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $interview_notes = $_POST['interview_notes'];
    $interview_date = $_POST['interview_date'];

    $stmt = $pdo->prepare("INSERT INTO medical_interview (patient_id, doctor_id, interview_notes, interview_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $interview_notes, $interview_date]);
}

// Update interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_interview'])) {
    $id = $_POST['interview_id'];
    $interview_notes = $_POST['interview_notes'];
    $interview_date = $_POST['interview_date'];

    // Check if this doctor is allowed to update this interview
    $stmt = $pdo->prepare("SELECT doctor_id FROM medical_interview WHERE id = ?");
    $stmt->execute([$id]);
    $owner = $stmt->fetchColumn();

    if ($role === 'admin' || $owner == $logged_doctor_id) {
        $stmt = $pdo->prepare("UPDATE medical_interview SET interview_notes = ?, interview_date = ? WHERE id = ?");
        $stmt->execute([$interview_notes, $interview_date, $id]);
    }
}

// Delete interview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_interview'])) {
    $id = $_POST['interview_id'];

    $stmt = $pdo->prepare("SELECT doctor_id FROM medical_interview WHERE id = ?");
    $stmt->execute([$id]);
    $owner = $stmt->fetchColumn();

    if ($role === 'admin' || $owner == $logged_doctor_id) {
        $stmt = $pdo->prepare("DELETE FROM medical_interview WHERE id = ?");
        $stmt->execute([$id]);
    }
}

$interviews = $pdo->query("SELECT mi.id, p.name AS patient_name, d.name AS doctor_name, d.id AS doctor_id,
                                  mi.interview_notes, mi.interview_date
                           FROM medical_interview mi
                           JOIN patient p ON mi.patient_id = p.id
                           JOIN doctor d ON mi.doctor_id = d.id")->fetchAll();

$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medical Interviews</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Medical Interviews</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInterviewModal">Add Interview</button>
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
        <th>Notes</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($interviews as $int): ?>
      <tr>
        <form method="post">
          <input type="hidden" name="interview_id" value="<?= $int['id'] ?>">
          <td><?= $int['id'] ?></td>
          <td><?= htmlspecialchars($int['patient_name']) ?></td>
          <td><?= htmlspecialchars($int['doctor_name']) ?></td>
          <td>
            <textarea name="interview_notes" class="form-control" rows="2"><?= htmlspecialchars($int['interview_notes']) ?></textarea>
          </td>
          <td><input type="date" name="interview_date" class="form-control" value="<?= $int['interview_date'] ?>"></td>
          <td class="d-flex gap-1">
            <?php if ($role === 'admin' || $int['doctor_id'] == $logged_doctor_id): ?>
              <button type="submit" name="update_interview" class="btn btn-sm btn-outline-success">Update</button>
              <button type="submit" name="delete_interview" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</button>
            <?php else: ?>
              <span class="text-muted">No Access</span>
            <?php endif; ?>
          </td>
        </form>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Interview Modal -->
<div class="modal fade" id="addInterviewModal" tabindex="-1" aria-labelledby="addInterviewLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Interview</h5></div>
      <div class="modal-body">
        <input type="hidden" name="add_interview" value="1">
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
          <label class="form-label">Doctor ID</label>
          <input type="number" name="doctor_id" class="form-control" value="<?= $logged_doctor_id ?>" readonly required>
        </div>
        <div class="mb-3">
          <label class="form-label">Interview Notes</label>
          <textarea name="interview_notes" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Interview Date</label>
          <input type="date" name="interview_date" class="form-control" required>
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

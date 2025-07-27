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

//Add result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exam_result'])) {
    $stmt = $pdo->prepare("INSERT INTO examination_result (examination_id, result, result_date, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['examination_id'],
        $_POST['result'],
        $_POST['result_date'],
        $_POST['status']
    ]);
}

//Update result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_result'])) {
    $stmt = $pdo->prepare("SELECT e.doctor_id FROM examination_result er JOIN examination e ON e.id = er.examination_id WHERE er.id = ?");
    $stmt->execute([$_POST['result_id']]);
    $row = $stmt->fetch();

    if ($row && ($role === 'admin' || ($role === 'doctor' && $user_id == $row['doctor_id']))) {
        $pdo->prepare("UPDATE examination_result SET result = ?, result_date = ?, status = ? WHERE id = ?")
            ->execute([
                $_POST['edit_result'],
                $_POST['edit_result_date'],
                $_POST['edit_status'],
                $_POST['result_id']
            ]);
    }
}

//Delete
if (isset($_GET['delete_result'])) {
    $stmt = $pdo->prepare("SELECT e.doctor_id FROM examination_result er JOIN examination e ON e.id = er.examination_id WHERE er.id = ?");
    $stmt->execute([$_GET['delete_result']]);
    $row = $stmt->fetch();

    if ($row && ($role === 'admin' || ($role === 'doctor' && $user_id == $row['doctor_id']))) {
        $pdo->prepare("DELETE FROM examination_result WHERE id = ?")->execute([$_GET['delete_result']]);
    }
    header("Location: examination_result.php");
    exit;
}
$results = $pdo->query("SELECT er.id, e.id AS examination_id, e.exam_type, e.doctor_id, er.result, er.result_date, er.status
                        FROM examination_result er
                        JOIN examination e ON er.examination_id = e.id")->fetchAll();
$examinations = $pdo->query("SELECT id FROM examination")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Examination Results - Hopaclinic</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style>
      tr:nth-child(even) {
            background-color: #f1f1f1;
        }

        tr:hover {
            background-color: #e9f7ef;
        }
  </style>
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Examination Results</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamResultModal">Add Result</button>
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
        <th>Exam ID</th>
        <th>Exam Type</th>
        <th>Result</th>
        <th>Result Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($results as $res): ?>
      <tr>
        <form method="post">
          <input type="hidden" name="result_id" value="<?= $res['id'] ?>">
          <td><?= $res['id'] ?></td>
          <td><?= $res['examination_id'] ?></td>
          <td><?= htmlspecialchars($res['exam_type']) ?></td>
          <td><input name="edit_result" class="form-control form-control-sm" value="<?= htmlspecialchars($res['result']) ?>"></td>
          <td><input type="date" name="edit_result_date" class="form-control form-control-sm" value="<?= $res['result_date'] ?>"></td>
          <td>
            <select name="edit_status" class="form-select form-select-sm">
              <option value="pending" <?= htmlspecialchars($res['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="completed"<?= htmlspecialchars($res['status']) === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
          </td>
          <td>
            <?php if ($role === 'admin' || ($role === 'doctor' && $user_id == $res['doctor_id'])): ?>
              <button type="submit" name="update_result" class="btn btn-sm btn-success">Save</button>
              <a href="?delete_result=<?= $res['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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
<!-- Add Modal -->
<div class="modal fade" id="addExamResultModal" tabindex="-1" aria-labelledby="addExamResultLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Examination Result</h5></div>
      <div class="modal-body">
        <input type="hidden" name="add_exam_result" value="1">
        <div class="mb-3">
          <label class="form-label">Examination</label>
          <select name="examination_id" class="form-control" required>
            <option value="">Select</option>
            <?php foreach ($examinations as $exam): ?>
              <option value="<?= $exam['id'] ?>"><?= $exam['id'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Result</label>
          <textarea name="result" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Result Date</label>
          <input type="date" name="result_date" class="form-control" required>
        </div>
        <div>
          <label class="form-label">Status</label>
          <select name="status" class="form-control" required>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
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

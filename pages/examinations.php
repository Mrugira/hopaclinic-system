<?php 
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';
// ADD EXAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_examination'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $test_type = $_POST['test_type'];
    $exam_date = $_POST['exam_date'];
    $comment = $_POST['comment'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("INSERT INTO examination (patient_id, doctor_id, exam_type, exam_date, comment, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $test_type, $exam_date, $comment, $status]);
}

// UPDATE EXAM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exam'])) {
    $exam_id = $_POST['edit_exam_id']; // 
    $comment = $_POST['edit_comment'];
    $status = $_POST['edit_status'];

    $stmt = $pdo->prepare("SELECT doctor_id FROM examination WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if ($exam && $_SESSION['user']['role'] === 'doctor' && $_SESSION['user']['id'] == $exam['doctor_id']) {
        $update = $pdo->prepare("UPDATE examination SET comment = ?, status = ? WHERE id = ?");
        $update->execute([$comment, $status, $exam_id]);
    }
}
// DELETE EXAM
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT doctor_id FROM examination WHERE id = ?");
    $stmt->execute([$delete_id]);
    $exam = $stmt->fetch();

    if ($exam && $_SESSION['user']['role'] === 'doctor' && $_SESSION['user']['id'] == $exam['doctor_id']) {
        $delete = $pdo->prepare("DELETE FROM examination WHERE id = ?");
        $delete->execute([$delete_id]);
    }
    header("Location: examinations.php");
    exit;
}

$examinations = $pdo->query("SELECT e.id, p.name AS patient_name, d.name AS doctor_name, d.id AS doctor_id,
                                     e.exam_type, e.exam_date, e.comment, e.status
                              FROM examination e
                              JOIN patient p ON e.patient_id = p.id
                              JOIN doctor d ON e.doctor_id = d.id")->fetchAll();
$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
$doctors = $pdo->query("SELECT id, name FROM doctor")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Examinations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4">Examinations</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addExaminationModal">Add Examination</button>
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
                <th>Test Type</th>
                <th>Date</th>
                <th>Comment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($examinations as $exam): ?>
            <tr>
                <td><?= $exam['id'] ?></td>
                <td><?= htmlspecialchars($exam['patient_name']) ?></td>
                <td><?= htmlspecialchars($exam['doctor_name']) ?></td>
                <td><?= htmlspecialchars($exam['exam_type']) ?></td>
                <td><?= $exam['exam_date'] ?></td>
                <td><?= htmlspecialchars($exam['comment']) ?></td>
                <td><?= htmlspecialchars($exam['status']) ?></td>
                <td>
                    <?php if ($_SESSION['user']['role'] === 'doctor' && $_SESSION['user']['id'] == $exam['doctor_id']): ?>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editExamModal<?= $exam['id'] ?>">Edit</button>
                        <a href="?delete_id=<?= $exam['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
                    <?php else: ?>
                        no action
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="modal fade" id="addExaminationModal" tabindex="-1" aria-labelledby="addExaminationLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="addExaminationLabel">Add New Examination</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="add_examination" value="1">

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
                <?php if ($_SESSION['user']['role'] === 'doctor'): ?>
                    <input type="hidden" name="doctor_id" value="<?= $_SESSION['user']['id'] ?>">
                    <input type="text" class="form-control" value="You (Logged-in Doctor)" disabled>
                <?php else: ?>
                    <select name="doctor_id" class="form-control" required>
                        <option value="">Select</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label>Test Type</label>
                <input type="text" name="test_type" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Examination Date</label>
                <input type="date" name="exam_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Comment</label>
                <input type="text" name="comment" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                    <option value="Requires Follow-up">Requires Follow-up</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success">Save</button>
        </div>
    </form>
  </div>
</div>
<?php foreach ($examinations as $exam): ?>
<?php if ($_SESSION['user']['role'] === 'doctor' && $_SESSION['user']['id'] == $exam['doctor_id']): ?>
<div class="modal fade" id="editExamModal<?= $exam['id'] ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="edit_exam_id" value="<?= $exam['id'] ?>">
      <div class="modal-header">
        <h5 class="modal-title">Edit Examination</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Comment</label>
          <textarea name="edit_comment" class="form-control"><?= htmlspecialchars($exam['comment']) ?></textarea>
        </div>
        <div class="mb-3">
          <label>Status</label>
          <select name="edit_status" class="form-control">
            <option <?= $exam['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option <?= $exam['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option <?= $exam['status'] === 'Requires Follow-up' ? 'selected' : '' ?>>Requires Follow-up</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="update_exam" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>


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

<?php include '../partials/footer.php'; ?>
</body>
</html>

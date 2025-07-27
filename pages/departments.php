<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO department (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
}

$departments = $pdo->query("SELECT * FROM department")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departments - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Departments</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">Add Department</button>
    </div>

    

<div class="mb-3">
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    searchInput.addEventListener('input', function () {
      const filter = searchInput.value.toLowerCase();
      const rows = document.querySelectorAll("table tbody tr");

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
      });
    });
  });
</script>

<!-- Export Button -->
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
<script>
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
    downloadLink.download = "table_export.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  }
</script>

<table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Department Name</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($departments as $dept): ?>
            <tr>
                <td><?= $dept['id'] ?></td>
                <td><?= htmlspecialchars($dept['name']) ?></td>
                <td><?= $dept['description'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add Department</h5></div>
        <div class="modal-body">
            <input type="hidden" name="add_department" value="1">
            <div class="mb-3">
                <label class="form-label">Department Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success">Save</button></div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../partials/footer.php'; ?>
</body>
</html>
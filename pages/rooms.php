<?php
require '../config/db.php';
// ✅ Add Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];
    $availability = $_POST['availability'];

    $stmt = $pdo->prepare("INSERT INTO room (room_number, type, capacity, availability) VALUES (?, ?, ?, ?)");
    $stmt->execute([$room_number, $type, $capacity, $availability]);
}

// ✅ Assign Patient to Room (with duplication & capacity check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_patient'])) {
    $room_id = $_POST['room_id'];
    $patient_id = $_POST['patient_id'];
    $assigned_date = $_POST['assigned_date'];

    $assigned_count = $pdo->prepare("SELECT COUNT(*) FROM room_assignment WHERE room_id = ?");
    $assigned_count->execute([$room_id]);
    $assigned = $assigned_count->fetchColumn();

    $capacity_stmt = $pdo->prepare("SELECT capacity FROM room WHERE id = ?");
    $capacity_stmt->execute([$room_id]);
    $capacity = $capacity_stmt->fetchColumn();

    $check = $pdo->prepare("SELECT COUNT(*) FROM room_assignment WHERE room_id = ? AND patient_id = ? AND assigned_date = ?");
    $check->execute([$room_id, $patient_id, $assigned_date]);

    if ($check->fetchColumn() == 0 && $assigned < $capacity) {
        $stmt = $pdo->prepare("INSERT INTO room_assignment (room_id, patient_id, assigned_date) VALUES (?, ?, ?)");
        $stmt->execute([$room_id, $patient_id, $assigned_date]);
        $new_status = ($assigned + 1 >= $capacity) ? 'Occupied' : 'Available';
        $pdo->prepare("UPDATE room SET availability = ? WHERE id = ?")->execute([$new_status, $room_id]);
    }
}

// ✅ Update Room Status Manually
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = $_POST['room_id'];
    $availability = $_POST['availability'];
    $pdo->prepare("UPDATE room SET availability = ? WHERE id = ?")->execute([$availability, $room_id]);
}

// ✅ Stop Room Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stop_assignment'])) {
    $assignment_id = $_POST['assignment_id'];

    $stmt = $pdo->prepare("SELECT room_id FROM room_assignment WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $room_id = $stmt->fetchColumn();

    $pdo->prepare("DELETE FROM room_assignment WHERE id = ?")->execute([$assignment_id]);

    // Recalculate assignment count
    $assigned_count = $pdo->prepare("SELECT COUNT(*) FROM room_assignment WHERE room_id = ?");
    $assigned_count->execute([$room_id]);
    $assigned = $assigned_count->fetchColumn();

    $capacity_stmt = $pdo->prepare("SELECT capacity FROM room WHERE id = ?");
    $capacity_stmt->execute([$room_id]);
    $capacity = $capacity_stmt->fetchColumn();

    $new_status = ($assigned >= $capacity) ? 'Occupied' : 'Available';
    $pdo->prepare("UPDATE room SET availability = ? WHERE id = ?")->execute([$new_status, $room_id]);
}

$rooms = $pdo->query("SELECT * FROM room")->fetchAll();
$assignments = $pdo->query("SELECT ra.id, r.room_number, p.name AS patient_name, ra.assigned_date
                            FROM room_assignment ra
                            JOIN room r ON ra.room_id = r.id
                            JOIN patient p ON ra.patient_id = p.id")->fetchAll();
$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Management - Hopaclinic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <style>
        body { background-color: #f8f9fa; }
        .table td, .table th { vertical-align: middle; }
        .badge { font-size: 0.9rem; }
        .card { border-radius: 10px; }
        .form-select, .form-control { font-size: 0.9rem; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="container my-4">
    <h2 class="mb-4 text-primary">Room Management</h2>

    <!-- Room Table -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Rooms</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Room Number</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>availability</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['room_number']) ?></td>
                        <td><?= htmlspecialchars($room['type']) ?></td>
                        <td><?= htmlspecialchars($room['capacity']) ?></td>
                        <td>
                            <span class="badge bg-<?= $room['availability'] === 'Available' ? 'success' : 'danger' ?>">
                                <?= htmlspecialchars($room['availability']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <select name="availability" class="form-select form-select-sm w-auto">
                                    <option value="Available" <?= $room['availability'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                    <option value="Occupied" <?= $room['availability'] === 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-primary">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<!-- Add Room -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">Add New Room</div>
    <div class="card-body">
        <form method="post" class="row g-2 align-items-end">
            <input type="hidden" name="add_room" value="1">
            <div class="col-md-3">
                <label class="form-label">Room Number</label>
                <input type="text" name="room_number" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
        <option value="">room type</option>
        <option value="Consultation">Consultation</option>
        <option value="Examination">Examination</option>
        <option value="Surgery">Surgery</option>
        <option value="Ward">Ward</option>
        <option value="ICU">ICU</option>
        <option value="Maternity">Maternity</option>
    </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">availability</label>
                <select name="availability" class="form-select" required>
                    <option value="Available">Available</option>
                    <option value="Occupied">Occupied</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">Add Room</button>
            </div>
        </form>
    </div>
</div>

    <!-- Assign Patient -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Assign Patient to Room</div>
        <div class="card-body">
            <form method="post" class="row g-2 align-items-end">
                <input type="hidden" name="assign_patient" value="1">
                <div class="col-md-4">
                    <label class="form-label">Room</label>
                    <select name="room_id" class="form-select" required>
                        <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= $room['room_number'] ?> (<?= $room['availability'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <?php foreach ($patients as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="assigned_date" class="form-control" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Assign</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Assignments -->
    <div class="card">
        <div class="card-header bg-dark text-white">Current Assignments</div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Room</th>
                        <th>Patient</th>
                        <th>Assigned Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['room_number']) ?></td>
                        <td><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td><?= htmlspecialchars($a['assigned_date']) ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                <button type="submit" name="stop_assignment" class="btn btn-sm btn-outline-danger">Stop</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php

?>

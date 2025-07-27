<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
require '../config/db.php';

// Fetch billing records
$stmt = $pdo->query("
    SELECT billing.*, patient.name AS patient_name, doctor.name AS doctor_name
    FROM billing
    LEFT JOIN patient ON billing.patient_id = patient.id
    LEFT JOIN doctor ON billing.doctor_id = doctor.id
    ORDER BY billing.billing_date DESC
");
$billings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing - Hopaclinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../partials/navbar.php'; ?>
<div class="container mt-4">
    <h2 class="mb-3">Billing Records</h2>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Amount</th>
                <th>Billing Date</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($billings as $bill): ?>
            <tr>
                <td><?= $bill['id'] ?></td>
                <td><?= htmlspecialchars($bill['patient_name']) ?></td>
                <td><?= htmlspecialchars($bill['doctor_name']) ?></td>
                <td>$<?= number_format($bill['amount'], 2) ?></td>
                <td><?= $bill['billing_date'] ?></td>
                <td><?= htmlspecialchars($bill['payment_method']) ?></td>
                <td><?= htmlspecialchars($bill['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include '../partials/footer.php'; ?>
</body>
</html>

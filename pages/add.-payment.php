<?php
require '../config/db.php';

// Fetch patients for dropdown
$patients = $pdo->query("SELECT id, name FROM patient")->fetchAll();

// Insert new payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    $patient_id = $_POST['patient_id'];
    $amount = $_POST['amount'];
    $date = $_POST['payment_date'];
    $method = $_POST['payment_method'];
    $purpose = $_POST['purpose'];

    $stmt = $pdo->prepare("INSERT INTO payment (patient_id, amount, payment_date, payment_method, purpose)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$patient_id, $amount, $date, $method, $purpose]);

    $success = true;
}

// Fetch all payments
$payments = $pdo->query("
    SELECT 
        p.id, 
        pt.name AS patient_name, 
        p.amount, 
        p.payment_date, 
        p.payment_method, 
        p.purpose, 
        p.status 
    FROM payment p 
    JOIN patient pt ON p.patient_id = pt.id
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <title>Payments - HopaClinic</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            padding: 30px;
           background-image: url('/hopaclinic/img/billingbg.webp');
        }

        h2 {
            color: #343a40;
        }

        .btn-toggle {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .btn-toggle:hover {
            background-color: #0b5ed7;
        }

        form {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin-bottom: 30px;
        }

        select, input[type="number"], input[type="text"], input[type="date"], button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            font-size: 16px;
        }

        button[type="submit"] {
            background-color: #198754;
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #157347;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            max-width: 600px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        th {
            background-color: #343a40;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f1f1f1;
        }

        tr:hover {
            background-color: #e9f7ef;
        }

        .filter-row {
            margin-bottom: 20px;
        }

        .form-select, .form-control {
            padding: 6px;
            font-size: 15px;
        }
        button{
            width: 20%;
        }
    </style>
</head>
<body>

<button onclick="toggleForm()" class="btn-toggle">➕ Add Payment</button>


<div id="paymentForm" style="display:none;">
    <?php if (!empty($success)): ?>
        <div class="alert">✅ Payment recorded successfully.</div>
    <?php endif; ?>

    <form method="post" action="">
        <h2>Payments</h2>
        <select name="patient_id" required>
            <option value="">Select Patient</option>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="number" name="amount" placeholder="Amount" required>
        <input type="date" name="payment_date" required>
        <input type="text" name="payment_method" placeholder="e.g., Cash, Card" required>
        <input type="text" name="purpose" placeholder="Purpose (e.g., consultation)" required>

        <button type="submit" name="submit_payment">Save Payment</button>
    </form>
</div>

<!-- Filter Section -->
<div class="filter-row">
    <label><strong>Search by column:</strong></label><br>
    <select class="form-select" id="columnSelect"></select>
    <input type="text" class="form-control" id="columnSearch" placeholder="Enter value to search...">
</div>

<!-- Payment Table -->
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Method</th>
            <th>Purpose</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($payments as $pay): ?>
            <tr>
                <td><?= $pay['id'] ?></td>
                <td><?= htmlspecialchars($pay['patient_name']) ?></td>
                <td><?= $pay['amount'] ?></td>
                <td><?= $pay['payment_date'] ?></td>
                <td><?= $pay['payment_method'] ?></td>
                <td><?= $pay['purpose'] ?></td>
                <td><?= $pay['status'] ?? 'N/A' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- JS to toggle form and handle search -->
<script>
function toggleForm() {
    const form = document.getElementById("paymentForm");
    form.style.display = form.style.display === "none" ? "block" : "none";
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('columnSearch');
    const columnSelect = document.getElementById('columnSelect');
    const table = document.querySelector("table");
    const headers = table.querySelectorAll("thead th");

    // Add options for each column
    headers.forEach((th, i) => {
        const option = document.createElement("option");
        option.value = i;
        option.textContent = th.textContent.trim();
        columnSelect.appendChild(option);
    });

    // Filter function
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

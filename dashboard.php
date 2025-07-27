<?php
session_start();
$user = $_SESSION['user'] ?? ['username' => 'Guest', 'role' => 'Viewer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hopaclinic Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .nav-link:hover, .nav-link.active { background-color: #495057; border-radius: 5px; transition: 0.3s; }
    .sidebar { width: 250px; min-height: 100vh; }
    .table-content { display: none; }
    .table-content.active { display: block; }
    iframe { width: 100%; height: 95vh; border: none; }
  </style>
  <script>
    function showTable(id, path) {
      document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
      document.getElementById('nav-' + id).classList.add('active');
      document.getElementById('content-frame').src = path;
    }
    window.onload = () => showTable('wlcmdashboard', 'pages/wlcmdashboard.php');
  </script>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <nav class="bg-dark text-white p-3 sidebar">
    <h1 class="text-center mb-4"><img src="/hopaclinic/img/logo.png" width="230" height="70">
</h1>
    <h3>&nbsp&nbsp&nbspWELCOME!</h3>
     <h4><?= htmlspecialchars($user['username']) ?>(<?= htmlspecialchars($user['role']) ?>)</h4>
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link text-white" id="nav-wlcmdashboard" href="#" onclick="showTable('wlcmdashboard', 'pages/wlcmdashboard.php')">🏠 Dashboard</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-patients" href="#" onclick="showTable('patients', 'pages/patients.php')">👥 Patients</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-appointments" href="#" onclick="showTable('appointments', 'pages/appointments.php')">📅 Appointments</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-doctors" href="#" onclick="showTable('doctors', 'pages/doctors.php')">🩺 Doctors</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-prescriptions" href="#" onclick="showTable('prescriptions', 'pages/prescriptions.php')">💊 Prescriptions</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-examinations" href="#" onclick="showTable('examinations', 'pages/examinations.php')">🧪 Examinations</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-rooms" href="#" onclick="showTable('rooms', 'pages/rooms.php')">🚪 Rooms</a></li>

       <li class="nav-item"><a class="nav-link text-white" id="nav-billing" href="#" onclick="showTable('billing', 'pages/add.-payment.php')"> <i class="fas fa-credit-card"></i> billing</a></li>

      <li class="nav-item"><a class="nav-link text-white" id="nav-departments" href="#" onclick="showTable('departments', 'pages/departments.php')">🏢 Departments</a></li>
      <?php if ($user['role'] === 'admin'): ?>
  <li class="nav-item">
    <a class="nav-link text-white" id="nav-users" href="#" onclick="showTable('users', 'pages/users.php')">
      👤 Users
    </a>
  </li>
<?php endif; ?>
      <li class="nav-item"><a class="nav-link text-white" id="nav-consultation" href="#" onclick="showTable('consultation', 'pages/consultation.php')">💬 Consultations</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-exam_results" href="#" onclick="showTable('exam_results', 'pages/examination_result.php')">📄 Exam Results</a></li>
      <li class="nav-item"><a class="nav-link text-white" id="nav-medical_interview" href="#" onclick="showTable('medical_interview', 'pages/medical_interview.php')">📝 Medical Interviews</a></li>
      <li class="nav-item mt-4"><a class="btn btn-outline-light w-100" href="auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
    </ul>
  </nav>

  <!-- Main Content with iframe -->
  <div class="flex-grow-1 p-4">
    <iframe id="content-frame" src=""></iframe>
  </div>
</div>
</body>
</html>

<?php
require_once('../config/db.php');
?>
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
    body {
   
   background-image: url('/hopaclinic/img/bg.png');
  background-size: cover; 
  background-repeat: no-repeat;
  background-position: center center;       
    }
    .nav-link:hover, .nav-link.active { background-color: #yellowgreen; border-radius: 5px; transition: 0.3s; }
    .dashboard-stats { display: flex; flex-wrap: wrap; gap: 1rem; }
    .dashboard-card{
      flex: 1;
      min-width: 200px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    h2{
      font-family: Source Sans Pro;
      font-size: 60px;
      color: #003300;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4 text-center">Welcome to Hopaclinic Managiment System</h2>
  <div class="dashboard-stats">

   <div class="dashboard-card text-center">
  <h5>Available Rooms</h5>
  <?php
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM room WHERE availability = 'Available'");
    $data = $stmt->fetch();
    echo '<p class="display-6">' . $data['total'] . '</p>';
  ?>
</div>

    <div class="dashboard-card text-center">
      <h5>Total Patients</h5>
      <?php
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM patient");
        $data = $stmt->fetch();
        echo '<p class="display-6">' . $data['total'] . '</p>';
      ?>
    </div>

    <div class="dashboard-card text-center">
      <h5>Today's Appointments</h5>
      <?php
        $today = date('Y-m-d');
       $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM appointment WHERE DATE(appointment_date) = ?");

        $stmt->execute([$today]);
        $data = $stmt->fetch();
        echo '<p class="display-6">' . $data['total'] . '</p>';
      ?>
    </div>

    <div class="dashboard-card text-center">
      <h5>Available Doctors</h5>
      <?php
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM doctor WHERE  status= 'available' ");
        $data = $stmt->fetch();
        echo '<p class="display-6">' . $data['total'] . '</p>';
      ?>
    </div>

    <div class="dashboard-card text-center">
      <h5>Pending Lab Results</h5>
      <?php
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM examination_result WHERE status = 'pending'");
        $data = $stmt->fetch();
        echo '<p class="display-6">' . $data['total'] . '</p>';
      ?>
    </div>

  </div>
</div>
</body>
</html>

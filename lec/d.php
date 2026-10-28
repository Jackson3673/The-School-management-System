<?php
session_start();

// Redirect if not logged in or not a lecturer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lecturer Dashboard | Hope Academy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
  <a class="navbar-brand" href="#"><i class="bi bi-person-badge-fill"></i> Lecturer Panel</a>
  <div class="ms-auto text-white">
    Welcome, <?= htmlspecialchars($_SESSION['fullname']); ?> |
    <a href="../logout.php" class="text-decoration-none text-danger ms-2">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h3 class="mb-4 text-primary">Dashboard</h3>

  <div class="row g-4">
    <!-- 1. Dashboard Overview -->
    <div class="col-md-4">
      <a href="overview.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-speedometer2 fs-1 text-primary"></i>
            <h5 class="mt-2">Dashboard Overview</h5>
            <p>Stats on today's attendance, classes & activities</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 2. My Classes -->
    <div class="col-md-4">
      <a href="my_classes.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-journal-bookmark fs-1 text-success"></i>
            <h5 class="mt-2">My Classes</h5>
            <p>View assigned classes and student lists</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 3. Attendance -->
    <div class="col-md-4">
      <a href="attendance.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-check2-square fs-1 text-warning"></i>
            <h5 class="mt-2">Attendance</h5>
            <p>Mark and view student attendance records</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 4. Academic Records -->
    <div class="col-md-4">
      <a href="academic_records.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-pencil-square fs-1 text-danger"></i>
            <h5 class="mt-2">Academic Records</h5>
            <p>Enter/update marks and view performance</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 5. Student Info -->
    <div class="col-md-4">
      <a href="student_info.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-person-lines-fill fs-1 text-info"></i>
            <h5 class="mt-2">Student Info</h5>
            <p>Search and view student academic history</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 6. Reports -->
    <div class="col-md-4">
      <a href="reports.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-bar-chart-line-fill fs-1 text-secondary"></i>
            <h5 class="mt-2">Reports</h5>
            <p>Generate class and attendance reports</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 7. Change Password -->
    <div class="col-md-4">
      <a href="change_password.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-lock-fill fs-1 text-dark"></i>
            <h5 class="mt-2">Change Password</h5>
            <p>Update your account password</p>
          </div>
        </div>
      </a>
    </div>

    <!-- 8. Logout -->
    <div class="col-md-4">
      <a href="../logout.php" class="text-decoration-none">
        <div class="card shadow h-100">
          <div class="card-body text-center">
            <i class="bi bi-box-arrow-right fs-1 text-danger"></i>
            <h5 class="mt-2">Logout</h5>
            <p>Sign out of your account</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

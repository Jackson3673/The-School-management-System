<?php
// index.php
session_start();
// If logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
  if ($_SESSION['role'] === 'admin') {
    header("Location: modules/dashboard/admin.php");
  } elseif ($_SESSION['role'] === 'student') {
    header("Location: modules/dashboard/student.php");
  } else {
    header("Location: modules/lecturer/dashboard.php");
  }
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hope Academy | School Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body,
    html {
      margin: 0;
      padding: 0;
      height: 100%;
    }

    body {
      background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1400&q=80') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Segoe UI', sans-serif;
      position: relative;
    }

    /* Overlay */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(0, 0, 50, 0.6);
      z-index: 0;
    }

    .content {
      position: relative;
      z-index: 1;
    }

    .card,
    .feature-card {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark bg-opacity-75 content">
    <div class="container">
      <a class="navbar-brand" href="#"><i class="bi bi-mortarboard-fill"></i> Hope Academy</a>
      <div class="collapse navbar-collapse justify-content-end">
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link" href="modules/auth/register.php"><i
                class="bi bi-person-plus-fill"></i> Register</a></li>
          <li class="nav-item"><a class="nav-link" href="modules/auth/login.php"><i
                class="bi bi-box-arrow-in-right"></i> Login</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Welcome Section -->
  <div class="container d-flex justify-content-center align-items-center content" style="min-height: 80vh;">
    <div class="card p-5 shadow-lg text-center" style="max-width: 600px;">
      <div class="card-body">
        <h1 class="card-title mb-3 text-primary"><i class="bi bi-mortarboard-fill"></i> Welcome</h1>
        <p class="lead mb-4">
          Hope Academy School Management System simplifies operations: registration, fee tracking, academic records, and
          more.
        </p>
        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
          <a href="modules/auth/register.php" class="btn btn-primary btn-lg px-4 gap-3">Register Now</a>
          <a href="modules/auth/login.php" class="btn btn-outline-secondary btn-lg px-4">Login</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
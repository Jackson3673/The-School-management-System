<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];

$host = 'localhost';
$db = 'naini';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get class_id from GET
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die("Class ID is required.");
}

// Verify class belongs to lecturer
$stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$class_id, $lecturer_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    die("You don't have permission to view this class.");
}

// Fetch attendance records grouped by date
$stmt = $pdo->prepare("
    SELECT a.attendance_date, s.fullname, a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.class_id = ?
    ORDER BY a.attendance_date DESC, s.fullname
");
$stmt->execute([$class_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize data by date for display
$attendance_by_date = [];
foreach ($records as $rec) {
    $attendance_by_date[$rec['attendance_date']][] = $rec;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>View Attendance - <?= htmlspecialchars($class['class_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 mb-4">
  <a class="navbar-brand" href="d.php">Lecturer Panel</a>
  <div class="ms-auto text-white">
    Welcome, <?= htmlspecialchars($_SESSION['fullname']); ?> |
    <a href="../logout.php" class="text-danger text-decoration-none ms-2">Logout</a>
  </div>
</nav>

<div class="container">
  <h2 class="mb-4">Attendance History for <?= htmlspecialchars($class['class_name']) ?></h2>

  <?php if (empty($attendance_by_date)): ?>
    <p>No attendance records found for this class.</p>
  <?php else: ?>
    <?php foreach ($attendance_by_date as $date => $records): ?>
      <h5><?= htmlspecialchars($date) ?></h5>
      <table class="table table-striped table-bordered mb-4">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $rec): ?>
            <tr>
              <td><?= htmlspecialchars($rec['fullname']) ?></td>
              <td><?= ucfirst(htmlspecialchars($rec['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

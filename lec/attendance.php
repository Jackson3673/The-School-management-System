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

// Fetch classes assigned to lecturer
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_class_id = $_POST['class_id'] ?? null;
$today = date('Y-m-d');
$message = "";

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $selected_class_id = intval($_POST['class_id']);
    $attendance_data = $_POST['attendance'] ?? [];

    foreach ($attendance_data as $student_id => $status) {
        // Insert or update attendance for student on the date
        $stmt = $pdo->prepare("
            INSERT INTO attendance (class_id, student_id, attendance_date, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$selected_class_id, $student_id, $today, $status]);
    }

    $message = "Attendance for " . htmlspecialchars($today) . " recorded successfully.";
}

// Fetch students for selected class
$students = [];
if ($selected_class_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.fullname
        FROM students s
        JOIN class_students cs ON s.id = cs.student_id
        WHERE cs.class_id = ?
        ORDER BY s.fullname
    ");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing attendance records for today
    $stmt = $pdo->prepare("
        SELECT student_id, status FROM attendance
        WHERE class_id = ? AND attendance_date = ?
    ");
    $stmt->execute([$selected_class_id, $today]);
    $existing_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $existing_attendance = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Attendance | Lecturer Dashboard</title>
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

  <h2 class="mb-4">Mark Attendance</h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
  <?php endif; ?>

  <!-- Select Class -->
  <form method="POST" class="mb-4 d-flex align-items-center gap-2">
    <label for="class_id" class="form-label mb-0 me-2">Select Class:</label>
    <select name="class_id" id="class_id" class="form-select w-auto" onchange="this.form.submit()" required>
      <option value="">-- Choose class --</option>
      <?php foreach ($classes as $class): ?>
        <option value="<?= $class['id'] ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($class['class_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <?php if ($selected_class_id): ?>
      <!-- View Attendance Button -->
      <a href="view_attendance.php?class_id=<?= $selected_class_id ?>" class="btn btn-info ms-2">View Attendance</a>
    <?php endif; ?>
  </form>

  <?php if ($selected_class_id && count($students) > 0): ?>
    <form method="POST">
      <input type="hidden" name="class_id" value="<?= $selected_class_id ?>" />
      <input type="hidden" name="submit_attendance" value="1" />

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Present</th>
            <th>Absent</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): 
            $status = $existing_attendance[$student['id']] ?? '';
          ?>
            <tr>
              <td><?= htmlspecialchars($student['fullname']) ?></td>
              <td class="text-center">
                <input type="radio" name="attendance[<?= $student['id'] ?>]" value="present" <?= $status === 'present' ? 'checked' : '' ?> required>
              </td>
              <td class="text-center">
                <input type="radio" name="attendance[<?= $student['id'] ?>]" value="absent" <?= $status === 'absent' ? 'checked' : '' ?> required>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <button type="submit" class="btn btn-primary">Submit Attendance for <?= htmlspecialchars($today) ?></button>
    </form>

  <?php elseif ($selected_class_id): ?>
    <p>No students assigned to this class yet.</p>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
$message = "";

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
    $selected_class_id = intval($_POST['class_id']);
    $marks_data = $_POST['marks'] ?? [];
    $grades_data = $_POST['grades'] ?? [];

    foreach ($marks_data as $student_id => $mark) {
        $grade = $grades_data[$student_id] ?? null;
        $mark = intval($mark);

        // Insert or update academic record
        $stmt = $pdo->prepare("
            INSERT INTO academic_records (class_id, student_id, marks, grade)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE marks = VALUES(marks), grade = VALUES(grade), updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$selected_class_id, $student_id, $mark, $grade]);
    }

    $message = "Academic records updated successfully.";
}

// Fetch students for selected class
$students = [];
$existing_records = [];
if ($selected_class_id) {
    // Verify the class belongs to this lecturer
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ? AND lecturer_id = ?");
    $stmt->execute([$selected_class_id, $lecturer_id]);
    $classCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$classCheck) {
        die("You don't have permission to view this class.");
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.fullname
        FROM students s
        JOIN class_students cs ON s.id = cs.student_id
        WHERE cs.class_id = ?
        ORDER BY s.fullname
    ");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing academic records
    $stmt = $pdo->prepare("
        SELECT student_id, marks, grade FROM academic_records
        WHERE class_id = ?
    ");
    $stmt->execute([$selected_class_id]);
    $existing_records = $stmt->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Academic Records | Lecturer Dashboard</title>
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

  <h2 class="mb-4">Enter/Update Academic Records</h2>

  <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
  <?php endif; ?>

  <!-- Select Class -->
  <form method="POST" class="mb-4">
    <label for="class_id" class="form-label">Select Class:</label>
    <select name="class_id" id="class_id" class="form-select w-auto" onchange="this.form.submit()" required>
      <option value="">-- Choose class --</option>
      <?php foreach ($classes as $class): ?>
        <option value="<?= $class['id'] ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($class['class_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($selected_class_id && count($students) > 0): ?>
    <form method="POST">
      <input type="hidden" name="class_id" value="<?= $selected_class_id ?>" />
      <input type="hidden" name="submit_marks" value="1" />

      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Marks</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $student): 
            $marks = $existing_records[$student['id']]['marks'] ?? '';
            $grade = $existing_records[$student['id']]['grade'] ?? '';
          ?>
            <tr>
              <td><?= htmlspecialchars($student['fullname']) ?></td>
              <td>
                <input 
                  type="number" 
                  name="marks[<?= $student['id'] ?>]" 
                  value="<?= htmlspecialchars($marks) ?>" 
                  class="form-control" 
                  min="0" max="100" 
                  required
                >
              </td>
              <td>
                <input 
                  type="text" 
                  name="grades[<?= $student['id'] ?>]" 
                  value="<?= htmlspecialchars($grade) ?>" 
                  class="form-control" 
                  maxlength="5"
                >
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <button type="submit" class="btn btn-primary">Save Records</button>
    </form>

  <?php elseif ($selected_class_id): ?>
    <p>No students assigned to this class yet.</p>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

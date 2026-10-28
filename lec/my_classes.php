<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id']; // Assuming you store lecturer's user id here

// DB connection (update with your credentials)
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

// Handle add class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    if ($class_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, lecturer_id) VALUES (?, ?)");
        $stmt->execute([$class_name, $lecturer_id]);
        header("Location: my_classes.php");
        exit();
    }
}

// Handle add student to class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $class_id = intval($_POST['class_id']);
    $student_email = trim($_POST['student_email']);
    $student_fullname = trim($_POST['student_fullname']);
    $registration_number = trim($_POST['registration_number']);

    // Check if student exists by email
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$student_email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        // Insert new student
        $stmt = $pdo->prepare("INSERT INTO students (fullname, email, registration_number) VALUES (?, ?, ?)");
        $stmt->execute([$student_fullname, $student_email, $registration_number]);
        $student_id = $pdo->lastInsertId();
    } else {
        $student_id = $student['id'];
    }

    // Link student to class
    try {
        $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
        $stmt->execute([$class_id, $student_id]);
    } catch (PDOException $e) {
        // Handle duplicate or other errors silently for now
    }

    header("Location: my_classes.php");
    exit();
}

// Fetch classes assigned to this lecturer
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Classes | Lecturer Dashboard</title>
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

  <h2 class="mb-4">My Classes</h2>

  <!-- Add Class Form -->
  <form method="POST" class="mb-5">
    <div class="input-group">
      <input type="text" name="class_name" class="form-control" placeholder="New Class Name" required />
      <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
    </div>
  </form>

  <?php if (count($classes) === 0): ?>
    <p>No classes assigned yet.</p>
  <?php else: ?>

    <?php foreach ($classes as $class): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5><?= htmlspecialchars($class['class_name']) ?></h5>
        </div>
        <div class="card-body">
          <!-- Student Registration Form -->
          <form method="POST" class="row g-3 align-items-center mb-3">
            <input type="hidden" name="class_id" value="<?= $class['id'] ?>" />
            <div class="col-md-3">
              <input type="text" name="student_fullname" class="form-control" placeholder="Student Full Name" required />
            </div>
            <div class="col-md-3">
              <input type="email" name="student_email" class="form-control" placeholder="Student Email" required />
            </div>
            <div class="col-md-3">
              <input type="text" name="registration_number" class="form-control" placeholder="Reg Number" required />
            </div>
            <div class="col-md-3">
              <button type="submit" name="add_student" class="btn btn-success w-100">Register Student</button>
            </div>
          </form>

          <!-- List registered students -->
          <h6>Registered Students:</h6>
          <ul class="list-group">
            <?php
              $stmt = $pdo->prepare("
                SELECT s.fullname, s.email, s.registration_number
                FROM students s
                INNER JOIN class_students cs ON s.id = cs.student_id
                WHERE cs.class_id = ?
                ORDER BY s.fullname
              ");
              $stmt->execute([$class['id']]);
              $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if (!$students) {
                echo '<li class="list-group-item">No students registered yet.</li>';
              } else {
                foreach ($students as $student) {
                  echo '<li class="list-group-item">';
                  echo htmlspecialchars($student['fullname']) . ' (' . htmlspecialchars($student['registration_number']) . ') - ' . htmlspecialchars($student['email']);
                  echo '</li>';
                }
              }
            ?>
          </ul>

        </div>
      </div>
    <?php endforeach; ?>

  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

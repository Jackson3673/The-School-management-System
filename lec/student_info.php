<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../login.php");
    exit();
}

$host = 'localhost';
$db = 'naini';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch all classes (optionally you can filter by lecturer if needed)
$stmt = $pdo->query("SELECT * FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_class_id = $_GET['class_id'] ?? null;
$search = trim($_GET['search'] ?? '');
$students = [];

if ($selected_class_id) {
    // Base SQL
    $sql = "
        SELECT s.id, s.fullname, s.email, s.registration_number, s.created_at
        FROM students s
        JOIN class_students cs ON s.id = cs.student_id
        WHERE cs.class_id = ?
    ";

    $params = [$selected_class_id];

    // Add search filter if provided
    if ($search !== '') {
        $sql .= " AND (s.fullname LIKE ? OR s.email LIKE ? OR s.registration_number LIKE ?)";
        $like_search = "%$search%";
        $params[] = $like_search;
        $params[] = $like_search;
        $params[] = $like_search;
    }

    $sql .= " ORDER BY s.fullname";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get the selected class name for display
    $selected_class_name = '';
    foreach ($classes as $c) {
        if ($c['id'] == $selected_class_id) {
            $selected_class_name = $c['class_name'];
            break;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Classes & Students | Lecturer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .class-card {
      cursor: pointer;
      transition: transform 0.15s ease-in-out;
    }
    .class-card:hover {
      transform: scale(1.05);
      box-shadow: 0 0 11px rgba(33,33,33,.2);
    }
    .selected-class {
      border: 2px solid #0d6efd;
    }
  </style>
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

  <h2 class="mb-4">Classes</h2>
  <div class="row mb-5">
    <?php foreach ($classes as $class): ?>
      <div class="col-md-3 mb-3">
        <a href="?class_id=<?= $class['id'] ?>" class="text-decoration-none">
          <div class="card class-card <?= ($selected_class_id == $class['id']) ? 'selected-class' : '' ?>">
            <div class="card-body text-center">
              <h5 class="card-title"><?= htmlspecialchars($class['class_name']) ?></h5>
              <p class="card-text"><small class="text-muted">Created: <?= htmlspecialchars(date('M d, Y', strtotime($class['created_at']))) ?></small></p>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($selected_class_id): ?>
    <h3>Students in: <span class="text-primary"><?= htmlspecialchars($selected_class_name) ?></span></h3>

    <!-- Search form -->
    <form method="GET" class="mb-3">
      <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class_id) ?>">
      <div class="input-group">
        <input 
          type="text" 
          name="search" 
          class="form-control" 
          placeholder="Search by name, email, or registration no." 
          value="<?= htmlspecialchars($search) ?>"
          autocomplete="off"
        >
        <button class="btn btn-outline-primary" type="submit">Search</button>
      </div>
    </form>

    <?php if (count($students) > 0): ?>
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Registration No.</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $student): ?>
              <tr>
                <td><?= htmlspecialchars($student['id']) ?></td>
                <td><?= htmlspecialchars($student['fullname']) ?></td>
                <td><?= htmlspecialchars($student['email']) ?></td>
                <td><?= htmlspecialchars($student['registration_number']) ?></td>
                <td><?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No students found matching your search criteria.</p>
    <?php endif; ?>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

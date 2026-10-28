<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
  header("Location: ../../modules/auth/login.php");
  exit();
}

require_once '../../config/db.php';
$lecturer_id = $_SESSION['user_id'];

// Handle add class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
  $class_name = trim($_POST['class_name']);
  if ($class_name !== '') {
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, lecturer_id) VALUES (?, ?)");
    $stmt->execute([$class_name, $lecturer_id]);
    header("Location: index.php");
    exit();
  }
}

// Handle add existing student to class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_existing_student'])) {
  $class_id = intval($_POST['class_id']);
  $student_id = intval($_POST['student_id']);

  try {
    $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
    $stmt->execute([$class_id, $student_id]);
  } catch (PDOException $e) {
    // Ignore duplicate or error
  }

  header("Location: index.php");
  exit();
}

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students for the dropdown
$stmt = $pdo->query("SELECT id, fullname, registration_number FROM students ORDER BY fullname");
$all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "My Classes";
    $page_subtitle = "Manage your subjects and students";
    require_once '../../includes/topbar.php';
    ?>

    <div class="container-fluid p-4">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">My Classes</li>
        </ol>
      </nav>

      <!-- Add Class Card -->
      <div class="custom-card mb-4" id="addClassCard">
        <div class="card-body">
          <form method="POST" class="row g-3 align-items-center">
            <div class="col-auto flex-grow-1">
              <label for="newClassName" class="visually-hidden">New Class Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-journal-plus"></i></span>
                <input type="text" name="class_name" id="newClassName" class="form-control"
                  placeholder="Enter new class name (e.g., Computer Science 101)" required />
              </div>
            </div>
            <div class="col-auto">
              <button type="submit" name="add_class" class="btn btn-primary px-4 rounded-pill">
                <i class="bi bi-plus-lg me-2"></i> Create Class
              </button>
            </div>
          </form>
        </div>
      </div>

      <?php if (count($classes) === 0): ?>
        <div class="text-center py-5">
          <i class="bi bi-journal-x fs-1 text-muted opacity-50 mb-3"></i>
          <p class="text-muted">You haven't created any classes yet.</p>
        </div>
      <?php else: ?>

        <div class="row">
          <?php foreach ($classes as $class): ?>
            <div class="col-xl-6 mb-4">
              <div class="custom-card h-100">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                  <h5 class="card-title mb-0 text-primary">
                    <i class="bi bi-journal-bookmark me-2"></i> <?= htmlspecialchars($class['class_name']) ?>
                  </h5>
                  <span class="badge bg-light text-muted border">Class ID: <?= $class['id'] ?></span>
                </div>
                <div class="card-body">

                  <!-- Add Student Section -->
                  <div class="bg-light rounded-3 p-3 mb-4">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Enroll Student</h6>
                    <form method="POST" class="row g-2">
                      <input type="hidden" name="class_id" value="<?= $class['id'] ?>" />
                      <div class="col-md-8">
                        <select name="student_id" class="form-select form-select-sm" required>
                          <option value="">-- Select Student to Enroll --</option>
                          <?php foreach ($all_students as $s): ?>
                            <option value="<?= $s['id'] ?>">
                              <?= htmlspecialchars($s['fullname']) ?> (<?= htmlspecialchars($s['registration_number']) ?>)
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <button type="submit" name="add_existing_student" class="btn btn-sm btn-success w-100">
                          <i class="bi bi-person-plus me-1"></i> Enroll
                        </button>
                      </div>
                    </form>
                  </div>

                  <!-- Student List -->
                  <h6 class="fw-bold mb-3 d-flex align-items-center justify-content-between">
                    <span>Registered Students</span>
                  </h6>

                  <div class="table-responsive border rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                      <?php
                      $stmt = $pdo->prepare("
                                 SELECT s.id, s.fullname, s.email, s.registration_number
                                 FROM students s
                                 INNER JOIN class_students cs ON s.id = cs.student_id
                                 WHERE cs.class_id = ?
                                 ORDER BY s.fullname
                               ");
                      $stmt->execute([$class['id']]);
                      $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                      if (!$students) {
                        echo '<li class="list-group-item text-center text-muted py-3 small">No students enrolled yet.</li>';
                      } else {
                        foreach ($students as $student) {
                          echo '<li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">';
                          echo '<div>';
                          echo '<div class="fw-bold text-dark small">' . htmlspecialchars($student['fullname']) . '</div>';
                          echo '<div class="text-muted" style="font-size: 0.75rem;">' . htmlspecialchars($student['registration_number']) . '</div>';
                          echo '</div>';
                          echo '<a href="../../modules/students/view.php?student_id=' . $student['id'] . '" class="btn btn-sm btn-light border text-muted px-2 py-0" style="font-size: 0.75rem;">Profile</a>';
                          echo '</li>';
                        }
                      }
                      ?>
                    </ul>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
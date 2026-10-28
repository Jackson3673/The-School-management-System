<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
  header("Location: ../auth/login.php");
  exit();
}
require_once '../../config/db.php';
$lecturer_id = $_SESSION['user_id'];

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_class_id = $_POST['class_id'] ?? null;
$today = date('Y-m-d');
$message = "";

// Handle attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
  $selected_class_id = intval($_POST['class_id']);
  $attendance_data = $_POST['attendance'] ?? [];

  foreach ($attendance_data as $student_id => $status) {
    // Only insert if status is set
    if ($status) {
      $stmt = $pdo->prepare("INSERT INTO attendance (class_id, student_id, attendance_date, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_at = CURRENT_TIMESTAMP");
      $stmt->execute([$selected_class_id, $student_id, $today, $status]);
    }
  }
  $message = "Attendance for " . date('M d, Y', strtotime($today)) . " recorded successfully.";
}

// Fetch students and existing attendance
$students = [];
$existing_attendance = [];
if ($selected_class_id) {
  $stmt = $pdo->prepare("
    SELECT s.id, s.fullname, s.registration_number 
    FROM students s 
    JOIN class_students cs ON s.id = cs.student_id 
    WHERE cs.class_id = ? 
    ORDER BY s.fullname
  ");
  $stmt->execute([$selected_class_id]);
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND attendance_date = ?");
  $stmt->execute([$selected_class_id, $today]);
  $existing_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "Mark Attendance";
    $page_subtitle = "Record student presence for " . date('F d, Y');
    require_once '../../includes/topbar.php';
    ?>

    <div class="container-fluid p-4">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Mark Attendance</li>
        </ol>
      </nav>

      <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i> <?= $message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <div class="custom-card mb-4">
        <div class="card-body">
          <form method="POST" class="row align-items-end g-3">
            <div class="col-md-6">
              <label for="class_id" class="form-label fw-bold text-muted small">SELECT CLASS</label>
              <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-journal-check"></i></span>
                <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()" required>
                  <option value="">-- Choose class to mark --</option>
                  <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <?php if ($selected_class_id): ?>
              <div class="col-md-6 text-md-end">
                <a href="view.php?class_id=<?= $selected_class_id ?>" class="btn btn-outline-info rounded-pill px-4">
                  <i class="bi bi-eye me-2"></i> View Past Attendance
                </a>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <?php if ($selected_class_id && count($students) > 0): ?>
        <div class="custom-card">
          <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Student List</h5>
            <span class="badge bg-primary-subtle text-primary"><?= count($students) ?> Students</span>
          </div>
          <div class="card-body p-0">
            <form method="POST">
              <input type="hidden" name="class_id" value="<?= $selected_class_id ?>" />
              <input type="hidden" name="submit_attendance" value="1" />

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-4">Student Name</th>
                      <th class="text-center" width="150">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $student):
                      $status = $existing_attendance[$student['id']] ?? '';
                      // Default to present if not set? Maybe not, prefer explicit action.
                      // But usually defaults help. Let's leave unchecked.
                      ?>
                      <tr>
                        <td class="ps-4">
                          <div class="fw-bold text-dark"><?= htmlspecialchars($student['fullname']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($student['registration_number']) ?></div>
                        </td>
                        <td class="text-center">
                          <div class="btn-group" role="group" aria-label="Attendance Status">
                            <input type="radio" class="btn-check" name="attendance[<?= $student['id'] ?>]"
                              id="present_<?= $student['id'] ?>" value="present" <?= $status === 'present' ? 'checked' : '' ?>
                              required>
                            <label class="btn btn-outline-success btn-sm"
                              for="present_<?= $student['id'] ?>">Present</label>

                            <input type="radio" class="btn-check" name="attendance[<?= $student['id'] ?>]"
                              id="absent_<?= $student['id'] ?>" value="absent" <?= $status === 'absent' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-danger btn-sm" for="absent_<?= $student['id'] ?>">Absent</label>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="p-3 bg-light border-top text-end sticky-bottom">
                <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm">
                  <i class="bi bi-save me-2"></i> Save Attendance
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php elseif ($selected_class_id): ?>
        <div class="alert alert-warning d-flex align-items-center">
          <i class="bi bi-exclamation-triangle me-3 fs-4"></i>
          <div>
            No students enrolled in this class.
            <div class="small">Go to 'My Classes' to enroll students.</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
  header("Location: ../../modules/auth/login.php");
  exit();
}
require_once '../../config/db.php';
$lecturer_id = $_SESSION['user_id'];

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_class_id = $_POST['class_id'] ?? null;
$message = "";

// Handle marks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
  $selected_class_id = intval($_POST['class_id']);
  $cats_data = $_POST['cats'] ?? [];
  $exam_data = $_POST['exam'] ?? [];

  foreach ($cats_data as $student_id => $cat_mark) {
    if ($cat_mark === '' && $exam_data[$student_id] === '')
      continue; // Skip if both empty

    $cat_mark = floatval($cat_mark);
    $exam_mark = floatval($exam_data[$student_id] ?? 0);

    // Enforcement (Optional): 
    // CATs max 30, Exam max 70 handled in UI, but safe to clamp here or trust input.

    $total_marks = $cat_mark + $exam_mark;

    // Auto-calculate grade using Grading Engine
    require_once '../../includes/grade_engine.php';
    // Default to 8-4-4 for now, this can be made dynamic later based on Class/Student
    $grading_result = calculateGrade($total_marks, '8-4-4');
    $grade = $grading_result['grade'];

    $stmt = $pdo->prepare("INSERT INTO academic_records (class_id, student_id, cats_marks, main_exam_marks, marks, grade) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cats_marks = VALUES(cats_marks), main_exam_marks = VALUES(main_exam_marks), marks = VALUES(marks), grade = VALUES(grade), updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$selected_class_id, $student_id, $cat_mark, $exam_mark, $total_marks, $grade]);
  }
  $message = "Academic records updated successfully.";
}

// Fetch students
$students = [];
$existing_records = [];
if ($selected_class_id) {
  $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ? AND lecturer_id = ?");
  $stmt->execute([$selected_class_id, $lecturer_id]);
  if (!$stmt->fetch()) {
    die("You don't have permission to view this class.");
  }

  $stmt = $pdo->prepare("SELECT s.id, s.fullname, s.registration_number FROM students s JOIN class_students cs ON s.id = cs.student_id WHERE cs.class_id = ? ORDER BY s.fullname");
  $stmt->execute([$selected_class_id]);
  $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch breakdown
  $stmt = $pdo->prepare("SELECT student_id, cats_marks, main_exam_marks, marks, grade FROM academic_records WHERE class_id = ?");
  $stmt->execute([$selected_class_id]);
  $existing_records = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "Grade Entry";
    $page_subtitle = "Enter marks for your classes";
    require_once '../../includes/topbar.php';
    ?>

    <div class="container-fluid p-4">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item active" aria-current="page">Grade Entry</li>
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
          <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-6">
              <label for="class_id" class="form-label fw-bold text-muted small">SELECT CLASS</label>
              <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()" required>
                <option value="">-- Choose class to grade --</option>
                <?php foreach ($classes as $class): ?>
                  <option value="<?= $class['id'] ?>" <?= ($selected_class_id == $class['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($class['class_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <div class="alert alert-info py-2 px-3 mb-0 small">
                <i class="bi bi-info-circle me-2"></i> CATs are graded out of <strong>30</strong>. Main Exams are out of
                <strong>70</strong>.
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php if ($selected_class_id && count($students) > 0): ?>
        <div class="custom-card">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Student List & Grades</h5>
          </div>
          <div class="card-body p-0">
            <form method="POST">
              <input type="hidden" name="class_id" value="<?= $selected_class_id ?>" />
              <input type="hidden" name="submit_marks" value="1" />

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-4">Student Details</th>
                      <th width="15%">CATs (30%)</th>
                      <th width="15%">Exam (70%)</th>
                      <th width="10%" class="text-center">Total</th>
                      <th width="10%" class="text-center">Grade</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $student):
                      $cats = $existing_records[$student['id']]['cats_marks'] ?? '';
                      $exam = $existing_records[$student['id']]['main_exam_marks'] ?? '';
                      $total = $existing_records[$student['id']]['marks'] ?? '';
                      $grade = $existing_records[$student['id']]['grade'] ?? '-';
                      ?>
                      <tr>
                        <td class="ps-4">
                          <div class="fw-bold text-dark"><?= htmlspecialchars($student['fullname']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($student['registration_number']) ?></div>
                        </td>
                        <td>
                          <input type="number" name="cats[<?= $student['id'] ?>]" value="<?= htmlspecialchars($cats) ?>"
                            class="form-control form-control-sm" min="0" max="30" step="0.5" placeholder="0-30">
                        </td>
                        <td>
                          <input type="number" name="exam[<?= $student['id'] ?>]" value="<?= htmlspecialchars($exam) ?>"
                            class="form-control form-control-sm" min="0" max="70" step="0.5" placeholder="0-70">
                        </td>
                        <td class="fw-bold text-center fs-5">
                          <?= $total !== '' ? $total : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-<?=
                            ($grade === 'A' || $grade === 'A-') ? 'success' :
                            (($grade === 'B+' || $grade === 'B' || $grade === 'B-') ? 'primary' :
                              (($grade === 'C+' || $grade === 'C' || $grade === 'C-') ? 'info' :
                                (($grade === 'D+' || $grade === 'D' || $grade === 'D-') ? 'warning' :
                                  ($grade === '-' ? 'secondary' : 'danger'))))
                            ?> rounded-pill px-3">
                            <?= htmlspecialchars($grade) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="p-3 bg-light border-top text-end">
                <button type="submit" class="btn btn-primary px-4 rounded-pill">
                  <i class="bi bi-save me-2"></i> Save Grades
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php elseif ($selected_class_id): ?>
        <div class="alert alert-warning d-flex align-items-center">
          <i class="bi bi-exclamation-triangle me-3 fs-4"></i>
          <div>
            No students assigned to this class yet.
            <div class="small">Contact an administrator to assign students.</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
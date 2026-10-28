<?php
session_start();
if (!isset($_SESSION['role'])) {
  header("Location: ../../modules/auth/login.php");
  exit();
}
require_once '../../config/db.php';

$student_id = intval($_GET['student_id'] ?? 0);

if ($student_id === 0) {
  echo "Invalid Student ID";
  exit();
}

// 1. Fetch Student Details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
  echo "Student not found.";
  exit();
}

// 2. Fetch Enrolled Courses (Classes)
$stmt = $pdo->prepare("
    SELECT c.id, c.class_name, c.course_code, u.fullname as lecturer_name, cs.status
    FROM classes c
    JOIN class_students cs ON c.id = cs.class_id
    LEFT JOIN users u ON c.lecturer_id = u.id
    WHERE cs.student_id = ?
");
$stmt->execute([$student_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Grades
$stmt = $pdo->prepare("
    SELECT c.class_name, ar.marks, ar.grade, ar.cats_marks, ar.main_exam_marks, ar.updated_at
    FROM academic_records ar
    JOIN classes c ON ar.class_id = c.id
    WHERE ar.student_id = ?
    ORDER BY c.class_name
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Handle Enrollment (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course']) && $_SESSION['role'] === 'admin') {
  $class_id = intval($_POST['class_id']);
  if ($class_id > 0) {
    try {
      $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id) VALUES (?, ?)");
      $stmt->execute([$class_id, $student_id]);
      // Refresh to show new course
      header("Location: view.php?student_id=$student_id");
      exit();
    } catch (PDOException $e) {
      // Likely duplicate
    }
  }
}

// 5. Fetch All Courses for Enrollment Dropdown
$all_courses = [];
if ($_SESSION['role'] === 'admin') {
  $stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name");
  $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 6. Fetch Fees
$stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY transaction_date DESC");
$stmt->execute([$student_id]);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$balance = 0;
foreach ($fees as $f) {
  if ($f['type'] === 'invoice') {
    $balance += $f['amount'];
  } else {
    $balance -= $f['amount'];
  }
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "Student Profile";
    $page_subtitle = "Detailed student information";
    require_once '../../includes/topbar.php';
    ?>

    <!-- Header Card -->
    <div class="custom-card mb-4 border-0">
      <div class="d-flex align-items-center flex-wrap gap-3">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm"
          style="width: 80px; height: 80px; font-size: 2rem;">
          <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
        </div>
        <div>
          <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($student['fullname']) ?></h3>
          <div class="text-muted small d-flex align-items-center gap-3">
            <span><i class="bi bi-person-badge me-1"></i>
              <?= htmlspecialchars($student['registration_number']) ?></span>
            <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($student['email']) ?></span>
          </div>
        </div>
        <div class="ms-auto">
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-primary rounded-pill px-4">
              <i class="bi bi-pencil-square me-2"></i> Edit Profile
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Custom Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="studentTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active rounded-pill px-4" id="info-tab" data-bs-toggle="pill" data-bs-target="#info"
          type="button">
          <i class="bi bi-person me-2"></i> General Info
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link rounded-pill px-4" id="courses-tab" data-bs-toggle="pill" data-bs-target="#courses"
          type="button">
          <i class="bi bi-book me-2"></i> Courses
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link rounded-pill px-4" id="grades-tab" data-bs-toggle="pill" data-bs-target="#grades"
          type="button">
          <i class="bi bi-graph-up me-2"></i> Grades
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link rounded-pill px-4" id="fees-tab" data-bs-toggle="pill" data-bs-target="#fees"
          type="button">
          <i class="bi bi-wallet2 me-2"></i> Fees
        </button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="myTabContent">
      <!-- General Info -->
      <div class="tab-pane fade show active" id="info">
        <div class="row">
          <!-- Academic Placement -->
          <div class="col-md-12 mb-4">
            <div class="custom-card border-0 h-100">
              <h6 class="fw-bold text-primary mb-3 text-uppercase small ls-1">Academic Placement</h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <small class="text-muted text-uppercase d-block mb-1"
                    style="font-size: 0.75rem; font-weight: 600;">Program</small>
                  <div class="fw-bold text-dark fs-5"><?= htmlspecialchars($student['program'] ?? 'Not Assigned') ?>
                  </div>
                </div>
                <div class="col-md-3">
                  <small class="text-muted text-uppercase d-block mb-1"
                    style="font-size: 0.75rem; font-weight: 600;">Study Year</small>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($student['year_of_study'] ?? 'Year 1') ?></div>
                </div>
                <div class="col-md-3">
                  <small class="text-muted text-uppercase d-block mb-1"
                    style="font-size: 0.75rem; font-weight: 600;">Semester</small>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($student['current_semester'] ?? 'Semester 1') ?>
                  </div>
                </div>
                <div class="col-md-2">
                  <small class="text-muted text-uppercase d-block mb-1"
                    style="font-size: 0.75rem; font-weight: 600;">Admission Date</small>
                  <div class="fw-bold text-dark">
                    <?= $student['admission_date'] ? date('M Y', strtotime($student['admission_date'])) : '-' ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Personal Details -->
          <div class="col-md-6 mb-4">
            <div class="custom-card border-0 h-100">
              <h6 class="fw-bold text-primary mb-3 text-uppercase small ls-1">Personal Details</h6>
              <div class="vstack gap-3">
                <div class="d-flex justify-content-between border-bottom pb-2 border-light">
                  <span class="text-muted small fw-bold">Date of Birth</span>
                  <span
                    class="fw-medium text-dark"><?= !empty($student['dob']) ? date('M d, Y', strtotime($student['dob'])) : '<span class="text-muted">Not Set</span>' ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 border-light">
                  <span class="text-muted small fw-bold">Gender</span>
                  <span class="fw-medium text-dark"><?= htmlspecialchars($student['gender'] ?? 'Not Set') ?></span>
                </div>
                <div class="d-flex justify-content-between border-bottom pb-2 border-light">
                  <span class="text-muted small fw-bold">Phone Number</span>
                  <span
                    class="fw-medium text-dark"><?= htmlspecialchars($student['phone_number'] ?? 'Not Set') ?></span>
                </div>
                <div>
                  <span class="text-muted small fw-bold d-block mb-1">Address</span>
                  <span
                    class="fw-medium text-dark"><?= nl2br(htmlspecialchars($student['address'] ?? 'Not Recorded')) ?></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Guardian Details -->
          <div class="col-md-6 mb-4">
            <div class="custom-card border-0 h-100 position-relative overflow-hidden">
              <div class="position-absolute top-0 end-0 p-3 opacity-10">
                <i class="bi bi-shield-check fs-1 text-warning"></i>
              </div>
              <h6 class="fw-bold text-warning mb-3 text-uppercase small ls-1" style="z-index: 1; position: relative;">
                Guardian / Next of Kin</h6>
              <div class="vstack gap-3" style="z-index: 1; position: relative;">
                <div>
                  <span class="text-muted small fw-bold d-block mb-1">Name</span>
                  <span
                    class="fw-bold fs-5 text-dark"><?= htmlspecialchars($student['next_of_kin_name'] ?? 'Not Recorded') ?></span>
                </div>
                <div>
                  <span class="text-muted small fw-bold d-block mb-1">Relationship</span>
                  <span
                    class="badge bg-light-warning text-warning border border-warning-subtle"><?= htmlspecialchars($student['next_of_kin_relation'] ?? 'N/A') ?></span>
                </div>
                <div>
                  <span class="text-muted small fw-bold d-block mb-1">Phone Contact</span>
                  <?php if (!empty($student['next_of_kin_phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($student['next_of_kin_phone']) ?>"
                      class="text-decoration-none text-dark fw-bold d-inline-flex align-items-center">
                      <i class="bi bi-telephone-fill me-2 text-warning"></i>
                      <?= htmlspecialchars($student['next_of_kin_phone']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">Not Recorded</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Courses -->
      <div class="tab-pane fade" id="courses">
        <div class="custom-card border-0">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Enrolled Courses</h5>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <form method="POST" class="d-flex align-items-center gap-2">
                <select name="class_id" class="form-select form-select-sm rounded-pill" required style="width: 200px;">
                  <option value="">Select Course to Enroll...</option>
                  <?php foreach ($all_courses as $ac): ?>
                    <option value="<?= $ac['id'] ?>"><?= htmlspecialchars($ac['class_name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="enroll_course" class="btn btn-primary btn-sm rounded-pill px-3">
                  Enroll <i class="bi bi-arrow-right ms-1"></i>
                </button>
              </form>
            <?php endif; ?>
          </div>

          <?php if (count($courses) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="border-0 rounded-start">Code</th>
                    <th class="border-0">Course Name</th>
                    <th class="border-0">Lecturer</th>
                    <th class="border-0">Status</th>
                    <th class="border-0 rounded-end">Attendance</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($courses as $c): ?>
                    <tr>
                      <td><span
                          class="badge bg-light text-dark border"><?= htmlspecialchars($c['course_code'] ?? 'N/A') ?></span>
                      </td>
                      <td class="fw-bold text-dark"><?= htmlspecialchars($c['class_name']) ?></td>
                      <td>
                        <?php if ($c['lecturer_name']): ?>
                          <div class="d-flex align-items-center gap-2">
                            <div
                              class="rounded-circle bg-light-secondary text-secondary d-flex align-items-center justify-content-center"
                              style="width: 24px; height: 24px; font-size: 0.7rem;">
                              <?= strtoupper(substr($c['lecturer_name'], 0, 1)) ?>
                            </div>
                            <span class="small"><?= htmlspecialchars($c['lecturer_name']) ?></span>
                          </div>
                        <?php else: ?>
                          <span class="text-muted small">Unassigned</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                        $status = $c['status'] ?? 'Enrolled';
                        $badgeClass = match ($status) {
                          'Completed' => 'success',
                          'Dropped' => 'danger',
                          default => 'primary'
                        };
                        ?>
                        <span
                          class="badge bg-light-<?= $badgeClass ?> text-<?= $badgeClass ?> border border-<?= $badgeClass ?>-subtle"><?= htmlspecialchars($status) ?></span>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <span class="me-2 small fw-bold">85%</span>
                          <div class="progress flex-grow-1" style="height: 6px; width: 80px;">
                            <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: 85%"></div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <div class="mb-3 text-muted"><i class="bi bi-book fs-1"></i></div>
              <h6 class="text-muted">Not enrolled in any courses yet.</h6>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Grades -->
      <div class="tab-pane fade" id="grades">
        <div class="custom-card border-0">
          <h5 class="fw-bold mb-4">Academic Performance</h5>
          <?php if (count($grades) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="border-0 rounded-start">Unit / Course</th>
                    <th class="text-center border-0">CATs (30%)</th>
                    <th class="text-center border-0">Main Exam (70%)</th>
                    <th class="text-center border-0">Total Score</th>
                    <th class="text-center border-0">Grade</th>
                    <th class="border-0 rounded-end">Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($grades as $g): ?>
                    <?php
                    $total = $g['marks'];
                    if ($total < 40) {
                      $remarks = '<span class="text-danger fw-bold small">Supplementary</span>';
                    } elseif ($total >= 70) {
                      $remarks = '<span class="text-success fw-bold small">Distinction</span>';
                    } elseif ($total >= 60) {
                      $remarks = '<span class="text-primary fw-bold small">Credit</span>';
                    } elseif ($total >= 40) {
                      $remarks = '<span class="text-dark fw-bold small">Pass</span>';
                    } else {
                      $remarks = '-';
                    }
                    ?>
                    <tr>
                      <td class="fw-bold text-dark"><?= htmlspecialchars($g['class_name']) ?></td>
                      <td class="text-center">
                        <?= $g['cats_marks'] > 0 ? htmlspecialchars($g['cats_marks']) : '<span class="text-muted">-</span>' ?>
                      </td>
                      <td class="text-center">
                        <?= $g['main_exam_marks'] > 0 ? htmlspecialchars($g['main_exam_marks']) : '<span class="text-muted">-</span>' ?>
                      </td>
                      <td class="text-center fw-bold"><?= htmlspecialchars($g['marks']) ?></td>
                      <td class="text-center">
                        <span class="badge bg-<?=
                          ($g['grade'] === 'A') ? 'success' :
                          (($g['grade'] === 'B') ? 'primary' :
                            (($g['grade'] === 'C') ? 'info' :
                              (($g['grade'] === 'D') ? 'warning' : 'danger')))
                          ?> rounded-circle"
                          style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                          <?= htmlspecialchars($g['grade']) ?>
                        </span>
                      </td>
                      <td><?= $remarks ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <div class="mb-3 text-muted"><i class="bi bi-graph-up-arrow fs-1"></i></div>
              <h6 class="text-muted">No academic records found.</h6>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Fees -->
      <div class="tab-pane fade" id="fees">
        <div class="custom-card border-0">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Financial Status</h5>
            <div class="px-3 py-2 rounded-3 bg-light border">
              <span class="text-muted small text-uppercase fw-bold me-2">Current Balance</span>
              <span class="fs-4 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($balance, 2) ?>
              </span>
            </div>
          </div>

          <?php if (count($fees) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th class="border-0 rounded-start">Date</th>
                    <th class="border-0">Description</th>
                    <th class="border-0">Type</th>
                    <th class="text-end border-0 rounded-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($fees as $f): ?>
                    <tr>
                      <td class="text-muted"><?= date('M d, Y', strtotime($f['transaction_date'])) ?></td>
                      <td class="fw-bold text-dark"><?= htmlspecialchars($f['description']) ?></td>
                      <td>
                        <?php if ($f['type'] === 'payment'): ?>
                          <span class="badge bg-light-success text-success border border-success-subtle">PAYMENT</span>
                        <?php else: ?>
                          <span class="badge bg-light-danger text-danger border border-danger-subtle">INVOICE</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end fw-bold <?= $f['type'] === 'payment' ? 'text-success' : 'text-danger' ?>">
                        <?= $f['type'] === 'payment' ? '+' : '-' ?>     <?= number_format($f['amount'], 2) ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="text-center py-5">
              <div class="mb-3 text-muted"><i class="bi bi-cash-coin fs-1"></i></div>
              <h6 class="text-muted">No financial records found.</h6>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
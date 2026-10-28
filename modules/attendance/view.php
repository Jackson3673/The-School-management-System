<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
  header("Location: ../../modules/auth/login.php");
  exit();
}

require_once '../../config/db.php';
$lecturer_id = $_SESSION['user_id'];

$class_id = $_GET['class_id'] ?? null;
if (!$class_id) {
  die("Class ID is required.");
}

// Fetch class details
$stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$class_id, $lecturer_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
  die("You don't have permission to view this class.");
}

// Fetch attendance records
$stmt = $pdo->prepare("
    SELECT a.attendance_date, s.fullname, s.registration_number, a.status 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE a.class_id = ? 
    ORDER BY a.attendance_date DESC, s.fullname
");
$stmt->execute([$class_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by date
$attendance_by_date = [];
foreach ($records as $rec) {
  $attendance_by_date[$rec['attendance_date']][] = $rec;
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "Attendance History";
    $page_subtitle = "Viewing records for " . htmlspecialchars($class['class_name']);
    require_once '../../includes/topbar.php';
    ?>

    <div class="container-fluid p-4">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="mark.php">Mark Attendance</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($class['class_name']) ?></li>
        </ol>
      </nav>

      <?php if (empty($attendance_by_date)): ?>
        <div class="text-center py-5">
          <i class="bi bi-calendar-x fs-1 text-muted opacity-50 mb-3"></i>
          <p class="text-muted">No attendance records found for this class.</p>
          <a href="mark.php" class="btn btn-primary rounded-pill px-4 mt-2">
            <i class="bi bi-calendar-check me-2"></i> Mark Attendance Now
          </a>
        </div>
      <?php else: ?>

        <div class="row">
          <?php foreach ($attendance_by_date as $date => $students_list): ?>
            <div class="col-12 mb-4">
              <div class="custom-card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center bg-light">
                  <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('l, F d, Y', strtotime($date)) ?>
                  </h6>
                  <span class="badge bg-white text-dark border">
                    <?= count($students_list) ?> Records
                  </span>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                      <thead class="table-light">
                        <tr>
                          <th class="ps-4">Student Name</th>
                          <th class="text-end pe-4">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($students_list as $rec): ?>
                          <tr>
                            <td class="ps-4">
                              <div class="fw-bold text-dark"><?= htmlspecialchars($rec['fullname']) ?></div>
                              <div class="text-muted small"><?= htmlspecialchars($rec['registration_number']) ?></div>
                            </td>
                            <td class="text-end pe-4">
                              <?php if ($rec['status'] === 'present'): ?>
                                <span class="badge bg-success-subtle text-success rounded-pill px-3">
                                  <i class="bi bi-check-circle me-1"></i> Present
                                </span>
                              <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger rounded-pill px-3">
                                  <i class="bi bi-x-circle me-1"></i> Absent
                                </span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
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
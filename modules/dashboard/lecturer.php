<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') {
  header("Location: ../auth/login.php");
  exit();
}

require_once '../../config/db.php';

// Fetch basic stats
$lecturer_id = $_SESSION['user_id'];
// Count assigned classes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$class_count = $stmt->fetchColumn();

// Fetch upcoming classes (Mock data or real if schedule exists)
// For now, let's just use the count.
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
  <?php require_once '../../includes/sidebar.php'; ?>

  <div class="main-content w-100">
    <?php
    $page_title = "Lecturer Dashboard";
    $page_subtitle = "Welcome back, " . htmlspecialchars($_SESSION['fullname']) . " 👋";
    require_once '../../includes/topbar.php';
    ?>

    <div class="container-fluid p-4">
      <!-- Stats Overview -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 mb-3">
          <div class="custom-card bg-primary-subtle border-0 h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-primary fw-bold">My Classes</span>
              <i class="bi bi-journal-bookmark-fill fs-4 text-primary opacity-50"></i>
            </div>
            <h2 class="fw-bold mb-0 text-dark"><?= $class_count ?></h2>
            <small class="text-primary">Total Assigned</small>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <div class="custom-card bg-success-subtle border-0 h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="text-success fw-bold">Status</span>
              <i class="bi bi-check-circle-fill fs-4 text-success opacity-50"></i>
            </div>
            <h2 class="fw-bold mb-0 text-dark">Active</h2>
            <small class="text-success">Current Semester</small>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="custom-card h-100">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0">Today's Schedule</h5>
            </div>
            <div class="card-body">
              <!-- Placeholder Schedule -->
              <div class="d-flex align-items-center p-3 border rounded-3 mb-3 bg-light">
                <div class="me-3 text-center" style="min-width: 80px;">
                  <span class="d-block fw-bold display-6 mb-0 text-dark">09:00</span>
                  <small class="text-muted text-uppercase">AM</small>
                </div>
                <div class="vr me-3"></div>
                <div class="flex-grow-1">
                  <h6 class="fw-bold mb-1 text-primary">Introduction to Programming</h6>
                  <p class="mb-0 text-muted small"><i class="bi bi-geo-alt me-1"></i> Lab 1 • BIT Year 1</p>
                </div>
                <span class="badge bg-success-subtle text-success rounded-pill">Ongoing</span>
              </div>

              <div class="d-flex align-items-center p-3 border rounded-3 bg-white">
                <div class="me-3 text-center" style="min-width: 80px;">
                  <span class="d-block fw-bold display-6 mb-0 text-muted">02:00</span>
                  <small class="text-muted text-uppercase">PM</small>
                </div>
                <div class="vr me-3"></div>
                <div class="flex-grow-1">
                  <h6 class="fw-bold mb-1 text-dark">Database Systems</h6>
                  <p class="mb-0 text-muted small"><i class="bi bi-geo-alt me-1"></i> Hall 4 • BIT Year 2</p>
                </div>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill">Upcoming</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="custom-card bg-dark text-white h-100">
            <div class="card-body">
              <h5 class="fw-bold mb-4">Quick Actions</h5>
              <div class="d-grid gap-3">
                <a href="../grades/entry.php"
                  class="btn btn-outline-light text-start p-3 d-flex align-items-center border-opacity-25 bg-white bg-opacity-10 border-0">
                  <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center me-3"
                    style="width: 40px; height: 40px;">
                    <i class="bi bi-pencil-square fs-5"></i>
                  </div>
                  <div>
                    <div class="fw-bold">Enter Grades</div>
                    <small class="text-white-50">Record marks for assessments</small>
                  </div>
                </a>

                <a href="../attendance/mark.php"
                  class="btn btn-outline-light text-start p-3 d-flex align-items-center border-opacity-25 bg-white bg-opacity-10 border-0">
                  <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3"
                    style="width: 40px; height: 40px;">
                    <i class="bi bi-calendar-check fs-5"></i>
                  </div>
                  <div>
                    <div class="fw-bold">Attendance</div>
                    <small class="text-white-50">Mark class attendance</small>
                  </div>
                </a>

                <a href="../classes/index.php"
                  class="btn btn-outline-light text-start p-3 d-flex align-items-center border-opacity-25 bg-white bg-opacity-10 border-0">
                  <div
                    class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3"
                    style="width: 40px; height: 40px;">
                    <i class="bi bi-journal-text fs-5"></i>
                  </div>
                  <div>
                    <div class="fw-bold">My Classes</div>
                    <small class="text-white-50">View assigned subjects</small>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
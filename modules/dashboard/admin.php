<?php
session_start();

// Ensure user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/db.php';

// Fetch key stats
try {
    // 1. Counts
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $student_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='lecturer'");
    $lecturer_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM classes");
    $class_count = $stmt->fetchColumn();

    // 2. Revenue (Total Payments)
    $stmt = $pdo->query("SELECT SUM(amount) FROM fees WHERE type='payment'");
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // 3. Recent Activity (Last 5 Fee Transactions)
    $stmt = $pdo->query("
        SELECT f.*, s.fullname 
        FROM fees f 
        JOIN students s ON f.student_id = s.id 
        ORDER BY f.transaction_date DESC, f.id DESC 
        LIMIT 5
    ");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching stats: " . $e->getMessage());
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Admin Dashboard";
        $page_subtitle = "Overview of school performance";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="custom-card bg-primary-subtle border-0 h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-primary fw-bold">Students</span>
                            <i class="bi bi-people-fill fs-4 text-primary opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark"><?= number_format($student_count) ?></h2>
                        <small class="text-primary">Total Enrolled</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="custom-card bg-info-subtle border-0 h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-info fw-bold text-dark-emphasis">Lecturers</span>
                            <i class="bi bi-person-badge-fill fs-4 text-info opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark"><?= number_format($lecturer_count) ?></h2>
                        <small class="text-info text-dark-emphasis">Active Staff</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="custom-card bg-warning-subtle border-0 h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-warning fw-bold text-dark-emphasis">Classes</span>
                            <i class="bi bi-journal-bookmark-fill fs-4 text-warning opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark"><?= number_format($class_count) ?></h2>
                        <small class="text-warning text-dark-emphasis">Active Courses</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="custom-card bg-success-subtle border-0 h-100 p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-success fw-bold">Revenue</span>
                            <i class="bi bi-wallet-fill fs-4 text-success opacity-50"></i>
                        </div>
                        <h2 class="fw-bold mb-0 text-dark"><?= format_currency($total_revenue, 0) ?></h2>
                        <small class="text-success">Total Collected</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Activity -->
                <div class="col-lg-8">
                    <div class="custom-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Financial Activity</h5>
                            <a href="../fees/index.php" class="btn btn-sm btn-light rounded-pill px-3">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recent_activity) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($recent_activity as $act): ?>
                                        <li
                                            class="list-group-item border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-cash-stack text-secondary fs-5"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <?= htmlspecialchars($act['fullname']) ?>
                                                    </div>
                                                    <small class="text-muted d-block">
                                                        <?= htmlspecialchars($act['description']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div
                                                    class="<?= $act['type'] === 'payment' ? 'text-success' : 'text-danger' ?> fw-bold fs-5">
                                                    <?= $act['type'] === 'payment' ? '+' : '-' ?>
                                                    <?= format_currency($act['amount']) ?>
                                                </div>
                                                <small
                                                    class="text-muted"><?= date('M d, h:i A', strtotime($act['transaction_date'])) ?></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 opacity-25"></i>
                                    <p class="mt-2">No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Side Widgets -->
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="custom-card mb-4 bg-dark text-white">
                        <div class="card-body">
                            <h5 class="fw-bold mb-4">Quick Actions</h5>
                            <div class="d-grid gap-3">
                                <a href="../students/create.php"
                                    class="btn btn-primary text-start p-3 rounded-3 border-0 bg-opacity-25 bg-white text-white d-flex align-items-center">
                                    <i class="bi bi-person-plus-fill fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Register Student</div>
                                        <small class="opacity-75">Add a new student to the system</small>
                                    </div>
                                </a>
                                <a href="../fees/index.php"
                                    class="btn btn-primary text-start p-3 rounded-3 border-0 bg-opacity-25 bg-white text-white d-flex align-items-center">
                                    <i class="bi bi-wallet-fill fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Record Payment</div>
                                        <small class="opacity-75">Log a fee transaction</small>
                                    </div>
                                </a>
                                <a href="../courses/index.php"
                                    class="btn btn-primary text-start p-3 rounded-3 border-0 bg-opacity-25 bg-white text-white d-flex align-items-center">
                                    <i class="bi bi-book-half fs-4 me-3"></i>
                                    <div>
                                        <div class="fw-bold">Manage Courses</div>
                                        <small class="opacity-75">Assign subjects to lecturers</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar / Date Widget -->
                    <div class="custom-card">
                        <div class="card-body text-center py-4">
                            <h5 class="text-muted text-uppercase small fw-bold mb-3"><?= date('l') ?></h5>
                            <h1 class="display-1 fw-bold text-primary mb-0"><?= date('j') ?></h1>
                            <h4 class="text-dark fw-bold mb-0"><?= date('F Y') ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
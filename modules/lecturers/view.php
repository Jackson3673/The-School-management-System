<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

// Fetch Lecturer Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'lecturer'");
$stmt->execute([$id]);
$lecturer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lecturer) {
    die("Lecturer not found.");
}

// Fetch Assigned Classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ? ORDER BY class_name");
$stmt->execute([$id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Lecturer Profile";
        $page_subtitle = "Detailed lecturer information";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Lecturers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($lecturer['fullname']) ?></li>
                </ol>
            </nav>

            <!-- Header Card -->
            <div class="custom-card mb-4 border-0">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm"
                        style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($lecturer['fullname'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($lecturer['fullname']) ?></h3>
                        <div class="text-muted small d-flex align-items-center gap-3">
                            <span><i class="bi bi-person-badge me-1"></i>
                                @<?= htmlspecialchars($lecturer['username']) ?></span>
                            <span><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($lecturer['email']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Lecturer Info -->
                <div class="col-md-4">
                    <div class="custom-card mb-4 h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Personal Details</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Username</span>
                                    <span class="fw-medium">@<?= htmlspecialchars($lecturer['username']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Email</span>
                                    <span class="fw-medium"><?= htmlspecialchars($lecturer['email']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Joined</span>
                                    <span
                                        class="fw-medium"><?= date('M d, Y', strtotime($lecturer['created_at'])) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span class="text-muted">Role</span>
                                    <span class="badge bg-primary-subtle text-primary">Lecturer</span>
                                </li>
                            </ul>

                            <hr class="my-4">

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-danger" disabled>
                                    <i class="bi bi-key me-2"></i> Reset Password
                                </button>
                                <div class="text-muted small text-center mt-1">(Coming Soon)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Classes -->
                <div class="col-md-8">
                    <div class="custom-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Assigned Classes</h5>
                            <span class="badge bg-primary px-3 rounded-pill"><?= count($classes) ?> Classes</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($classes) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Course Name</th>
                                                <th>Created Date</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classes as $c): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">
                                                        <?= htmlspecialchars($c['class_name']) ?>
                                                    </td>
                                                    <td>
                                                        <?= date('M d, Y', strtotime($c['created_at'])) ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="../courses/edit.php?id=<?= $c['id'] ?>"
                                                            class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                            Reassign
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="mb-3 text-muted">
                                        <i class="bi bi-book fs-1 opacity-50"></i>
                                    </div>
                                    <h5 class="text-muted">No classes assigned yet</h5>
                                    <p class="mb-4 text-muted small">Assign this lecturer to a course to get started.</p>
                                    <a href="../courses/index.php" class="btn btn-primary rounded-pill px-4">
                                        Assign a Course
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
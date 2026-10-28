<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

// Fetch Lecturers
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'lecturer' ORDER BY fullname ASC");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Manage Lecturers";
        $page_subtitle = "View and manage teaching staff";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Lecturers</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-end mb-4">
                <a href="create.php" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-plus-lg me-2"></i> Add Lecturer
                </a>
            </div>

            <div class="card custom-card">
                <div class="card-body p-0">
                    <?php if (count($lecturers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lecturers as $l): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold"
                                                        style="width: 40px; height: 40px;">
                                                        <?= strtoupper(substr($l['fullname'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <a href="view.php?id=<?= $l['id'] ?>"
                                                            class="text-decoration-none text-dark fw-medium">
                                                            <?= htmlspecialchars($l['fullname']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted">
                                                @<?= htmlspecialchars($l['username']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($l['email']) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($l['created_at'])) ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="view.php?id=<?= $l['id'] ?>"
                                                    class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                    View Profile
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
                                <i class="bi bi-person-x fs-1 opacity-50"></i>
                            </div>
                            <h5 class="text-muted">No lecturers found</h5>
                            <p class="text-muted small">Get started by creating a new lecturer account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
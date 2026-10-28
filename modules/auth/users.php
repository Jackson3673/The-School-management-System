<?php
session_start();

// Ensure user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once '../../config/db.php';

$error = null;
$success = null;

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Prevent deleting self and protected admins if any
    if ($delete_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$delete_id])) {
            $success = "User account deleted successfully.";
        } else {
            $error = "Failed to delete user.";
        }
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Manage Users";
        $page_subtitle = "Administer system accounts";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/admin.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="custom-card">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= count($users) ?>
                            Users</span>
                    </div>
                    <a href="register.php" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-person-plus-fill me-2"></i> Add New User
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">User Details</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border me-3"
                                                    style="width: 40px; height: 40px;">
                                                    <span
                                                        class="fw-bold text-muted"><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark">
                                                        <?= htmlspecialchars($user['fullname']) ?>
                                                    </div>
                                                    <div class="text-muted small"><?= htmlspecialchars($user['email']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?=
                                                ($user['role'] === 'admin') ? 'danger' :
                                                (($user['role'] === 'lecturer') ? 'info' : 'success')
                                                ?> rounded-pill px-3 text-white">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : '-' ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?delete_id=<?= $user['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                    onclick="return confirm('Are you sure you want to permanently delete this user account?');">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill px-3">Current
                                                    User</span>
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
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
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

$error = null;
$success = null;

// Handle Password Reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match. Please verify and try again.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'lecturer'");
        if ($update_stmt->execute([$hashed, $id])) {
            $success = "Password for <strong>" . htmlspecialchars($lecturer['fullname']) . "</strong> has been reset successfully.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
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
                    <li class="breadcrumb-item"><a href="../dashboard/admin.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Lecturers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($lecturer['fullname']) ?></li>
                </ol>
            </nav>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

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
                                <button type="button" class="btn btn-outline-danger rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                    <i class="bi bi-key me-2"></i> Reset Password
                                </button>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="view.php?id=<?= htmlspecialchars($id) ?>" id="resetPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-key-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="resetPasswordModalLabel">Reset Password</h5>
                            <small class="text-muted">Set a new password for this lecturer</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-primary fs-5"></i>
                            <div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($lecturer['fullname']) ?></div>
                                <div class="text-muted small">@<?= htmlspecialchars($lecturer['username']) ?> &bull; <?= htmlspecialchars($lecturer['email']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="new_password" class="form-label fw-medium mb-0">New Password</label>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary" id="btnGeneratePassword">
                                <i class="bi bi-magic me-1"></i> Generate Password
                            </button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordVisibility" title="Toggle visibility">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <div class="form-text small text-muted">Must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-medium mb-1">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password" minlength="6" required autocomplete="new-password">
                        </div>
                        <div id="passwordMismatch" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i> Passwords do not match.
                        </div>
                    </div>

                    <div id="generatedAlert" class="alert alert-info py-2 px-3 small d-none mb-0" role="alert">
                        <i class="bi bi-info-circle me-1"></i> New password generated! Make sure to copy and provide it to the lecturer.
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4" id="submitBtn">
                        <i class="bi bi-check2 me-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    const toggleBtn = document.getElementById('togglePasswordVisibility');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    const generateBtn = document.getElementById('btnGeneratePassword');
    const generatedAlert = document.getElementById('generatedAlert');
    const mismatchNotice = document.getElementById('passwordMismatch');
    const form = document.getElementById('resetPasswordForm');
    const submitBtn = document.getElementById('submitBtn');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = newPass.type === 'password';
            newPass.type = isPassword ? 'text' : 'password';
            confirmPass.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', function () {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
            const uppers = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const lowers = 'abcdefghijkmnopqrstuvwxyz';
            const numbers = '23456789';
            const specials = '!@#$%';
            
            let password = '';
            password += uppers.charAt(Math.floor(Math.random() * uppers.length));
            password += lowers.charAt(Math.floor(Math.random() * lowers.length));
            password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            password += specials.charAt(Math.floor(Math.random() * specials.length));
            
            for (let i = 4; i < 10; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            password = password.split('').sort(function () { return 0.5 - Math.random(); }).join('');

            newPass.value = password;
            confirmPass.value = password;
            newPass.type = 'text';
            confirmPass.type = 'text';
            if (toggleIcon) toggleIcon.className = 'bi bi-eye-slash';
            if (generatedAlert) generatedAlert.classList.remove('d-none');
            if (mismatchNotice) mismatchNotice.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    function validateMatch() {
        if (confirmPass.value && newPass.value !== confirmPass.value) {
            mismatchNotice.classList.remove('d-none');
            submitBtn.disabled = true;
        } else {
            mismatchNotice.classList.add('d-none');
            submitBtn.disabled = false;
        }
    }

    if (newPass && confirmPass) {
        newPass.addEventListener('input', validateMatch);
        confirmPass.addEventListener('input', validateMatch);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (newPass.value !== confirmPass.value) {
                e.preventDefault();
                mismatchNotice.classList.remove('d-none');
                return false;
            }
        });
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>

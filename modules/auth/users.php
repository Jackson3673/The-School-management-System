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

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$target_user_id) {
        $error = "Invalid user specified.";
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error = "Both password fields are required.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match. Please verify and try again.";
    } else {
        $chk_stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
        $chk_stmt->execute([$target_user_id]);
        $target_user = $chk_stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt->execute([$hashed, $target_user_id])) {
                $success = "Password for " . htmlspecialchars($target_user['fullname']) . " has been reset successfully.";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "User not found.";
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
                    <i class="bi bi-check-circle me-2"></i> <?= $success ?>
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
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 reset-pass-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#resetUserPasswordModal"
                                                    data-user-id="<?= $user['id'] ?>"
                                                    data-user-name="<?= htmlspecialchars($user['fullname']) ?>"
                                                    data-user-username="<?= htmlspecialchars($user['username']) ?>">
                                                    <i class="bi bi-key me-1"></i> Reset Password
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete_id=<?= $user['id'] ?>"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                        onclick="return confirm('Are you sure you want to permanently delete this user account?');">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border rounded-pill px-3 d-inline-flex align-items-center">Current User</span>
                                                <?php endif; ?>
                                            </div>
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

<!-- Reset Password Modal -->
<div class="modal fade" id="resetUserPasswordModal" tabindex="-1" aria-labelledby="resetUserPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" action="users.php" id="resetUserPasswordForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="modalUserId" value="">
                
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                            <i class="bi bi-key-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="resetUserPasswordModalLabel">Reset User Password</h5>
                            <small class="text-muted">Set a new password for account</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body px-4 py-3">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-primary fs-5"></i>
                            <div>
                                <div class="fw-bold text-dark" id="modalUserName">-</div>
                                <div class="text-muted small" id="modalUserUsername">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="user_new_password" class="form-label fw-medium mb-0">New Password</label>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary" id="btnUserGeneratePassword">
                                <i class="bi bi-magic me-1"></i> Generate Password
                            </button>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" id="user_new_password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="toggleUserPasswordVisibility" title="Toggle visibility">
                                <i class="bi bi-eye" id="toggleUserPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="form-text small text-muted">Must be at least 6 characters long.</div>
                    </div>

                    <div class="mb-3">
                        <label for="user_confirm_password" class="form-label fw-medium mb-1">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-check"></i></span>
                            <input type="password" name="confirm_password" id="user_confirm_password" class="form-control" placeholder="Re-enter new password" minlength="6" required autocomplete="new-password">
                        </div>
                        <div id="userPasswordMismatch" class="text-danger small mt-1 d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i> Passwords do not match.
                        </div>
                    </div>

                    <div id="userGeneratedAlert" class="alert alert-info py-2 px-3 small d-none mb-0" role="alert">
                        <i class="bi bi-info-circle me-1"></i> New password generated! Make sure to copy and provide it to the user.
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4" id="submitUserResetBtn">
                        <i class="bi bi-check2 me-1"></i> Save New Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('resetUserPasswordModal');
    const modalUserId = document.getElementById('modalUserId');
    const modalUserName = document.getElementById('modalUserName');
    const modalUserUsername = document.getElementById('modalUserUsername');
    const newPass = document.getElementById('user_new_password');
    const confirmPass = document.getElementById('user_confirm_password');
    const toggleBtn = document.getElementById('toggleUserPasswordVisibility');
    const toggleIcon = document.getElementById('toggleUserPasswordIcon');
    const generateBtn = document.getElementById('btnUserGeneratePassword');
    const generatedAlert = document.getElementById('userGeneratedAlert');
    const mismatchNotice = document.getElementById('userPasswordMismatch');
    const form = document.getElementById('resetUserPasswordForm');
    const submitBtn = document.getElementById('submitUserResetBtn');

    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (button) {
                const userId = button.getAttribute('data-user-id');
                const userName = button.getAttribute('data-user-name');
                const userUsername = button.getAttribute('data-user-username');

                modalUserId.value = userId;
                modalUserName.textContent = userName;
                modalUserUsername.textContent = '@' + userUsername;

                newPass.value = '';
                confirmPass.value = '';
                newPass.type = 'password';
                confirmPass.type = 'password';
                toggleIcon.className = 'bi bi-eye';
                generatedAlert.classList.add('d-none');
                mismatchNotice.classList.add('d-none');
                submitBtn.disabled = false;
            }
        });
    }

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

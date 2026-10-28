<?php
// modules/auth/register.php
session_start();
require_once '../../config/db.php';

$success = "";
$error = "";
$fullname = "";
$email = "";
$username = "";
$role = "lecturer";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $role = $_POST["role"] ?? "lecturer";
    $plainPassword = $_POST["password"] ?? "";

    if (empty($fullname) || empty($email) || empty($username) || empty($plainPassword)) {
        $error = "All fields are required.";
    } elseif (strlen($plainPassword) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);

        try {
            // Check specifically which field exists
            $stmtCheck = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
            $stmtCheck->execute([$username, $email]);
            $existing = $stmtCheck->fetch();

            if ($existing) {
                if (strcasecmp($existing['username'], $username) === 0) {
                    $error = "The username <strong>" . htmlspecialchars($username) . "</strong> is already taken. Please choose another username.";
                } else {
                    $error = "The email address <strong>" . htmlspecialchars($email) . "</strong> is already registered. <a href='login.php' class='alert-link'>Click here to login</a>.";
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$fullname, $email, $username, $password, $role])) {
                    $success = "Registration successful! You can now <a href='login.php' class='alert-link'>Login</a>.";
                    $fullname = $email = $username = "";
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!-- Include Header -->
<?php
$pageTitle = "Register";
require_once '../../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h4><i class="bi bi-person-plus-fill"></i> Register</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= $success ?>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($fullname) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="lecturer" <?= ($role === 'lecturer') ? 'selected' : '' ?>>Lecturer</option>
                            <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Admin</option>
                            <option value="student" <?= ($role === 'student') ? 'selected' : '' ?>>Student</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</div>

<!-- Include Footer -->
<?php require_once '../../includes/footer.php'; ?>
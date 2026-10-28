<?php
// modules/auth/login.php
session_start();
require_once '../../config/db.php';

// Redirect if already logged in
if (isset($_SESSION["user_id"]) && isset($_SESSION["role"])) {
    if ($_SESSION["role"] === 'admin') {
        header("Location: ../dashboard/admin.php");
    } elseif ($_SESSION["role"] === 'student') {
        header("Location: ../dashboard/student.php");
    } else {
        header("Location: ../lecturer/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginInput = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, fullname, email, username, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Save to session
                $_SESSION["user_id"] = $user['id'];
                $_SESSION["username"] = $user['username'];
                $_SESSION["email"] = $user['email'];
                $_SESSION["fullname"] = $user['fullname'];
                $_SESSION["role"] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: ../dashboard/admin.php");
                } elseif ($user['role'] === 'student') {
                    header("Location: ../dashboard/student.php");
                } else {
                    header("Location: ../lecturer/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!-- Include Header -->
<?php
$pageTitle = "Login";
require_once '../../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header bg-success text-white text-center">
                <h4><i class="bi bi-box-arrow-in-right"></i> Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
</div>

<!-- Include Footer -->
<?php require_once '../../includes/footer.php'; ?>
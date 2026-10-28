<?php
// includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
// Auto-detect project base URL dynamically for plug-and-play anywhere
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
$appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$rootPath = ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) ? substr($appRoot, strlen($docRoot)) : '';
$rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $rootPath ?>/index.php">
            <i class="bi bi-mortarboard-fill"></i> Hope Academy
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item d-flex align-items-center text-white me-3">
                        Welcome, <?= htmlspecialchars($_SESSION['fullname'] ?? 'User') ?><?= !empty($role) ? ' (' . ucfirst($role) . ')' : '' ?>
                    </li>
                    <?php if ($role === 'lecturer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/lecturer/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/lecturer/classes.php">My Classes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/lecturer/attendance.php">Attendance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/lecturer/grades.php">Grades</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/lecturer/students.php">Students</a>
                        </li>
                    <?php elseif ($role === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/dashboard/admin.php">Dashboard</a>
                        </li>
                    <?php elseif ($role === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $rootPath ?>/modules/dashboard/student.php">Dashboard</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?= $rootPath ?>/modules/auth/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $rootPath ?>/modules/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $rootPath ?>/modules/auth/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container main-content">

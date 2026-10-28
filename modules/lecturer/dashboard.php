<?php
// modules/lecturer/dashboard.php
require_once '../../includes/lecturer_auth.php';

$pageTitle = "Lecturer Dashboard";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <h3 class="mb-4 text-primary">Dashboard</h3>

    <div class="row g-4">
        <!-- 1. Dashboard Overview (Placeholder for now) -->
        <!-- 
        <div class="col-md-4">
            <a href="#" class="text-decoration-none disabled">
                <div class="card shadow h-100 opacity-50">
                    <div class="card-body text-center">
                        <i class="bi bi-speedometer2 fs-1 text-primary"></i>
                        <h5 class="mt-2">Dashboard Overview</h5>
                        <p>Coming Soon</p>
                    </div>
                </div>
            </a>
        </div>
        -->

        <!-- 2. My Classes -->
        <div class="col-md-4">
            <a href="classes.php" class="text-decoration-none">
                <div class="card shadow h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-bookmark fs-1 text-success"></i>
                        <h5 class="mt-2">My Classes</h5>
                        <p>View assigned classes and student lists</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Attendance -->
        <div class="col-md-4">
            <a href="attendance.php" class="text-decoration-none">
                <div class="card shadow h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-check2-square fs-1 text-warning"></i>
                        <h5 class="mt-2">Attendance</h5>
                        <p>Mark and view student attendance records</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. Academic Records (Grades) -->
        <div class="col-md-4">
            <a href="grades.php" class="text-decoration-none">
                <div class="card shadow h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-pencil-square fs-1 text-danger"></i>
                        <h5 class="mt-2">Academic Records</h5>
                        <p>Enter/update marks and view performance</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- 5. Student Info -->
        <div class="col-md-4">
            <a href="students.php" class="text-decoration-none">
                <div class="card shadow h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-lines-fill fs-1 text-info"></i>
                        <h5 class="mt-2">Student Info</h5>
                        <p>Search and view student academic history</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- 6. Reports (Placeholder) -->
        <!--
        <div class="col-md-4">
            <a href="#" class="text-decoration-none disabled">
                <div class="card shadow h-100 opacity-50">
                    <div class="card-body text-center">
                        <i class="bi bi-bar-chart-line-fill fs-1 text-secondary"></i>
                        <h5 class="mt-2">Reports</h5>
                        <p>Coming Soon</p>
                    </div>
                </div>
            </a>
        </div>
        -->
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
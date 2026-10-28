<div class="sidebar">
    <div class="sidebar-logo">
        Hope Academy<span>.</span>
    </div>
    <nav class="nav flex-column">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="../../modules/dashboard/admin.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard/admin.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
            <a href="../../modules/students/index.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/students') ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Students
            </a>
            <a href="../../modules/lecturers/index.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/lecturers') ? 'active' : '' ?>">
                <i class="bi bi-person-badge-fill"></i> Lecturers
            </a>
            <a href="../../modules/courses/index.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/courses') ? 'active' : '' ?>">
                <i class="bi bi-book-half"></i> Courses
            </a>
            <a href="../../modules/fees/index.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/fees') ? 'active' : '' ?>">
                <i class="bi bi-wallet-fill"></i> Fees
            </a>
            <!-- New Exam & Report Links -->
            <a href="../../modules/exams/schedule.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/exams') ? 'active' : '' ?>">
                <i class="bi bi-calendar-event-fill"></i> Exams
            </a>
            <a href="../../modules/reports/broadsheet.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/reports/broadsheet') ? 'active' : '' ?>">
                <i class="bi bi-table"></i> Broadsheets
            </a>
            <!-- End New Links -->
            <a href="../../modules/grades/student_report.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'student_report.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>
            <a href="../../modules/auth/users.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/auth') ? 'active' : '' ?>">
                <i class="bi bi-gear-fill"></i> Users
            </a>

        <?php elseif ($_SESSION['role'] === 'lecturer'): ?>
            <a href="../../modules/dashboard/lecturer.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard/lecturer.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
            <a href="../../modules/classes/index.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/classes') ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> My Classes
            </a>
            <a href="../../modules/attendance/mark.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/attendance') ? 'active' : '' ?>">
                <i class="bi bi-check2-square"></i> Attendance
            </a>
            <a href="../../modules/grades/entry.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/grades/entry') ? 'active' : '' ?>">
                <i class="bi bi-pencil-square"></i> Grades
            </a>
            <a href="../../modules/grades/student_report.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'modules/grades/student_report') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>

        <?php elseif ($_SESSION['role'] === 'student'): ?>
            <a href="../../modules/dashboard/student.php"
                class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard/student.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../../modules/auth/logout.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
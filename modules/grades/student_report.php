<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

$student_id = null;
$student = null;
$reports = [];

// If admin/lecturer, allow selecting student
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
} else if ($_SESSION['role'] === 'student') {
    // If student, force their own ID
    // Assuming student user has a linked student record or logic to find it.
    // For now, let's assume the user table has no direct student_id link or use email/reg number.
    // BUT the previous Logic allowed Admin/Lecturer selection.
    // Let's stick to the previous logic for Admin/Lecturer and basic handling.
}

// Fetch all students for dropdown (if admin/lecturer)
$all_students = [];
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'lecturer') {
    $stmt = $pdo->query("SELECT id, fullname, registration_number FROM students ORDER BY fullname");
    $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch report if student selected
if ($student_id) {
    // Get Student Details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        // Get Grades
        $stmt = $pdo->prepare("
            SELECT c.class_name, u.fullname as lecturer_name, ar.marks, ar.grade, ar.updated_at
            FROM academic_records ar
            JOIN classes c ON ar.class_id = c.id
            JOIN users u ON c.lecturer_id = u.id
            WHERE ar.student_id = ?
            ORDER BY c.class_name
        ");
        $stmt->execute([$student_id]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Student Reports";
        $page_subtitle = "View academic performance";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4 d-print-none">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Reports</li>
                </ol>
            </nav>

            <!-- Search Form -->
            <?php if ($_SESSION['role'] !== 'student'): ?>
                <div class="custom-card mb-4 d-print-none">
                    <div class="card-body">
                        <form method="GET" class="row align-items-end g-3">
                            <div class="col-md-9">
                                <label class="form-label fw-bold text-muted small">SELECT STUDENT</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-search"></i></span>
                                    <select name="student_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">-- Choose Student to View Report --</option>
                                        <?php foreach ($all_students as $s): ?>
                                            <option value="<?= $s['id'] ?>" <?= ($student_id == $s['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['fullname']) ?>
                                                (<?= htmlspecialchars($s['registration_number']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                    <i class="bi bi-search me-2"></i> View Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($student): ?>
                <div class="custom-card printable-area">
                    <div class="card-header bg-white border-bottom pb-3 pt-4 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1 text-primary fw-bold">Academic Report Card</h4>
                                <p class="text-muted mb-0 small">Generated on <?= date('F d, Y') ?></p>
                            </div>
                            <div class="text-end">
                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($student['fullname']) ?></h5>
                                <div class="badge bg-light text-dark border">
                                    Reg No: <?= htmlspecialchars($student['registration_number']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (count($reports) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Class / Subject</th>
                                            <th>Lecturer</th>
                                            <th class="text-center">Marks</th>
                                            <th class="text-center">Grade</th>
                                            <th class="text-end">Date Recorded</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $row): ?>
                                            <tr>
                                                <td class="fw-bold">
                                                    <?= htmlspecialchars($row['class_name']) ?>
                                                </td>
                                                <td class="text-muted">
                                                    <?= htmlspecialchars($row['lecturer_name']) ?>
                                                </td>
                                                <td class="text-center fw-bold">
                                                    <?= htmlspecialchars($row['marks']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?=
                                                        ($row['grade'] === 'A') ? 'success' :
                                                        (($row['grade'] === 'B') ? 'primary' :
                                                            (($row['grade'] === 'C') ? 'info' :
                                                                (($row['grade'] === 'D') ? 'warning' : 'danger')))
                                                        ?> rounded-pill px-3">
                                                        <?= htmlspecialchars($row['grade']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end text-muted small">
                                                    <?= date('M d, Y', strtotime($row['updated_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-5">
                                <div class="col-8">
                                    <p class="text-muted small">
                                        <em>This document is electronically generated and serves as an official record of the
                                            student's academic performance.</em>
                                    </p>
                                </div>
                                <div class="col-4 text-center border-top">
                                    <p class="mt-2 mb-0 fw-bold">Registrar's Signature</p>
                                </div>
                            </div>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-journal-x fs-1 text-muted opacity-50 mb-3"></i>
                                <p class="text-muted mb-0">No academic records found for this student.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4 d-print-none">
                    <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
                        <i class="bi bi-printer me-2"></i> Print Official Report
                    </button>
                </div>

            <?php elseif ($student_id): ?>
                <div class="alert alert-warning rounded-4 shadow-sm border-0">
                    <i class="bi bi-exclamation-triangle me-2"></i> Student not found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .printable-area,
        .printable-area * {
            visibility: visible;
        }

        .printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            box-shadow: none !important;
        }

        .d-print-none {
            display: none !important;
        }

        /* Hide sidebar and topbar explicitly */
        .sidebar,
        .topbar {
            display: none !important;
        }
    }
</style>

<?php require_once '../../includes/footer.php'; ?>
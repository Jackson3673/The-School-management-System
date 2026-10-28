<?php
session_start();

// Ensure user is student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/db.php';

$email = $_SESSION['email'];

// Fetch linked Student Profile
$stmt = $pdo->prepare("SELECT * FROM students WHERE email = ?");
$stmt->execute([$email]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Error: No student profile linked to this account ($email). Please contact Admin.");
}

$student_id = $student['id'];

// 1. Fetch Enrolled Courses
$stmt = $pdo->prepare("
    SELECT c.class_name, c.course_code, u.fullname as lecturer_name, cs.status
    FROM classes c
    JOIN class_students cs ON c.id = cs.class_id
    LEFT JOIN users u ON c.lecturer_id = u.id
    WHERE cs.student_id = ?
");
$stmt->execute([$student_id]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Grades
$stmt = $pdo->prepare("
    SELECT c.class_name, ar.marks, ar.grade, ar.cats_marks, ar.main_exam_marks 
    FROM academic_records ar
    JOIN classes c ON ar.class_id = c.id
    WHERE ar.student_id = ?
    ORDER BY c.class_name
");
$stmt->execute([$student_id]);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Fees
$stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY transaction_date DESC");
$stmt->execute([$student_id]);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$balance = 0;
foreach ($fees as $f) {
    if ($f['type'] === 'invoice') {
        $balance += $f['amount'];
    } else {
        $balance -= $f['amount'];
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Student Dashboard";
        $page_subtitle = "Welcome back, " . htmlspecialchars($student['first_name']);
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <!-- Header Card -->
            <div class="custom-card mb-4 border-0 bg-primary text-white overflow-hidden position-relative">
                <div class="card-body position-relative z-1 d-flex align-items-center">
                    <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center me-4 shadow-sm"
                        style="width: 72px; height: 72px; font-size: 2rem;">
                        <?= strtoupper(substr($student['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h2 class="mb-1 fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-person-badge me-2"></i> <?= htmlspecialchars($student['registration_number']) ?>
                            <span class="mx-2">•</span>
                            <i class="bi bi-mortarboard me-2"></i> <?= htmlspecialchars($student['program']) ?>
                        </p>
                    </div>
                    <div class="ms-auto text-end d-none d-md-block">
                         <small class="d-block opacity-75 text-uppercase fw-bold ls-1">Current Balance</small>
                         <h2 class="fw-bold mb-0">
                            <?= ($balance > 0) ? '-' . format_currency($balance) : format_currency(abs($balance)) ?>
                         </h2>
                         <small class="badge bg-white bg-opacity-25 mt-1">
                            <?= ($balance > 0) ? 'Outstanding' : 'Credit' ?>
                         </small>
                    </div>
                </div>
                <!-- Decorative Circle -->
                 <div class="position-absolute top-0 end-0 translate-middle-y me-n5 mt-n5 rounded-circle bg-white opacity-10" style="width: 300px; height: 300px;"></div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-pills mb-4 gap-2" id="studentTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                        <i class="bi bi-grid me-2"></i> Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" id="courses-tab" data-bs-toggle="pill" data-bs-target="#courses" type="button">
                        <i class="bi bi-journal-bookmark me-2"></i> My Courses
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" id="grades-tab" data-bs-toggle="pill" data-bs-target="#grades" type="button">
                        <i class="bi bi-trophy me-2"></i> Academic Results
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" id="fees-tab" data-bs-toggle="pill" data-bs-target="#fees" type="button">
                        <i class="bi bi-wallet2 me-2"></i> Fee Statement
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- Overview -->
                <div class="tab-pane fade show active" id="overview">
                     <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="custom-card bg-primary-subtle border-0 h-100 p-4">
                                <div class="d-flex flex-column h-100 justify-content-center text-center">
                                    <h1 class="display-3 fw-bold text-primary mb-2"><?= count($courses) ?></h1>
                                    <p class="text-primary fw-bold text-uppercase small mb-0">Enrolled Units</p>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-4">
                            <div class="custom-card bg-success-subtle border-0 h-100 p-4">
                                <div class="d-flex flex-column h-100 justify-content-center text-center">
                                    <h1 class="display-3 fw-bold text-success mb-2">92%</h1>
                                    <p class="text-success fw-bold text-uppercase small mb-0">Attendance Rate</p>
                                </div>
                            </div>
                        </div>
                         <div class="col-md-4">
                            <div class="custom-card bg-info-subtle border-0 h-100 p-4">
                                <div class="d-flex flex-column h-100 justify-content-center text-center">
                                    <h2 class="fw-bold text-info mb-2">Nov 15</h2>
                                    <p class="text-info fw-bold text-uppercase small mb-0">Next Exam Starts</p>
                                </div>
                            </div>
                        </div>
                     </div>

                     <div class="custom-card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Personal Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted text-uppercase fw-bold">Contact Info</small>
                                        <div class="mt-2">
                                            <div class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i> <?= htmlspecialchars($student['email']) ?></div>
                                            <div class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i> <?= htmlspecialchars($student['phone_number'] ?? 'N/A') ?></div>
                                            <div><i class="bi bi-geo-alt me-2 text-primary"></i> <?= htmlspecialchars($student['address'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted text-uppercase fw-bold">Academic Info</small>
                                        <div class="mt-2">
                                            <div class="mb-2"><i class="bi bi-calendar-event me-2 text-primary"></i> Admitted: <?= $student['admission_date'] ?></div>
                                            <div class="mb-2"><i class="bi bi-clock-history me-2 text-primary"></i> Year: <?= $student['year_of_study'] ?></div>
                                            <div><i class="bi bi-layers me-2 text-primary"></i> Semester: <?= $student['current_semester'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                     </div>
                </div>

                <!-- Courses -->
                <div class="tab-pane fade" id="courses">
                    <div class="custom-card">
                         <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">My Enrolled Courses</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Code</th>
                                            <th>Unit Name</th>
                                            <th>Lecturer</th>
                                            <th class="text-end pe-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($courses) > 0): ?>
                                            <?php foreach ($courses as $c): ?>
                                                <tr>
                                                    <td class="ps-4"><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($c['course_code']) ?></span></td>
                                                    <td class="fw-bold"><?= htmlspecialchars($c['class_name']) ?></td>
                                                    <td>
                                                        <?php if ($c['lecturer_name']): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 24px; height: 24px;">
                                                                    <span class="small fw-bold text-muted"><?= substr($c['lecturer_name'], 0, 1) ?></span>
                                                                </div>
                                                                <?= htmlspecialchars($c['lecturer_name']) ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">TBA</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4"><span class="badge bg-primary-subtle text-primary rounded-pill px-3"><?= htmlspecialchars($c['status']) ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No courses enrolled.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades -->
                <div class="tab-pane fade" id="grades">
                    <div class="custom-card">
                         <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Academic Performance</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                         <tr>
                                            <th class="ps-4">Unit</th>
                                            <th class="text-center">CATs (30%)</th>
                                            <th class="text-center">Exam (70%)</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Grade</th>
                                            <th class="text-end pe-4">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($grades) > 0): ?>
                                            <?php foreach ($grades as $g): ?>
                                                <?php 
                                                    $total = $g['marks'];
                                                    if ($total < 40) $rem = '<span class="text-danger fw-bold">Supplementary</span>';
                                                    elseif ($total >= 70) $rem = '<span class="text-success fw-bold">Distinction</span>';
                                                    elseif ($total >= 60) $rem = '<span class="text-primary fw-bold">Credit</span>';
                                                    else $rem = 'Pass';
                                                ?>
                                                <tr>
                                                    <td class="fw-bold ps-4"><?= htmlspecialchars($g['class_name']) ?></td>
                                                    <td class="text-center text-muted"><?= $g['cats_marks'] ?: '-' ?></td>
                                                    <td class="text-center text-muted"><?= $g['main_exam_marks'] ?: '-' ?></td>
                                                    <td class="text-center fw-bold"><?= $total ?: '-' ?></td>
                                                    <td class="text-center"><span class="badge bg-dark rounded-pill px-3"><?= $g['grade'] ?: '-' ?></span></td>
                                                    <td class="text-end pe-4"><?= $rem ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center py-4 text-muted">No grades recorded yet.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fees -->
                <div class="tab-pane fade" id="fees">
                    <div class="custom-card">
                         <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Fee Statement</h5>
                        </div>
                        <div class="card-body p-0">
                             <div class="table-responsive">
                                 <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Date</th>
                                            <th>Description</th>
                                            <th class="text-end pe-4">Amount (<?= htmlspecialchars(get_currency_symbol()) ?>)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($fees) > 0): ?>
                                            <?php foreach ($fees as $f): ?>
                                                <tr>
                                                    <td class="ps-4 text-muted"><?= date('d M Y', strtotime($f['transaction_date'])) ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                                 style="width: 32px; height: 32px; background-color: <?= $f['type'] == 'invoice' ? '#fff3cd' : '#d1e7dd' ?>;">
                                                                <i class="bi <?= $f['type'] == 'invoice' ? 'bi-file-invoice text-warning' : 'bi-check-lg text-success' ?>"></i>
                                                            </div>
                                                            <div>
                                                                <span class="d-block fw-medium"><?= htmlspecialchars($f['description']) ?></span>
                                                                <small class="text-muted text-uppercase" style="font-size: 0.7rem;">
                                                                    <?= $f['type'] == 'invoice' ? 'INVOICE' : 'PAYMENT' ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold pe-4 <?= $f['type'] == 'payment' ? 'text-success' : 'text-danger' ?>">
                                                         <?= $f['type'] == 'payment' ? '-' : '+' ?> 
                                                         <?= format_currency($f['amount']) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No fee transactions found.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                             </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

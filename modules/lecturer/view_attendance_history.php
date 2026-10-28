<?php
// modules/lecturer/view_attendance_history.php (was view_attendance.php)
require_once '../../includes/lecturer_auth.php';

$class_id = $_GET['class_id'] ?? null;
// $lecturer_id is available from lecturer_auth.php

if (!$class_id) {
    header("Location: dashboard.php");
    exit();
}

// Verify ownership
$stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ? AND lecturer_id = ?");
$stmt->execute([$class_id, $lecturer_id]);
$class = $stmt->fetch();

if (!$class) {
    die("Permission denied.");
}

// Fetch history
$stmt = $pdo->prepare("
    SELECT a.attendance_date, s.fullname, a.status
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.class_id = ?
    ORDER BY a.attendance_date DESC, s.fullname
");
$stmt->execute([$class_id]);
$records = $stmt->fetchAll();

// Group by date
$history = [];
foreach ($records as $r) {
    $history[$r['attendance_date']][] = $r;
}

$pageTitle = "Attendance History";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Attendance:
            <?= htmlspecialchars($class['class_name']) ?>
        </h2>
        <a href="attendance.php?class_id=<?= $class_id ?>" class="btn btn-secondary">Back to Marking</a>
    </div>

    <?php if (empty($history)): ?>
        <p>No records found.</p>
    <?php else: ?>
        <div class="accordion" id="attendanceAccordion">
            <?php foreach ($history as $date => $rows):
                $accordionId = "acc_" . str_replace('-', '', $date);
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#<?= $accordionId ?>">
                            <?= date('l, M j, Y', strtotime($date)) ?>
                            <span class="badge bg-secondary ms-2">
                                <?= count($rows) ?> Students
                            </span>
                        </button>
                    </h2>
                    <div id="<?= $accordionId ?>" class="accordion-collapse collapse" data-bs-parent="#attendanceAccordion">
                        <div class="accordion-body">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr class="<?= $row['status'] === 'absent' ? 'table-danger' : '' ?>">
                                            <td>
                                                <?= htmlspecialchars($row['fullname']) ?>
                                            </td>
                                            <td>
                                                <?= ucfirst($row['status']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
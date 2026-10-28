<?php
// modules/lecturer/attendance.php
require_once '../../includes/lecturer_auth.php';

$message = "";

// Handle submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['submit_attendance'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid CSRF token.";
        $selected_class_id = intval($_POST['class_id']);
    } else {
        $class_id = intval($_POST['class_id']);

        // Strict ownership check
        $stmt0 = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND lecturer_id = ?");
        $stmt0->execute([$class_id, $lecturer_id]);
        if (!$stmt0->fetch()) {
            die("Unauthorized access to this class.");
        }

        $attendance_data = $_POST['attendance'] ?? [];
        $today = date('Y-m-d');

        try {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (class_id, student_id, attendance_date, status)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status), recorded_at = CURRENT_TIMESTAMP
            ");

            foreach ($attendance_data as $student_id => $status) {
                $stmt->execute([$class_id, $student_id, $today, $status]);
            }
            $message = "Attendance recorded successfully for today.";
        } catch (PDOException $e) {
            $message = "Error recording attendance: " . $e->getMessage();
        }

        // Persist selection
        $selected_class_id = $class_id;
    }
} else {
    $selected_class_id = $_GET['class_id'] ?? ($_POST['class_id'] ?? null);
}

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll();

// Verify selected class belongs to lecturer if set
if ($selected_class_id) {
    $belongs = false;
    foreach ($classes as $c) {
        if ($c['id'] == $selected_class_id) {
            $belongs = true;
            break;
        }
    }
    if (!$belongs) {
        $selected_class_id = null; // Reset if trying to access unauthorized class
    }
}

// Fetch students if class selected
$students = [];
$today_attendance = [];
if ($selected_class_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.fullname 
        FROM students s 
        JOIN class_students cs ON s.id = cs.student_id 
        WHERE cs.class_id = ? 
        ORDER BY s.fullname
    ");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll();

    // Get existing records for today to pre-fill
    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE class_id = ? AND attendance_date = CURRENT_DATE");
    $stmt->execute([$selected_class_id]);
    $today_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$pageTitle = "Attendance";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Mark Attendance (
        <?= date('M d, Y') ?>)
    </h2>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-center">
                <div class="col-auto">
                    <label class="col-form-label">Select Class:</label>
                </div>
                <div class="col-auto">
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($selected_class_id == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <?php if ($selected_class_id): ?>
                        <a href="view_attendance_history.php?class_id=<?= $selected_class_id ?>"
                            class="btn btn-outline-info">View History</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selected_class_id && count($students) > 0): ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
            <input type="hidden" name="submit_attendance" value="1">
            <?php csrf_field(); ?>

            <div class="card shadow-sm">
                <table class="table table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Student Name</th>
                            <th class="text-center" width="150">Present</th>
                            <th class="text-center" width="150">Absent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student):
                            $status = $today_attendance[$student['id']] ?? null;
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($student['fullname']) ?>
                                </td>
                                <td class="text-center">
                                    <input class="form-check-input" type="radio" name="attendance[<?= $student['id'] ?>]"
                                        value="present" <?= ($status === 'present' || $status === null) ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input class="form-check-input" type="radio" name="attendance[<?= $student['id'] ?>]"
                                        value="absent" <?= ($status === 'absent') ? 'checked' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary px-4">Save Attendance</button>
                </div>
            </div>
        </form>
    <?php elseif ($selected_class_id): ?>
        <p class="text-muted">No students enrolled in this class yet.</p>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
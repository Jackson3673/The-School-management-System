<?php
// modules/lecturer/grades.php (was academic_records.php)
require_once '../../includes/lecturer_auth.php';

$message = "";

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_marks'])) {
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

        $marks_data = $_POST['marks'] ?? [];
        $grades_data = $_POST['grades'] ?? [];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO academic_records (class_id, student_id, marks, grade)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE marks = VALUES(marks), grade = VALUES(grade), updated_at = CURRENT_TIMESTAMP
            ");

            foreach ($marks_data as $student_id => $mark) {
                $grade = $grades_data[$student_id] ?? '';
                $stmt->execute([$class_id, $student_id, $mark, $grade]);
            }
            $message = "Grades updated successfully.";
        } catch (PDOException $e) {
            $message = "Error saving grades: " . $e->getMessage();
        }

        $selected_class_id = $class_id;
    }
} else {
    $selected_class_id = $_GET['class_id'] ?? null;
}

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll();

// Verify selected class belongs to lecturer
if ($selected_class_id) {
    $belongs = false;
    foreach ($classes as $c) {
        if ($c['id'] == $selected_class_id) {
            $belongs = true;
            break;
        }
    }
    if (!$belongs) {
        $selected_class_id = null;
    }
}

// Fetch students/records if class selected
$students = [];
$current_grades = [];
if ($selected_class_id) {
    // Determine student list
    $stmt = $pdo->prepare("
        SELECT s.id, s.fullname 
        FROM students s 
        JOIN class_students cs ON s.id = cs.student_id 
        WHERE cs.class_id = ? 
        ORDER BY s.fullname
    ");
    $stmt->execute([$selected_class_id]);
    $students = $stmt->fetchAll();

    // Determine existing grades
    $stmt = $pdo->prepare("SELECT student_id, marks, grade FROM academic_records WHERE class_id = ?");
    $stmt->execute([$selected_class_id]);
    $current_grades = $stmt->fetchAll(PDO::FETCH_UNIQUE); // Key by student_id
}

$pageTitle = "Academic Records";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Academic Records</h2>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="mb-4">
        <label class="form-label">Select Class:</label>
        <select name="class_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
            <option value="">-- Choose Class --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($selected_class_id == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['class_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selected_class_id && count($students) > 0): ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
            <input type="hidden" name="submit_marks" value="1">
            <?php csrf_field(); ?>

            <div class="card shadow-sm">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th style="width: 150px;">Marks (0-100)</th>
                            <th style="width: 150px;">Grade (e.g. A)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student):
                            $rec = $current_grades[$student['id']] ?? [];
                            $mark = $rec['marks'] ?? '';
                            $grade = $rec['grade'] ?? '';
                            ?>
                            <tr>
                                <td class="align-middle">
                                    <?= htmlspecialchars($student['fullname']) ?>
                                </td>
                                <td>
                                    <input type="number" name="marks[<?= $student['id'] ?>]" class="form-control"
                                        value="<?= htmlspecialchars($mark) ?>" min="0" max="100">
                                </td>
                                <td>
                                    <input type="text" name="grades[<?= $student['id'] ?>]" class="form-control"
                                        value="<?= htmlspecialchars($grade) ?>" maxlength="3">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    <?php elseif ($selected_class_id): ?>
        <p>No students in this class.</p>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
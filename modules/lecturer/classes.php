<?php
// modules/lecturer/classes.php
require_once '../../includes/lecturer_auth.php';

$message = "";

// Handle add class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid CSRF token.";
    } else {
        $class_name = trim($_POST['class_name']);
        if ($class_name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO classes (class_name, lecturer_id) VALUES (?, ?)");
                $stmt->execute([$class_name, $lecturer_id]);
                // Redirect to avoid form resubmission
                header("Location: classes.php");
                exit();
            } catch (PDOException $e) {
                $message = "Error adding class: " . $e->getMessage();
            }
        }
    }
}

// Handle add student to class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Invalid CSRF token.";
    } else {
        $class_id = intval($_POST['class_id']);

        // Strict ownership check
        $stmt0 = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND lecturer_id = ?");
        $stmt0->execute([$class_id, $lecturer_id]);
        if (!$stmt0->fetch()) {
            die("Unauthorized access to this class.");
        }

        $student_email = trim($_POST['student_email']);
        $student_fullname = trim($_POST['student_fullname']);
        $registration_number = trim($_POST['registration_number']);

        try {
            // 1. Check if student exists or create
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$student_email]);
            $student = $stmt->fetch();

            if (!$student) {
                $stmt = $pdo->prepare("INSERT INTO students (fullname, email, registration_number) VALUES (?, ?, ?)");
                $stmt->execute([$student_fullname, $student_email, $registration_number]);
                $student_id = $pdo->lastInsertId();
            } else {
                $student_id = $student['id'];
            }

            // 2. Link student to class
            $stmt = $pdo->prepare("INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?, ?)");
            $stmt->execute([$class_id, $student_id]);

            header("Location: classes.php");
            exit();
        } catch (PDOException $e) {
            $message = "Error registering student: " . $e->getMessage();
        }
    }
}

// Fetch classes
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll();

$pageTitle = "My Classes";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">My Classes</h2>

    <!-- Add Class Form -->
    <form method="POST" class="mb-5">
        <?php csrf_field(); ?>
        <div class="input-group">
            <input type="text" name="class_name" class="form-control" placeholder="New Class Name" required />
            <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
        </div>
    </form>

    <?php if ($message): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (count($classes) === 0): ?>
        <p>No classes assigned yet.</p>
    <?php else: ?>
        <?php foreach ($classes as $class): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Add Student Form -->
                    <form method="POST" class="row g-3 align-items-center mb-3">
                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>" />
                        <?php csrf_field(); ?>
                        <div class="col-md-3">
                            <input type="text" name="student_fullname" class="form-control" placeholder="Student Name"
                                required />
                        </div>
                        <div class="col-md-3">
                            <input type="email" name="student_email" class="form-control" placeholder="Student Email"
                                required />
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="registration_number" class="form-control" placeholder="Reg Number"
                                required />
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="add_student" class="btn btn-outline-success w-100">Enroll
                                Student</button>
                        </div>
                    </form>

                    <hr>

                    <!-- Student List -->
                    <h6>Enrolled Students:</h6>
                    <ul class="list-group list-group-flush">
                        <?php
                        $sStmt = $pdo->prepare("
                            SELECT s.fullname, s.email, s.registration_number
                            FROM students s
                            INNER JOIN class_students cs ON s.id = cs.student_id
                            WHERE cs.class_id = ?
                            ORDER BY s.fullname
                        ");
                        $sStmt->execute([$class['id']]);
                        $students = $sStmt->fetchAll();

                        if (!$students) {
                            echo '<li class="list-group-item text-muted">No students registered yet.</li>';
                        } else {
                            foreach ($students as $student) {
                                echo '<li class="list-group-item">';
                                echo '<strong>' . htmlspecialchars($student['fullname']) . '</strong> ';
                                echo '<span class="text-muted">(' . htmlspecialchars($student['registration_number']) . ')</span> - ';
                                echo htmlspecialchars($student['email']);
                                echo '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
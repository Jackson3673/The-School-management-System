<?php
// modules/lecturer/students.php
require_once '../../includes/lecturer_auth.php';

// Fetch all classes for filter dropdown (only for this lecturer)
$stmt = $pdo->prepare("SELECT * FROM classes WHERE lecturer_id = ? ORDER BY class_name");
$stmt->execute([$lecturer_id]);
$classes = $stmt->fetchAll();

$selected_class_id = $_GET['class_id'] ?? null;
$search = trim($_GET['search'] ?? '');
$students = [];

if ($selected_class_id) {
    // Verify ownership
    $belongs = false;
    foreach ($classes as $c) {
        if ($c['id'] == $selected_class_id) {
            $belongs = true;
            break;
        }
    }
    if (!$belongs) {
        die("Unauthorized access to this class.");
    }

    $sql = "
        SELECT s.id, s.fullname, s.email, s.registration_number, s.created_at
        FROM students s
        JOIN class_students cs ON s.id = cs.student_id
        WHERE cs.class_id = ?
    ";
    $params = [$selected_class_id];

    if ($search !== '') {
        $sql .= " AND (s.fullname LIKE ? OR s.email LIKE ? OR s.registration_number LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $sql .= " ORDER BY s.fullname";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
}

$pageTitle = "Student Info";
require_once '../../includes/header.php';
require_once '../../includes/navbar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">Student Information</h2>

    <div class="row mb-4">
        <div class="col-12">
            <h5>Select a Class to View Students</h5>
        </div>
        <?php foreach ($classes as $class): ?>
            <div class="col-md-3 mb-3">
                <a href="?class_id=<?= $class['id'] ?>" class="text-decoration-none">
                    <div class="card <?= ($selected_class_id == $class['id']) ? 'border-primary bg-light' : '' ?>">
                        <div class="card-body text-center p-3">
                            <h6>
                                <?= htmlspecialchars($class['class_name']) ?>
                            </h6>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($selected_class_id): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Class Roster</h5>
                <form method="GET" class="d-flex" role="search">
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class_id) ?>">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search students..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                </form>
            </div>
            <div class="card-body p-0">
                <?php if (count($students) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Reg. No</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Joined Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($student['registration_number']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($student['fullname']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($student['email']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        No students found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../../modules/auth/login.php");
    exit();
}
require_once '../../config/db.php';
require_once '../../includes/grade_engine.php';

// Fetch all classes for selection
$stmt = $pdo->query("SELECT * FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_class_id = $_GET['class_id'] ?? null;
$students_data = [];
$assessment_columns = ['CATS', 'EXAM', 'TOTAL', 'GRADE'];
// In a real pivot, subject names would be columns. 
// For this "Course-based" system, a Class usually represents a specific Subject/Unit (e.g. "Math 101").
// So a broadsheet usually shows One Class -> All Students.
// OR, if "Class" = "Form 1", and "Subjects" are sub-entities, we'd pivot subjects.
// Current Naini DB: "classes" seems to be "Subjects/Units" (linked to a lecturer).
// So this report will show a performance list for that ONE unit.
// Future SMS scaling: "Class" = "Grade 1", "Subjects" = "Math, Eng". Broadsheet = Grid of Subjects.
// For now: We display the "Class List with Analysis" (Ranked).

if ($selected_class_id) {
    // Fetch records
    $stmt = $pdo->prepare("
        SELECT s.fullname, s.registration_number, ar.cats_marks, ar.main_exam_marks, ar.marks, ar.grade
        FROM students s
        JOIN class_students cs ON s.id = cs.student_id
        LEFT JOIN academic_records ar ON (s.id = ar.student_id AND ar.class_id = ?)
        WHERE cs.class_id = ?
        ORDER BY ar.marks DESC
    ");
    $stmt->execute([$selected_class_id, $selected_class_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Class Name
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$selected_class_id]);
    $selected_class_name = $stmt->fetchColumn();
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<style>
    @media print {

        .sidebar,
        .topbar,
        .no-print {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            width: 100% !important;
        }

        .card {
            border: none !important;
            shadow: none !important;
        }
    }
</style>

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Class Broadsheet";
        $page_subtitle = "Performance Analysis Matrix";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">

            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <form class="d-flex gap-2" method="GET">
                    <select name="class_id" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selected_class_id == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if ($selected_class_id): ?>
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer me-2"></i> Print Broadsheet
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($selected_class_id): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-uppercase mb-0">
                                <?= htmlspecialchars($selected_class_name) ?>
                            </h3>
                            <p class="text-muted">Performance Report</p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle table-sm border-dark">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th width="50">Pos</th>
                                        <th class="text-start">Student Name</th>
                                        <th class="text-start">Reg. No</th>
                                        <th width="80">CATs (30)</th>
                                        <th width="80">Exam (70)</th>
                                        <th width="80">Total (100)</th>
                                        <th width="80">Grade</th>
                                        <th>Comment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rank = 1;
                                    foreach ($results as $row):
                                        $total = $row['marks'] ?? 0;
                                        $grading = calculateGrade($total); // Use engine
                                        // Highlight failing grades
                                        $bg_class = ($total < 40) ? 'bg-danger-subtle text-danger fw-bold' : '';
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold">
                                                <?= $rank++ ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['fullname']) ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['registration_number']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $row['cats_marks'] ?? '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $row['main_exam_marks'] ?? '-' ?>
                                            </td>
                                            <td class="text-center fw-bold <?= $bg_class ?>">
                                                <?= $row['marks'] ?? '-' ?>
                                            </td>
                                            <td class="text-center fw-bold <?= $bg_class ?>">
                                                <?= $row['grade'] ?? '-' ?>
                                            </td>
                                            <td class="small">
                                                <?= $grading['comment'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($results) == 0): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-3">No students found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 row">
                            <div class="col-md-4">
                                <div class="p-3 border rounded bg-light">
                                    <h6 class="fw-bold">Summary</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Total Students:</span>
                                        <strong>
                                            <?= count($results) ?>
                                        </strong>
                                    </div>
                                    <!-- Add more stats like average later -->
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="bi bi-arrow-left me-2"></i> Please select a class to view the broadsheet.
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

$error = '';
$success = '';

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $class_name = trim($_POST['class_name']);
    $lecturer_id = intval($_POST['lecturer_id']);

    if (empty($class_name) || $lecturer_id === 0) {
        $error = "Course Name and Lecturer are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, lecturer_id) VALUES (?, ?)");
        if ($stmt->execute([$class_name, $lecturer_id])) {
            $success = "Course added successfully.";
        } else {
            $error = "Failed to add course.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $success = "Course deleted successfully.";
    } else {
        $error = "Failed to delete course.";
    }
}

// Fetch all courses
$stmt = $pdo->query("
    SELECT c.id, c.class_name, c.created_at, u.fullname as lecturer_name 
    FROM classes c 
    LEFT JOIN users u ON c.lecturer_id = u.id 
    ORDER BY c.class_name
");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all lecturers for dropdown
$stmt = $pdo->query("SELECT id, fullname FROM users WHERE role = 'lecturer' ORDER BY fullname");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Manage Courses";
        $page_subtitle = "Add and assign subjects";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Courses</li>
                </ol>
            </nav>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Add Course Form -->
                <div class="col-md-4">
                    <div class="custom-card mb-4 h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Add New Course</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Course Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-book"></i></span>
                                        <input type="text" name="class_name" class="form-control"
                                            placeholder="e.g. Mathematics 101" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Assign Lecturer</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-video3"></i></span>
                                        <select name="lecturer_id" class="form-select" required>
                                            <option value="">-- Select Lecturer --</option>
                                            <?php foreach ($lecturers as $l): ?>
                                                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['fullname']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" name="add_course" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg me-2"></i> Create Course
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Course List -->
                <div class="col-md-8">
                    <div class="custom-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Available Courses</h5>
                            <span class="badge bg-primary-subtle text-primary rounded-pill"><?= count($courses) ?>
                                Courses</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($courses) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Course Name</th>
                                                <th>Lecturer</th>
                                                <th class="text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($courses as $c): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold text-dark">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                                                style="width: 32px; height: 32px;">
                                                                <i class="bi bi-journal-bookmark"></i>
                                                            </div>
                                                            <?= htmlspecialchars($c['class_name']) ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($c['lecturer_name']): ?>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border"
                                                                    style="width: 24px; height: 24px; font-size: 0.7rem;">
                                                                    <?= strtoupper(substr($c['lecturer_name'], 0, 1)) ?>
                                                                </div>
                                                                <span
                                                                    class="text-muted small"><?= htmlspecialchars($c['lecturer_name']) ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <span
                                                                class="badge bg-secondary-subtle text-secondary border">Unassigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="edit.php?id=<?= $c['id'] ?>"
                                                            class="btn btn-sm btn-outline-primary me-1 py-1 px-2" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="index.php?delete_id=<?= $c['id'] ?>"
                                                            class="btn btn-sm btn-outline-danger py-1 px-2"
                                                            onclick="return confirm('Are you sure?');" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="mb-3 text-muted">
                                        <i class="bi bi-journals fs-1 opacity-50"></i>
                                    </div>
                                    <h5 class="text-muted">No courses found.</h5>
                                    <p class="mb-0 text-muted small">Add a new course to get started.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
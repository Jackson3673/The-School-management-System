<?php
session_start();

// Ensure user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = trim($_POST['class_name']);
    $lecturer_id = intval($_POST['lecturer_id']);
    $course_code = trim($_POST['course_code'] ?? ''); // Added course code if available in DB schema, otherwise ignore or add column

    if (empty($class_name) || $lecturer_id === 0) {
        $error = "Course Name and Lecturer are required.";
    } else {
        // Update query
        $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, lecturer_id = ? WHERE id = ?");
        if ($stmt->execute([$class_name, $lecturer_id, $id])) {
            $success = "Course details updated successfully.";
        } else {
            $error = "Failed to update course. Please try again.";
        }
    }
}

// Fetch Course Data
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Course not found.");
}

// Fetch Lecturers
$stmt = $pdo->query("SELECT id, fullname FROM users WHERE role = 'lecturer' ORDER BY fullname");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Edit Course";
        $page_subtitle = "Update course details and assignment";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard/admin.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Courses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Course</li>
                </ol>
            </nav>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="custom-card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Course Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Course
                                        Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-book"></i></span>
                                        <input type="text" name="class_name" class="form-control"
                                            placeholder="e.g. Introduction to Computer Science" required
                                            value="<?= htmlspecialchars($course['class_name']) ?>">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Assigned
                                        Lecturer</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i
                                                class="bi bi-person-workspace"></i></span>
                                        <select name="lecturer_id" class="form-select" required>
                                            <option value="">-- Select Lecturer --</option>
                                            <?php foreach ($lecturers as $l): ?>
                                                <option value="<?= $l['id'] ?>" <?= $course['lecturer_id'] == $l['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($l['fullname']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-text">The selected lecturer will manage this course's students and
                                        grades.</div>
                                </div>

                                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                                    <a href="index.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                                    <button type="submit" class="btn btn-primary rounded-pill px-5">
                                        <i class="bi bi-check-lg me-2"></i> Update Course
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
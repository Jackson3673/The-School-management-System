<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    if ($stmt->execute([$delete_id])) {
        $success = "Student deleted successfully.";
    } else {
        $error = "Failed to delete student.";
    }
}

// Fetch all students (the kids)
$stmt = $pdo->query("SELECT * FROM students ORDER BY fullname");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Students List";
        $page_subtitle = "View and manage students";
        require_once '../../includes/topbar.php';
        ?>

        <div class="d-flex justify-content-end mb-4">
            <a href="create.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-person-plus-fill me-2"></i> Register New Student
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?= $success ?></div>
        <?php endif; ?>

        <div class="custom-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start">Student Info</th>
                            <th class="border-0">Reg Number</th>
                            <th class="border-0">Joined</th>
                            <th class="border-0 rounded-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) > 0): ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-light-info text-info d-flex align-items-center justify-content-center"
                                                style="width: 40px; height: 40px;">
                                                <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold">
                                                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span
                                            class="badge bg-light text-dark border"><?= htmlspecialchars($student['registration_number']) ?></span>
                                    </td>
                                    <td class="text-muted">
                                        <?= htmlspecialchars(date('M d, Y', strtotime($student['created_at']))) ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light rounded-circle" type="button"
                                                data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                <li><a class="dropdown-item" href="view.php?student_id=<?= $student['id'] ?>"><i
                                                            class="bi bi-person-badge me-2"></i> Profile</a></li>
                                                <li><a class="dropdown-item" href="edit.php?id=<?= $student['id'] ?>"><i
                                                            class="bi bi-pencil me-2"></i> Edit</a></li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li><a class="dropdown-item text-danger"
                                                        href="index.php?delete_id=<?= $student['id'] ?>"
                                                        onclick="return confirm('Are you sure?');"><i
                                                            class="bi bi-trash me-2"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
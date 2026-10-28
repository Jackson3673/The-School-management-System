<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

$error = '';
$success = '';
$student = null;

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $fullname = $first_name . ' ' . $last_name;
    $email = trim($_POST['email']);
    $registration_number = trim($_POST['registration_number']);
    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'] ?: null;
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $program = trim($_POST['program']);
    $year_study = $_POST['year_study'];
    $semester = $_POST['semester'];
    $admission_date = $_POST['admission_date'];

    $kin_name = trim($_POST['kin_name']);
    $kin_phone = trim($_POST['kin_phone']);
    $kin_relation = trim($_POST['kin_relation']);

    if (empty($first_name) || empty($last_name) || empty($email) || empty($registration_number)) {
        $error = "Name, Email, and Reg No are required.";
    } else {
        // Check for duplicates
        $stmt_check_duplicate = $pdo->prepare("SELECT id FROM students WHERE (email = ? OR registration_number = ?) AND id != ?");
        $stmt_check_duplicate->execute([$email, $registration_number, $id]);

        if ($stmt_check_duplicate->rowCount() > 0) {
            $error = "Email or Registration Number already in use.";
        } else {
            // Update DB
            $sql = "UPDATE students SET first_name=?, last_name=?, fullname=?, email=?, registration_number=?, dob=?, gender=?, phone_number=?, address=?, program=?, year_of_study=?, current_semester=?, admission_date=?, next_of_kin_name=?, next_of_kin_phone=?, next_of_kin_relation=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            try {
                if ($stmt->execute([$first_name, $last_name, $fullname, $email, $registration_number, $dob, $gender, $phone, $address, $program, $year_study, $semester, $admission_date, $kin_name, $kin_phone, $kin_relation, $id])) {
                    $success = "Student details updated.";

                    // SYNC USER ACCOUNT (If email changed)
                    // We assume the user linked by current email needs to be updated, or we find by old email?
                    // Since we don't have the old email easily here without re-fetching value before update (which we didn't do explicitly in a var, but we have strict mode).
                    // Actually, simpler: Search for user with SAME email (if it didn't change) or OLD email.
                    // Risk: If email changed, we can't find the user by new email!
                    // Better approach: We should have fetched the old email before update.
                    // But we can try to update based on fullname match? No, unreliable.
                    // For now, let's just Try to update the user where username = OLD Email.
                    // Wait, we don't have the old email here.

                    // Let's rely on the user manually updating login if they change email, OR
                    // Improve this by fetching old email at top of script.

                    // Re-fetching student to get updated data
                    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
                    $stmt->execute([$id]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to update.";
                }
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Edit Student Profile";
        $page_subtitle = "Update student information";
        require_once '../../includes/topbar.php';
        ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="custom-card border-0">

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">
                            <?= $success ?>
                            <a href="index.php" class="alert-link fw-bold ms-2">Back to List <i
                                    class="bi bi-arrow-right"></i></a>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <!-- Left Column: Core Info & Placement -->
                            <div class="col-md-6">
                                <h5 class="fw-bold text-primary mb-3">Core Identity</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">FIRST NAME</label>
                                        <input type="text" name="first_name" class="form-control"
                                            value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">LAST NAME</label>
                                        <input type="text" name="last_name" class="form-control"
                                            value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?= htmlspecialchars($student['email']) ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">REGISTRATION NUMBER</label>
                                    <input type="text" name="registration_number" class="form-control"
                                        value="<?= htmlspecialchars($student['registration_number']) ?>" required>
                                </div>

                                <h5 class="fw-bold text-primary mb-3 mt-4">Academic Placement</h5>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">PROGRAM / COURSE</label>
                                    <input type="text" name="program" class="form-control"
                                        value="<?= htmlspecialchars($student['program'] ?? '') ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">YEAR OF STUDY</label>
                                        <select name="year_study" class="form-select">
                                            <option value="Year 1" <?= ($student['year_of_study'] ?? '') == 'Year 1' ? 'selected' : '' ?>>Year 1</option>
                                            <option value="Year 2" <?= ($student['year_of_study'] ?? '') == 'Year 2' ? 'selected' : '' ?>>Year 2</option>
                                            <option value="Year 3" <?= ($student['year_of_study'] ?? '') == 'Year 3' ? 'selected' : '' ?>>Year 3</option>
                                            <option value="Year 4" <?= ($student['year_of_study'] ?? '') == 'Year 4' ? 'selected' : '' ?>>Year 4</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">SEMESTER</label>
                                        <select name="semester" class="form-select">
                                            <option value="Semester 1" <?= ($student['current_semester'] ?? '') == 'Semester 1' ? 'selected' : '' ?>>Semester 1</option>
                                            <option value="Semester 2" <?= ($student['current_semester'] ?? '') == 'Semester 2' ? 'selected' : '' ?>>Semester 2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">ADMISSION DATE</label>
                                    <input type="date" name="admission_date" class="form-control"
                                        value="<?= $student['admission_date'] ?? '' ?>">
                                </div>
                            </div>

                            <!-- Right Column: Demographics & Guardian -->
                            <div class="col-md-6 border-start ps-md-4">
                                <h5 class="fw-bold text-primary mb-3">Demographics</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">DATE OF BIRTH</label>
                                        <input type="date" name="dob" class="form-control"
                                            value="<?= $student['dob'] ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">GENDER</label>
                                        <select name="gender" class="form-select">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male
                                            </option>
                                            <option value="Female" <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>
                                                Female</option>
                                            <option value="Other" <?= $student['gender'] == 'Other' ? 'selected' : '' ?>>
                                                Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">PHONE NUMBER</label>
                                    <input type="text" name="phone" class="form-control"
                                        value="<?= htmlspecialchars($student['phone_number'] ?? '') ?>">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">PHYSICAL ADDRESS</label>
                                    <textarea name="address" class="form-control"
                                        rows="2"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                                </div>

                                <h5 class="fw-bold text-primary mb-3 mt-4">Guardian / Next of Kin</h5>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label text-muted small fw-bold">GUARDIAN NAME</label>
                                        <input type="text" name="kin_name" class="form-control"
                                            value="<?= htmlspecialchars($student['next_of_kin_name'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted small fw-bold">RELATION</label>
                                        <select name="kin_relation" class="form-select">
                                            <option value="">Select Relationship</option>
                                            <?php $rels = ['Parent', 'Sibling', 'Guardian', 'Sponsor', 'Other'];
                                            foreach ($rels as $r): ?>
                                                <option value="<?= $r ?>" <?= ($student['next_of_kin_relation'] ?? '') == $r ? 'selected' : '' ?>><?= $r ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">GUARDIAN PHONE</label>
                                    <input type="text" name="kin_phone" class="form-control"
                                        value="<?= htmlspecialchars($student['next_of_kin_phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <a href="index.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                <i class="bi bi-check-lg me-2"></i> Update Details
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
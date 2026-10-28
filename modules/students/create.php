<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

$error = '';
$success = '';

// Predefined courses
$courses = ['BIT', 'BCS', 'DIT', 'SE', 'BCOM'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $fullname = $first_name . ' ' . $last_name; // Keep for backward compatibility or display
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $program = trim($_POST['program']); // Replaces course
    $year_study = $_POST['year_study'];
    $semester = $_POST['semester'];
    $admission_date = $_POST['admission_date'] ?: date('Y-m-d');

    $dob = $_POST['dob'] ?: null;
    $gender = $_POST['gender'] ?: null;
    $address = trim($_POST['address']);
    $kin_name = trim($_POST['kin_name']);
    $kin_phone = trim($_POST['kin_phone']);
    $kin_relation = trim($_POST['kin_relation']);

    $year = date('Y', strtotime($admission_date));

    // Basic Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($program)) {
        $error = "Name, Email, and Program are required.";
    } else {
        // Check for email duplicate
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "Student with this Email already exists.";
        } else {
            // GENERATE ADMISSION NUMBER
            // Pattern: PROGRAM/YEAR/XXXX (e.g., BIT/2026/0001)
            $stmt = $pdo->prepare("SELECT registration_number FROM students WHERE registration_number LIKE ? ORDER BY id DESC");
            $like_pattern = "$program/%";
            $stmt->execute([$like_pattern]);
            $existing_regs = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $max_seq = 0;
            foreach ($existing_regs as $reg) {
                // Format: BIT/2026/0001
                $parts = explode('/', $reg);
                if (count($parts) === 3) {
                    $seq_part = intval($parts[2]);
                    if ($seq_part > $max_seq) {
                        $max_seq = $seq_part;
                    }
                }
            }

            $next_seq = $max_seq + 1;
            $registration_number = sprintf("%s/%s/%04d", $program, $year, $next_seq);

            $sql = "INSERT INTO students (first_name, last_name, fullname, email, phone_number, registration_number, program, year_of_study, current_semester, admission_date, dob, gender, address, next_of_kin_name, next_of_kin_phone, next_of_kin_relation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$first_name, $last_name, $fullname, $email, $phone, $registration_number, $program, $year_study, $semester, $admission_date, $dob, $gender, $address, $kin_name, $kin_phone, $kin_relation])) {

                // AUTO-CREATE USER ACCOUNT
                // Check if user exists first (by email or username)
                $stmt_u = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt_u->execute([$email, $email]); // Use email as username

                if ($stmt_u->rowCount() == 0) {
                    $default_pass = password_hash('student123', PASSWORD_BCRYPT);
                    // USE REGISTRATION NUMBER AS USERNAME
                    $stmt_user = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'student')");
                    $stmt_user->execute([$fullname, $email, $registration_number, $default_pass]);
                    $user_msg = " User account created (Username: <strong>$registration_number</strong>, Pass: student123).";
                } else {
                    $user_msg = " User account already exists.";
                }

                $success = "Student added successfully. Admission No: <strong>$registration_number</strong>." . $user_msg;
            } else {
                $error = "Failed to add student.";
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
        $page_title = "Register New Student";
        $page_subtitle = "Enroll a student into the system";
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
                            <a href="index.php" class="alert-link fw-bold ms-2">View List <i
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
                                        <input type="text" name="first_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">LAST NAME</label>
                                        <input type="text" name="last_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">EMAIL ADDRESS (LOGIN)</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <h5 class="fw-bold text-primary mb-3 mt-4">Academic Placement</h5>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">PROGRAM / COURSE</label>
                                    <select name="program" class="form-select" required>
                                        <option value="">-- Select Program --</option>
                                        <?php foreach ($courses as $c): ?>
                                            <option value="<?= $c ?>"><?= $c ?> (Bachelor/Diploma)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text small"><i class="bi bi-info-circle me-1"></i>Used to generate
                                        Admission No.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">YEAR OF STUDY</label>
                                        <select name="year_study" class="form-select">
                                            <option value="Year 1">Year 1</option>
                                            <option value="Year 2">Year 2</option>
                                            <option value="Year 3">Year 3</option>
                                            <option value="Year 4">Year 4</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">SEMESTER</label>
                                        <select name="semester" class="form-select">
                                            <option value="Semester 1">Semester 1</option>
                                            <option value="Semester 2">Semester 2</option>
                                            <option value="Trimester 1">Trimester 1</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">ADMISSION DATE</label>
                                    <input type="date" name="admission_date" class="form-control"
                                        value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <!-- Right Column: Demographics & Guardian -->
                            <div class="col-md-6 border-start ps-md-4">
                                <h5 class="fw-bold text-primary mb-3">Demographics</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">DATE OF BIRTH</label>
                                        <input type="date" name="dob" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted small fw-bold">GENDER</label>
                                        <select name="gender" class="form-select">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">PHONE NUMBER</label>
                                    <input type="text" name="phone" class="form-control" placeholder="07...">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">PHYSICAL ADDRESS</label>
                                    <textarea name="address" class="form-control" rows="2"
                                        placeholder="e.g. Thika, Kiambu"></textarea>
                                </div>

                                <h5 class="fw-bold text-primary mb-3 mt-4">Guardian / Next of Kin</h5>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label text-muted small fw-bold">GUARDIAN NAME</label>
                                        <input type="text" name="kin_name" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label text-muted small fw-bold">RELATION</label>
                                        <select name="kin_relation" class="form-select">
                                            <option value="">Select...</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Sibling">Sibling</option>
                                            <option value="Guardian">Guardian</option>
                                            <option value="Sponsor">Sponsor</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">GUARDIAN PHONE</label>
                                    <input type="text" name="kin_phone" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                            <a href="index.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                <i class="bi bi-check-lg me-2"></i> Register Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
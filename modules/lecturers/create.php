<?php
session_start();

// Ensure user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../includes/header.php';

// Setup page variables for the navbar
$page_title = "Add Lecturer";
$page_subtitle = "Create a new lecturer account";
?>

<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <!-- Sidebar -->
    <?php require_once '../../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content w-100">
        <!-- Topbar -->
        <?php require_once '../../includes/topbar.php'; ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard/admin.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Lecturers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Lecturer</li>
                </ol>
            </nav>

            <?php
            // HANDLE FORM SUBMISSION
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $fullname = trim($_POST['fullname']);
                $email = trim($_POST['email']);
                $username = trim($_POST['username']);
                $password = $_POST['password'];

                if (empty($fullname) || empty($email) || empty($username) || empty($password)) {
                    $error = "All fields are required.";
                } else {
                    require_once '../../config/db.php'; // Ensure DB is available
            
                    // Check if email or username exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                    $stmt->execute([$email, $username]);

                    if ($stmt->rowCount() > 0) {
                        $error = "Email or Username already exists. Please choose another.";
                    } else {
                        // Create Lecturer
                        $hashed = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'lecturer')");

                        if ($stmt->execute([$fullname, $email, $username, $hashed])) {
                            $success = "Lecturer <strong>$fullname</strong> created successfully.";
                        } else {
                            $error = "Failed to create lecturer account.";
                        }
                    }
                }
            }
            ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card custom-card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Lecturer Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="fullname" class="form-label">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" name="fullname" id="fullname"
                                                placeholder="e.g. Dr. John Doe" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i
                                                    class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" id="email"
                                                placeholder="john@university.edu" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i
                                                    class="bi bi-person-badge"></i></span>
                                            <input type="text" class="form-control" name="username" id="username"
                                                placeholder="johndoe" required>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="password" class="form-label">Temporary Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                            <input type="text" class="form-control" name="password" id="password"
                                                value="lecturer123" required>
                                        </div>
                                        <div class="form-text">Lecturers can change this after their first login.</div>
                                    </div>

                                    <div class="col-12 mt-4 d-flex justify-content-end gap-2">
                                        <a href="index.php" class="btn btn-light border rounded-pill px-4">Cancel</a>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">Create
                                            Lecturer</button>
                                    </div>
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
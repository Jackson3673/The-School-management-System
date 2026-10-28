<?php
// Connect to the database
$host = "localhost";
$user = "root";       // change if using another user
$pass = "";           // change if password is set
$db   = "naini";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"]);
    $email    = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $role     = $_POST["role"];

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullname, $email, $username, $password, $role);

    if ($stmt->execute()) {
        $success = "Registration successful!";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Hope Academy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
          <h4><i class="bi bi-person-plus-fill"></i> Register</h4>
        </div>
        <div class="card-body">
          <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
          <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" action="">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <select name="role" class="form-select">
                <option value="lecturer">Lecturer</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Register</button>
          </form>
        </div>
        <div class="card-footer text-center">
          Already have an account? <a href="login.php">Login here</a>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>

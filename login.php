<?php
session_start();

// DB Connection
$host = "localhost";
$user = "root";
$pass = ""; // update if needed
$db   = "naini";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, fullname, email, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // If user exists
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $fullname, $email, $username_db, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Save to session
            $_SESSION["user_id"]   = $id;
            $_SESSION["username"]  = $username_db;
            $_SESSION["email"]     = $email;
            $_SESSION["fullname"]  = $fullname;
            $_SESSION["role"]      = $role;

            // Redirect
            if ($role === 'admin') {
                header("Location: admin/d.php");
            } else {
                header("Location: lec/d.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - Hope Academy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-header bg-success text-white text-center">
          <h4><i class="bi bi-box-arrow-in-right"></i> Login</h4>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST" action="">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Login</button>
          </form>
        </div>
        <div class="card-footer text-center">
          Don't have an account? <a href="register.php">Register here</a>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>

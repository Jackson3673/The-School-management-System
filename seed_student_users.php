<?php
require_once 'config/db.php';

try {
    // Fetch all students without a user account (matching by email)
    $stmt = $pdo->query("SELECT * FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Checking " . count($students) . " students...<br>";

    $count = 0;
    foreach ($students as $s) {
        $email = $s['email'];
        $fullname = $s['first_name'] . ' ' . $s['last_name'];

        // Check user
        $stmt_u = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_u->execute([$email]);

        if ($stmt_u->rowCount() == 0) {
            // Create User
            $pass = password_hash('student123', PASSWORD_BCRYPT);
            $stmt_ins = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, 'student')");
            // Use Email as Username for simplicity
            if ($stmt_ins->execute([$fullname, $email, $email, $pass])) {
                echo "Created User for: $fullname (Email: $email)<br>";
                $count++;
            } else {
                echo "Failed to create user for: $fullname<br>";
            }
        } else {
            echo "User already exists for: $fullname<br>";
        }
    }

    echo "Done. Created $count new user accounts.<br>";
    echo "Default Password: <strong>student123</strong>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
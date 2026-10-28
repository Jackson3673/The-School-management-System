<?php
require_once 'config/db.php';

try {
    echo "Updating Student Usernames to Registration Numbers...<br>";

    // Get all students
    $stmt = $pdo->query("SELECT email, registration_number FROM students");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($students as $s) {
        // Update user with matching email
        $sql = "UPDATE users SET username = ? WHERE email = ? AND role = 'student'";
        $stmt_upd = $pdo->prepare($sql);

        if ($stmt_upd->execute([$s['registration_number'], $s['email']])) {
            if ($stmt_upd->rowCount() > 0) {
                echo "Updated user for {$s['email']} -> Username: <strong>{$s['registration_number']}</strong><br>";
                $count++;
            }
        }
    }

    echo "Update Complete. Modified $count users.";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
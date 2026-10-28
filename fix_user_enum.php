<?php
require_once 'config/db.php';

try {
    echo "Updating users table role ENUM...<br>";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student'");
    echo "✓ Role ENUM updated.<br>";

    echo "Fixing 'student' user account...<br>";
    $stmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE username = 'student'");
    $stmt->execute();
    echo "✓ User 'student' role updated to 'student'.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
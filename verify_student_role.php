<?php
require_once 'config/db.php';
$stmt = $pdo->prepare("SELECT username, role, email FROM users WHERE username = 'student'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "User: " . $user['username'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
} else {
    echo "User 'student' not found.";
}
?>
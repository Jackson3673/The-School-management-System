<?php
require_once 'config/db.php';

try {
    $username = 'admin';
    $password = 'admin123';
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $fullname = 'System Administrator';
    $email = 'admin@hopeacademy.com';
    $role = 'admin';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = ? WHERE id = ?");
        $stmt->execute([$password_hash, $role, $user['id']]);
        echo "Updated existing 'admin' user password and role.<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fullname, $email, $username, $password_hash, $role]);
        echo "Created new 'admin' user with password 'admin123'.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
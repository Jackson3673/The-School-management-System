<?php
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, username, email, role, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>User Diagnostic</h2>";
    echo "<table border='1'><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash (Prefix)</th></tr>";
    foreach ($users as $u) {
        $pass_prefix = substr($u['password'], 0, 10) . '...';
        echo "<tr>
            <td>{$u['id']}</td>
            <td>{$u['username']}</td>
            <td>{$u['email']}</td>
            <td>{$u['role']}</td>
            <td>{$pass_prefix}</td>
        </tr>";
    }
    echo "</table>";

    // Test Admin Login specifically
    echo "<h3>Testing Admin Login (admin / admin123)</h3>";
    $test_user = 'admin';
    $test_pass = 'admin123';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$test_user]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        echo "Admin user found.<br>";
        if (password_verify($test_pass, $admin['password'])) {
            echo "<span style='color:green'>Password Verify: SUCCESS</span><br>";
        } else {
            echo "<span style='color:red'>Password Verify: FAILED</span><br>";
        }
    } else {
        echo "Admin user NOT found.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
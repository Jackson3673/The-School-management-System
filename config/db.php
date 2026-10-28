<?php
// config/db.php
// Central Database Connection with Auto-Provisioning

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'naini';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If database does not exist (Error code 1049), auto-create and initialize it
    if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $pdoInit = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
            $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Run database initialization script silently
            $setupScript = __DIR__ . '/../setup_database.php';
            if (file_exists($setupScript)) {
                ob_start();
                include $setupScript;
                ob_end_clean();
            }
        } catch (\PDOException $initEx) {
            die("<div style='font-family:sans-serif;padding:25px;max-width:600px;margin:40px auto;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;'>
                <h3 style='margin-top:0;'>Database Setup Error</h3>
                <p>Could not auto-create database <strong>$db</strong>: " . htmlspecialchars($initEx->getMessage()) . "</p>
            </div>");
        }
    } else {
        // Friendly message if MySQL server is not running
        die("<div style='font-family:sans-serif;padding:30px;max-width:620px;margin:40px auto;background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);'>
            <h3 style='margin-top:0;'>Database Connection Required</h3>
            <p>Could not connect to the MySQL database server at <strong>$host</strong>.</p>
            <p><strong>To fix this on your computer:</strong></p>
            <ol>
                <li>Open <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to <strong>MySQL</strong>.</li>
                <li>Or double-click <strong>start.bat</strong> in the project folder to start everything automatically.</li>
            </ol>
            <p style='margin-bottom:0;'><a href='' onclick='location.reload();return false;' style='display:inline-block;padding:8px 16px;background:#0d6efd;color:white;text-decoration:none;border-radius:5px;'>Retry Connection</a></p>
        </div>");
    }
}
?>
<?php
// config/db.php
// Central Database Connection with Auto-Provisioning

$configuredHost = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'naini';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port = getenv('DB_PORT') ?: '3306';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Candidates: configured host first, then fallback to 127.0.0.1 and localhost
$candidateHosts = array_values(array_unique([$configuredHost, '127.0.0.1', 'localhost']));
$pdo = null;
$connectedHost = null;
$lastException = null;

foreach ($candidateHosts as $h) {
    try {
        $dsn = "mysql:host=$h;port=$port;dbname=$db;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, $options);
        $connectedHost = $h;
        $host = $h;
        break;
    } catch (\PDOException $e) {
        $lastException = $e;
        $msg = $e->getMessage();
        $code = (string)$e->getCode();

        // Check if server is reachable but the database does not exist
        $isUnknownDb = ($code === '1049' || $code === '42000' || strpos($msg, '1049') !== false || stripos($msg, 'Unknown database') !== false);

        if ($isUnknownDb) {
            try {
                $pdoServer = new PDO("mysql:host=$h;port=$port;charset=$charset", $user, $pass, $options);
                $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo = new PDO($dsn, $user, $pass, $options);
                $connectedHost = $h;
                $host = $h;

                $setupScript = __DIR__ . '/../setup_database.php';
                if (file_exists($setupScript)) {
                    ob_start();
                    include $setupScript;
                    ob_end_clean();
                }
                break;
            } catch (\PDOException $initEx) {
                $lastException = $initEx;
            }
        }
    }
}

if (!$pdo) {
    $errDetail = $lastException ? htmlspecialchars($lastException->getMessage()) : 'Unknown connection error';
    die("<div style='font-family:sans-serif;padding:30px;max-width:620px;margin:40px auto;background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.08);'>
        <h3 style='margin-top:0;'>Database Connection Required</h3>
        <p>Could not connect to the MySQL database server at <strong>$configuredHost</strong>.</p>
        <p><small style='color:#856404;background:#fff8e1;padding:4px 8px;border-radius:4px;display:inline-block;'>Error: $errDetail</small></p>
        <p><strong>To fix this on your computer:</strong></p>
        <ol>
            <li>Open <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to <strong>MySQL</strong>.</li>
            <li>Or double-click <strong>start.bat</strong> in the project folder to start everything automatically.</li>
        </ol>
        <p style='margin-bottom:0;'><a href='' onclick='location.reload();return false;' style='display:inline-block;padding:8px 16px;background:#0d6efd;color:white;text-decoration:none;border-radius:5px;'>Retry Connection</a></p>
    </div>");
}

// Global System Currency Configuration & Formatting Helpers
require_once __DIR__ . '/currency.php';
?>
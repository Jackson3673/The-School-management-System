<?php
// tests/db_test.php
require_once '../config/db.php';

try {
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "<h1>Database Connection Successful!</h1>";
    echo "<p>Connected to database: <strong>" . htmlspecialchars($dbName) . "</strong></p>";

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables Found:</h3><ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h1>Connection Failed</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

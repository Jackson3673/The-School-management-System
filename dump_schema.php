<?php
require_once 'config/db.php';

try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            foreach ($col as $val) {
                echo "<td>" . ($val ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<?php
require_once 'config/db.php';
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:<br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }

    if (in_array('fees', $tables)) {
        echo "<br><b>SUCCESS:</b> 'fees' table is present.";
    } else {
        echo "<br><b>FAILURE:</b> 'fees' table is still missing.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
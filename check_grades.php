<?php
require_once 'config/db.php';
try {
    echo "<h3>Academic Records:</h3>";
    $stmt = $pdo->query("SELECT * FROM academic_records");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($records) {
        echo "<pre>" . print_r($records, true) . "</pre>";
    } else {
        echo "No academic records found.<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
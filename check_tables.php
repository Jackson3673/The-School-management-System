<?php
require_once 'config/db.php';

function checkTable($pdo, $table)
{
    echo "<h3>Table: $table</h3>";
    try {
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

checkTable($pdo, 'class_students');
checkTable($pdo, 'academic_records');
checkTable($pdo, 'students');
?>
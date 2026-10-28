<?php
require_once 'config/db.php';

try {
    // 1. Add course_code to classes
    $pdo->exec("ALTER TABLE classes ADD COLUMN course_code VARCHAR(20) DEFAULT 'N/A' AFTER id");
    echo "Added course_code to classes.<br>";

    // Update existing classes with a dummy code if needed
    $pdo->exec("UPDATE classes SET course_code = CONCAT('CRS-', id) WHERE course_code = 'N/A'");

    // 2. Add status to class_students
    $pdo->exec("ALTER TABLE class_students ADD COLUMN status ENUM('Enrolled', 'Completed', 'Dropped') DEFAULT 'Enrolled' AFTER assigned_at");
    echo "Added status to class_students.<br>";

} catch (PDOException $e) {
    echo "Error (columns might already exist): " . $e->getMessage() . "<br>";
}

echo "Schema update v3 complete.";
?>
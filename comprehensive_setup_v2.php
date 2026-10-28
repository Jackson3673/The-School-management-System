<?php
// comprehensive_setup_v2.php
require_once 'config/db.php';

try {
    echo "Starting Database Schema Harmonization (v2)...<br><br>";

    // Helper function to safely add columns
    function addColumn($pdo, $table, $column, $definition)
    {
        try {
            // Check if column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                echo "✓ Added column '$column' to '$table'.<br>";
            } else {
                echo "i Column '$column' already exists in '$table'.<br>";
            }
        } catch (PDOException $e) {
            echo "✗ Error adding column '$column' to '$table': " . $e->getMessage() . "<br>";
        }
    }

    // 1. Harmonize class_students
    // Application expects 'status' column
    addColumn($pdo, 'class_students', 'status', "ENUM('Enrolled', 'Completed', 'Dropped') DEFAULT 'Enrolled' AFTER student_id");

    // Also handle possible mismatch between 'enrolled_at' and 'assigned_at' or 'assigned_at' if both used
    // Current schema check showed PRI(class_id, student_id) and enrolled_at
    // But view.php doesn't seem to use the date columns directly yet

    // 2. Harmonize academic_records
    // Application expects cats_marks and main_exam_marks
    addColumn($pdo, 'academic_records', 'cats_marks', "INT DEFAULT 0 AFTER student_id");
    addColumn($pdo, 'academic_records', 'main_exam_marks', "INT DEFAULT 0 AFTER cats_marks");

    // 3. Ensure students table has all fields seen in update scripts
    addColumn($pdo, 'students', 'phone_number', "VARCHAR(20) DEFAULT NULL");
    addColumn($pdo, 'students', 'address', "TEXT DEFAULT NULL");
    addColumn($pdo, 'students', 'next_of_kin_name', "VARCHAR(100) DEFAULT NULL");
    addColumn($pdo, 'students', 'next_of_kin_phone', "VARCHAR(50) DEFAULT NULL");
    addColumn($pdo, 'students', 'next_of_kin_relation', "VARCHAR(50) DEFAULT NULL");

    echo "<br><b>Harmonization Successful!</b> Missing columns have been added.";

} catch (PDOException $e) {
    die("<br><b>Harmonization Error:</b> " . $e->getMessage());
}
?>
<?php
require_once 'config/db.php';

try {
    echo "<h3>Current Classes:</h3>";
    $stmt = $pdo->query("SELECT * FROM classes");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($classes) {
        echo "<pre>" . print_r($classes, true) . "</pre>";
    } else {
        echo "No classes found.<br>";
    }

    echo "<h3>Current Enrollments:</h3>";
    $stmt = $pdo->query("SELECT * FROM class_students");
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($enrollments) {
        echo "<pre>" . print_r($enrollments, true) . "</pre>";
    } else {
        echo "No enrollments found.<br>";
    }

    // Get the first student ID to enroll for demo
    $stmt = $pdo->query("SELECT id, fullname FROM students LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        echo "<h3>Target Student:</h3>";
        echo "ID: " . $student['id'] . " - " . $student['fullname'] . "<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
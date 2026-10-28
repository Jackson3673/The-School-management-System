<?php
require_once 'config/db.php';

try {
    // 1. Get a lecturer (Use admin if no lecturer exists)
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('lecturer', 'admin') LIMIT 1");
    $lecturer_id = $stmt->fetchColumn();

    if (!$lecturer_id) {
        // Create a dummy lecturer if needed, but for now assuming admin exists
        die("No lecturer/admin found to assign classes to.");
    }

    // 2. Insert Classes
    $classes = [
        ['Introduction to Programming', 'BIT-101'],
        ['Database Systems', 'BIT-102'],
        ['Networking Fundamentals', 'BIT-103'],
        ['Web Development I', 'BIT-104']
    ];

    foreach ($classes as $class) {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, course_code, lecturer_id) VALUES (?, ?, ?)");
        // Only insert if not exists
        $check = $pdo->prepare("SELECT id FROM classes WHERE course_code = ?");
        $check->execute([$class[1]]);
        if (!$check->fetch()) {
            $stmt->execute([$class[0], $class[1], $lecturer_id]);
            echo "Created class: " . $class[0] . "<br>";
        }
    }

    // 3. Enroll Student (ID 2 from previous check)
    $student_id = 2; // Hardcoded based on previous check

    // Get all class IDs
    $stmt = $pdo->query("SELECT id FROM classes");
    $class_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($class_ids as $cid) {
        // Enroll
        $stmt = $pdo->prepare("INSERT IGNORE INTO class_students (class_id, student_id, status) VALUES (?, ?, 'Enrolled')");
        $stmt->execute([$cid, $student_id]);
        echo "Enrolled student $student_id into class $cid<br>";

        // Add random grade for fun?
        // ... maybe later
    }

    echo "Sample data added successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<?php
require_once 'config/db.php';

try {
    // 1. Get a student
    $stmt = $pdo->query("SELECT id, fullname FROM students LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("No students found.");
    }
    $student_id = $student['id'];
    echo "Adding grades for: " . $student['fullname'] . " (ID: $student_id)<br>";

    // 2. Get Enrolled Classes
    $stmt = $pdo->prepare("SELECT class_id FROM class_students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$classes) {
        die("Student not enrolled in any classes.");
    }

    // 3. Define Sample Grades
    $grade_data = [
        ['cats' => 25, 'exam' => 60, 'grade' => 'A'], // Total 85
        ['cats' => 20, 'exam' => 45, 'grade' => 'B'], // Total 65
        ['cats' => 15, 'exam' => 30, 'grade' => 'C'], // Total 45
        ['cats' => 10, 'exam' => 25, 'grade' => 'D'], // Total 35 (Supplementary)
    ];

    $i = 0;
    foreach ($classes as $class_id) {
        if (!isset($grade_data[$i]))
            break; // Stop if we run out of sample data

        $g = $grade_data[$i];
        $total = $g['cats'] + $g['exam'];

        // Upsert
        $sql = "INSERT INTO academic_records (class_id, student_id, cats_marks, main_exam_marks, marks, grade) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE cats_marks=?, main_exam_marks=?, marks=?, grade=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $class_id,
            $student_id,
            $g['cats'],
            $g['exam'],
            $total,
            $g['grade'],
            $g['cats'],
            $g['exam'],
            $total,
            $g['grade']
        ]);

        echo "Added Grade for Class ID $class_id: CATS={$g['cats']}, Exam={$g['exam']}, Total=$total<br>";
        $i++;
    }

    echo "Sample grades added successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
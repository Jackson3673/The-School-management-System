<?php
require_once 'config/db.php';

try {
    // Exam Schedule Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS exam_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL, 
        exam_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        room_number VARCHAR(50) NOT NULL,
        invigilator_name VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_booking (room_number, exam_date, start_time) 
    )");
    // Note: The unique key above is a simple constraint. A real clash check needs range overlap logic in PHP.
    echo "Table 'exam_schedule' checked/created.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
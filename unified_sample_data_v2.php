<?php
// unified_sample_data_v2.php
require_once 'config/db.php';

try {
    echo "Starting Sample Data Population (v2)...<br><br>";

    // 1. Get Lecturers
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'lecturer' LIMIT 2");
    $lecturer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($lecturer_ids) < 2) {
        // Create if not enough
        $pass = password_hash('pass123', PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES ('Sample Lecturer 1', 'l1@test.com', 'lecturer1', ?, 'lecturer')")->execute([$pass]);
        $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES ('Sample Lecturer 2', 'l2@test.com', 'lecturer2', ?, 'lecturer')")->execute([$pass]);
        $lecturer_ids = $pdo->query("SELECT id FROM users WHERE role = 'lecturer' ORDER BY id DESC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    }

    // 2. Get Classes
    $stmt = $pdo->query("SELECT id FROM classes LIMIT 4");
    $class_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($class_ids) == 0) {
        $pdo->prepare("INSERT INTO classes (class_name, course_code, lecturer_id) VALUES ('Introduction to Computing', 'COM101', ?)")->execute([$lecturer_ids[0]]);
        $pdo->prepare("INSERT INTO classes (class_name, course_code, lecturer_id) VALUES ('Database Systems', 'COM201', ?)")->execute([$lecturer_ids[1]]);
        $class_ids = $pdo->query("SELECT id FROM classes LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    }

    // 3. Create 10 Students (if they don't exist)
    $student_data = [
        ['Alice', 'Wonderland', 'alice@naini.com', 'STU001'],
        ['Bob', 'Builder', 'bob@naini.com', 'STU002'],
        ['Charlie', 'Chocolate', 'charlie@naini.com', 'STU003'],
        ['Diana', 'Prince', 'diana@naini.com', 'STU004'],
        ['Edward', 'Scissorhands', 'edward@naini.com', 'STU005'],
        ['Fiona', 'Shrek', 'fiona@naini.com', 'STU006'],
        ['George', 'Jungle', 'george@naini.com', 'STU007'],
        ['Hannah', 'Montana', 'hannah@naini.com', 'STU008'],
        ['Ian', 'Solo', 'ian@naini.com', 'STU009'],
        ['Jack', 'Sparrow', 'jack@naini.com', 'STU010']
    ];

    $student_ids = [];
    foreach ($student_data as $s) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE registration_number = ?");
        $stmt->execute([$s[3]]);
        $sid = $stmt->fetchColumn();

        if (!$sid) {
            $pdo->prepare("INSERT INTO students (first_name, last_name, fullname, email, registration_number, program) VALUES (?, ?, ?, ?, ?, 'Software Engineering')")
                ->execute([$s[0], $s[1], $s[0] . ' ' . $s[1], $s[2], $s[3]]);
            $sid = $pdo->lastInsertId();
        }
        $student_ids[] = $sid;
    }

    // 4. Populate Enrollments, Grades, and Fees
    foreach ($student_ids as $sid) {
        // Randomly enroll in 1-3 classes
        shuffle($class_ids);
        $enrolled_count = rand(1, min(3, count($class_ids)));
        for ($i = 0; $i < $enrolled_count; $i++) {
            $cid = $class_ids[$i];

            // Enroll with status
            $status = (rand(0, 10) > 8) ? 'Completed' : 'Enrolled';
            $stmt = $pdo->prepare("INSERT INTO class_students (class_id, student_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?");
            $stmt->execute([$cid, $sid, $status, $status]);

            // Add grades with marks
            $cats = rand(15, 30);
            $exam = rand(25, 70);
            $total = $cats + $exam;
            $grade = ($total >= 80) ? 'A' : (($total >= 70) ? 'B' : (($total >= 60) ? 'C' : (($total >= 40) ? 'D' : 'F')));

            $stmt = $pdo->prepare("INSERT INTO academic_records (class_id, student_id, cats_marks, main_exam_marks, marks, grade) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE cats_marks = ?, main_exam_marks = ?, marks = ?, grade = ?");
            $stmt->execute([$cid, $sid, $cats, $exam, $total, $grade, $cats, $exam, $total, $grade]);
        }

        // Add 1 invoice and 1 payment for fees
        $invoice_amt = rand(1000, 5000);
        $payment_amt = rand(500, $invoice_amt);

        $pdo->prepare("INSERT INTO fees (student_id, type, amount, description, transaction_date) VALUES (?, 'invoice', ?, 'Tuition Fee - Year 1', DATE_SUB(CURDATE(), INTERVAL 10 DAY))")
            ->execute([$sid, $invoice_amt]);
        $pdo->prepare("INSERT INTO fees (student_id, type, amount, description, transaction_date) VALUES (?, 'payment', ?, 'Partial Payment', DATE_SUB(CURDATE(), INTERVAL 2 DAY))")
            ->execute([$sid, $payment_amt]);
    }

    echo "✓ Population Successful! Sample data with marks and status created for 10 students.<br>";

} catch (PDOException $e) {
    die("<br><b>Population Error:</b> " . $e->getMessage());
}
?>
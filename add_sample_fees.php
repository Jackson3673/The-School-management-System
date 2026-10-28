<?php
require_once 'config/db.php';

try {
    // 1. Get a student
    $stmt = $pdo->query("SELECT id, fullname FROM students LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("No students found to assign fees to.");
    }
    $student_id = $student['id'];
    echo "Adding fees for: " . $student['fullname'] . " (ID: $student_id)<br>";

    // 2. Define Sample Transactions
    $transactions = [
        ['invoice', 50000.00, 'Tuition Fee - Year 1 Term 1', date('Y-m-d', strtotime('-30 days'))],
        ['invoice', 5000.00, 'Medical Levy', date('Y-m-d', strtotime('-30 days'))],
        ['invoice', 2500.00, 'Library Caution Money', date('Y-m-d', strtotime('-29 days'))],
        ['invoice', 1500.00, 'Student ID Card', date('Y-m-d', strtotime('-29 days'))],

        ['payment', 20000.00, 'Bank Deposit - Ref: REF123456', date('Y-m-d', strtotime('-25 days'))],
        ['payment', 15000.00, 'M-Pesa Payment - QWE123ASD', date('Y-m-d', strtotime('-10 days'))],

        ['invoice', 3000.00, 'Computer Lab Fee', date('Y-m-d', strtotime('-5 days'))],
    ];

    // 3. Insert Data
    $stmt = $pdo->prepare("INSERT INTO fees (student_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");

    foreach ($transactions as $t) {
        $stmt->execute([$student_id, $t[0], $t[1], $t[2], $t[3]]);
        $typeLabel = strtoupper($t[0]);
        echo "Added $typeLabel: {$t[2]} - Amount: {$t[1]}<br>";
    }

    echo "Sample fees data added successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
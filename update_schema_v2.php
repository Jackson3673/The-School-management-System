<?php
require_once 'config/db.php';

try {
    // Split Name
    $pdo->exec("ALTER TABLE students ADD COLUMN first_name VARCHAR(50) NOT NULL AFTER id");
    $pdo->exec("ALTER TABLE students ADD COLUMN last_name VARCHAR(50) NOT NULL AFTER first_name");
    echo "Added first_name and last_name columns.<br>";

    // Academic Placement
    $pdo->exec("ALTER TABLE students ADD COLUMN program VARCHAR(100) DEFAULT NULL AFTER registration_number");
    $pdo->exec("ALTER TABLE students ADD COLUMN year_of_study VARCHAR(20) DEFAULT 'Year 1' AFTER program");
    $pdo->exec("ALTER TABLE students ADD COLUMN current_semester VARCHAR(20) DEFAULT 'Semester 1' AFTER year_of_study");
    $pdo->exec("ALTER TABLE students ADD COLUMN admission_date DATE DEFAULT NULL AFTER current_semester"); // Will set default in PHP or DB
    echo "Added academic placement columns.<br>";

    // Contact
    $pdo->exec("ALTER TABLE students ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL AFTER gender");
    echo "Added phone_number column.<br>";

    // Optional: Migrate existing fullname to first/last name (simple split)
    $stmt = $pdo->query("SELECT id, fullname FROM students");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $parts = explode(' ', $row['fullname'], 2);
        $first = $parts[0];
        $last = $parts[1] ?? '';
        $update = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?");
        $update->execute([$first, $last, $row['id']]);
    }
    echo "Migrated existing names.<br>";

    // Set Admission Date for existing records to created_at date
    $pdo->exec("UPDATE students SET admission_date = DATE(created_at) WHERE admission_date IS NULL");
    echo "Updated null admission dates.<br>";

} catch (PDOException $e) {
    echo "Error (columns might already exist): " . $e->getMessage() . "<br>";
}

echo "Schema update v2 complete.";
?>
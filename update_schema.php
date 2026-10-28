<?php
require_once 'config/db.php';

try {
    // Add DOB
    $pdo->exec("ALTER TABLE students ADD COLUMN dob DATE DEFAULT NULL");
    echo "Added dob column.<br>";
} catch (PDOException $e) {
    echo "dob exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Add Gender
    $pdo->exec("ALTER TABLE students ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT NULL");
    echo "Added gender column.<br>";
} catch (PDOException $e) {
    echo "gender exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Add Address
    $pdo->exec("ALTER TABLE students ADD COLUMN address TEXT DEFAULT NULL");
    echo "Added address column.<br>";
} catch (PDOException $e) {
    echo "address exists or error: " . $e->getMessage() . "<br>";
}

try {
    // Add Next of Kin fields
    $pdo->exec("ALTER TABLE students ADD COLUMN next_of_kin_name VARCHAR(100) DEFAULT NULL");
    echo "Added next_of_kin_name column.<br>";
} catch (PDOException $e) {
    echo "next_of_kin_name exists or error: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE students ADD COLUMN next_of_kin_phone VARCHAR(50) DEFAULT NULL");
    echo "Added next_of_kin_phone column.<br>";
} catch (PDOException $e) {
    echo "next_of_kin_phone exists or error: " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE students ADD COLUMN next_of_kin_relation VARCHAR(50) DEFAULT NULL");
    echo "Added next_of_kin_relation column.<br>";
} catch (PDOException $e) {
    echo "next_of_kin_relation exists or error: " . $e->getMessage() . "<br>";
}

echo "Database schema update complete.";
?>
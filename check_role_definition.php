<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE users");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fields as $field) {
        if ($field['Field'] === 'role') {
            echo "Role definition: " . $field['Type'] . "<br>";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
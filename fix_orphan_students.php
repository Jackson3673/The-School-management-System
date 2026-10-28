<?php
/**
 * fix_orphan_students.php
 * 
 * Admin utility to fix the "No student profile linked to this account" error.
 * 
 * Finds users with role='student' that have no matching row in the students table,
 * and creates a stub student profile for each one so they can log in successfully.
 * 
 * Run once via browser: http://localhost/The-School-management-System/fix_orphan_students.php
 */

require_once 'config/db.php';

echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
echo "<title>Fix Orphan Students</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'><div class='container mt-4' style='max-width:700px'>";
echo "<h3 class='mb-4'><i class='bi bi-tools'></i> Fix Orphan Student Accounts</h3>";

try {
    // Find all student users without a linked students row
    $stmt = $pdo->query("
        SELECT u.id, u.fullname, u.email, u.username
        FROM users u
        LEFT JOIN students s ON s.email = u.email
        WHERE u.role = 'student' AND s.id IS NULL
    ");
    $orphans = $stmt->fetchAll();

    if (empty($orphans)) {
        echo "<div class='alert alert-success'><strong>✓ All good!</strong> Every student user account has a linked student profile. No fixes needed.</div>";
    } else {
        echo "<div class='alert alert-warning'><strong>Found " . count($orphans) . " orphan student account(s).</strong> Creating stub profiles...</div>";
        echo "<ul class='list-group mb-3'>";

        $fixed = 0;
        foreach ($orphans as $u) {
            $name_parts = explode(' ', trim($u['fullname']), 2);
            $first = $name_parts[0] ?: 'Student';
            $last  = $name_parts[1] ?? '';

            // Use username as registration number placeholder (may be an email)
            $reg_no = $u['username'];

            // Make sure reg_no is unique in students
            $chk = $pdo->prepare("SELECT id FROM students WHERE registration_number = ?");
            $chk->execute([$reg_no]);
            if ($chk->rowCount() > 0) {
                $reg_no = 'TEMP-' . $u['id'];
            }

            $ins = $pdo->prepare(
                "INSERT INTO students (first_name, last_name, fullname, email, registration_number, program, year_of_study, current_semester, admission_date)
                 VALUES (?, ?, ?, ?, ?, 'General Studies', 'Year 1', 'Semester 1', CURDATE())
                 ON DUPLICATE KEY UPDATE id=id"
            );
            if ($ins->execute([$first, $last, $u['fullname'], $u['email'], $reg_no])) {
                $fixed++;
                echo "<li class='list-group-item list-group-item-success'>";
                echo "<strong>{$u['fullname']}</strong> ({$u['email']}) &mdash; stub profile created (Reg No: <code>$reg_no</code>).";
                echo "</li>";
            } else {
                echo "<li class='list-group-item list-group-item-danger'>";
                echo "Failed to create profile for <strong>{$u['fullname']}</strong> ({$u['email']}).";
                echo "</li>";
            }
        }
        echo "</ul>";
        echo "<div class='alert alert-success'>✓ Fixed <strong>$fixed</strong> of " . count($orphans) . " account(s). <br>";
        echo "Note: Admin should update the student profiles (program, reg number, etc.) from the Students module.</div>";
    }

    // Also report students without user accounts
    $stmt2 = $pdo->query("
        SELECT s.id, s.fullname, s.email, s.registration_number
        FROM students s
        LEFT JOIN users u ON u.email = s.email
        WHERE u.id IS NULL
    ");
    $noUser = $stmt2->fetchAll();

    if (!empty($noUser)) {
        echo "<hr><h5>Students Without Login Accounts (" . count($noUser) . ")</h5>";
        echo "<p class='text-muted small'>These students have profiles but can't log in. Run <a href='seed_student_users.php'>seed_student_users.php</a> to create their accounts.</p>";
        echo "<ul class='list-group'>";
        foreach ($noUser as $s) {
            echo "<li class='list-group-item'><strong>{$s['fullname']}</strong> ({$s['email']}) &mdash; Reg: {$s['registration_number']}</li>";
        }
        echo "</ul>";
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<div class='mt-3'><a href='index.php' class='btn btn-outline-secondary btn-sm'>← Back to Home</a></div>";
echo "</div></body></html>";

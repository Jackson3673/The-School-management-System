<?php
// comprehensive_setup.php
require_once 'config/db.php';

try {
    echo "Starting Comprehensive Database Synchronization...<br><br>";

    // 1. Initial Tables (from setup_database.php)

    // Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Table 'users' checked/created.<br>";

    // Classes
    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(100) NOT NULL,
        lecturer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "✓ Table 'classes' checked/created.<br>";

    // Students
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        registration_number VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Table 'students' checked/created.<br>";

    // Class Students
    $pdo->exec("CREATE TABLE IF NOT EXISTS class_students (
        draw_id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        student_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (class_id, student_id)
    )");
    echo "✓ Table 'class_students' checked/created.<br>";

    // Attendance
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        student_id INT NOT NULL,
        attendance_date DATE NOT NULL,
        status ENUM('present', 'absent', 'late') NOT NULL,
        recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_daily_attendance (class_id, student_id, attendance_date)
    )");
    echo "✓ Table 'attendance' checked/created.<br>";

    // Academic Records
    $pdo->exec("CREATE TABLE IF NOT EXISTS academic_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        student_id INT NOT NULL,
        marks INT DEFAULT 0,
        grade VARCHAR(5),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_record (class_id, student_id)
    )");
    echo "✓ Table 'academic_records' checked/created.<br>";

    // 2. Schema Updates (v1 - v5)

    // Helper function to safely add columns
    function addColumn($pdo, $table, $column, $definition)
    {
        try {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            echo "✓ Added column '$column' to '$table'.<br>";
        } catch (PDOException $e) {
            // Likely column already exists
        }
    }

    // Students Updates
    addColumn($pdo, 'students', 'dob', 'DATE DEFAULT NULL');
    addColumn($pdo, 'students', 'gender', "ENUM('Male', 'Female', 'Other') DEFAULT NULL");
    addColumn($pdo, 'students', 'address', 'TEXT DEFAULT NULL');
    addColumn($pdo, 'students', 'next_of_kin_name', 'VARCHAR(100) DEFAULT NULL');
    addColumn($pdo, 'students', 'next_of_kin_phone', 'VARCHAR(50) DEFAULT NULL');
    addColumn($pdo, 'students', 'next_of_kin_relation', 'VARCHAR(50) DEFAULT NULL');
    addColumn($pdo, 'students', 'first_name', 'VARCHAR(50) NOT NULL AFTER id');
    addColumn($pdo, 'students', 'last_name', 'VARCHAR(50) NOT NULL AFTER first_name');
    addColumn($pdo, 'students', 'program', 'VARCHAR(100) DEFAULT NULL AFTER registration_number');
    addColumn($pdo, 'students', 'year_of_study', "VARCHAR(20) DEFAULT 'Year 1' AFTER program");
    addColumn($pdo, 'students', 'current_semester', "VARCHAR(20) DEFAULT 'Semester 1' AFTER year_of_study");
    addColumn($pdo, 'students', 'admission_date', 'DATE DEFAULT NULL AFTER current_semester');
    addColumn($pdo, 'students', 'phone_number', 'VARCHAR(20) DEFAULT NULL AFTER gender');

    // Classes Updates
    addColumn($pdo, 'classes', 'course_code', "VARCHAR(20) DEFAULT 'N/A' AFTER id");

    // Class Students Updates
    addColumn($pdo, 'class_students', 'status', "ENUM('Enrolled', 'Completed', 'Dropped') DEFAULT 'Enrolled' AFTER assigned_at");

    // 3. Additional Tables

    // Fees
    $pdo->exec("CREATE TABLE IF NOT EXISTS fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        type ENUM('invoice', 'payment') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        description VARCHAR(255) NOT NULL,
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");
    echo "✓ Table 'fees' checked/created.<br>";

    // Exam Schedule
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
    echo "✓ Table 'exam_schedule' checked/created.<br>";

    // 4. Data Migration / Cleanup

    // Migrate fullname to first/last name if empty
    $stmt = $pdo->query("SELECT id, fullname FROM students WHERE first_name = ''");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $parts = explode(' ', $row['fullname'], 2);
        $first = $parts[0] ?: 'Student';
        $last = $parts[1] ?? '';
        $update = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ? WHERE id = ?");
        $update->execute([$first, $last, $row['id']]);
    }

    // Set Admission Date for existing records to created_at date
    $pdo->exec("UPDATE students SET admission_date = DATE(created_at) WHERE admission_date IS NULL");

    // Update existing classes with a dummy code if needed
    $pdo->exec("UPDATE classes SET course_code = CONCAT('CRS-', id) WHERE course_code = 'N/A'");

    // 5. Ensure Admin User
    $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['System Admin', 'admin@hopeacademy.com', 'admin', $admin_pass, 'admin']);
        echo "✓ Default admin created (User: admin, Pass: admin123).<br>";
    }

    echo "<br><b>Synchronization Successful!</b> The database is now up to date.";

} catch (PDOException $e) {
    die("<br><b>Synchronization Error:</b> " . $e->getMessage());
}
?>
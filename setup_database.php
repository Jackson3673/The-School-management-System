<?php
// setup_database.php - Universal Database Provisioner (PDO & MySQLi Fallback)

$host = getenv('DB_HOST') ?: '127.0.0.1';
$db   = getenv('DB_NAME') ?: 'naini';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$port = (int)(getenv('DB_PORT') ?: 3306);
$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? PHP_EOL : "<br>\n";

$candidateHosts = array_values(array_unique([$host, '127.0.0.1', 'localhost']));

// -------------------------------------------------------------
// Helper: Provision using PDO
// -------------------------------------------------------------
function provisionWithPDO($candidateHosts, $port, $user, $pass, $db, $nl) {
    $pdo = null;
    $lastError = '';

    foreach ($candidateHosts as $h) {
        try {
            $pdo = new PDO("mysql:host=$h;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 4
            ]);
            break;
        } catch (PDOException $ex) {
            $lastError = $ex->getMessage();
        }
    }

    if (!$pdo) {
        throw new Exception("PDO connection failed: $lastError");
    }

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database '$db' checked/created." . $nl;

    $pdo->exec("USE `$db`");

    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "classes" => "CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_name VARCHAR(100) NOT NULL,
            lecturer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            registration_number VARCHAR(50) NOT NULL UNIQUE,
            dob DATE DEFAULT NULL,
            gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
            address TEXT DEFAULT NULL,
            next_of_kin_name VARCHAR(100) DEFAULT NULL,
            next_of_kin_phone VARCHAR(50) DEFAULT NULL,
            next_of_kin_relation VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "class_students" => "CREATE TABLE IF NOT EXISTS class_students (
            draw_id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (class_id, student_id)
        )",
        "attendance" => "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present', 'absent', 'late') NOT NULL,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_daily_attendance (class_id, student_id, attendance_date)
        )",
        "academic_records" => "CREATE TABLE IF NOT EXISTS academic_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            marks INT DEFAULT 0,
            grade VARCHAR(5),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_record (class_id, student_id)
        )",
        "fees" => "CREATE TABLE IF NOT EXISTS fees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            type ENUM('invoice', 'payment') NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            description VARCHAR(255) NOT NULL,
            transaction_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )",
        "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $tName => $sql) {
        $pdo->exec($sql);
        echo "Table '$tName' checked/created." . $nl;
    }

    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
        ('currency_symbol', 'KSh'),
        ('currency_code', 'KES'),
        ('currency_position', 'before')
    ");

    // Seeds
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['System Admin', 'admin@hopeacademy.com', 'admin', $admin_pass, 'admin']);
        echo "Default admin created (User: admin, Pass: admin123)." . $nl;
    }

    $lectCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer'")->fetchColumn();
    if ($lectCount == 0) {
        $lect_pass = password_hash('test123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test Lecturer', 'test@hopeacademy.com', 'Test', $lect_pass, 'lecturer']);
        echo "Default lecturer created (User: Test, Pass: test123)." . $nl;
    }

    $studCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    if ($studCount == 0) {
        $student_pass = password_hash('student123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Jane Doe', 'student@hopeacademy.com', 'BIT/2026/0001', $student_pass, 'student']);

        $stmtStudent = $pdo->prepare("INSERT INTO students (fullname, email, registration_number) VALUES (?, ?, ?)");
        $stmtStudent->execute(['Jane Doe', 'student@hopeacademy.com', 'BIT/2026/0001']);
        echo "Default student created (User: BIT/2026/0001, Pass: student123)." . $nl;
    }
}

// -------------------------------------------------------------
// Helper: Provision using MySQLi
// -------------------------------------------------------------
function provisionWithMySQLi($candidateHosts, $port, $user, $pass, $db, $nl) {
    $mysqli = null;
    $lastError = '';

    foreach ($candidateHosts as $h) {
        $mysqli = @new mysqli($h, $user, $pass, "", $port);
        if (!$mysqli->connect_errno) {
            break;
        }
        $lastError = $mysqli->connect_error;
        $mysqli = null;
    }

    if (!$mysqli) {
        throw new Exception("MySQLi connection failed: $lastError");
    }

    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database '$db' checked/created (via mysqli)." . $nl;

    $mysqli->select_db($db);

    $tables = [
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "classes" => "CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_name VARCHAR(100) NOT NULL,
            lecturer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            registration_number VARCHAR(50) NOT NULL UNIQUE,
            dob DATE DEFAULT NULL,
            gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
            address TEXT DEFAULT NULL,
            next_of_kin_name VARCHAR(100) DEFAULT NULL,
            next_of_kin_phone VARCHAR(50) DEFAULT NULL,
            next_of_kin_relation VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "class_students" => "CREATE TABLE IF NOT EXISTS class_students (
            draw_id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment (class_id, student_id)
        )",
        "attendance" => "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present', 'absent', 'late') NOT NULL,
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_daily_attendance (class_id, student_id, attendance_date)
        )",
        "academic_records" => "CREATE TABLE IF NOT EXISTS academic_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            class_id INT NOT NULL,
            student_id INT NOT NULL,
            marks INT DEFAULT 0,
            grade VARCHAR(5),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            UNIQUE KEY unique_record (class_id, student_id)
        )",
        "fees" => "CREATE TABLE IF NOT EXISTS fees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            type ENUM('invoice', 'payment') NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            description VARCHAR(255) NOT NULL,
            transaction_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )",
        "system_settings" => "CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(50) NOT NULL PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];

    foreach ($tables as $tName => $sql) {
        $mysqli->query($sql);
        echo "Table '$tName' checked/created (via mysqli)." . $nl;
    }

    $mysqli->query("INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
        ('currency_symbol', 'KSh'),
        ('currency_code', 'KES'),
        ('currency_position', 'before')
    ");

    // Seeds
    $res = $mysqli->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ($res && $res->fetch_row()[0] == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $role = 'admin';
        $fullname = 'System Admin';
        $email = 'admin@hopeacademy.com';
        $user = 'admin';
        $stmt->bind_param("sssss", $fullname, $email, $user, $admin_pass, $role);
        $stmt->execute();
        echo "Default admin created (User: admin, Pass: admin123)." . $nl;
    }

    $res = $mysqli->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer'");
    if ($res && $res->fetch_row()[0] == 0) {
        $lect_pass = password_hash('test123', PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $role = 'lecturer';
        $fullname = 'Test Lecturer';
        $email = 'test@hopeacademy.com';
        $user = 'Test';
        $stmt->bind_param("sssss", $fullname, $email, $user, $lect_pass, $role);
        $stmt->execute();
        echo "Default lecturer created (User: Test, Pass: test123)." . $nl;
    }

    $res = $mysqli->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    if ($res && $res->fetch_row()[0] == 0) {
        $student_pass = password_hash('student123', PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
        $role = 'student';
        $fullname = 'Jane Doe';
        $email = 'student@hopeacademy.com';
        $reg = 'BIT/2026/0001';
        $stmt->bind_param("sssss", $fullname, $email, $reg, $student_pass, $role);
        $stmt->execute();

        $stmtStudent = $mysqli->prepare("INSERT INTO students (fullname, email, registration_number) VALUES (?, ?, ?)");
        $stmtStudent->bind_param("sss", $fullname, $email, $reg);
        $stmtStudent->execute();
        echo "Default student created (User: BIT/2026/0001, Pass: student123)." . $nl;
    }

    $mysqli->close();
}

// -------------------------------------------------------------
// Main Execution
// -------------------------------------------------------------
try {
    if (extension_loaded('pdo_mysql')) {
        provisionWithPDO($candidateHosts, $port, $user, $pass, $db, $nl);
    } elseif (extension_loaded('mysqli')) {
        echo "[INFO] pdo_mysql not loaded; using mysqli fallback..." . $nl;
        provisionWithMySQLi($candidateHosts, $port, $user, $pass, $db, $nl);
    } else {
        throw new Exception("Neither 'pdo_mysql' nor 'mysqli' extension is enabled in PHP. Please enable extension=pdo_mysql in your php.ini.");
    }
} catch (Exception $e) {
    echo "[ERROR] Database Setup: " . $e->getMessage() . $nl;
    exit(1);
}
?>

<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../modules/auth/login.php");
    exit();
}
require_once '../../config/db.php';

// Fetch classes
$stmt = $pdo->query("SELECT * FROM classes ORDER BY class_name");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$error = "";

// Handle Scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_number = trim($_POST['room_number']);
    $invigilator = trim($_POST['invigilator']);

    // CLASH DETECTION LOGIC
    // Check if Room is booked during this time range on this date
    $stmt = $pdo->prepare("
        SELECT c.class_name, es.start_time, es.end_time 
        FROM exam_schedule es
        JOIN classes c ON es.class_id = c.id
        WHERE es.room_number = ? 
        AND es.exam_date = ?
        AND (
            (es.start_time <= ? AND es.end_time > ?) OR  -- Starts during existing
            (es.start_time < ? AND es.end_time >= ?) OR  -- Ends during existing
            (es.start_time >= ? AND es.end_time <= ?)    -- Enclosed by existing
        )
    ");
    // Parameters: Room, Date, Start, Start, End, End, Start, End
    $stmt->execute([
        $room_number, $exam_date, 
        $start_time, $start_time, 
        $end_time, $end_time,
        $start_time, $end_time
    ]);

    if ($conflict = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $error = "<strong>Conflict Detected:</strong> Room $room_number is already booked for <strong>{$conflict['class_name']}</strong> from {$conflict['start_time']} to {$conflict['end_time']}.";
    } else {
        // No conflict, insert
        $stmt = $pdo->prepare("INSERT INTO exam_schedule (class_id, exam_date, start_time, end_time, room_number, invigilator_name) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$class_id, $exam_date, $start_time, $end_time, $room_number, $invigilator])) {
            $message = "Exam scheduled successfully.";
        } else {
            $error = "Failed to schedule exam.";
        }
    }
}

// Fetch Schedules
$stmt = $pdo->query("
    SELECT es.*, c.class_name 
    FROM exam_schedule es 
    JOIN classes c ON es.class_id = c.id 
    ORDER BY es.exam_date ASC, es.start_time ASC
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php 
        $page_title = "Exam Scheduling";
        $page_subtitle = "Manage Timetables & Rooms";
        require_once '../../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            
            <div class="row">
                <!-- Schedule Form -->
                <div class="col-md-4">
                    <div class="custom-card">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">Book Exam Slot</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-octagon me-2"></i> <?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i> <?= $message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Subject / Class Unit</label>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">-- Select --</option>
                                        <?php foreach($classes as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="exam_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="start_time" class="form-control" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="end_time" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" name="room_number" class="form-control" placeholder="e.g. Hall 1, Room 104" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Invigilator</label>
                                    <input type="text" name="invigilator" class="form-control" placeholder="Teacher Name">
                                </div>
                                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                                    <i class="bi bi-calendar-check me-2"></i> Schedule Exam
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Schedule List -->
                <div class="col-md-8">
                    <div class="custom-card">
                        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Upcoming Exams</h5>
                            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary d-none d-print-block">Print</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                        <th>Invigilator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($schedules as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= date('D, d M', strtotime($s['exam_date'])) ?></div>
                                            <div class="small text-muted"><?= date('Y', strtotime($s['exam_date'])) ?></div>
                                        </td>
                                        <td>
                                            <?= date('H:i', strtotime($s['start_time'])) ?> - 
                                            <?= date('H:i', strtotime($s['end_time'])) ?>
                                        </td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($s['class_name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($s['room_number']) ?></span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($s['invigilator_name']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($schedules)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No exams scheduled yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>

<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../../modules/auth/login.php");
    exit();
}
require_once '../../config/db.php';
require_once '../../includes/grade_engine.php';

$student_id = $_GET['student_id'] ?? null;
if (!$student_id)
    die("Student ID required.");

// Fetch Student Info
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student)
    die("Student not found.");

// Fetch Grades (Assuming current term for now, can filter by Year/Term later)
$stmt = $pdo->prepare("
    SELECT ar.*, c.class_name 
    FROM academic_records ar
    JOIN classes c ON ar.class_id = c.id
    WHERE ar.student_id = ?
");
$stmt->execute([$student_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// School Details (Hardcoded for now, moved to settings later)
$school_name = "HOPE ACADEMY";
$school_address = "P.O. Box 12345, Nairobi";
$school_phone = "0712 345 678";
$school_logo = "../../assets/img/logo.png"; // Placeholder
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transcript -
        <?= htmlspecialchars($student['fullname']) ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #fff;
            color: #000;
            font-family: 'Times New Roman', serif;
            /* Classic academic look */
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 10rem;
            opacity: 0.05;
            z-index: -1;
            pointer-events: none;
        }

        .header-line {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }

        .grade-table th,
        .grade-table td {
            border: 1px solid #000 !important;
            padding: 8px 12px;
        }

        .signature-box {
            margin-top: 50px;
            border-top: 1px solid #000;
            width: 200px;
            padding-top: 5px;
            text-align: center;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }

            .container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="watermark">OFFICIAL</div>

    <div class="container py-5">
        <!-- Non-printable controls -->
        <div class="d-flex justify-content-between mb-4 no-print">
            <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print / Save as PDF
            </button>
        </div>

        <!-- Official Header -->
        <div class="text-center mb-4">
            <!-- <img src="<?= $school_logo ?>" alt="Logo" style="height: 80px;"> -->
            <h1 class="fw-bold mb-0">
                <?= $school_name ?>
            </h1>
            <p class="mb-0 text-uppercase small letter-spacing-1">
                <?= $school_address ?>
            </p>
            <p class="mb-0 small">Tel:
                <?= $school_phone ?>
            </p>
            <div class="header-line mt-3"></div>
            <h3 class="text-uppercase fw-bold text-decoration-underline">Official Academic Transcript</h3>
        </div>

        <!-- Student Details -->
        <div class="row mb-4">
            <div class="col-6">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th width="150">Student Name:</th>
                        <td>
                            <?= htmlspecialchars($student['fullname']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Reg Number:</th>
                        <td>
                            <?= htmlspecialchars($student['registration_number']) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Program:</th>
                        <td>
                            <?= htmlspecialchars($student['program'] ?? 'N/A') ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-6 text-end">
                <table class="table table-borderless table-sm float-end" style="width: auto;">
                    <tr>
                        <th class="text-end">Date:</th>
                        <td>
                            <?= date('d M Y') ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-end">Year:</th>
                        <td>
                            <?= $student['year_of_study'] ?? 'N/A' ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-end">Term:</th>
                        <td>
                            <?= $student['current_semester'] ?? 'N/A' ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Grades Table -->
        <table class="table grade-table table-bordered mb-4">
            <thead class="bg-light text-uppercase">
                <tr>
                    <th>Subject / Course Unit</th>
                    <th class="text-center" width="100">Score</th>
                    <th class="text-center" width="100">Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r):
                    $gradeInfo = calculateGrade($r['marks']); // Use engine for comment
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($r['class_name']) ?>
                        </td>
                        <td class="text-center fw-bold">
                            <?= $r['marks'] ?>
                        </td>
                        <td class="text-center fw-bold">
                            <?= $r['grade'] ?>
                        </td>
                        <td>
                            <?= $gradeInfo['comment'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">No academic records found for this period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Summary & Footer -->
        <div class="row mt-5">
            <div class="col-8">
                <p class="small text-muted">
                    <strong>Grading System:</strong> A (80-100), A- (75-79), B+ (70-74), B (65-69), B- (60-64), C+
                    (55-59), C (50-54), C- (45-49), D+ (40-44), D (35-39), D- (30-34), E (0-29).
                </p>
                <p class="small text-muted fst-italic mt-3">
                    This transcript is issued without any erasures or alterations.
                </p>
            </div>
            <div class="col-4 d-flex flex-column align-items-end">
                <div class="signature-box">
                    Principal's Signature<br>
                    <div class="small text-muted">(Stamp)</div>
                </div>
                <div class="signature-box mt-4">
                    Registrar's Signature
                </div>
            </div>
        </div>
    </div>

</body>

</html>
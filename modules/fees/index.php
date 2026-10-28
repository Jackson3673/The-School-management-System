<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $type = $_POST['type'] ?? 'invoice';
    $amount = $_POST['amount'] ?? 0;
    $description = trim($_POST['description']);
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

    if ($student_id && $amount > 0 && !empty($description)) {
        $stmt = $pdo->prepare("INSERT INTO fees (student_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $type, $amount, $description, $transaction_date])) {
            $success = "Transaction recorded successfully.";
        } else {
            $error = "Failed to record transaction.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Fetch Students for Dropdown
$stmt = $pdo->query("SELECT id, fullname FROM students ORDER BY fullname");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Recent Transactions
$stmt = $pdo->query("
    SELECT f.*, s.fullname 
    FROM fees f 
    JOIN students s ON f.student_id = s.id 
    ORDER BY f.transaction_date DESC, f.id DESC 
    LIMIT 50
");
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../../includes/header.php'; ?>
<link rel="stylesheet" href="../../assets/css/modern.css">

<div class="d-flex">
    <?php require_once '../../includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php
        $page_title = "Fees Management";
        $page_subtitle = "Track payments and invoices";
        require_once '../../includes/topbar.php';
        ?>

        <div class="container-fluid p-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Fees</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex gap-2">
                    <!-- Potential place for filters/search -->
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addTransactionModal">
                    <i class="bi bi-plus-lg me-2"></i> New Transaction
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="custom-card">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Student</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= date('M d, Y', strtotime($t['transaction_date'])) ?>
                                        </td>
                                        <td class="fw-bold">
                                            <a href="../students/view.php?id=<?= $t['student_id'] ?>"
                                                class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($t['fullname']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($t['description']) ?></td>
                                        <td>
                                            <?php if ($t['type'] === 'payment'): ?>
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                                                    Payment
                                                </span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">
                                                    Invoice
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td
                                            class="text-end pe-4 fw-bold <?= $t['type'] === 'payment' ? 'text-success' : 'text-danger' ?>">
                                            <?= $t['type'] === 'payment' ? '+' : '-' ?>
                                            <?= number_format($t['amount'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Record New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student...</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="invoice">Invoice (Charge)</option>
                                <option value="payment">Payment (Receipt)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" step="0.01" class="form-control" placeholder="0.00"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Tuition Fee Term 1"
                            required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>"
                            required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
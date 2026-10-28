<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/db.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add_transaction';

    if ($action === 'update_currency') {
        $new_symbol = trim($_POST['currency_symbol'] ?? '');
        $new_code = trim($_POST['currency_code'] ?? '');
        $new_position = in_array($_POST['currency_position'] ?? '', ['before', 'after']) ? $_POST['currency_position'] : 'before';

        if (!empty($new_symbol)) {
            if (set_system_currency($new_symbol, $new_code ?: $new_symbol, $new_position)) {
                $success = "Global currency updated to " . htmlspecialchars($new_symbol) . " successfully.";
            } else {
                $error = "Failed to update global currency.";
            }
        } else {
            $error = "Currency symbol cannot be empty.";
        }
    } else {
        $student_id = $_POST['student_id'] ?? null;
        $type = $_POST['type'] ?? 'invoice';
        $amount = $_POST['amount'] ?? 0;
        $description = trim($_POST['description'] ?? '');
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
                    <span class="badge bg-light text-dark border px-3 py-2 d-flex align-items-center">
                        <i class="bi bi-cash-stack me-2 text-primary"></i> Active Currency: <strong class="ms-1 text-primary"><?= htmlspecialchars(get_currency_symbol()) ?> (<?= htmlspecialchars(get_currency_code()) ?>)</strong>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#currencySettingsModal">
                        <i class="bi bi-gear-fill me-1"></i> Currency Settings
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#addTransactionModal">
                        <i class="bi bi-plus-lg me-2"></i> New Transaction
                    </button>
                </div>
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
                                    <th class="text-end pe-4">Amount (<?= htmlspecialchars(get_currency_symbol()) ?>)</th>
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
                                            <?= format_currency($t['amount']) ?>
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
                <input type="hidden" name="action" value="add_transaction">
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
                                <span class="input-group-text"><?= htmlspecialchars(get_currency_symbol()) ?></span>
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

<!-- Global Currency Settings Modal -->
<div class="modal fade" id="currencySettingsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Global Currency Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_currency">
                <div class="modal-body">
                    <p class="text-muted small">
                        This setting controls the currency symbol and format across the entire system, including Student Fee Statements, Admin Dashboards, and Financial Reports.
                    </p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Preset</label>
                        <select class="form-select" id="currencyPreset" onchange="applyCurrencyPreset(this.value)">
                            <option value="">-- Choose Preset or type below --</option>
                            <option value="KSh|KES|before" <?= get_currency_symbol() === 'KSh' ? 'selected' : '' ?>>KSh - Kenyan Shilling (Default / MPESA)</option>
                            <option value="KES|KES|before" <?= get_currency_symbol() === 'KES' ? 'selected' : '' ?>>KES - Kenyan Shilling (ISO Code)</option>
                            <option value="$|USD|before" <?= get_currency_symbol() === '$' ? 'selected' : '' ?>>$ - US Dollar (USD)</option>
                            <option value="€|EUR|before" <?= get_currency_symbol() === '€' ? 'selected' : '' ?>>€ - Euro (EUR)</option>
                            <option value="£|GBP|before" <?= get_currency_symbol() === '£' ? 'selected' : '' ?>>£ - British Pound (GBP)</option>
                            <option value="UGX|UGX|before" <?= get_currency_symbol() === 'UGX' ? 'selected' : '' ?>>UGX - Ugandan Shilling</option>
                            <option value="TZS|TZS|before" <?= get_currency_symbol() === 'TZS' ? 'selected' : '' ?>>TZS - Tanzanian Shilling</option>
                            <option value="custom">Custom Currency...</option>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Currency Symbol</label>
                            <input type="text" name="currency_symbol" id="currencySymbolInput" class="form-control" 
                                value="<?= htmlspecialchars(get_currency_symbol()) ?>" required placeholder="e.g. KSh, $, €">
                            <div class="form-text small">Symbol shown next to amounts</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Currency Code</label>
                            <input type="text" name="currency_code" id="currencyCodeInput" class="form-control" 
                                value="<?= htmlspecialchars(get_currency_code()) ?>" required placeholder="e.g. KES, USD">
                            <div class="form-text small">ISO 3-letter currency code</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Symbol Position</label>
                        <select name="currency_position" id="currencyPositionSelect" class="form-select">
                            <option value="before" <?= get_currency_position() === 'before' ? 'selected' : '' ?>>Before Amount (e.g. <?= htmlspecialchars(get_currency_symbol()) ?> 5,000.00)</option>
                            <option value="after" <?= get_currency_position() === 'after' ? 'selected' : '' ?>>After Amount (e.g. 5,000.00 <?= htmlspecialchars(get_currency_symbol()) ?>)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Global Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyCurrencyPreset(val) {
    if (!val || val === 'custom') return;
    const parts = val.split('|');
    if (parts.length === 3) {
        document.getElementById('currencySymbolInput').value = parts[0];
        document.getElementById('currencyCodeInput').value = parts[1];
        document.getElementById('currencyPositionSelect').value = parts[2];
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>

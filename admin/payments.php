<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/payment-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'payments.php';
$pageHeading = 'Payments';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['payment_status'])) {
    require_valid_csrf('admin_payment_status');
    $paymentId = (int) $_POST['payment_id'];
    $paymentStatus = normalize_text($_POST['payment_status']);
    $remarks = normalize_text($_POST['remarks'] ?? '');

    if (update_payment_status($mysqli, $paymentId, $paymentStatus, $remarks)) {
        $message = 'Payment status updated successfully.';
    } else {
        $message = 'Unable to update payment status.';
        $messageType = 'danger';
    }
}

$payments = fetch_all_payments($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Payments</h3><p class="mb-0 text-dark">Verify fee submissions, monitor pending payments, and maintain receipt status.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="8" data-renumber="true" data-empty-message="No payments found">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead><tr><th>#</th><th>Receipt</th><th>Student</th><th>Room</th><th>Month</th><th>Amount</th><th>Method</th><th>Proof</th><th>Status</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if ($payments): foreach ($payments as $index => $payment): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e($payment['receipt_number'] ?? '--'); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(trim($payment['first_name'] . ' ' . ($payment['middle_name'] ?? '') . ' ' . $payment['last_name'])); ?></div>
                                            <div class="small text-muted"><?php echo e($payment['registration_number']); ?></div>
                                        </td>
                                        <td><?php echo e($payment['room_no'] ?? '--'); ?></td>
                                        <td><?php echo e($payment['payment_month'] ?? '--'); ?></td>
                                        <td>Rs. <?php echo number_format((float) ($payment['amount'] ?? 0), 2); ?></td>
                                        <td><?php echo e(ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '')))); ?></td>
                                        <td>
                                            <?php if (!empty($payment['proof_path'])): ?>
                                            <a href="../<?php echo e($payment['proof_path']); ?>" target="_blank" rel="noopener">View</a>
                                            <?php else: ?>
                                            --
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge text-bg-<?php echo ($payment['status'] ?? '') === 'paid' ? 'success' : (($payment['status'] ?? '') === 'failed' ? 'danger' : 'warning'); ?>"><?php echo e(ucfirst((string) $payment['status'])); ?></span></td>
                                        <td style="min-width: 220px;">
                                            <form method="POST" class="d-grid gap-2">
                                                <?php echo csrf_input('admin_payment_status'); ?>
                                                <input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
                                                <select name="payment_status" class="form-select form-select-sm">
                                                    <option value="pending" <?php echo ($payment['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="paid" <?php echo ($payment['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                    <option value="failed" <?php echo ($payment['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                </select>
                                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Remarks">
                                                <button type="submit" class="btn btn-primary btn-sm" data-confirm-message="Update this payment verification status?">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr class="empty-row"><td colspan="10" class="text-center py-4">No payments found</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebarmenu.js"></script>
<script src="../assets/js/app.min.js"></script>
<script src="../assets/js/hostel-table.js?v=20260509a"></script>
</body>
</html>

<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/payment-helpers.php');
require_once('../includes/security-helpers.php');
require_once('../includes/upload-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'payments.php';
$pageHeading = 'Payments';
$message = '';
$messageType = 'success';
$studentId = current_user_id();
$allocation = fetch_student_allocation($mysqli, $studentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    require_valid_csrf('student_payment');

    $proofPath = '';
    if (!empty($_FILES['proof_file']['name'])) {
        $uploadResult = store_uploaded_file(
            $_FILES['proof_file'],
            PAYMENT_UPLOAD_DIR,
            ['jpg', 'jpeg', 'png', 'webp', 'pdf']
        );

        if ($uploadResult['ok']) {
            $proofPath = $uploadResult['path'];
        } else {
            $result = ['ok' => false, 'errors' => ['proof_file' => $uploadResult['error']]];
        }
    }

    if (!isset($result)) {
        $payload = $_POST;
        $payload['proof_path'] = $proofPath;
        $result = create_payment_record($mysqli, $studentId, $payload);
    }

    if ($result['ok']) {
        $message = 'Payment submitted successfully.';
    } else {
        $message = $result['errors']['general'] ?? implode(' ', array_values($result['errors']));
        $messageType = 'danger';
    }
}

$payments = fetch_student_payments($mysqli, $studentId);
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
            <div class="hostel-page-header"><h3 class="mb-1">Payments</h3><p class="mb-0 text-dark">Submit monthly fee records and track their verification status.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card content-card">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Submit Payment</h4>
                            <?php if (!$allocation): ?>
                            <div class="alert alert-warning mb-0">Payments are available only after the admin allocates your hostel room.</div>
                            <?php else: ?>
                            <div class="mb-3 p-3 bg-light rounded">
                                <div class="small text-muted">Allocated Room</div>
                                <div class="fw-semibold">Room <?php echo e($allocation['room_no'] ?? '--'); ?></div>
                                <div class="small text-muted mt-2">Monthly Fee</div>
                                <div class="fw-semibold">Rs. <?php echo number_format((float) ($allocation['monthly_fee'] ?? 0), 2); ?></div>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <?php echo csrf_input('student_payment'); ?>
                                <div class="mb-3"><label class="form-label">Amount</label><input type="text" class="form-control" value="Rs. <?php echo number_format((float) ($allocation['monthly_fee'] ?? 0), 2); ?>" readonly></div>
                                <div class="mb-3"><label class="form-label">Payment Month</label><input type="month" name="payment_month" class="form-control" value="<?php echo e(date('Y-m')); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select"><option value="upi">UPI</option><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="card">Card</option><option value="other">Other</option></select></div>
                                <div class="mb-3"><label class="form-label">Transaction Reference</label><input type="text" name="transaction_reference" class="form-control" placeholder="UPI ID, bank ref, card ref, or cash receipt no."></div>
                                <div class="mb-3"><label class="form-label">Payment Proof</label><input type="file" name="proof_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf"></div>
                                <div class="mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                                <button type="submit" name="submit_payment" class="btn btn-primary w-100">Submit Payment</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mt-4 mt-lg-0">
                    <div class="card content-card">
                        <div class="card-body">
                            <div class="hostel-datatable" data-page-size="8" data-renumber="true" data-empty-message="No payment history yet">
                                <div class="table-responsive">
                                    <table class="table align-middle compact-table js-hostel-table">
                                        <thead><tr><th>#</th><th>Receipt</th><th>Room</th><th>Month</th><th>Amount</th><th>Method</th><th>Status</th><th>Submitted</th></tr></thead>
                                        <tbody>
                                            <?php if ($payments): foreach ($payments as $index => $payment): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo e($payment['receipt_number'] ?? '--'); ?></td>
                                                <td><?php echo e($payment['room_no'] ?? '--'); ?></td>
                                                <td><?php echo e($payment['payment_month'] ?? '--'); ?></td>
                                                <td>Rs. <?php echo number_format((float) ($payment['amount'] ?? 0), 2); ?></td>
                                                <td><?php echo e(ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '')))); ?></td>
                                                <td><span class="badge text-bg-<?php echo ($payment['status'] ?? '') === 'paid' ? 'success' : (($payment['status'] ?? '') === 'failed' ? 'danger' : 'warning'); ?>"><?php echo e(ucfirst((string) $payment['status'])); ?></span></td>
                                                <td><?php echo e($payment['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr class="empty-row"><td colspan="8" class="text-center py-4">No payment history yet</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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

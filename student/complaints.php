<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/complaint-helpers.php');
require_once('../includes/security-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'complaints.php';
$pageHeading = 'Complaints';
$studentId = current_user_id();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    require_valid_csrf('student_complaint');
    $result = create_complaint_record($mysqli, $studentId, $_POST);
    if ($result['ok']) {
        $message = 'Complaint submitted successfully.';
    } else {
        $message = $result['errors']['general'] ?? implode(' ', array_values($result['errors']));
        $messageType = 'danger';
    }
}

$complaints = fetch_student_complaints($mysqli, $studentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complaints</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Complaints</h3><p class="mb-0 text-dark">Raise hostel issues and track their resolution progress.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <div class="row">
                <div class="col-lg-4">
                    <div class="card content-card">
                        <div class="card-body">
                            <h4 class="card-title mb-3">New Complaint</h4>
                            <form method="POST">
                                <?php echo csrf_input('student_complaint'); ?>
                                <div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
                                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required></textarea></div>
                                <button type="submit" name="submit_complaint" class="btn btn-primary w-100">Submit Complaint</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mt-4 mt-lg-0">
                    <div class="card content-card">
                        <div class="card-body">
                            <div class="hostel-datatable" data-page-size="8" data-renumber="true" data-empty-message="No complaints yet">
                                <div class="table-responsive">
                                    <table class="table align-middle compact-table js-hostel-table">
                                        <thead><tr><th>#</th><th>Subject</th><th>Priority</th><th>Status</th><th>Warden</th><th>Remarks</th><th>Created</th></tr></thead>
                                        <tbody>
                                            <?php if ($complaints): foreach ($complaints as $index => $complaint): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo e($complaint['subject']); ?></td>
                                                <td><?php echo e(ucfirst((string) $complaint['priority'])); ?></td>
                                                <td><span class="badge text-bg-<?php echo ($complaint['status'] ?? '') === 'resolved' ? 'success' : (($complaint['status'] ?? '') === 'in_progress' ? 'primary' : 'warning'); ?>"><?php echo e(str_replace('_', ' ', ucfirst((string) $complaint['status']))); ?></span></td>
                                                <td><?php echo e($complaint['warden_name'] ?? '--'); ?></td>
                                                <td><?php echo e($complaint['remarks'] ?? '--'); ?></td>
                                                <td><?php echo e($complaint['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; else: ?>
                                            <tr class="empty-row"><td colspan="7" class="text-center py-4">No complaints yet</td></tr>
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

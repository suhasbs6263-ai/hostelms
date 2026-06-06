<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/complaint-helpers.php');
require_once('../includes/security-helpers.php');
check_login('warden');

$portalRole = 'warden';
$activePage = 'complaints.php';
$pageHeading = 'Complaints';
$wardenId = current_user_id();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['complaint_status'])) {
    require_valid_csrf('warden_complaint_status');

    $complaintId = (int) $_POST['complaint_id'];
    $complaint = fetch_complaint_by_id($mysqli, $complaintId);

    if ($complaint && !empty($complaint['assigned_warden_id']) && (int) $complaint['assigned_warden_id'] !== $wardenId) {
        $message = 'This complaint is assigned to another warden.';
        $messageType = 'danger';
    } elseif (update_complaint_record($mysqli, $complaintId, normalize_text($_POST['complaint_status']), normalize_text($_POST['remarks'] ?? ''), $wardenId)) {
        $message = 'Complaint updated successfully.';
    } else {
        $message = 'Unable to update complaint.';
        $messageType = 'danger';
    }
}

$complaints = fetch_all_complaints($mysqli, $wardenId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warden Complaints</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Complaint Resolution Desk</h3><p class="mb-0 text-dark">Handle complaints assigned to you and update their progress.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <div class="card content-card"><div class="card-body">
                <div class="hostel-datatable" data-page-size="8" data-renumber="true" data-empty-message="No complaints found">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead><tr><th>#</th><th>Student</th><th>Subject</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if ($complaints): foreach ($complaints as $index => $complaint): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo e(trim($complaint['first_name'] . ' ' . ($complaint['middle_name'] ?? '') . ' ' . $complaint['last_name'])); ?></td>
                                    <td><div class="fw-semibold"><?php echo e($complaint['subject']); ?></div><div class="small text-muted"><?php echo e($complaint['description']); ?></div></td>
                                    <td><?php echo e(ucfirst((string) $complaint['priority'])); ?></td>
                                    <?php $status = (string) ($complaint['status'] ?? 'pending'); ?>
                                    <td><span class="badge text-bg-<?php echo $status === 'resolved' ? 'success' : ($status === 'in_progress' ? 'primary' : 'warning'); ?>"><?php echo e(str_replace('_', ' ', ucfirst($status))); ?></span></td>
                                    <td style="min-width:240px;">
                                        <form method="POST" class="d-grid gap-2">
                                            <?php echo csrf_input('warden_complaint_status'); ?>
                                            <input type="hidden" name="complaint_id" value="<?php echo (int) $complaint['id']; ?>">
                                            <select name="complaint_status" class="form-select form-select-sm">
                                                <option value="pending" <?php echo ($complaint['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?php echo ($complaint['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="resolved" <?php echo ($complaint['status'] ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Update remarks">
                                            <button type="submit" class="btn btn-primary btn-sm" data-confirm-message="Update this complaint status?">Update</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr class="empty-row"><td colspan="6" class="text-center py-4">No complaints found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div></div>
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

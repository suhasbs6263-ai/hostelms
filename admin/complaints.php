<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/complaint-helpers.php');
require_once('../includes/warden-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'complaints.php';
$pageHeading = 'Complaints';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['complaint_status'])) {
    require_valid_csrf('admin_complaint_status');

    $complaintId = (int) $_POST['complaint_id'];
    $complaintStatus = normalize_text($_POST['complaint_status']);
    $remarks = normalize_text($_POST['remarks'] ?? '');
    $wardenId = isset($_POST['assigned_warden_id']) && ctype_digit((string) $_POST['assigned_warden_id']) ? (int) $_POST['assigned_warden_id'] : null;

    if (update_complaint_record($mysqli, $complaintId, $complaintStatus, $remarks, $wardenId)) {
        $message = 'Complaint updated successfully.';
    } else {
        $message = 'Unable to update complaint.';
        $messageType = 'danger';
    }
}

$complaints = fetch_all_complaints($mysqli);
$wardens = fetch_active_wardens($mysqli);
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
            <div class="hostel-page-header"><h3 class="mb-1">Complaints</h3><p class="mb-0 text-dark">Assign complaints to wardens, track their status, and close issues with proper remarks.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <?php if (!$wardens): ?><div class="alert alert-warning">No active wardens are available for assignment. <a href="wardens.php" class="alert-link">Create or activate a warden</a>.</div><?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="8" data-renumber="true" data-empty-message="No complaints found">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead><tr><th>#</th><th>Student</th><th>Subject</th><th>Priority</th><th>Status</th><th>Assigned Warden</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php if ($complaints): foreach ($complaints as $index => $complaint): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><div class="fw-semibold"><?php echo e(trim($complaint['first_name'] . ' ' . ($complaint['middle_name'] ?? '') . ' ' . $complaint['last_name'])); ?></div><div class="small text-muted"><?php echo e($complaint['registration_number']); ?></div></td>
                                        <td><div class="fw-semibold"><?php echo e($complaint['subject']); ?></div><div class="small text-muted"><?php echo e($complaint['description']); ?></div></td>
                                        <td><?php echo e(ucfirst((string) $complaint['priority'])); ?></td>
                                        <td><span class="badge text-bg-<?php echo ($complaint['status'] ?? '') === 'resolved' ? 'success' : (($complaint['status'] ?? '') === 'in_progress' ? 'primary' : 'warning'); ?>"><?php echo e(str_replace('_', ' ', ucfirst((string) $complaint['status']))); ?></span></td>
                                        <td><?php echo e($complaint['warden_name'] ?? '--'); ?></td>
                                        <td style="min-width: 260px;">
                                            <form method="POST" class="d-grid gap-2">
                                                <?php echo csrf_input('admin_complaint_status'); ?>
                                                <input type="hidden" name="complaint_id" value="<?php echo (int) $complaint['id']; ?>">
                                                <select name="assigned_warden_id" class="form-select form-select-sm">
                                                    <option value="">Assign warden</option>
                                                    <?php foreach ($wardens as $warden): ?>
                                                    <option value="<?php echo (int) $warden['id']; ?>" <?php echo (int) ($complaint['assigned_warden_id'] ?? 0) === (int) $warden['id'] ? 'selected' : ''; ?>><?php echo e($warden['full_name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="complaint_status" class="form-select form-select-sm">
                                                    <option value="pending" <?php echo ($complaint['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="in_progress" <?php echo ($complaint['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="resolved" <?php echo ($complaint['status'] ?? '') === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
                                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Resolution remarks">
                                                <button type="submit" class="btn btn-primary btn-sm" data-confirm-message="Update this complaint assignment and status?">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr class="empty-row"><td colspan="7" class="text-center py-4">No complaints found</td></tr>
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

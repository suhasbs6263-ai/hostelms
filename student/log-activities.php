<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login('student');

$portalRole = 'student';
$activePage = 'log-activities.php';
$pageHeading = 'Activity Log';
$studentId = current_user_id();
$logs = [];

$stmt = $mysqli->prepare(
    "SELECT action, description, ip_address, created_at
     FROM activity_logs
     WHERE actor_type = 'student' AND actor_id = ?
     ORDER BY id DESC"
);

if ($stmt) {
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activity Log</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Activity Log</h3><p class="mb-0 text-dark">Review recent actions performed on your hostel account.</p></div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="10" data-renumber="true" data-empty-message="No activity yet">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead><tr><th>#</th><th>Action</th><th>Description</th><th>IP</th><th>Created</th></tr></thead>
                                <tbody>
                                    <?php if ($logs): foreach ($logs as $index => $log): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e(str_replace('_', ' ', ucfirst($log['action']))); ?></td>
                                        <td><?php echo e($log['description'] ?: '--'); ?></td>
                                        <td><?php echo e($log['ip_address'] ?: '--'); ?></td>
                                        <td><?php echo e($log['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr class="empty-row"><td colspan="5" class="text-center py-4">No activity yet</td></tr>
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

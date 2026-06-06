<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/complaint-helpers.php');
check_login('warden');

$portalRole = 'warden';
$activePage = 'dashboard.php';
$pageHeading = 'Warden Dashboard';
$wardenId = current_user_id();
$complaints = fetch_all_complaints($mysqli, $wardenId);
$pendingCount = count(array_filter($complaints, static fn(array $complaint): bool => ($complaint['status'] ?? '') === 'pending'));
$progressCount = count(array_filter($complaints, static fn(array $complaint): bool => ($complaint['status'] ?? '') === 'in_progress'));
$resolvedCount = count(array_filter($complaints, static fn(array $complaint): bool => ($complaint['status'] ?? '') === 'resolved'));
$complaintChart = e(json_encode([
    'type' => 'donut',
    'labels' => ['Pending', 'In Progress', 'Resolved'],
    'series' => [$pendingCount, $progressCount, $resolvedCount],
    'colors' => ['#f59e0b', '#2563eb', '#16a34a'],
    'totalLabel' => 'Issues',
], JSON_UNESCAPED_SLASHES));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warden Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Warden Operations</h3><p class="mb-0 text-dark">Monitor complaint flow and daily hostel issue resolution.</p></div>
            <div class="row metric-grid g-3">
                <div class="col-md-4"><div class="card stat-card stat-card-warning"><div class="card-body"><span class="stat-icon"><i class="ti ti-alert-triangle"></i></span><p class="text-muted mb-2">Pending Complaints</p><h2 class="display-6"><?php echo $pendingCount; ?></h2></div></div></div>
                <div class="col-md-4"><div class="card stat-card stat-card-primary"><div class="card-body"><span class="stat-icon"><i class="ti ti-progress-check"></i></span><p class="text-muted mb-2">In Progress</p><h2 class="display-6"><?php echo $progressCount; ?></h2></div></div></div>
                <div class="col-md-4"><div class="card stat-card stat-card-success"><div class="card-body"><span class="stat-icon"><i class="ti ti-circle-check"></i></span><p class="text-muted mb-2">Resolved</p><h2 class="display-6"><?php echo $resolvedCount; ?></h2></div></div></div>
            </div>
            <div class="row mt-4 g-3">
                <div class="col-lg-5">
                    <div class="card content-card h-100"><div class="card-body">
                        <h4 class="section-card-title mb-3">Resolution Mix</h4>
                        <div class="dashboard-chart" data-hostel-chart="<?php echo $complaintChart; ?>"></div>
                        <div class="quick-action-grid mt-3">
                            <a class="quick-action-card" href="complaints.php"><i class="ti ti-alert-circle"></i><span>Open Queue</span></a>
                            <a class="quick-action-card" href="notifications.php"><i class="ti ti-bell"></i><span>Notifications</span></a>
                        </div>
                    </div></div>
                </div>
                <div class="col-lg-7">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="section-card-title mb-0">Latest Complaint Queue</h4>
                            <a href="complaints.php" class="btn btn-sm btn-outline-primary">Manage Queue</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle compact-table mb-0">
                                <thead><tr><th>Student</th><th>Subject</th><th>Status</th><th>Created</th></tr></thead>
                                <tbody>
                                    <?php if ($complaints): foreach (array_slice($complaints, 0, 8) as $complaint): ?>
                                    <?php $status = (string) ($complaint['status'] ?? 'pending'); ?>
                                    <tr>
                                        <td><?php echo e(trim($complaint['first_name'] . ' ' . ($complaint['middle_name'] ?? '') . ' ' . $complaint['last_name'])); ?></td>
                                        <td><?php echo e($complaint['subject']); ?></td>
                                        <td><span class="badge text-bg-<?php echo $status === 'resolved' ? 'success' : ($status === 'in_progress' ? 'primary' : 'warning'); ?>"><?php echo e(str_replace('_', ' ', ucfirst($status))); ?></span></td>
                                        <td><?php echo e($complaint['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-4">No complaints assigned yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div></div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/chartjs/chart.umd.min.js"></script>
<script src="../assets/js/sidebarmenu.js"></script>
<script src="../assets/js/app.min.js"></script>
<script src="../assets/js/hostel-dashboard-charts.js?v=20260527a"></script>
</body>
</html>

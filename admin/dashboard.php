<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/dashboard-helpers.php');
require_once('../includes/student-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'dashboard.php';
$pageHeading = 'Admin Dashboard';

$summary = dashboard_summary($mysqli);
$chartData = dashboard_chart_data($mysqli);
$studentStatusBars = [
    ['label' => 'Pending', 'value' => (int) $chartData['student_status']['pending'], 'class' => 'bg-warning'],
    ['label' => 'Approved', 'value' => (int) $chartData['student_status']['approved'], 'class' => 'bg-success'],
    ['label' => 'Rejected', 'value' => (int) $chartData['student_status']['rejected'], 'class' => 'bg-danger'],
];
$complaintStatusBars = [
    ['label' => 'Pending', 'value' => (int) $chartData['complaints']['pending'], 'class' => 'bg-danger'],
    ['label' => 'In Progress', 'value' => (int) $chartData['complaints']['in_progress'], 'class' => 'bg-primary'],
    ['label' => 'Resolved', 'value' => (int) $chartData['complaints']['resolved'], 'class' => 'bg-success'],
];
$studentStatusTotal = max(array_sum(array_column($studentStatusBars, 'value')), 1);
$complaintStatusTotal = max(array_sum(array_column($complaintStatusBars, 'value')), 1);
$occupancyTotal = max((int) $summary['occupied_beds'] + (int) $summary['available_beds'], 1);
$occupancyRate = round(((int) $summary['occupied_beds'] / $occupancyTotal) * 100);
$occupancyChart = e(json_encode([
    'type' => 'donut',
    'labels' => ['Occupied Beds', 'Available Beds'],
    'series' => [(int) $summary['occupied_beds'], (int) $summary['available_beds']],
    'colors' => ['#2563eb', '#14b8a6'],
    'totalLabel' => 'Beds',
], JSON_UNESCAPED_SLASHES));
$studentChart = e(json_encode([
    'type' => 'bar',
    'categories' => array_column($studentStatusBars, 'label'),
    'series' => [[
        'name' => 'Students',
        'data' => array_column($studentStatusBars, 'value'),
    ]],
    'colors' => ['#2563eb'],
], JSON_UNESCAPED_SLASHES));
$complaintChart = e(json_encode([
    'type' => 'donut',
    'labels' => array_column($complaintStatusBars, 'label'),
    'series' => array_column($complaintStatusBars, 'value'),
    'colors' => ['#ef4444', '#2563eb', '#16a34a'],
    'totalLabel' => 'Issues',
], JSON_UNESCAPED_SLASHES));
$pendingStudents = array_slice(
    array_values(array_filter(fetch_all_students($mysqli), static function (array $student): bool {
        return ($student['status'] ?? '') === 'pending';
    })),
    0,
    5
);
$recentLogs = fetch_recent_activity_logs($mysqli, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar">
        <?php include('../includes/sidebar.php'); ?>
    </aside>
    <div class="body-wrapper">
        <header class="app-header">
            <?php include('../includes/navigation.php'); ?>
        </header>
        <div class="container-fluid">
            <div class="hostel-page-header">
                <h3 class="mb-1">Operations Overview</h3>
                <p class="mb-0 text-dark">Monitor student approvals, room occupancy, complaints, and hostel fee performance.</p>
            </div>

            <div class="row metric-grid g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-primary"><div class="card-body"><span class="stat-icon"><i class="ti ti-users"></i></span><p class="text-muted mb-2">Total Students</p><h2 class="display-6 fs-8"><?php echo $summary['total_students']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-warning"><div class="card-body"><span class="stat-icon"><i class="ti ti-user-check"></i></span><p class="text-muted mb-2">Pending Approvals</p><h2 class="display-6 fs-8"><?php echo $summary['pending_students']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-accent"><div class="card-body"><span class="stat-icon"><i class="ti ti-bed"></i></span><p class="text-muted mb-2">Occupancy Rate</p><h2 class="display-6 fs-8"><?php echo $occupancyRate; ?>%</h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-success"><div class="card-body"><span class="stat-icon"><i class="ti ti-door-enter"></i></span><p class="text-muted mb-2">Available Beds</p><h2 class="display-6 fs-8"><?php echo $summary['available_beds']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body"><span class="stat-icon"><i class="ti ti-home-check"></i></span><p class="text-muted mb-2">Allocated Students</p><h2 class="display-6 fs-8"><?php echo $summary['allocated_students']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body"><span class="stat-icon"><i class="ti ti-building"></i></span><p class="text-muted mb-2">Total Rooms</p><h2 class="display-6 fs-8"><?php echo $summary['total_rooms']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-danger"><div class="card-body"><span class="stat-icon"><i class="ti ti-alert-circle"></i></span><p class="text-muted mb-2">Pending Complaints</p><h2 class="display-6 fs-8"><?php echo $summary['complaints_pending']; ?></h2></div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-card-accent"><div class="card-body"><span class="stat-icon"><i class="ti ti-cash-banknote"></i></span><p class="text-muted mb-2">Fees Collected</p><h2 class="display-6 fs-8">Rs. <?php echo number_format($summary['fees_collected'], 2); ?></h2></div></div>
                </div>
            </div>

            <div class="row mt-4 g-3">
                <div class="col-xl-4 col-lg-6">
                    <div class="card content-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="section-card-title mb-0">Bed Occupancy</h4>
                                <span class="badge text-bg-primary"><?php echo $occupancyRate; ?>%</span>
                            </div>
                            <div class="dashboard-chart" data-hostel-chart="<?php echo $occupancyChart; ?>"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6">
                    <div class="card content-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="section-card-title mb-0">Student Review Flow</h4>
                                <a href="register-student.php" class="btn btn-sm btn-primary">Review</a>
                            </div>
                            <div class="dashboard-chart" data-hostel-chart="<?php echo $studentChart; ?>"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card content-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="section-card-title mb-0">Complaint Health</h4>
                                <a href="complaints.php" class="btn btn-sm btn-outline-primary">Manage</a>
                            </div>
                            <div class="dashboard-chart" data-hostel-chart="<?php echo $complaintChart; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 g-3">
                <div class="col-lg-7">
                    <div class="card content-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="section-card-title mb-0">Pending Student Reviews</h4>
                                <a href="register-student.php" class="btn btn-sm btn-outline-primary">Open Approval Desk</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle compact-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Reg No</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($pendingStudents): ?>
                                        <?php foreach ($pendingStudents as $student): ?>
                                        <tr>
                                            <td><?php echo e($student['registration_number']); ?></td>
                                            <td>
                                                <div class="fw-semibold"><?php echo e(student_full_name($student)); ?></div>
                                                <div class="small text-muted"><?php echo e($student['email']); ?></div>
                                            </td>
                                            <td><?php echo e($student['course_sn'] ?? $student['course_fn'] ?? '--'); ?></td>
                                            <td><?php echo e($student['created_at']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr><td colspan="4" class="text-center py-4">No pending approvals right now.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="card content-card h-100">
                        <div class="card-body">
                            <h4 class="section-card-title mb-3">Quick Actions</h4>
                            <div class="quick-action-grid mb-4">
                                <a class="quick-action-card" href="register-student.php"><i class="ti ti-user-check"></i><span>Review Students</span></a>
                                <a class="quick-action-card" href="book-hostel.php"><i class="ti ti-bed"></i><span>Allocate Rooms</span></a>
                                <a class="quick-action-card" href="payments.php"><i class="ti ti-credit-card"></i><span>Verify Payments</span></a>
                                <a class="quick-action-card" href="complaints.php"><i class="ti ti-alert-circle"></i><span>Assign Complaints</span></a>
                            </div>
                            <h4 class="section-card-title mb-3">Recent Activity</h4>
                            <?php if ($recentLogs): ?>
                            <div class="list-group list-group-flush activity-timeline">
                                <?php foreach ($recentLogs as $log): ?>
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold"><?php echo e(str_replace('_', ' ', ucfirst($log['action']))); ?></div>
                                    <div class="small text-muted"><?php echo e($log['description'] ?: ($log['actor_type'] . ' activity')); ?></div>
                                    <div class="small text-muted"><?php echo e($log['created_at']); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="empty-state py-5">
                                <h4>No activity yet</h4>
                                <p class="text-muted">Recent system activity will appear here.</p>
                            </div>
                            <?php endif; ?>
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
<script src="../assets/libs/chartjs/chart.umd.min.js"></script>
<script src="../assets/js/sidebarmenu.js"></script>
<script src="../assets/js/app.min.js"></script>
<script src="../assets/js/hostel-dashboard-charts.js?v=20260527a"></script>
</body>
</html>

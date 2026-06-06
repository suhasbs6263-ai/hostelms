<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/student-helpers.php');
require_once('../includes/room-helpers.php');
require_once('../includes/payment-helpers.php');
require_once('../includes/complaint-helpers.php');
require_once('../includes/notification-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'dashboard.php';
$pageHeading = 'Student Dashboard';

$studentId = current_user_id();
$student = fetch_student_by_id($mysqli, $studentId);
$rooms = fetch_available_rooms($mysqli);
$allocation = fetch_student_allocation($mysqli, $studentId);
$latestRequest = fetch_latest_student_request($mysqli, $studentId);
$payments = fetch_student_payments($mysqli, $studentId);
$complaints = fetch_student_complaints($mysqli, $studentId);
$unreadNotifications = count_unread_notifications($mysqli, 'student', $studentId);
$paidCount = count(array_filter($payments, static fn(array $payment): bool => ($payment['status'] ?? '') === 'paid'));
$pendingPaymentCount = count(array_filter($payments, static fn(array $payment): bool => ($payment['status'] ?? '') === 'pending'));
$openComplaints = count(array_filter($complaints, static fn(array $complaint): bool => ($complaint['status'] ?? '') !== 'resolved'));
$resolvedComplaints = count(array_filter($complaints, static fn(array $complaint): bool => ($complaint['status'] ?? '') === 'resolved'));
$paymentBars = [
    ['label' => 'Paid Payments', 'value' => $paidCount, 'class' => 'bg-success'],
    ['label' => 'Pending Payments', 'value' => $pendingPaymentCount, 'class' => 'bg-warning'],
];
$complaintBars = [
    ['label' => 'Open Complaints', 'value' => $openComplaints, 'class' => 'bg-danger'],
    ['label' => 'Resolved Complaints', 'value' => $resolvedComplaints, 'class' => 'bg-success'],
];
$paymentTotal = max(array_sum(array_column($paymentBars, 'value')), 1);
$complaintTotal = max(array_sum(array_column($complaintBars, 'value')), 1);
$studentStatus = ucfirst((string) ($student['status'] ?? 'pending'));
$studentChart = e(json_encode([
    'type' => 'donut',
    'labels' => ['Paid Payments', 'Pending Payments'],
    'series' => [$paidCount, $pendingPaymentCount],
    'colors' => ['#16a34a', '#f59e0b'],
    'totalLabel' => 'Payments',
], JSON_UNESCAPED_SLASHES));
$complaintChart = e(json_encode([
    'type' => 'donut',
    'labels' => ['Open Complaints', 'Resolved Complaints'],
    'series' => [$openComplaints, $resolvedComplaints],
    'colors' => ['#ef4444', '#16a34a'],
    'totalLabel' => 'Issues',
], JSON_UNESCAPED_SLASHES));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
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
        <h3 class="mb-1">Welcome back, <?php echo e($student ? $student['first_name'] : 'Student'); ?></h3>
        <p class="mb-0 text-dark">Track approval status, room allocation, fees, and complaints from one dashboard.</p>
      </div>
      <div class="row metric-grid g-3">
        <div class="col-md-3">
          <div class="card stat-card stat-card-primary"><div class="card-body"><span class="stat-icon"><i class="ti ti-user-check"></i></span><p class="text-muted mb-2">Account Status</p><h2 class="display-6"><?php echo e($studentStatus); ?></h2></div></div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card stat-card-accent"><div class="card-body"><span class="stat-icon"><i class="ti ti-bed"></i></span><p class="text-muted mb-2">Rooms With Free Beds</p><h2 class="display-6"><?php echo count($rooms); ?></h2></div></div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card stat-card-warning"><div class="card-body"><span class="stat-icon"><i class="ti ti-credit-card"></i></span><p class="text-muted mb-2">Pending Payments</p><h2 class="display-6"><?php echo $pendingPaymentCount; ?></h2></div></div>
        </div>
        <div class="col-md-3">
          <div class="card stat-card stat-card-danger"><div class="card-body"><span class="stat-icon"><i class="ti ti-bell"></i></span><p class="text-muted mb-2">Unread Notifications</p><h2 class="display-6"><?php echo $unreadNotifications; ?></h2></div></div>
        </div>
      </div>

      <div class="row mt-4 g-3">
        <div class="col-lg-7">
          <div class="card content-card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <h4 class="card-title mb-1">Hostel Status</h4>
                  <p class="text-muted mb-0">Current room application or allocation summary.</p>
                </div>
                <a href="book-hostel.php" class="btn btn-primary">Manage Request</a>
              </div>
              <?php if ($allocation): ?>
              <div class="row g-3">
                <div class="col-md-6"><strong>Allocated Room:</strong> <?php echo e($allocation['room_no'] ?? '--'); ?></div>
                <div class="col-md-6"><strong>Room Type:</strong> <?php echo e($allocation['room_type'] ?? '--'); ?></div>
                <div class="col-md-6"><strong>Stay From:</strong> <?php echo e($allocation['stay_from']); ?></div>
                <div class="col-md-6"><strong>Monthly Fee:</strong> Rs. <?php echo number_format((float) ($allocation['monthly_fee'] ?? 0), 2); ?></div>
              </div>
              <div class="mt-4">
                <a href="room-details.php" class="btn btn-light-primary">View Full Allocation Details</a>
              </div>
              <?php elseif ($latestRequest): ?>
              <div class="empty-state">
                <h4>Request Status: <?php echo e(ucfirst((string) $latestRequest['status'])); ?></h4>
                <p class="text-muted">Preferred room: <?php echo e($latestRequest['preferred_room_no'] ?? '--'); ?> | Stay from <?php echo e($latestRequest['stay_from'] ?? '--'); ?></p>
                <a href="book-hostel.php" class="btn btn-primary">Open Request</a>
              </div>
              <?php else: ?>
              <div class="empty-state">
                <h4>No hostel request yet</h4>
                <p class="text-muted">Once your account is approved, you can apply for a hostel room.</p>
                <a href="book-hostel.php" class="btn btn-primary">Apply for Room</a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
          <div class="card content-card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">Quick Actions</h4>
              </div>
              <div class="quick-action-grid mb-4">
                <a class="quick-action-card" href="book-hostel.php"><i class="ti ti-bed"></i><span>Room Request</span></a>
                <a class="quick-action-card" href="payments.php"><i class="ti ti-credit-card"></i><span>Pay Fees</span></a>
                <a class="quick-action-card" href="complaints.php"><i class="ti ti-alert-circle"></i><span>Raise Issue</span></a>
                <a class="quick-action-card" href="notifications.php"><i class="ti ti-bell"></i><span>Notifications</span></a>
              </div>
              <h4 class="card-title mb-3">Quick Summary</h4>
              <div class="mb-3"><strong>Payments Marked Paid:</strong> <?php echo $paidCount; ?></div>
              <div class="mb-3"><strong>Open Complaints:</strong> <?php echo $openComplaints; ?></div>
              <div class="mb-3"><strong>Guardian:</strong> <?php echo e($student['guardian_name'] ?? '--'); ?></div>
              <div class="mb-0"><strong>Course:</strong> <?php echo e($student['course_fn'] ?? $student['course_sn'] ?? 'Not selected'); ?></div>
              <div class="metric-bars mt-4">
                <?php foreach ($paymentBars as $bar): ?>
                <?php $width = $bar['value'] > 0 ? max(6, round(($bar['value'] / $paymentTotal) * 100, 2)) : 0; ?>
                <div class="metric-bar-row">
                  <div class="metric-bar-label"><span><?php echo e($bar['label']); ?></span><strong><?php echo $bar['value']; ?></strong></div>
                  <div class="metric-bar-track"><div class="metric-bar-fill <?php echo e($bar['class']); ?>" style="width: <?php echo $width; ?>%;"></div></div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($complaintBars as $bar): ?>
                <?php $width = $bar['value'] > 0 ? max(6, round(($bar['value'] / $complaintTotal) * 100, 2)) : 0; ?>
                <div class="metric-bar-row">
                  <div class="metric-bar-label"><span><?php echo e($bar['label']); ?></span><strong><?php echo $bar['value']; ?></strong></div>
                  <div class="metric-bar-track"><div class="metric-bar-fill <?php echo e($bar['class']); ?>" style="width: <?php echo $width; ?>%;"></div></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-4 g-3">
        <div class="col-lg-6">
          <div class="card content-card h-100">
            <div class="card-body">
              <h4 class="section-card-title mb-3">Payment Progress</h4>
              <div class="dashboard-chart" data-hostel-chart="<?php echo $studentChart; ?>"></div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card content-card h-100">
            <div class="card-body">
              <h4 class="section-card-title mb-3">Complaint Progress</h4>
              <div class="dashboard-chart" data-hostel-chart="<?php echo $complaintChart; ?>"></div>
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

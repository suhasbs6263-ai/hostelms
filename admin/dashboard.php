<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/course-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'dashboard.php';
$pageHeading = 'Admin Dashboard';

$studentsCount = 0;
$bookingsCount = 0;
$roomsCount = 0;
$coursesCount = 0;
$logEntries = [];

$result = $mysqli->query("SELECT COUNT(*) AS total FROM userregistration");
if ($result) {
    $studentsCount = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("SELECT COUNT(*) AS total FROM registration");
if ($result) {
    $bookingsCount = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("SELECT COUNT(*) AS total FROM rooms");
if ($result) {
    $roomsCount = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("SELECT COUNT(*) AS total FROM courses");
if ($result) {
    $coursesCount = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$logResult = $mysqli->query("SELECT userEmail, userIp FROM userLog ORDER BY id DESC LIMIT 5");
if ($logResult) {
    while ($row = $logResult->fetch_assoc()) {
        $logEntries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260402b">
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
                <h3 class="mb-1">Good Afternoon, admin!</h3>
            </div>
            <div class="row metric-grid">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-2">Registered Student</p>
                            <h2 class="display-6 fs-8"><?php echo $studentsCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-2">Total Rooms</p>
                            <h2 class="display-6 fs-8"><?php echo $roomsCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-2">Booked Rooms</p>
                            <h2 class="display-6 fs-8"><?php echo $bookingsCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <p class="text-muted mb-2">Featured Courses</p>
                            <h2 class="display-6 fs-8"><?php echo $coursesCount; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card content-card mt-4">
                <div class="card-body">
                    <h4 class="section-card-title mb-3">Student Log Activities</h4>
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No log activity yet">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student's Email</th>
                                    <th>Last Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logEntries): ?>
                                <?php foreach ($logEntries as $index => $log): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($log['userEmail']); ?></td>
                                    <td><?php echo htmlspecialchars($log['userIp']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="3" class="text-center py-4">No log activity yet</td>
                                </tr>
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
<script src="../assets/js/hostel-table.js?v=20260402b"></script>
</body>
</html>

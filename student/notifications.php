<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/notification-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'notifications.php';
$pageHeading = 'Notifications';
$studentId = current_user_id();

if (isset($_GET['read']) && ctype_digit((string) $_GET['read'])) {
    mark_notification_read($mysqli, (int) $_GET['read'], 'student', $studentId);
}

$notifications = fetch_notifications($mysqli, 'student', $studentId, 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">Notifications</h3><p class="mb-0 text-dark">Review approval updates, allocation messages, fee updates, and complaint changes.</p></div>
            <div class="card content-card">
                <div class="card-body">
                    <?php if ($notifications): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                        <a href="?read=<?php echo (int) $notification['id']; ?>" class="list-group-item list-group-item-action px-0 <?php echo (int) $notification['is_read'] === 0 ? 'bg-light' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold"><?php echo e($notification['title']); ?></div>
                                    <div class="text-muted small"><?php echo e($notification['message']); ?></div>
                                </div>
                                <small class="text-muted"><?php echo e($notification['created_at']); ?></small>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state"><h4>No notifications yet</h4><p class="text-muted">System updates will appear here.</p></div>
                    <?php endif; ?>
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
</body>
</html>

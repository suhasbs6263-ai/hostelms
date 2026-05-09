<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login();

$portalRole = 'student';
$activePage = 'log-activities.php';
$pageHeading = 'Log Activities';
$studentEmail = $_SESSION['login'];

$logs = [];
$stmt = $mysqli->prepare("SELECT userIp, city, country FROM userLog WHERE userEmail = ?");
$stmt->bind_param('s', $studentEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log Activities</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
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
                <h3 class="mb-1">Login Activities</h3>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="5" data-renumber="false" data-empty-message="No login activities found">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead>
                                <tr>
                                    <th>User IP</th>
                                    <th>City</th>
                                    <th>Country</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($logs): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['userIp']); ?></td>
                                    <td><?php echo htmlspecialchars($log['city']); ?></td>
                                    <td><?php echo htmlspecialchars($log['country']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="3" class="text-center py-4">No login activities found</td>
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

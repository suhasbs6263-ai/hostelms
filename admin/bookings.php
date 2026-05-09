<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'bookings.php';
$pageHeading = 'Hostel Bookings';

$bookings = [];
$result = $mysqli->query("SELECT regno, roomno, seater, stayfrom, contactno, emailid, firstName, middleName, lastName FROM registration ORDER BY stayfrom DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bookings</title>
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
                <h3 class="mb-1">Hostel Student Management</h3>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No hostel students found">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reg No.</th>
                                    <th>Student's Name</th>
                                    <th>Room No</th>
                                    <th>Seater</th>
                                    <th>Staying From</th>
                                    <th>Contact</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings): ?>
                                <?php foreach ($bookings as $index => $booking): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($booking['regno']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($booking['firstName'] . ' ' . $booking['middleName'] . ' ' . $booking['lastName'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['roomno']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['seater']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['stayfrom']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['contactno']); ?></td>
                                    <td>
                                        <div class="action-icon-group">
                                            <a class="action-icon-btn" href="tel:<?php echo htmlspecialchars($booking['contactno']); ?>" title="Call Student">
                                                <i class="ti ti-phone"></i>
                                            </a>
                                            <a class="action-icon-btn" href="mailto:<?php echo rawurlencode($booking['emailid']); ?>" title="Email Student">
                                                <i class="ti ti-mail"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="8" class="text-center py-4">No hostel students found</td>
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

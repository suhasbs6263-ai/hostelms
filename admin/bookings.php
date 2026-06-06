<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'bookings.php';
$pageHeading = 'Room Allocations';
$allocations = fetch_all_allocations($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Allocations</title>
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
                <h3 class="mb-1">Hostel Allocations</h3>
                <p class="mb-0 text-dark">Track pending, allocated, rejected, and completed hostel room requests.</p>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="10" data-renumber="true" data-empty-message="No room allocations found">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Reg No.</th>
                                        <th>Student</th>
                                        <th>Assigned Room</th>
                                        <th>Stay From</th>
                                        <th>Monthly Fee</th>
                                        <th>Status</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($allocations): ?>
                                    <?php foreach ($allocations as $index => $allocation): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e($allocation['registration_number']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(trim($allocation['first_name'] . ' ' . ($allocation['middle_name'] ?? '') . ' ' . $allocation['last_name'])); ?></div>
                                            <div class="small text-muted"><?php echo e($allocation['email']); ?></div>
                                        </td>
                                        <td><?php echo e($allocation['room_no'] ?? '--'); ?> <?php if (!empty($allocation['room_type'])): ?><span class="text-muted small">(<?php echo e($allocation['room_type']); ?>)</span><?php endif; ?></td>
                                        <td><?php echo e($allocation['stay_from']); ?></td>
                                        <td>Rs. <?php echo number_format((float) ($allocation['monthly_fee'] ?? 0), 2); ?></td>
                                        <td><span class="badge text-bg-<?php echo ($allocation['status'] ?? '') === 'allocated' ? 'success' : (($allocation['status'] ?? '') === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo e(ucfirst((string) $allocation['status'])); ?></span></td>
                                        <td><?php echo e($allocation['updated_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="empty-row"><td colspan="8" class="text-center py-4">No room allocations found</td></tr>
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

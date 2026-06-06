<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'book-hostel.php';
$pageHeading = 'Room Allocation';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['allocation_action'])) {
    require_valid_csrf('admin_room_allocation');

    $requestId = (int) $_POST['request_id'];
    $allocationAction = normalize_text($_POST['allocation_action']);
    $remarks = normalize_text($_POST['remarks'] ?? '');

    if ($allocationAction === 'allocate') {
        $roomId = (int) ($_POST['assigned_room_id'] ?? 0);
        $result = allocate_room_request($mysqli, $requestId, $roomId, current_user_id(), $remarks);
        if ($result['ok']) {
            $message = 'Room allocated successfully.';
        } else {
            $message = $result['error'];
            $messageType = 'danger';
        }
    } elseif ($allocationAction === 'reject') {
        if (reject_room_request($mysqli, $requestId, current_user_id(), $remarks)) {
            $message = 'Room request rejected successfully.';
        } else {
            $message = 'Unable to reject the room request.';
            $messageType = 'danger';
        }
    }
}

$pendingRequests = fetch_pending_room_requests($mysqli);
$rooms = fetch_available_rooms($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Allocation</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
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
                <h3 class="mb-1">Room Allocation Desk</h3>
                <p class="mb-0 text-dark">Review pending student requests and assign rooms without exceeding bed capacity.</p>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No pending room requests">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student</th>
                                        <th>Preferred Room</th>
                                        <th>Stay From</th>
                                        <th>Duration</th>
                                        <th>Food</th>
                                        <th>Request Notes</th>
                                        <th>Allocation Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pendingRequests): ?>
                                    <?php foreach ($pendingRequests as $index => $request): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(trim($request['first_name'] . ' ' . ($request['middle_name'] ?? '') . ' ' . $request['last_name'])); ?></div>
                                            <div class="small text-muted"><?php echo e($request['registration_number']); ?> | <?php echo e($request['email']); ?></div>
                                        </td>
                                        <td><?php echo e($request['preferred_room_no'] ?? '--'); ?> <?php if (!empty($request['preferred_room_type'])): ?><span class="text-muted small">(<?php echo e($request['preferred_room_type']); ?>)</span><?php endif; ?></td>
                                        <td><?php echo e($request['stay_from']); ?></td>
                                        <td><?php echo e((string) $request['duration_months']); ?> Months</td>
                                        <td><?php echo ((int) $request['food_status']) === 1 ? 'With Food' : 'Without Food'; ?></td>
                                        <td><?php echo e($request['requested_notes'] ?: '--'); ?></td>
                                        <td style="min-width: 280px;">
                                            <form method="POST" class="d-grid gap-2">
                                                <?php echo csrf_input('admin_room_allocation'); ?>
                                                <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                                                <select name="assigned_room_id" class="form-select form-select-sm">
                                                    <option value="">Choose room</option>
                                                    <?php foreach ($rooms as $room): ?>
                                                    <option value="<?php echo (int) $room['id']; ?>" <?php echo (int) ($request['preferred_room_id'] ?? 0) === (int) $room['id'] ? 'selected' : ''; ?>>
                                                        Room <?php echo e($room['room_no']); ?> | <?php echo (int) $room['available_beds']; ?> left
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <textarea name="remarks" class="form-control form-control-sm" rows="2" placeholder="Remarks for student"></textarea>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="allocation_action" value="allocate" class="btn btn-success btn-sm w-100" data-confirm-message="Allocate the selected room to this student?">Allocate</button>
                                                    <button type="submit" name="allocation_action" value="reject" class="btn btn-danger btn-sm w-100" data-confirm-message="Reject this room request?">Reject</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="empty-row"><td colspan="8" class="text-center py-4">No pending room requests</td></tr>
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

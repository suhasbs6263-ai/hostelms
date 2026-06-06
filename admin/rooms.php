<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'rooms.php';
$pageHeading = 'Manage Rooms';
$message = '';
$messageType = 'success';
$showRoomForm = isset($_GET['open']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    require_valid_csrf('create_room');

    $result = create_room_record($mysqli, [
        'room_no' => $_POST['room_no'] ?? '',
        'room_type' => $_POST['room_type'] ?? '',
        'capacity' => $_POST['capacity'] ?? '',
        'fees' => $_POST['fees'] ?? '',
        'status' => $_POST['status'] ?? 'available',
    ]);

    if ($result['ok']) {
        $message = 'Room saved successfully.';
        $showRoomForm = false;
    } else {
        $message = $result['errors']['general'] ?? implode(' ', array_values($result['errors']));
        $messageType = 'danger';
        $showRoomForm = true;
    }
}

$rooms = fetch_rooms($mysqli, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Rooms</title>
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
                <h3 class="mb-1">Room Management</h3>
                <p class="mb-0 text-dark">Maintain capacity, occupancy, room status, and monthly hostel fees with real-time bed tracking.</p>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <p class="mb-0 text-muted small">Rooms stay available until the number of active allocations reaches the room capacity.</p>
                        <button class="btn btn-success room-toolbar-btn" type="button" data-bs-toggle="collapse" data-bs-target="#roomFormCollapse" aria-expanded="<?php echo $showRoomForm ? 'true' : 'false'; ?>" aria-controls="roomFormCollapse">
                            Add New Room Details
                        </button>
                    </div>
                    <div class="collapse <?php echo $showRoomForm ? 'show' : ''; ?>" id="roomFormCollapse">
                        <div class="card room-form-card">
                            <div class="card-body">
                                <form method="POST">
                                    <?php echo csrf_input('create_room'); ?>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Room No</label>
                                            <input type="text" name="room_no" class="form-control" placeholder="121 or A-101" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Room Type</label>
                                            <input type="text" name="room_type" class="form-control" value="Standard" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Capacity</label>
                                            <input type="number" name="capacity" min="1" step="1" class="form-control" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Fees / Month</label>
                                            <input type="number" name="fees" min="0" step="0.01" class="form-control" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select">
                                                <option value="available">Available</option>
                                                <option value="maintenance">Maintenance</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button name="add_room" class="btn btn-primary">Save Room</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="hostel-datatable" data-page-size="10" data-renumber="true" data-empty-message="No rooms added yet">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Room No.</th>
                                        <th>Room Type</th>
                                        <th>Capacity</th>
                                        <th>Occupied</th>
                                        <th>Available</th>
                                        <th>Fees Per Month</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rooms): ?>
                                    <?php foreach ($rooms as $index => $room): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e($room['room_no']); ?></td>
                                        <td><?php echo e($room['room_type']); ?></td>
                                        <td><?php echo (int) $room['capacity']; ?></td>
                                        <td><?php echo (int) $room['occupied_beds']; ?></td>
                                        <td><?php echo (int) $room['available_beds']; ?></td>
                                        <td>Rs. <?php echo number_format((float) $room['fees'], 2); ?></td>
                                        <td><span class="badge text-bg-<?php echo $room['status'] === 'available' ? 'success' : ($room['status'] === 'full' ? 'danger' : 'secondary'); ?>"><?php echo e(ucfirst($room['status'])); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="empty-row"><td colspan="8" class="text-center py-4">No rooms added yet</td></tr>
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

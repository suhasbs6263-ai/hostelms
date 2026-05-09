<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'rooms.php';
$pageHeading = 'Manage Rooms';
$message = '';
$messageType = 'success';
$roomno = '';
$seater = '';
$fees = '';
$showRoomForm = isset($_GET['open']);

function find_room_column(mysqli $mysqli, array $candidates): ?string
{
    $columns = [];
    $result = $mysqli->query("SHOW COLUMNS FROM rooms");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

if (isset($_POST['add'])) {
    $roomno = trim($_POST['roomno']);
    $seater = trim($_POST['seater']);
    $fees = trim($_POST['fees']);
    $showRoomForm = true;

    if ($roomno === '' || !preg_match('/^[A-Za-z0-9\-\/]+$/', $roomno)) {
        $message = 'Enter a valid room number. You can use letters, numbers, hyphen, or slash.';
        $messageType = 'danger';
    } elseif (!ctype_digit($seater) || (int) $seater <= 0) {
        $message = 'Seater must be a whole number greater than zero.';
        $messageType = 'danger';
    } elseif (!is_numeric($fees) || (float) $fees < 0) {
        $message = 'Fees must be a valid non-negative amount.';
        $messageType = 'danger';
    } else {
        $checkStmt = $mysqli->prepare("SELECT id FROM rooms WHERE room_no = ? LIMIT 1");
        $checkStmt->bind_param('s', $roomno);
        $checkStmt->execute();
        $existingRoom = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existingRoom) {
            $message = 'Room number already exists.';
            $messageType = 'danger';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO rooms (room_no, seater, fees) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $roomno, $seater, $fees);

            if ($stmt->execute()) {
                $message = 'Room added successfully. It is now available in hostel bookings.';
                $roomno = '';
                $seater = '';
                $fees = '';
                $showRoomForm = false;
            } else {
                $message = 'Unable to add room.';
                $messageType = 'danger';
            }

            $stmt->close();
        }
    }
}

$postingDateColumn = find_room_column($mysqli, ['posting_date', 'postingDate', 'created_at', 'createdAt', 'createdon', 'created_on']);
$rooms = fetch_bookable_rooms($mysqli, true);
$dates = [];

if ($postingDateColumn) {
    $result = $mysqli->query("SELECT room_no, `{$postingDateColumn}` AS posting_date FROM rooms ORDER BY room_no ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dates[(string) $row['room_no']] = $row['posting_date'];
        }
        $result->close();
    }
}

foreach ($rooms as &$room) {
    $room['posting_date'] = $dates[(string) $room['room_no']] ?? null;
}
unset($room);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Rooms</title>
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
                <h3 class="mb-1">Room Management</h3>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <p class="mb-0 text-muted small">Add rooms whenever needed. A room stays available for booking until all seats are occupied.</p>
                        <button class="btn btn-success room-toolbar-btn" type="button" data-bs-toggle="collapse" data-bs-target="#roomFormCollapse" aria-expanded="<?php echo $showRoomForm ? 'true' : 'false'; ?>" aria-controls="roomFormCollapse">
                        Add New Room Details
                        </button>
                    </div>
                    <div class="collapse <?php echo $showRoomForm ? 'show' : ''; ?>" id="roomFormCollapse">
                        <div class="card room-form-card">
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Room No</label>
                                            <input type="text" name="roomno" class="form-control" placeholder="121 or A-101" value="<?php echo htmlspecialchars($roomno); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Seater</label>
                                            <input type="number" name="seater" min="1" step="1" class="form-control" value="<?php echo htmlspecialchars($seater); ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fees Per Month</label>
                                            <input type="number" name="fees" min="0" step="0.01" class="form-control" value="<?php echo htmlspecialchars($fees); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button name="add" class="btn btn-primary">Save Room</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No rooms added yet">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Room No.</th>
                                    <th>Total Seats</th>
                                    <th>Occupied</th>
                                    <th>Available</th>
                                    <th>Fees Per Month</th>
                                    <th>Status</th>
                                    <th>Posting Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($rooms): ?>
                                <?php foreach ($rooms as $index => $room): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($room['room_no']); ?></td>
                                    <td><?php echo htmlspecialchars($room['seater']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $room['booked_count']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $room['available_seats']); ?></td>
                                    <td><?php echo htmlspecialchars($room['fees']); ?></td>
                                    <td>
                                        <?php if ($room['is_full']): ?>
                                        <span class="badge text-bg-danger">Full</span>
                                        <?php else: ?>
                                        <span class="badge text-bg-success">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($room['posting_date'] ?? '--'); ?></td>
                                    <td>
                                        <div class="action-icon-group">
                                            <a class="action-icon-btn" href="book-hostel.php?room=<?php echo urlencode($room['room_no']); ?>" title="Book This Room">
                                                <i class="ti ti-bed"></i>
                                            </a>
                                            <button class="action-icon-btn js-copy-room" type="button" data-room="<?php echo htmlspecialchars($room['room_no']); ?>" title="Copy Room Number">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="9" class="text-center py-4">No rooms added yet</td>
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

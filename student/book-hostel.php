<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
require_once('../includes/student-helpers.php');
require_once('../includes/security-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'book-hostel.php';
$pageHeading = 'Apply for Hostel Room';
$message = '';
$messageType = 'success';

$studentId = current_user_id();
$student = fetch_student_by_id($mysqli, $studentId);
$rooms = fetch_available_rooms($mysqli);
$latestRequest = fetch_latest_student_request($mysqli, $studentId);
$allocation = fetch_student_allocation($mysqli, $studentId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    require_valid_csrf('student_room_request');

    $result = submit_room_request($mysqli, $studentId, [
        'preferred_room_id' => $_POST['preferred_room_id'] ?? '',
        'stay_from' => $_POST['stay_from'] ?? '',
        'duration_months' => $_POST['duration_months'] ?? '',
        'food_status' => $_POST['food_status'] ?? '0',
        'requested_notes' => $_POST['requested_notes'] ?? '',
    ]);

    if ($result['ok']) {
        $message = 'Room request submitted successfully. Please wait for admin allocation.';
        $latestRequest = fetch_latest_student_request($mysqli, $studentId);
    } else {
        $message = $result['errors']['general'] ?? 'Please correct the highlighted fields and try again.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
<title>Apply for Hostel Room | Hostel Management System</title>
<link href="../assets/css/styles.min.css" rel="stylesheet">
<link href="../assets/css/hostel-custom.css?v=20260509a" rel="stylesheet">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
<aside class="left-sidebar">
<?php include('../includes/sidebar.php');?>
</aside>
<div class="body-wrapper">
<header class="app-header">
<?php include('../includes/navigation.php');?>
</header>
<div class="container-fluid">
<div class="hostel-page-header">
<h3 class="mb-1">Hostel Room Application</h3>
<p class="mb-0 text-dark">Submit a room request after your account is approved. The admin will allocate a bed based on availability.</p>
</div>
<?php if ($message !== ''): ?>
<div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if (!$student || ($student['status'] ?? 'pending') !== 'approved'): ?>
<div class="alert alert-warning">Your account is not approved yet. Hostel room requests are enabled only after admin approval.</div>
<?php elseif ($allocation): ?>
<div class="alert alert-success">You already have an active room allocation. <a class="alert-link" href="room-details.php">View room details</a>.</div>
<?php else: ?>
  <?php if ($latestRequest && ($latestRequest['status'] ?? '') === 'pending'): ?>
  <div class="alert alert-info">Your previous room request is pending admin review. You cannot submit another request until it is processed.</div>
  <?php elseif ($latestRequest && ($latestRequest['status'] ?? '') === 'rejected'): ?>
  <div class="alert alert-danger">Your previous room request was rejected. You may submit a fresh request below. <?php if (!empty($latestRequest['admin_remarks'])): ?><br><strong>Admin remarks:</strong> <?php echo e($latestRequest['admin_remarks']); ?><?php endif; ?></div>
  <?php endif; ?>
  <?php if (!$rooms): ?>
  <div class="alert alert-warning">No rooms with free beds are available right now. Please check again later or contact the hostel office.</div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-4">
      <div class="card content-card h-100">
        <div class="card-body">
          <h4 class="card-title mb-3">Student Snapshot</h4>
          <p class="mb-2"><strong>Name:</strong> <?php echo e(student_full_name($student)); ?></p>
          <p class="mb-2"><strong>Reg No:</strong> <?php echo e($student['registration_number']); ?></p>
          <p class="mb-2"><strong>Email:</strong> <?php echo e($student['email']); ?></p>
          <p class="mb-0"><strong>Phone:</strong> <?php echo e($student['phone']); ?></p>
        </div>
      </div>
    </div>
    <div class="col-lg-8 mt-4 mt-lg-0">
      <div class="card content-card">
        <div class="card-body">
          <form method="POST">
            <?php echo csrf_input('student_room_request'); ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Preferred Room</label>
                <select name="preferred_room_id" class="form-select" required <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>>
                  <option value="">Choose room</option>
                  <?php foreach ($rooms as $room): ?>
                  <option value="<?php echo (int) $room['id']; ?>">
                    Room <?php echo e($room['room_no']); ?> | <?php echo e($room['room_type']); ?> | <?php echo (int) $room['available_beds']; ?> beds left | Rs. <?php echo number_format((float) $room['fees'], 2); ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Stay From</label>
                <input type="date" name="stay_from" class="form-control" min="<?php echo date('Y-m-d'); ?>" required <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>>
              </div>
              <div class="col-md-3">
                <label class="form-label">Duration (Months)</label>
                <input type="number" name="duration_months" class="form-control" min="1" max="24" value="6" required <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>>
              </div>
              <div class="col-md-4">
                <label class="form-label">Food Preference</label>
                <select name="food_status" class="form-select" <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>>
                  <option value="1">With Food</option>
                  <option value="0">Without Food</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Notes for Admin</label>
                <textarea name="requested_notes" class="form-control" rows="3" placeholder="Any preference or hostel note" <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>></textarea>
              </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
              <button type="submit" name="submit" class="btn btn-primary" <?php echo (($latestRequest && ($latestRequest['status'] ?? '') === 'pending') || !$rooms) ? 'disabled' : ''; ?>>Submit Room Request</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

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

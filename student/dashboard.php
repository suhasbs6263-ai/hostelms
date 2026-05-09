<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login();

$portalRole = 'student';
$activePage = 'dashboard.php';
$pageHeading = 'Student Dashboard';

$totalRooms = 0;
$availableRooms = 0;
$myBookings = 0;
$latestBooking = null;

$result = $mysqli->query("SELECT COUNT(*) AS total FROM rooms");
if ($result) {
    $totalRooms = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$result = $mysqli->query("SELECT COUNT(*) AS total FROM rooms WHERE room_no NOT IN (SELECT DISTINCT roomno FROM registration)");
if ($result) {
    $availableRooms = (int) ($result->fetch_assoc()['total'] ?? 0);
}

$email = $_SESSION['login'];

$stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM registration WHERE emailid = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$myBookingsResult = $stmt->get_result();
if ($myBookingsResult) {
    $myBookings = (int) ($myBookingsResult->fetch_assoc()['total'] ?? 0);
}
$stmt->close();

$stmt = $mysqli->prepare("SELECT roomno, seater, feespm, stayfrom, duration, course, foodstatus FROM registration WHERE emailid = ? ORDER BY stayfrom DESC LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$latestResult = $stmt->get_result();
if ($latestResult) {
    $latestBooking = $latestResult->fetch_assoc();
}
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>
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
        <h3 class="mb-1">Welcome back</h3>
        <p class="mb-0 text-dark">Use this dashboard to manage your hostel registration, booking, and room details.</p>
      </div>
      <div class="row">
        <div class="col-md-4">
          <div class="card stat-card">
            <div class="card-body">
              <p class="text-muted mb-2">Total Rooms</p>
              <h2 class="display-6"><?php echo $totalRooms; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card">
            <div class="card-body">
              <p class="text-muted mb-2">Available Rooms</p>
              <h2 class="display-6"><?php echo $availableRooms; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card stat-card">
            <div class="card-body">
              <p class="text-muted mb-2">My Bookings</p>
              <h2 class="display-6"><?php echo $myBookings; ?></h2>
            </div>
          </div>
        </div>
      </div>

      <div class="card content-card mt-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4 class="card-title mb-1">Latest Booking</h4>
              <p class="text-muted mb-0">Your most recent hostel allocation summary.</p>
            </div>
            <a href="book-hostel.php" class="btn btn-primary">Book Hostel</a>
          </div>
          <?php if ($latestBooking): ?>
          <div class="row g-3">
            <div class="col-md-4"><strong>Room No:</strong> <?php echo htmlspecialchars($latestBooking['roomno']); ?></div>
            <div class="col-md-4"><strong>Course:</strong> <?php echo htmlspecialchars($latestBooking['course']); ?></div>
            <div class="col-md-4"><strong>Stay From:</strong> <?php echo htmlspecialchars($latestBooking['stayfrom']); ?></div>
            <div class="col-md-4"><strong>Seater:</strong> <?php echo htmlspecialchars($latestBooking['seater']); ?></div>
            <div class="col-md-4"><strong>Duration:</strong> <?php echo htmlspecialchars($latestBooking['duration']); ?> month(s)</div>
            <div class="col-md-4"><strong>Food:</strong> <?php echo (int) $latestBooking['foodstatus'] === 1 ? 'Required' : 'Not Required'; ?></div>
          </div>
          <div class="mt-4">
            <a href="room-details.php" class="btn btn-light-primary">View Full Room Details</a>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <h4>No hostel room booked yet</h4>
            <p class="text-muted">Complete the booking form to see your room details here.</p>
            <a href="book-hostel.php" class="btn btn-primary">Go to Booking Form</a>
          </div>
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

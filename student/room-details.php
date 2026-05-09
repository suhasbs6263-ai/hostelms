<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login();

$portalRole = 'student';
$activePage = 'room-details.php';
$pageHeading = 'My Room Details';
$studentEmail = $_SESSION['login'];

$booking = null;
$stmt = $mysqli->prepare("SELECT * FROM registration WHERE emailid = ? ORDER BY stayfrom DESC LIMIT 1");
$stmt->bind_param('s', $studentEmail);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $booking = $result->fetch_assoc();
}
$stmt->close();

function booking_value(array $booking, array $keys, string $fallback = '--'): string
{
    foreach ($keys as $key) {
        if (isset($booking[$key]) && trim((string) $booking[$key]) !== '') {
            return (string) $booking[$key];
        }
    }

    return $fallback;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Room Details</title>
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
                <h3 class="mb-1">My Room Details</h3>
            </div>
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Room booked successfully.</div>
            <?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <?php if ($booking): ?>
                    <h4 class="section-card-title mb-3">Detail About My Hostel Room</h4>
                    <div class="table-responsive">
                        <table class="table room-detail-grid">
                            <tbody>
                                <tr>
                                    <th>Date &amp; Time of Registration</th>
                                    <td><?php echo htmlspecialchars(booking_value($booking, ['postingDate', 'posting_date', 'created_at', 'createdAt', 'regDate', 'regdate'])); ?></td>
                                    <th>Starting Date</th>
                                    <td><?php echo htmlspecialchars($booking['stayfrom']); ?></td>
                                    <th>Seater</th>
                                    <td><?php echo htmlspecialchars($booking['seater']); ?></td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td><?php echo htmlspecialchars($booking['duration']); ?> Months</td>
                                    <th>Food Status</th>
                                    <td><?php echo (int) $booking['foodstatus'] === 1 ? 'Required' : 'Not Required'; ?></td>
                                    <th>Fees Per Month</th>
                                    <td><?php echo htmlspecialchars($booking['feespm']); ?></td>
                                </tr>
                                <tr>
                                    <th>Hostel Room No.</th>
                                    <td><?php echo htmlspecialchars($booking['roomno']); ?></td>
                                    <th>Registration Number</th>
                                    <td><?php echo htmlspecialchars(booking_value($booking, ['regno', 'regNo'])); ?></td>
                                    <th>Full Name</th>
                                    <td><?php echo htmlspecialchars(trim($booking['firstName'] . ' ' . $booking['middleName'] . ' ' . $booking['lastName'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?php echo htmlspecialchars($booking['contactno']); ?></td>
                                    <th>Gender</th>
                                    <td><?php echo htmlspecialchars($booking['gender']); ?></td>
                                    <th>Email Address</th>
                                    <td><?php echo htmlspecialchars($booking['emailid']); ?></td>
                                </tr>
                                <tr>
                                    <th>Emergency Contact</th>
                                    <td><?php echo htmlspecialchars($booking['egycontactno']); ?></td>
                                    <th>Guardian Name</th>
                                    <td><?php echo htmlspecialchars($booking['guardianName']); ?></td>
                                    <th>Guardian Relation</th>
                                    <td><?php echo htmlspecialchars($booking['guardianRelation']); ?></td>
                                </tr>
                                <tr>
                                    <th>Guardian Contact No</th>
                                    <td><?php echo htmlspecialchars($booking['guardianContactno']); ?></td>
                                    <th>Selected Course</th>
                                    <td><?php echo htmlspecialchars($booking['course']); ?></td>
                                    <th></th>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th>Current Address</th>
                                    <td colspan="2"><?php echo nl2br(htmlspecialchars(trim($booking['corresAddress'] . "\n" . $booking['corresCity'] . "\n" . $booking['corresPincode']))); ?></td>
                                    <th>Permanent Address</th>
                                    <td colspan="2"><?php echo nl2br(htmlspecialchars(trim($booking['pmntAddress'] . "\n" . $booking['pmntCity'] . "\n" . $booking['pmntPincode']))); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <h4>No room has been booked yet</h4>
                        <p class="text-muted">Once you complete the hostel booking form, your room details will appear here.</p>
                        <a href="book-hostel.php" class="btn btn-primary">Book Hostel</a>
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

<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/course-helpers.php');
require_once('../includes/room-helpers.php');
check_login();

$portalRole = 'student';
$activePage = 'book-hostel.php';
$pageHeading = 'Book Hostel';
$studentEmail = $_SESSION['login'];

$message = '';
$messageType = 'success';

$rooms = fetch_bookable_rooms($mysqli);

$courses = fetch_courses($mysqli);

$existingBooking = null;
$bookingStmt = $mysqli->prepare("SELECT * FROM registration WHERE emailid = ? LIMIT 1");
$bookingStmt->bind_param('s', $studentEmail);
$bookingStmt->execute();
$bookingResult = $bookingStmt->get_result();
if ($bookingResult) {
    $existingBooking = $bookingResult->fetch_assoc();
}
$bookingStmt->close();

if (isset($_POST['submit'])) {
    if ($existingBooking) {
        $message = 'You have already booked a hostel room.';
        $messageType = 'warning';
    } else {
        $roomno = trim($_POST['room']);
        $foodstatus = trim($_POST['foodstatus']);
        $stayfrom = trim($_POST['stayf']);
        $duration = trim($_POST['duration']);
        $course = trim($_POST['course']);
        $regno = trim($_POST['regno']);
        $fname = trim($_POST['fname']);
        $mname = trim($_POST['mname']);
        $lname = trim($_POST['lname']);
        $gender = trim($_POST['gender']);
        $contactno = trim($_POST['contact']);
        $emailid = trim($_POST['email']);
        $emcntno = trim($_POST['econtact']);
        $gname = trim($_POST['gname']);
        $grelation = trim($_POST['grelation']);
        $gcontact = trim($_POST['gcontact']);
        $caddress = trim($_POST['address']);
        $ccity = trim($_POST['city']);
        $cpincode = trim($_POST['pincode']);
        $paddress = trim($_POST['paddress']);
        $pcity = trim($_POST['pcity']);
        $ppincode = trim($_POST['ppincode']);

        $roomDetails = get_room_capacity($mysqli, $roomno);

        if (!$roomDetails) {
            $message = 'Selected room does not exist.';
            $messageType = 'danger';
        } elseif ($roomDetails['available_seats'] <= 0) {
            $message = 'Selected room is already full. Please choose another room.';
            $messageType = 'danger';
            $rooms = fetch_bookable_rooms($mysqli);
        } else {
            $seater = (string) $roomDetails['seater'];
            $feespm = (string) $roomDetails['fees'];
            $query = "INSERT INTO registration (
                roomno, seater, feespm, foodstatus, stayfrom, duration, course, regno,
                firstName, middleName, lastName, gender, contactno, emailid, egycontactno,
                guardianName, guardianRelation, guardianContactno, corresAddress, corresCity,
                corresPincode, pmntAddress, pmntCity, pmntPincode
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $mysqli->prepare($query);
            $types = str_repeat('s', 24);
            $stmt->bind_param(
                $types,
                $roomno,
                $seater,
                $feespm,
                $foodstatus,
                $stayfrom,
                $duration,
                $course,
                $regno,
                $fname,
                $mname,
                $lname,
                $gender,
                $contactno,
                $emailid,
                $emcntno,
                $gname,
                $grelation,
                $gcontact,
                $caddress,
                $ccity,
                $cpincode,
                $paddress,
                $pcity,
                $ppincode
            );

            if ($stmt->execute()) {
                header('Location: room-details.php?success=1');
                exit;
            }

            $message = 'Unable to complete hostel booking.';
            $messageType = 'danger';
            $stmt->close();
        }
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

<title>Book Hostel | Hostel Management System</title>
<link href="../assets/css/styles.min.css" rel="stylesheet">
<link href="../assets/css/hostel-custom.css?v=20260402b" rel="stylesheet">

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
<h3 class="mb-1">Hostel Booking Form</h3>
<p class="mb-0 text-dark">Fill in all details to request room allocation.</p>
</div>
<?php if ($message !== ''): ?>
<div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($existingBooking): ?>
<div class="alert alert-primary">You have already booked room <strong><?php echo htmlspecialchars($existingBooking['roomno']); ?></strong>. <a href="room-details.php" class="alert-link">View room details</a>.</div>
<?php endif; ?>
<?php if (!$existingBooking && !$rooms): ?>
<div class="alert alert-warning">No rooms currently have free seats. Please contact the admin or add more rooms first.</div>
<?php endif; ?>
<?php if (!$existingBooking && $rooms): ?>
<div class="alert alert-light border">Rooms stay available until all seats are filled. Choose any room that still has seats left.</div>
<?php endif; ?>

<form method="POST">

<div class="row">

<!-- Room -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Room No</label>
<select name="room" id="room" class="form-select" onChange="getSeater(this.value);" onBlur="checkAvailability();" required <?php echo ($existingBooking || !$rooms) ? 'disabled' : ''; ?>>
<option value="">Select room</option>
<?php foreach ($rooms as $room): ?>
<option value="<?php echo htmlspecialchars($room['room_no']); ?>">Room <?php echo htmlspecialchars($room['room_no']); ?> - <?php echo htmlspecialchars((string) $room['booked_count']); ?>/<?php echo htmlspecialchars((string) $room['seater']); ?> occupied, <?php echo htmlspecialchars((string) $room['available_seats']); ?> left</option>
<?php endforeach; ?>
</select>
<span id="room-availability-status" class="small"></span>
</div>
</div>
</div>

<!-- Seater -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Seater</label>
<input type="number" id="seater" name="seater" class="form-control" readonly required>
</div>
</div>
</div>

<!-- Fees -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Fees</label>
<input type="number" id="fpm" name="fpm" class="form-control" readonly required>
</div>
</div>
</div>

<!-- Food -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Food Status</label>
<select name="foodstatus" id="foodstatus" class="form-select" onchange="updateAmount();">
<option value="1">With Food</option>
<option value="0">Without Food</option>
</select>
</div>
</div>
</div>

<!-- Stay -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Stay From</label>
<input type="date" name="stayf" class="form-control" required>
</div>
</div>
</div>

<!-- Duration -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Duration</label>
<select name="duration" id="duration" class="form-select" onchange="updateAmount();" required>
<option value="">Choose...</option>
<?php for ($month = 1; $month <= 12; $month++): ?>
<option value="<?php echo $month; ?>"><?php echo $month; ?> Month</option>
<?php endfor; ?>
</select>
</div>
</div>
</div>

<!-- Course -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Course</label>
<?php if ($courses): ?>
<select name="course" class="form-select" required>
<option value="">Choose course</option>
<?php foreach ($courses as $course): ?>
<option value="<?php echo htmlspecialchars($course['course_fn']); ?>"><?php echo htmlspecialchars($course['course_fn']); ?> (<?php echo htmlspecialchars($course['course_sn']); ?>)</option>
<?php endforeach; ?>
</select>
<?php else: ?>
<input type="text" name="course" class="form-control" required>
<?php endif; ?>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Total Amount</label>
<input type="text" id="ta" class="form-control" readonly>
</div>
</div>
</div>

<!-- Student Info -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Reg No</label>
<input type="text" name="regno" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>First Name</label>
<input type="text" name="fname" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Middle Name</label>
<input type="text" name="mname" class="form-control">
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Last Name</label>
<input type="text" name="lname" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Gender</label>
<select name="gender" class="form-select" required>
<option value="">Select gender</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Others">Others</option>
</select>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Contact</label>
<input type="text" name="contact" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Email</label>
<input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($studentEmail); ?>" readonly required>
</div>
</div>
</div>

<!-- Guardian -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Emergency Contact</label>
<input type="text" name="econtact" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Guardian Name</label>
<input type="text" name="gname" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Relation</label>
<input type="text" name="grelation" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Guardian Contact</label>
<input type="text" name="gcontact" class="form-control" required>
</div>
</div>
</div>

<!-- Address -->
<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Address</label>
<input type="text" name="address" id="address" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>City</label>
<input type="text" name="city" id="city" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Pincode</label>
<input type="text" name="pincode" id="pincode" class="form-control" required>
</div>
</div>
</div>

<div class="col-12">
<div class="card content-card">
<div class="card-body">
<div class="form-check">
<input class="form-check-input" type="checkbox" value="1" id="adcheck" name="adcheck">
<label class="form-check-label" for="adcheck">My permanent address is same as current address</label>
</div>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Permanent Address</label>
<input type="text" name="paddress" id="paddress" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Permanent City</label>
<input type="text" name="pcity" id="pcity" class="form-control" required>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card content-card">
<div class="card-body">
<label>Permanent Pincode</label>
<input type="text" name="ppincode" id="ppincode" class="form-control" required>
</div>
</div>
</div>

</div>

<div class="mt-4 text-center">
<button name="submit" class="btn btn-primary" <?php echo ($existingBooking || !$rooms) ? 'disabled' : ''; ?>>Book Hostel</button>
</div>

</form>

<?php include('../includes/footer.php'); ?>
</div>
</div>

<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebarmenu.js"></script>
<script src="../assets/js/app.min.js"></script>
<script>
function getSeater(val) {
  if (!val) {
    $('#seater').val('');
    $('#fpm').val('');
    $('#ta').val('');
    return;
  }

  $.ajax({
    type: "POST",
    url: "get-seater.php",
    data: { roomid: val },
    success: function(data) {
      $('#seater').val(data);
    }
  });

  $.ajax({
    type: "POST",
    url: "get-seater.php",
    data: { rid: val },
    success: function(data) {
      $('#fpm').val(data);
      updateAmount();
    }
  });
}

function checkAvailability() {
  $.ajax({
    url: "check-availability.php",
    type: "POST",
    data: { roomno: $("#room").val() },
    success: function(data) {
      $("#room-availability-status").html(data);
    }
  });
}

function updateAmount() {
  var fees = parseFloat($("#fpm").val() || 0);
  var duration = parseFloat($("#duration").val() || 0);
  var foodStatus = $("#foodstatus").val();
  var total = fees * duration;

  if (foodStatus === "1") {
    total += 211 * duration;
  }

  $("#ta").val(total > 0 ? total.toFixed(2) : "");
}

$('#adcheck').on('change', function() {
  if ($(this).is(':checked')) {
    $('#paddress').val($('#address').val());
    $('#pcity').val($('#city').val());
    $('#ppincode').val($('#pincode').val());
  }
});
</script>

</body>
</html>

<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/course-helpers.php');
require_once('../includes/room-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'book-hostel.php';
$pageHeading = 'Book Hostel';
$message = '';
$messageType = 'success';
$selectedRoom = trim($_GET['room'] ?? '');

$courses = [];
$rooms = fetch_bookable_rooms($mysqli);

$courses = fetch_courses($mysqli);

if (isset($_POST['submit'])) {
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
        $message = 'This room is already full. Please choose another room.';
        $messageType = 'danger';
    } else {
        $seater = (string) $roomDetails['seater'];
        $feespm = (string) $roomDetails['fees'];
        $stmt = $mysqli->prepare("INSERT INTO registration (
            roomno, seater, feespm, foodstatus, stayfrom, duration, course, regno,
            firstName, middleName, lastName, gender, contactno, emailid, egycontactno,
            guardianName, guardianRelation, guardianContactno, corresAddress, corresCity,
            corresPincode, pmntAddress, pmntCity, pmntPincode
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
            $message = 'Hostel booked successfully.';
            $selectedRoom = '';
            $rooms = fetch_bookable_rooms($mysqli);
        } else {
            $message = 'Unable to complete booking.';
            $messageType = 'danger';
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Hostel</title>
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
                <h3 class="mb-1">Hostel Bookings</h3>
                <p class="mb-0 text-dark">Allot hostel rooms to students from the admin panel.</p>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="mb-0 text-muted small">Each room remains bookable until all its seats are occupied.</p>
                <a href="rooms.php?open=1" class="btn btn-outline-primary btn-sm">Add New Room</a>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if (!$rooms): ?>
            <div class="alert alert-warning">No rooms currently have free seats. Add a new room or free up an existing room before creating another booking.</div>
            <?php endif; ?>
            <form method="POST">
                <div class="row">
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Room Number</label><select class="form-select" name="room" id="room" onchange="getSeater(this.value);" onblur="checkAvailability();" required <?php echo !$rooms ? 'disabled' : ''; ?>><option value="">Select...</option><?php foreach ($rooms as $room): ?><option value="<?php echo htmlspecialchars($room['room_no']); ?>" <?php echo $selectedRoom === (string) $room['room_no'] ? 'selected' : ''; ?>>Room <?php echo htmlspecialchars($room['room_no']); ?> - <?php echo htmlspecialchars((string) $room['booked_count']); ?>/<?php echo htmlspecialchars((string) $room['seater']); ?> occupied, <?php echo htmlspecialchars((string) $room['available_seats']); ?> left</option><?php endforeach; ?></select><span id="room-availability-status" class="small"></span></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Start Date</label><input type="date" name="stayf" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Seater</label><input type="text" id="seater" name="seater" class="form-control" readonly required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Total Duration</label><select class="form-select" id="duration" name="duration" onchange="updateAmount();" required><option value="">Choose...</option><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?> Month</option><?php endfor; ?></select></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Food Status</label><div class="form-check"><input class="form-check-input" type="radio" id="food1" value="1" name="foodstatus" onchange="updateAmount();" checked><label class="form-check-label" for="food1">Required</label></div><div class="form-check"><input class="form-check-input" type="radio" id="food0" value="0" name="foodstatus" onchange="updateAmount();"><label class="form-check-label" for="food0">Not Required</label></div></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Total Fees Per Month</label><input type="text" id="fpm" name="fpm" class="form-control" readonly required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Total Amount</label><input type="text" id="ta" class="form-control" readonly></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Registration Number</label><input type="text" name="regno" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>First Name</label><input type="text" name="fname" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Middle Name</label><input type="text" name="mname" class="form-control"></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Last Name</label><input type="text" name="lname" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Email</label><input type="email" name="email" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Gender</label><select name="gender" class="form-select" required><option value="">Select gender</option><option value="Male">Male</option><option value="Female">Female</option><option value="Others">Others</option></select></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Contact Number</label><input type="text" name="contact" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Emergency Contact Number</label><input type="text" name="econtact" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Preferred Course</label><?php if ($courses): ?><select class="form-select" name="course" required><option value="">Please Select...</option><?php foreach ($courses as $course): ?><option value="<?php echo htmlspecialchars($course['course_fn']); ?>"><?php echo htmlspecialchars($course['course_fn']); ?> (<?php echo htmlspecialchars($course['course_sn']); ?>)</option><?php endforeach; ?></select><?php else: ?><input type="text" name="course" class="form-control" required><?php endif; ?></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Guardian Name</label><input type="text" name="gname" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Relation</label><input type="text" name="grelation" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Guardian Contact Number</label><input type="text" name="gcontact" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Address</label><input type="text" name="address" id="address" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>City</label><input type="text" name="city" id="city" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Postal Code</label><input type="text" name="pincode" id="pincode" class="form-control" required></div></div></div>
                    <div class="col-12"><div class="card content-card"><div class="card-body"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" id="adcheck"><label class="form-check-label" for="adcheck">My permanent address is same as current address</label></div></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Permanent Address</label><input type="text" name="paddress" id="paddress" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Permanent City</label><input type="text" name="pcity" id="pcity" class="form-control" required></div></div></div>
                    <div class="col-md-4"><div class="card content-card"><div class="card-body"><label>Permanent Postal Code</label><input type="text" name="ppincode" id="ppincode" class="form-control" required></div></div></div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" name="submit" class="btn btn-success" <?php echo !$rooms ? 'disabled' : ''; ?>>Submit</button>
                    <button type="reset" class="btn btn-dark">Reset</button>
                </div>
            </form>
            <?php include('../includes/footer.php'); ?>
        </div>
    </div>
</div>
<script src="../assets/libs/jquery/dist/jquery.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebarmenu.js"></script>
<script src="../assets/js/app.min.js"></script>
<script>
function getSeater(val) {
  $.post("../student/get-seater.php", { roomid: val }, function(data) {
    $("#seater").val(data);
  });

  $.post("../student/get-seater.php", { rid: val }, function(data) {
    $("#fpm").val(data);
    updateAmount();
  });
}

function checkAvailability() {
  $.post("../student/check-availability.php", { roomno: $("#room").val() }, function(data) {
    $("#room-availability-status").html(data);
  });
}

function updateAmount() {
  var fees = parseFloat($("#fpm").val() || 0);
  var duration = parseFloat($("#duration").val() || 0);
  var foodStatus = $("input[name='foodstatus']:checked").val();
  var total = fees * duration;

  if (foodStatus === "1") {
    total += 211 * duration;
  }

  $("#ta").val(total > 0 ? total.toFixed(2) : "");
}

$("#adcheck").on("change", function() {
  if ($(this).is(":checked")) {
    $("#paddress").val($("#address").val());
    $("#pcity").val($("#city").val());
    $("#ppincode").val($("#pincode").val());
  }
});

if ($("#room").val()) {
  getSeater($("#room").val());
}
</script>
</body>
</html>

<?php
session_start();
require_once('../includes/dbconn.php');

$portalRole = 'student';
$activePage = 'register.php';
$pageHeading = 'Student Registration';

$message = '';
$messageType = 'success';

if (isset($_POST['submit'])) {
    $regno = trim($_POST['regno']);
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $gender = trim($_POST['gender']);
    $contactno = trim($_POST['contact']);
    $emailid = trim($_POST['email']);
    $password = md5($_POST['password']);

    $checkStmt = $mysqli->prepare("SELECT id FROM userregistration WHERE email = ? LIMIT 1");
    $checkStmt->bind_param('s', $emailid);
    $checkStmt->execute();
    $existingUser = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existingUser) {
        $message = 'A student with this email is already registered.';
        $messageType = 'danger';
    } else {
        $query = "INSERT INTO userregistration (regNo, firstName, middleName, lastName, gender, contactNo, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssssssss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $password);

        if ($stmt->execute()) {
            $message = 'Student has been registered successfully.';
        } else {
            $message = 'Unable to register student right now.';
            $messageType = 'danger';
        }

        $stmt->close();
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

<title>Student Registration | Hostel Management System</title>
<link href="../assets/css/styles.min.css" rel="stylesheet">
<link href="../assets/css/hostel-custom.css?v=20260402b" rel="stylesheet">

<script type="text/javascript">
function valid(){
if(document.registration.password.value!=document.registration.cpassword.value)
{
alert("Password and Confirm Password does not match");
document.registration.cpassword.focus();
return false;
}
return true;
}
</script>

</head>

<body>

<div class="preloader">
<div class="lds-ripple">
<div class="lds-pos"></div>
<div class="lds-pos"></div>
</div>
</div>

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
<h3 class="mb-1">Student Registration Form</h3>
<p class="mb-0 text-dark">Create a student account before hostel booking.</p>
</div>
<?php if ($message !== ''): ?>
<div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form method="POST" name="registration" onSubmit="return valid();">

<div class="row">

<!-- Registration Number -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Registration Number</h4>
<input type="text" name="regno" id="regno" class="form-control" required>
</div>
</div>
</div>

<!-- First Name -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">First Name</h4>
<input type="text" name="fname" id="fname" class="form-control" required>
</div>
</div>
</div>

<!-- Middle Name -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Middle Name</h4>
<input type="text" name="mname" id="mname" class="form-control">
</div>
</div>
</div>

<!-- Last Name -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Last Name</h4>
<input type="text" name="lname" id="lname" class="form-control" required>
</div>
</div>
</div>

<!-- Gender -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Gender</h4>
<select class="form-select" id="gender" name="gender" required>
<option value="">Choose...</option>
<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Others">Others</option>
</select>
</div>
</div>
</div>

<!-- Contact -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Contact Number</h4>
<input type="number" name="contact" id="contact" class="form-control" required>
</div>
</div>
</div>

<!-- Email -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Email ID</h4>
<input type="email" name="email" id="email" class="form-control" onBlur="checkAvailability();" required>
<span id="user-availability-status"></span>
</div>
</div>
</div>

<!-- Password -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Password</h4>
<input type="password" name="password" id="password" class="form-control" required>
</div>
</div>
</div>

<!-- Confirm Password -->
<div class="col-sm-12 col-md-6 col-lg-4">
<div class="card content-card form-field-card">
<div class="card-body">
<h4 class="card-title">Confirm Password</h4>
<input type="password" name="cpassword" id="cpassword" class="form-control" required>
</div>
</div>
</div>

</div>

<div class="form-actions">
<div class="text-center">
<button type="submit" name="submit" class="btn btn-success">Register</button>
<button type="reset" class="btn btn-danger">Reset</button>
</div>
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
function checkAvailability() {
  var email = $("#email").val();
  if (!email) {
    $("#user-availability-status").html("");
    return;
  }

  $.ajax({
    url: "check-availability.php",
    type: "POST",
    data: { emailid: email },
    success: function (data) {
      $("#user-availability-status").html(data);
    }
  });
}
</script>

</body>
</html>

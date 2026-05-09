<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'register-student.php';
$pageHeading = 'Register Student';

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
        $message = 'A student with this email already exists.';
        $messageType = 'danger';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO userregistration (regNo, firstName, middleName, lastName, gender, contactNo, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssss', $regno, $fname, $mname, $lname, $gender, $contactno, $emailid, $password);

        if ($stmt->execute()) {
            $message = 'Student registered successfully.';
        } else {
            $message = 'Unable to register student.';
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
    <title>Register Student</title>
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
                <h3 class="mb-1">Student Registration Form</h3>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" onsubmit="return validatePasswords();">
                <div class="row">
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Registration Number</h4><input type="text" name="regno" id="regno" class="form-control" required></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">First Name</h4><input type="text" name="fname" class="form-control" required></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Middle Name</h4><input type="text" name="mname" class="form-control"></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Last Name</h4><input type="text" name="lname" class="form-control" required></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Gender</h4><select name="gender" class="form-select" required><option value="">Choose...</option><option value="Male">Male</option><option value="Female">Female</option><option value="Others">Others</option></select></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Contact Number</h4><input type="text" name="contact" class="form-control" required></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Email ID</h4><input type="email" name="email" id="email" class="form-control" onblur="checkAvailability();" required><span id="user-availability-status"></span></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Password</h4><input type="password" name="password" id="password" class="form-control" required></div></div>
                    </div>
                    <div class="col-sm-12 col-md-6 col-lg-4">
                        <div class="card content-card form-field-card"><div class="card-body"><h4 class="card-title">Confirm Password</h4><input type="password" id="cpassword" class="form-control" required></div></div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button type="submit" name="submit" class="btn btn-success">Register</button>
                    <button type="reset" class="btn btn-danger">Reset</button>
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
function validatePasswords() {
  if ($("#password").val() !== $("#cpassword").val()) {
    alert("Password and Confirm Password does not match");
    $("#cpassword").focus();
    return false;
  }
  return true;
}

function checkAvailability() {
  $.ajax({
    url: "../student/check-availability.php",
    type: "POST",
    data: { emailid: $("#email").val() },
    success: function(data) {
      $("#user-availability-status").html(data);
    }
  });
}
</script>
</body>
</html>

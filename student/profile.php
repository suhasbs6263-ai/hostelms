<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/student-helpers.php');
require_once('../includes/course-helpers.php');
require_once('../includes/security-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'profile.php';
$pageHeading = 'My Profile';
$studentId = current_user_id();
$student = fetch_student_by_id($mysqli, $studentId);
$courses = fetch_courses($mysqli);
$message = '';
$messageType = 'success';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    require_valid_csrf('student_profile');

    $result = update_student_profile($mysqli, $studentId, $_POST);
    if ($result['ok']) {
        $message = 'Profile updated successfully.';
        $student = fetch_student_by_id($mysqli, $studentId);
    } else {
        $errors = $result['errors'];
        $message = $errors['general'] ?? 'Please correct the highlighted fields.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header"><h3 class="mb-1">My Profile</h3><p class="mb-0 text-dark">Keep your hostel contact and guardian details up to date.</p></div>
            <?php if ($message !== ''): ?><div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div><?php endif; ?>
            <div class="card content-card">
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrf_input('student_profile'); ?>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?php echo e($student['first_name'] ?? ''); ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control" value="<?php echo e($student['middle_name'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?php echo e($student['last_name'] ?? ''); ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" value="<?php echo e($student['phone'] ?? ''); ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Emergency Contact</label><input type="text" name="emergency_contact" class="form-control <?php echo isset($errors['emergency_contact']) ? 'is-invalid' : ''; ?>" value="<?php echo e($student['emergency_contact'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Course</label><select name="course_id" class="form-select"><option value="">Choose course</option><?php foreach ($courses as $course): ?><option value="<?php echo (int) $course['id']; ?>" <?php echo (int) ($student['course_id'] ?? 0) === (int) $course['id'] ? 'selected' : ''; ?>><?php echo e($course['course_fn']); ?> (<?php echo e($course['course_sn']); ?>)</option><?php endforeach; ?></select></div>
                            <div class="col-md-4"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control" value="<?php echo e($student['guardian_name'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Guardian Relation</label><input type="text" name="guardian_relation" class="form-control" value="<?php echo e($student['guardian_relation'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control <?php echo isset($errors['guardian_phone']) ? 'is-invalid' : ''; ?>" value="<?php echo e($student['guardian_phone'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Address</label><textarea name="address_line" class="form-control" rows="3"><?php echo e($student['address_line'] ?? ''); ?></textarea></div>
                            <div class="col-md-3"><label class="form-label">City</label><input type="text" name="city" class="form-control" value="<?php echo e($student['city'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Pincode</label><input type="text" name="pincode" class="form-control" value="<?php echo e($student['pincode'] ?? ''); ?>"></div>
                        </div>
                        <div class="mt-4 text-end"><button type="submit" name="save_profile" class="btn btn-primary">Save Profile</button></div>
                    </form>
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

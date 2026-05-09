<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/course-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'manage-courses.php';
$pageHeading = 'Manage Courses';
$message = '';
$messageType = 'success';
$courseFull = '';
$courseShort = '';

resolve_course_schema($mysqli);

if (isset($_POST['submit'])) {
    $courseFull = trim($_POST['course_fn']);
    $courseShort = trim($_POST['course_sn']);

    if ($courseFull !== '' && $courseShort !== '') {
        $insertResult = insert_course_record($mysqli, $courseFull, $courseShort);

        if ($insertResult['ok']) {
            if (!empty($insertResult['duplicate'])) {
                $message = 'Course already exists. The existing record is shown in the list.';
                $messageType = 'warning';
            } else {
                $message = 'Course added successfully.';
                $courseFull = '';
                $courseShort = '';
            }
        } else {
            $message = 'Unable to add course: ' . $insertResult['error'];
            $messageType = 'danger';
        }
    } else {
        $message = 'Please enter both course name and short name.';
        $messageType = 'danger';
    }
}

$courses = fetch_courses($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Courses</title>
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
                <h3 class="mb-1">Manage Courses</h3>
            </div>
            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-7">
                    <div class="card content-card">
                        <div class="card-body">
                            <div class="hostel-datatable" data-page-size="5" data-empty-message="No courses added yet">
                            <div class="table-responsive">
                                <table class="table align-middle compact-table js-hostel-table">
                                    <thead>
                                        <tr>
                                            <th>Course Name</th>
                                            <th>Short Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($courses): ?>
                                        <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['course_fn']); ?></td>
                                            <td><?php echo htmlspecialchars($course['course_sn']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr class="empty-row">
                                            <td colspan="2" class="text-center py-4">No courses added yet</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card content-card">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Add Course</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Course Full Name</label>
                                    <input type="text" name="course_fn" class="form-control" value="<?php echo htmlspecialchars($courseFull); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Course Short Name</label>
                                    <input type="text" name="course_sn" class="form-control" value="<?php echo htmlspecialchars($courseShort); ?>" required>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Add Course</button>
                            </form>
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

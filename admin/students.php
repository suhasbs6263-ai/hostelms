<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/student-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'students.php';
$pageHeading = 'Students';
$students = fetch_all_students($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
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
                <h3 class="mb-1">Student Accounts</h3>
                <p class="mb-0 text-dark">Track every student, approval status, contact record, and course association in one place.</p>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="10" data-renumber="true" data-empty-message="No student accounts found">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Reg No</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Course</th>
                                        <th>Contact</th>
                                        <th>Address</th>
                                        <th>Approval</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($students): ?>
                                    <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e($student['registration_number']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(student_full_name($student)); ?></div>
                                            <div class="small text-muted"><?php echo e($student['email']); ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $student['status'] ?? 'pending';
                                            $badgeClass = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : 'warning');
                                            ?>
                                            <span class="badge text-bg-<?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                                        </td>
                                        <td><?php echo e($student['course_fn'] ?? $student['course_sn'] ?? '--'); ?></td>
                                        <td>
                                            <div><?php echo e($student['phone']); ?></div>
                                            <div class="small text-muted"><?php echo e($student['guardian_phone'] ?? '--'); ?></div>
                                        </td>
                                        <td><?php echo e(trim(($student['address_line'] ?? '') . ', ' . ($student['city'] ?? '') . ' ' . ($student['pincode'] ?? '')) ?: '--'); ?></td>
                                        <td>
                                            <div class="small"><?php echo e($student['approved_by_name'] ?? '--'); ?></div>
                                            <div class="text-muted small"><?php echo e($student['approved_at'] ?? '--'); ?></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="empty-row">
                                        <td colspan="8" class="text-center py-4">No student accounts found</td>
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
<script src="../assets/js/hostel-table.js?v=20260509a"></script>
</body>
</html>

<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/student-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'register-student.php';
$pageHeading = 'Pending Student Approvals';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'], $_POST['action_type'])) {
    require_valid_csrf('student_approval');

    $studentId = (int) $_POST['student_id'];
    $actionType = normalize_text($_POST['action_type']);
    $remarks = normalize_text($_POST['approval_remarks'] ?? '');
    $status = $actionType === 'approve' ? 'approved' : ($actionType === 'reject' ? 'rejected' : '');

    if ($status === '') {
        $message = 'Invalid approval action.';
        $messageType = 'danger';
    } elseif (update_student_approval_status($mysqli, $studentId, $status, current_user_id(), $remarks)) {
        $message = $status === 'approved'
            ? 'Student approved successfully.'
            : 'Student rejected successfully.';
    } else {
        $message = 'Unable to update student approval right now.';
        $messageType = 'danger';
    }
}

$pendingStudents = array_values(array_filter(fetch_all_students($mysqli), static function (array $student): bool {
    return ($student['status'] ?? '') === 'pending';
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Student Approvals</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
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
                <h3 class="mb-1">Pending Student Approvals</h3>
                <p class="mb-0 text-dark">Review new student registrations before granting access to the hostel system.</p>
            </div>

            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No pending students found">
                        <div class="table-responsive">
                            <table class="table align-middle compact-table js-hostel-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Reg No</th>
                                        <th>Student</th>
                                        <th>Contact</th>
                                        <th>Course</th>
                                        <th>Documents</th>
                                        <th>Created</th>
                                        <th>Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pendingStudents): ?>
                                    <?php foreach ($pendingStudents as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo e($student['registration_number']); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(student_full_name($student)); ?></div>
                                            <div class="small text-muted"><?php echo e($student['email']); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo e($student['phone']); ?></div>
                                            <div class="small text-muted"><?php echo e($student['guardian_phone'] ?? '--'); ?></div>
                                        </td>
                                        <td><?php echo e($student['course_fn'] ?? $student['course_sn'] ?? '--'); ?></td>
                                        <td>
                                            <?php if (!empty($student['profile_photo'])): ?>
                                            <a class="d-block small" href="../<?php echo e($student['profile_photo']); ?>" target="_blank">Profile Photo</a>
                                            <?php endif; ?>
                                            <?php if (!empty($student['id_document_path'])): ?>
                                            <a class="d-block small" href="../<?php echo e($student['id_document_path']); ?>" target="_blank">ID Document</a>
                                            <?php endif; ?>
                                            <?php if (empty($student['profile_photo']) && empty($student['id_document_path'])): ?>
                                            <span class="text-muted small">No uploads</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($student['created_at']); ?></td>
                                        <td style="min-width: 260px;">
                                            <form method="POST" class="d-grid gap-2">
                                                <?php echo csrf_input('student_approval'); ?>
                                                <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                                                <textarea name="approval_remarks" class="form-control form-control-sm" rows="2" placeholder="Optional review remarks"></textarea>
                                                <div class="d-flex gap-2">
                                                    <button type="submit" name="action_type" value="approve" class="btn btn-success btn-sm w-100" data-confirm-message="Approve this student and allow access to the hostel portal?">Approve</button>
                                                    <button type="submit" name="action_type" value="reject" class="btn btn-danger btn-sm w-100" data-confirm-message="Reject this student registration?">Reject</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="empty-row">
                                        <td colspan="8" class="text-center py-4">No pending students found</td>
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

<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/room-helpers.php');
require_once('../includes/student-helpers.php');
check_login('student');

$portalRole = 'student';
$activePage = 'room-details.php';
$pageHeading = 'My Allocation';

$studentId = current_user_id();
$student = fetch_student_by_id($mysqli, $studentId);
$allocation = fetch_student_allocation($mysqli, $studentId);
$latestRequest = fetch_latest_student_request($mysqli, $studentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Room Details</title>
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
                <h3 class="mb-1">My Room Details</h3>
                <p class="mb-0 text-dark">Track your current hostel allocation, request status, and room details.</p>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <?php if ($allocation): ?>
                    <h4 class="section-card-title mb-3">Active Hostel Allocation</h4>
                    <div class="table-responsive">
                        <table class="table room-detail-grid">
                            <tbody>
                                <tr>
                                    <th>Room No.</th>
                                    <td><?php echo e($allocation['room_no'] ?? '--'); ?></td>
                                    <th>Room Type</th>
                                    <td><?php echo e($allocation['room_type'] ?? '--'); ?></td>
                                    <th>Capacity</th>
                                    <td><?php echo e((string) ($allocation['capacity'] ?? '--')); ?></td>
                                </tr>
                                <tr>
                                    <th>Stay From</th>
                                    <td><?php echo e($allocation['stay_from']); ?></td>
                                    <th>Duration</th>
                                    <td><?php echo e((string) $allocation['duration_months']); ?> Months</td>
                                    <th>Monthly Fee</th>
                                    <td>Rs. <?php echo number_format((float) ($allocation['monthly_fee'] ?? 0), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Food Status</th>
                                    <td><?php echo ((int) ($allocation['food_status'] ?? 0)) === 1 ? 'With Food' : 'Without Food'; ?></td>
                                    <th>Allocated On</th>
                                    <td><?php echo e($allocation['allocated_at'] ?? '--'); ?></td>
                                    <th>Status</th>
                                    <td><span class="badge text-bg-success">Allocated</span></td>
                                </tr>
                                <tr>
                                    <th>Student Name</th>
                                    <td><?php echo e($student ? student_full_name($student) : '--'); ?></td>
                                    <th>Registration Number</th>
                                    <td><?php echo e($student['registration_number'] ?? '--'); ?></td>
                                    <th>Phone</th>
                                    <td><?php echo e($student['phone'] ?? '--'); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo e($student['email'] ?? '--'); ?></td>
                                    <th>Guardian Name</th>
                                    <td><?php echo e($student['guardian_name'] ?? '--'); ?></td>
                                    <th>Guardian Phone</th>
                                    <td><?php echo e($student['guardian_phone'] ?? '--'); ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td colspan="5"><?php echo e(trim(($student['address_line'] ?? '') . ', ' . ($student['city'] ?? '') . ' ' . ($student['pincode'] ?? '')) ?: '--'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif ($latestRequest): ?>
                    <div class="empty-state">
                        <h4>No room allocated yet</h4>
                        <p class="text-muted mb-2">Your latest room request is currently <strong><?php echo e(str_replace('_', ' ', $latestRequest['status'])); ?></strong>.</p>
                        <?php if (!empty($latestRequest['admin_remarks'])): ?>
                        <p class="text-muted mb-3"><strong>Admin remarks:</strong> <?php echo e($latestRequest['admin_remarks']); ?></p>
                        <?php endif; ?>
                        <a href="book-hostel.php" class="btn btn-primary">Manage Room Request</a>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <h4>No room request submitted yet</h4>
                        <p class="text-muted">Submit a hostel room application to view allocation details here.</p>
                        <a href="book-hostel.php" class="btn btn-primary">Apply for Room</a>
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

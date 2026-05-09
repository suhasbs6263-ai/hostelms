<?php
session_start();
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'students.php';
$pageHeading = 'Registered Students';

$students = [];
$result = $mysqli->query("SELECT regNo, firstName, middleName, lastName, gender, contactNo, email FROM userregistration ORDER BY firstName ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students</title>
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
                <h3 class="mb-1">Student's Account</h3>
            </div>
            <div class="card content-card">
                <div class="card-body">
                    <div class="hostel-datatable" data-page-size="5" data-renumber="true" data-empty-message="No student accounts found">
                    <div class="table-responsive">
                        <table class="table align-middle compact-table js-hostel-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reg No</th>
                                    <th>Student Name</th>
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students): ?>
                                <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['regNo']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($student['firstName'] . ' ' . $student['middleName'] . ' ' . $student['lastName'])); ?></td>
                                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($student['contactNo']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <div class="action-icon-group">
                                            <a class="action-icon-btn" href="mailto:<?php echo rawurlencode($student['email']); ?>" title="Email Student">
                                                <i class="ti ti-mail"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center py-4">No student accounts found</td>
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
<script src="../assets/js/hostel-table.js?v=20260402b"></script>
</body>
</html>

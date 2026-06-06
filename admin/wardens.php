<?php
require_once('../includes/dbconn.php');
require_once('../includes/check-login.php');
require_once('../includes/warden-helpers.php');
require_once('../includes/security-helpers.php');
check_login('admin');

$portalRole = 'admin';
$activePage = 'wardens.php';
$pageHeading = 'Wardens';
$message = '';
$messageType = 'success';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_warden'])) {
    require_valid_csrf('manage_wardens');

    $result = create_warden_account($mysqli, $_POST);

    if ($result['ok']) {
        $message = 'Warden account created successfully.';
        log_activity($mysqli, 'admin', current_user_id(), 'warden_created', 'Created warden account #' . (int) $result['warden_id'] . '.');
    } else {
        $errors = $result['errors'];
        $message = $errors['general'] ?? $errors['identity'] ?? 'Please correct the highlighted warden details.';
        $messageType = 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_warden'], $_POST['warden_id'])) {
    require_valid_csrf('manage_wardens');

    $wardenId = (int) $_POST['warden_id'];
    $result = update_warden_account($mysqli, $wardenId, $_POST);

    if ($result['ok']) {
        $message = 'Warden account updated successfully.';
        log_activity($mysqli, 'admin', current_user_id(), 'warden_updated', 'Updated warden account #' . $wardenId . '.');
    } else {
        $errors = $result['errors'];
        $message = $errors['general'] ?? $errors['identity'] ?? 'Please correct the highlighted warden details.';
        $messageType = 'danger';
    }
}

$wardens = fetch_wardens($mysqli);
$activeWardens = count(array_filter($wardens, static fn(array $warden): bool => ($warden['status'] ?? '') === 'active'));
$inactiveWardens = count($wardens) - $activeWardens;
$createOld = isset($_POST['create_warden']) ? $_POST : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wardens</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebar-position="fixed" data-header-position="fixed" data-sidebartype="full">
    <aside class="left-sidebar"><?php include('../includes/sidebar.php'); ?></aside>
    <div class="body-wrapper">
        <header class="app-header"><?php include('../includes/navigation.php'); ?></header>
        <div class="container-fluid">
            <div class="hostel-page-header">
                <h3 class="mb-1">Warden Management</h3>
                <p class="mb-0 text-dark">Create warden logins, keep contact details current, and control who can receive complaint assignments.</p>
            </div>

            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="row metric-grid">
                <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted mb-2">Total Wardens</p><h2 class="display-6 fs-8"><?php echo count($wardens); ?></h2></div></div></div>
                <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted mb-2">Active Wardens</p><h2 class="display-6 fs-8"><?php echo $activeWardens; ?></h2></div></div></div>
                <div class="col-md-4"><div class="card stat-card"><div class="card-body"><p class="text-muted mb-2">Inactive Wardens</p><h2 class="display-6 fs-8"><?php echo $inactiveWardens; ?></h2></div></div></div>
            </div>

            <div class="card content-card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="section-card-title mb-1">Create Warden Login</h4>
                            <p class="text-muted mb-0 small">The warden can log in immediately when status is active.</p>
                        </div>
                    </div>
                    <form method="POST">
                        <?php echo csrf_input('manage_wardens'); ?>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo e($createOld['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo e($createOld['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo e($createOld['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo e($createOld['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" name="create_warden" class="btn btn-primary">Create Warden</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card content-card">
                <div class="card-body">
                    <h4 class="section-card-title mb-3">Warden Accounts</h4>
                    <div class="table-responsive">
                        <table class="table align-middle compact-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Reset Password</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($wardens): ?>
                                <?php foreach ($wardens as $warden): ?>
                                <?php $formId = 'warden-form-' . (int) $warden['id']; ?>
                                <tr>
                                    <td><input form="<?php echo $formId; ?>" type="text" name="full_name" class="form-control form-control-sm" value="<?php echo e($warden['full_name']); ?>" required></td>
                                    <td><input form="<?php echo $formId; ?>" type="text" name="username" class="form-control form-control-sm" value="<?php echo e($warden['username'] ?? ''); ?>" required></td>
                                    <td><input form="<?php echo $formId; ?>" type="email" name="email" class="form-control form-control-sm" value="<?php echo e($warden['email']); ?>" required></td>
                                    <td><input form="<?php echo $formId; ?>" type="text" name="phone" class="form-control form-control-sm" value="<?php echo e($warden['phone'] ?? ''); ?>"></td>
                                    <td>
                                        <select form="<?php echo $formId; ?>" name="status" class="form-select form-select-sm">
                                            <option value="active" <?php echo ($warden['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo ($warden['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </td>
                                    <td><input form="<?php echo $formId; ?>" type="password" name="password" class="form-control form-control-sm" placeholder="Leave blank"></td>
                                    <td>
                                        <form method="POST" id="<?php echo $formId; ?>">
                                            <?php echo csrf_input('manage_wardens'); ?>
                                            <input type="hidden" name="warden_id" value="<?php echo (int) $warden['id']; ?>">
                                            <button type="submit" name="update_warden" class="btn btn-primary btn-sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4">No wardens found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
</body>
</html>

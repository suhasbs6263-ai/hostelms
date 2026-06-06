<?php
require_once('../includes/dbconn.php');
require_once('../includes/admin-helpers.php');
require_once('../includes/security-helpers.php');

if (is_logged_in_as('admin')) {
    safe_redirect('dashboard.php');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_valid_csrf('admin_login');

    $identity = normalize_text($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $authResult = authenticate_admin($mysqli, $identity, $password);

    if ($authResult['ok']) {
        safe_redirect('dashboard.php');
    }

    $errorMessage = $authResult['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
    <title>Admin Login | Hostel Management System</title>
    <link href="../assets/css/styles.min.css" rel="stylesheet">
    <link href="../assets/css/hostel-custom.css?v=20260509a" rel="stylesheet">
</head>
<body>
    <div class="auth-shell d-flex align-items-center justify-content-center p-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="admin-login-stage">
                        <div class="admin-login-visual d-none d-lg-block">
                            <div class="admin-gear-hero">
                                <i class="ti ti-settings"></i>
                                <div class="admin-gear-core"></div>
                                <div class="admin-gear-pointer"></div>
                            </div>
                        </div>
                        <div class="admin-login-card">
                            <p class="auth-mobile-note">Use the administrator account to manage students, allocations, fees, and complaints.</p>
                            <div class="admin-login-badge">
                                <i class="ti ti-map-pin"></i>
                            </div>
                            <h3>Admin Login</h3>
                            <p class="text-muted small">Secure access for hostel administrators.</p>
                            <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($errorMessage); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <?php echo csrf_input('admin_login'); ?>
                                <div class="mb-3">
                                    <label class="form-label">Email or Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button class="btn admin-login-submit w-100 py-8" name="login">Login</button>
                                <div class="text-center mt-3">
                                    <a href="../warden/index.php" class="admin-login-link d-block">Go to Warden Panel</a>
                                    <a href="../index.php" class="admin-login-link">Go Back</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

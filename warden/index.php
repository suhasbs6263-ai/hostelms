<?php
require_once('../includes/dbconn.php');
require_once('../includes/warden-helpers.php');
require_once('../includes/security-helpers.php');

if (is_logged_in_as('warden')) {
    safe_redirect('dashboard.php');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_valid_csrf('warden_login');

    $identity = normalize_text($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $authResult = authenticate_warden($mysqli, $identity, $password);

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warden Login | Hostel Management System</title>
    <link rel="stylesheet" href="../assets/css/styles.min.css">
    <link rel="stylesheet" href="../assets/css/hostel-custom.css?v=20260509a">
</head>
<body>
    <div class="auth-shell d-flex align-items-center justify-content-center p-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <div class="student-login-badge mx-auto mb-3">
                                    <i class="ti ti-shield-check"></i>
                                </div>
                                <h3 class="mb-1">Warden Login</h3>
                                <p class="text-muted mb-0">Monitor complaints and day-to-day hostel operations.</p>
                            </div>
                            <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($errorMessage); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <?php echo csrf_input('warden_login'); ?>
                                <div class="mb-3">
                                    <label class="form-label">Email or Username</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo e($_POST['username'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="login" class="btn btn-primary w-100 py-8">Login</button>
                                <div class="text-center mt-3">
                                    <a href="../index.php" class="student-login-link">Back to Student Login</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

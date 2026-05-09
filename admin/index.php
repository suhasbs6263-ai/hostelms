<?php
session_start();
require_once('../includes/dbconn.php');

if (!empty($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = md5($_POST['password']);

    if ($username === 'admin' && $password === md5('admin123')) {
        $_SESSION['admin'] = $username;
        header('Location: dashboard.php');
        exit;
    }

    $errorMessage = 'Invalid admin username or password.';
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
    <link href="../assets/css/hostel-custom.css?v=20260402d" rel="stylesheet">
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
                            <p class="auth-mobile-note">Use the default admin account to manage hostel records.</p>
                            <div class="admin-login-badge">
                                <i class="ti ti-map-pin"></i>
                            </div>
                            <h3>Admin Login</h3>
                            <?php if ($errorMessage !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Email or Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button class="btn admin-login-submit w-100 py-8" name="login">Login</button>
                                <a href="../index.php" class="admin-login-link">Go Back</a>
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

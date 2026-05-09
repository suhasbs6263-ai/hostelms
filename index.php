<?php
session_start();
require_once('includes/dbconn.php');
require_once('includes/auth-helpers.php');

if (!empty($_SESSION['login'])) {
    header('Location: student/dashboard.php');
    exit;
}

$errorMessage = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $plainPassword = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, email, password FROM userregistration WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($user && verify_app_password($plainPassword, (string) $user['password'])) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['login'] = $user['email'];

        if (password_hash_needs_upgrade((string) $user['password'])) {
            upgrade_password_hash($mysqli, 'userregistration', 'id', (string) $user['id'], $plainPassword);
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $logStmt = $mysqli->prepare("INSERT INTO userLog (userId, userEmail, userIp, city, country) VALUES (?, ?, ?, ?, ?)");

        if ($logStmt) {
            $emptyValue = '';
            $logStmt->bind_param('issss', $user['id'], $user['email'], $ipAddress, $emptyValue, $emptyValue);
            $logStmt->execute();
            $logStmt->close();
        }

        header('Location: student/dashboard.php');
        exit;
    }

    $errorMessage = 'Invalid student email or password.';
}
?>
<!DOCTYPE html>
<html dir="ltr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logos/favicon.png">
  <title>Student Login | Hostel Management System</title>
  <link href="assets/css/styles.min.css" rel="stylesheet">
  <link href="assets/css/hostel-custom.css?v=20260402d" rel="stylesheet">
</head>
<body>
  <div class="auth-shell d-flex align-items-center justify-content-center p-4">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-xl-10">
          <div class="auth-card student-login-stage">
            <div class="row g-0">
              <div class="col-lg-7">
                <div class="student-login-visual">
                  <div>
                    <div class="student-illustration">
                      <div class="student-illustration-stack">
                        <i class="ti ti-home student-house"></i>
                        <i class="ti ti-bed student-bed"></i>
                        <i class="ti ti-user-circle student-user"></i>
                      </div>
                    </div>
                    <div class="student-visual-title">Hostels</div>
                  </div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="student-login-form-shell">
                  <div class="student-login-form">
                    <p class="auth-mobile-note">Use your registered student account to continue.</p>
                    <div class="mb-4">
                      <div class="student-login-badge">
                        <i class="ti ti-home"></i>
                      </div>
                      <h3>Student Login</h3>
                    </div>
                    <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <form method="POST" class="mt-4">
                      <div class="mb-3">
                        <label class="form-label" for="uname">Email</label>
                        <input class="form-control" name="email" id="uname" type="email" required>
                      </div>
                      <div class="mb-4">
                        <label class="form-label" for="pwd">Password</label>
                        <input class="form-control" name="password" id="pwd" type="password" required>
                      </div>
                      <button type="submit" name="login" class="btn student-login-submit w-100 py-8">Login</button>
                      <a href="admin/index.php" class="student-login-link">Go to Admin Panel</a>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

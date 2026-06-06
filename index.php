<?php
require_once('includes/dbconn.php');
require_once('includes/auth-helpers.php');
require_once('includes/student-helpers.php');
require_once('includes/security-helpers.php');
require_once('includes/activity-helpers.php');

if (is_logged_in_as('student')) {
    safe_redirect('student/dashboard.php');
}

$errorMessage = '';
$successMessage = '';

if (isset($_GET['registered'])) {
    $successMessage = 'Registration submitted successfully. Your account is pending admin approval.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_valid_csrf('student_login');

    $email = strtolower(normalize_text($_POST['email'] ?? ''));
    $plainPassword = (string) ($_POST['password'] ?? '');
    $student = fetch_student_by_email($mysqli, $email);

    if (!$student || !verify_app_password($plainPassword, (string) $student['password'])) {
        $errorMessage = 'Invalid student email or password.';
    } elseif (($student['status'] ?? 'pending') === 'pending') {
        $errorMessage = 'Your account is pending admin approval. Please wait for confirmation.';
    } elseif (($student['status'] ?? 'pending') === 'rejected') {
        $errorMessage = 'Your account has been rejected. Please contact the admin for details.';
    } else {
        if (password_hash_needs_upgrade((string) $student['password'])) {
            $stmt = $mysqli->prepare("UPDATE students SET password = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $newHash = hash_app_password($plainPassword);
                $studentId = (int) $student['id'];
                $stmt->bind_param('si', $newHash, $studentId);
                $stmt->execute();
                $stmt->close();
            }
        }

        login_user('student', [
            'id' => (int) $student['id'],
            'name' => student_full_name($student),
            'email' => $student['email'],
            'status' => $student['status'],
        ]);

        log_activity($mysqli, 'student', (int) $student['id'], 'student_login', 'Student signed in successfully.');
        safe_redirect('student/dashboard.php');
    }
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
  <link href="assets/css/hostel-custom.css?v=20260509a" rel="stylesheet">
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
                    <div class="student-visual-title">Student Portal</div>
                    <p class="text-white-50 mb-4">Apply for hostel accommodation, track approval, manage payments, and raise complaints.</p>
                    <div class="student-login-bullets text-start mx-auto" style="max-width: 270px;">
                      <div class="mb-2 text-white fw-semibold">1. Register account</div>
                      <div class="mb-2 text-white fw-semibold">2. Wait for admin approval</div>
                      <div class="mb-2 text-white fw-semibold">3. Apply for hostel room</div>
                      <div class="text-white fw-semibold">4. Manage allocation and payments</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="student-login-form-shell">
                  <div class="student-login-form">
                    <p class="auth-mobile-note">Use your approved student account to continue.</p>
                    <div class="mb-4">
                      <div class="student-login-badge">
                        <i class="ti ti-home"></i>
                      </div>
                      <h3>Student Login</h3>
                      <p class="text-muted mb-0">Only approved students can access the hostel dashboard.</p>
                    </div>
                    <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success"><?php echo e($successMessage); ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($errorMessage); ?></div>
                    <?php endif; ?>
                    <form method="POST" class="mt-4">
                      <?php echo csrf_input('student_login'); ?>
                      <div class="mb-3">
                        <label class="form-label" for="student-email">Email</label>
                        <input class="form-control" name="email" id="student-email" type="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                      </div>
                      <div class="mb-4">
                        <label class="form-label" for="student-password">Password</label>
                        <input class="form-control" name="password" id="student-password" type="password" required>
                      </div>
                      <button type="submit" name="login" class="btn student-login-submit w-100 py-8">Login</button>
                      <div class="text-center mt-3">
                        <a href="student/register.php" class="student-login-link d-block">Create Student Account</a>
                        <a href="admin/index.php" class="student-login-link">Go to Admin Panel</a>
                        <a href="warden/index.php" class="student-login-link d-block">Go to Warden Panel</a>
                      </div>
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

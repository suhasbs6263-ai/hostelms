<?php
require_once('../includes/dbconn.php');
require_once('../includes/student-helpers.php');
require_once('../includes/course-helpers.php');
require_once('../includes/security-helpers.php');

if (is_logged_in_as('student')) {
    safe_redirect('dashboard.php');
}

$message = '';
$messageType = 'success';
$errors = [];
$old = [
    'registration_number' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'gender' => '',
    'phone' => '',
    'email' => '',
    'course_id' => '',
    'emergency_contact' => '',
    'guardian_name' => '',
    'guardian_relation' => '',
    'guardian_phone' => '',
    'address_line' => '',
    'city' => '',
    'pincode' => '',
];

$courses = fetch_courses($mysqli);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    require_valid_csrf('student_register');

    foreach ($old as $key => $value) {
        $old[$key] = (string) ($_POST[$key] ?? '');
    }

    if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        $errors['confirm_password'] = 'Password and confirm password must match.';
    }

    $profilePhotoPath = '';
    $documentPath = '';

    if (!$errors && !empty($_FILES['profile_photo']['name'])) {
        $uploadResult = store_uploaded_file(
            $_FILES['profile_photo'],
            PROFILE_UPLOAD_DIR,
            ['jpg', 'jpeg', 'png', 'webp']
        );

        if ($uploadResult['ok']) {
            $profilePhotoPath = $uploadResult['path'];
        } else {
            $errors['profile_photo'] = $uploadResult['error'];
        }
    }

    if (!$errors && !empty($_FILES['id_document']['name'])) {
        $uploadResult = store_uploaded_file(
            $_FILES['id_document'],
            DOCUMENT_UPLOAD_DIR,
            ['jpg', 'jpeg', 'png', 'pdf']
        );

        if ($uploadResult['ok']) {
            $documentPath = $uploadResult['path'];
        } else {
            $errors['id_document'] = $uploadResult['error'];
        }
    }

    if (!$errors) {
        $result = register_student_account($mysqli, [
            'registration_number' => $_POST['registration_number'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'course_id' => $_POST['course_id'] ?? '',
            'emergency_contact' => $_POST['emergency_contact'] ?? '',
            'guardian_name' => $_POST['guardian_name'] ?? '',
            'guardian_relation' => $_POST['guardian_relation'] ?? '',
            'guardian_phone' => $_POST['guardian_phone'] ?? '',
            'address_line' => $_POST['address_line'] ?? '',
            'city' => $_POST['city'] ?? '',
            'pincode' => $_POST['pincode'] ?? '',
            'profile_photo' => $profilePhotoPath,
            'id_document_path' => $documentPath,
        ]);

        if ($result['ok']) {
            safe_redirect('../index.php?registered=1');
        }

        $errors = $result['errors'];
        $message = $errors['general'] ?? 'Please correct the highlighted fields.';
        $messageType = 'danger';
    } else {
        $message = 'Please correct the highlighted fields.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" sizes="16x16" href="../assets/images/logos/favicon.png">
<title>Student Registration | Hostel Management System</title>
<link href="../assets/css/styles.min.css" rel="stylesheet">
<link href="../assets/css/hostel-custom.css?v=20260509a" rel="stylesheet">
</head>
<body>
<div class="auth-shell d-flex align-items-center justify-content-center p-4">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-xl-11">
        <div class="card shadow border-0 rounded-4">
          <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center mb-4">
              <div class="col-lg-8">
                <h2 class="mb-2">Student Registration</h2>
                <p class="text-muted mb-0">Create your hostel account. Your profile will stay pending until the admin reviews and approves it.</p>
              </div>
              <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="../index.php" class="btn btn-outline-primary">Back to Login</a>
              </div>
            </div>

            <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo e($message); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
              <?php echo csrf_input('student_register'); ?>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Registration Number</label>
                  <input type="text" name="registration_number" class="form-control <?php echo isset($errors['registration_number']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['registration_number']); ?>" required>
                  <?php if (isset($errors['registration_number'])): ?><div class="invalid-feedback"><?php echo e($errors['registration_number']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">First Name</label>
                  <input type="text" name="first_name" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['first_name']); ?>" required>
                  <?php if (isset($errors['first_name'])): ?><div class="invalid-feedback"><?php echo e($errors['first_name']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Middle Name</label>
                  <input type="text" name="middle_name" class="form-control" value="<?php echo e($old['middle_name']); ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Last Name</label>
                  <input type="text" name="last_name" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['last_name']); ?>" required>
                  <?php if (isset($errors['last_name'])): ?><div class="invalid-feedback"><?php echo e($errors['last_name']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Gender</label>
                  <select name="gender" class="form-select <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" required>
                    <option value="">Choose...</option>
                    <?php foreach (['Male', 'Female', 'Others'] as $gender): ?>
                    <option value="<?php echo e($gender); ?>" <?php echo $old['gender'] === $gender ? 'selected' : ''; ?>><?php echo e($gender); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php if (isset($errors['gender'])): ?><div class="invalid-feedback"><?php echo e($errors['gender']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Phone Number</label>
                  <input type="text" name="phone" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['phone']); ?>" required>
                  <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?php echo e($errors['phone']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Email Address</label>
                  <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['email']); ?>" required>
                  <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo e($errors['email']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" required>
                  <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?php echo e($errors['password']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Confirm Password</label>
                  <input type="password" name="confirm_password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" required>
                  <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback"><?php echo e($errors['confirm_password']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Course</label>
                  <select name="course_id" class="form-select">
                    <option value="">Choose course</option>
                    <?php foreach ($courses as $course): ?>
                    <option value="<?php echo e((string) $course['id']); ?>" <?php echo $old['course_id'] === (string) $course['id'] ? 'selected' : ''; ?>>
                      <?php echo e($course['course_fn']); ?> (<?php echo e($course['course_sn']); ?>)
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Emergency Contact</label>
                  <input type="text" name="emergency_contact" class="form-control <?php echo isset($errors['emergency_contact']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['emergency_contact']); ?>">
                  <?php if (isset($errors['emergency_contact'])): ?><div class="invalid-feedback"><?php echo e($errors['emergency_contact']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Guardian Name</label>
                  <input type="text" name="guardian_name" class="form-control" value="<?php echo e($old['guardian_name']); ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Guardian Relation</label>
                  <input type="text" name="guardian_relation" class="form-control" value="<?php echo e($old['guardian_relation']); ?>">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Guardian Phone</label>
                  <input type="text" name="guardian_phone" class="form-control <?php echo isset($errors['guardian_phone']) ? 'is-invalid' : ''; ?>" value="<?php echo e($old['guardian_phone']); ?>">
                  <?php if (isset($errors['guardian_phone'])): ?><div class="invalid-feedback"><?php echo e($errors['guardian_phone']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Current Address</label>
                  <textarea name="address_line" class="form-control" rows="3"><?php echo e($old['address_line']); ?></textarea>
                </div>
                <div class="col-md-3">
                  <label class="form-label">City</label>
                  <input type="text" name="city" class="form-control" value="<?php echo e($old['city']); ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Pincode</label>
                  <input type="text" name="pincode" class="form-control" value="<?php echo e($old['pincode']); ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Profile Photo</label>
                  <input type="file" name="profile_photo" class="form-control <?php echo isset($errors['profile_photo']) ? 'is-invalid' : ''; ?>" accept=".jpg,.jpeg,.png,.webp">
                  <?php if (isset($errors['profile_photo'])): ?><div class="invalid-feedback"><?php echo e($errors['profile_photo']); ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                  <label class="form-label">ID Document</label>
                  <input type="file" name="id_document" class="form-control <?php echo isset($errors['id_document']) ? 'is-invalid' : ''; ?>" accept=".jpg,.jpeg,.png,.pdf">
                  <?php if (isset($errors['id_document'])): ?><div class="invalid-feedback"><?php echo e($errors['id_document']); ?></div><?php endif; ?>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
                <a href="../index.php" class="btn btn-light">Cancel</a>
                <button type="submit" name="submit" class="btn btn-primary">Create Account</button>
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

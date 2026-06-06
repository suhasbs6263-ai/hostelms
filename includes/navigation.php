<?php
$portalRole = $portalRole ?? (current_user_role() ?? 'student');
$pageHeading = $pageHeading ?? ucfirst($portalRole) . ' Portal';
$assetBasePath = $assetBasePath ?? '../';
$logoutPath = match ($portalRole) {
    'admin', 'warden' => 'logout.php',
    default => '../logout.php',
};
$accountLabel = current_user_name();
$notificationCount = function_exists('count_unread_notifications')
    ? count_unread_notifications($mysqli, $portalRole, current_user_id())
    : 0;
?>
<nav class="navbar navbar-expand-lg navbar-light app-topbar">
  <div class="container-fluid px-0 justify-content-between gap-3">
    <button class="btn topbar-icon-btn sidebartoggler d-xl-none" type="button" id="headerCollapse" aria-label="Open navigation">
      <i class="ti ti-menu-2"></i>
    </button>
    <div class="topbar-heading">
      <div class="topbar-eyebrow"><?php echo e(ucfirst($portalRole)); ?> Workspace</div>
      <h5 class="mb-0"><?php echo e($pageHeading); ?></h5>
    </div>
    <div class="navbar-collapse justify-content-end px-0">
      <div class="topbar-user">
        <a class="btn topbar-icon-btn position-relative" href="notifications.php" title="Notifications" aria-label="Notifications">
          <i class="ti ti-bell"></i>
          <?php if ($notificationCount > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $notificationCount; ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown">
          <button class="btn account-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo $assetBasePath; ?>assets/images/profile/user-1.jpg" alt="">
            <span class="account-copy">
              <strong><?php echo e($accountLabel); ?></strong>
              <small><?php echo e(ucfirst($portalRole)); ?></small>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><h6 class="dropdown-header"><?php echo e($accountLabel); ?></h6></li>
            <li><a class="dropdown-item" href="notifications.php"><i class="ti ti-bell me-2"></i>Notifications</a></li>
            <?php if ($portalRole === 'student'): ?>
            <li><a class="dropdown-item" href="profile.php"><i class="ti ti-user-circle me-2"></i>Profile</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?php echo $logoutPath; ?>"><i class="ti ti-logout me-2"></i>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

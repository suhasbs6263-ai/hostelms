<?php
$portalRole = $portalRole ?? (current_user_role() ?? 'student');
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$logoPath = '../assets/images/logos/logo.svg';
$portalTitle = match ($portalRole) {
    'admin' => 'Admin Console',
    'warden' => 'Warden Desk',
    default => 'Student Desk',
};

$portalTagline = match ($portalRole) {
    'admin' => 'Operations ERP',
    'warden' => 'Resolution Desk',
    default => 'Resident Portal',
};

$menuGroups = match ($portalRole) {
    'admin' => [
        'Overview' => [
            ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard'],
        ],
        'Students' => [
            ['file' => 'register-student.php', 'label' => 'Pending Approvals', 'icon' => 'ti ti-user-check'],
            ['file' => 'students.php', 'label' => 'Student Directory', 'icon' => 'ti ti-users'],
        ],
        'Hostel Operations' => [
            ['file' => 'book-hostel.php', 'label' => 'Room Allocation', 'icon' => 'ti ti-bed'],
            ['file' => 'bookings.php', 'label' => 'Allocations', 'icon' => 'ti ti-home-edit'],
            ['file' => 'rooms.php', 'label' => 'Rooms', 'icon' => 'ti ti-door'],
        ],
        'Administration' => [
            ['file' => 'wardens.php', 'label' => 'Wardens', 'icon' => 'ti ti-shield-check'],
            ['file' => 'manage-courses.php', 'label' => 'Courses', 'icon' => 'ti ti-book'],
        ],
        'Finance & Support' => [
            ['file' => 'payments.php', 'label' => 'Payments', 'icon' => 'ti ti-credit-card'],
            ['file' => 'complaints.php', 'label' => 'Complaints', 'icon' => 'ti ti-alert-circle'],
            ['file' => 'notifications.php', 'label' => 'Notifications', 'icon' => 'ti ti-bell'],
        ],
      ],
    'warden' => [
        'Overview' => [
            ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard'],
        ],
        'Operations' => [
            ['file' => 'complaints.php', 'label' => 'Complaint Queue', 'icon' => 'ti ti-alert-circle'],
        ],
        'Updates' => [
            ['file' => 'notifications.php', 'label' => 'Notifications', 'icon' => 'ti ti-bell'],
        ],
      ],
    default => [
        'Overview' => [
            ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard'],
        ],
        'My Stay' => [
            ['file' => 'book-hostel.php', 'label' => 'Apply for Room', 'icon' => 'ti ti-bed'],
            ['file' => 'room-details.php', 'label' => 'My Allocation', 'icon' => 'ti ti-home'],
        ],
        'Finance & Support' => [
            ['file' => 'payments.php', 'label' => 'Payments', 'icon' => 'ti ti-credit-card'],
            ['file' => 'complaints.php', 'label' => 'Complaints', 'icon' => 'ti ti-alert-circle'],
            ['file' => 'notifications.php', 'label' => 'Notifications', 'icon' => 'ti ti-bell'],
        ],
        'Account' => [
            ['file' => 'profile.php', 'label' => 'My Profile', 'icon' => 'ti ti-user-circle'],
            ['file' => 'log-activities.php', 'label' => 'Activity Log', 'icon' => 'ti ti-history'],
        ],
      ],
};
?>
<div class="sidebar-shell">
  <div class="brand-logo d-flex align-items-center justify-content-between">
    <a href="dashboard.php" class="sidebar-brand text-decoration-none d-flex align-items-center gap-2">
      <span class="brand-mark"><img src="<?php echo $logoPath; ?>" width="28" alt="Hostel ERP logo"></span>
      <span class="brand-copy">
        <span class="brand-title">Hostel ERP</span>
        <small><?php echo e($portalTagline); ?></small>
      </span>
    </a>
    <button class="btn sidebar-close sidebartoggler d-xl-none" type="button" aria-label="Close navigation">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <nav class="sidebar-nav scroll-sidebar" data-simplebar>
    <div class="portal-pill">
      <span><?php echo e($portalTitle); ?></span>
      <strong><?php echo e(ucfirst($portalRole)); ?></strong>
    </div>
    <ul id="sidebarnav" class="sidebar-menu">
      <?php foreach ($menuGroups as $section => $items): ?>
      <li class="nav-section-label"><?php echo e($section); ?></li>
      <?php foreach ($items as $item): ?>
      <?php $isActive = $activePage === $item['file']; ?>
      <li class="sidebar-item <?php echo $isActive ? 'selected' : ''; ?>">
        <a class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo $item['file']; ?>" aria-current="<?php echo $isActive ? 'page' : 'false'; ?>">
          <span class="nav-icon"><i class="<?php echo $item['icon']; ?>"></i></span>
          <span class="hide-menu"><?php echo e($item['label']); ?></span>
        </a>
      </li>
      <?php endforeach; ?>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="sidebar-support-card">
    <div class="support-icon"><i class="ti ti-building-community"></i></div>
    <div>
      <strong>Hostel Control</strong>
      <span>Live rooms, fees, support</span>
    </div>
  </div>
</div>

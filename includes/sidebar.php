<?php
$portalRole = $portalRole ?? 'student';
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$logoPath = '../assets/images/logos/logo.svg';

$menuItems = $portalRole === 'admin'
    ? [
        ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard'],
        ['file' => 'register-student.php', 'label' => 'Register Student', 'icon' => 'ti ti-user-plus'],
        ['file' => 'students.php', 'label' => 'View Student Acc.', 'icon' => 'ti ti-users'],
        ['file' => 'book-hostel.php', 'label' => 'Book Hostel', 'icon' => 'ti ti-bed'],
        ['file' => 'bookings.php', 'label' => 'Hostel Students', 'icon' => 'ti ti-home-edit'],
        ['file' => 'rooms.php', 'label' => 'Manage Rooms', 'icon' => 'ti ti-door'],
        ['file' => 'manage-courses.php', 'label' => 'Manage Courses', 'icon' => 'ti ti-book'],
      ]
    : [
        ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'ti ti-layout-dashboard'],
        ['file' => 'book-hostel.php', 'label' => 'Book Hostel', 'icon' => 'ti ti-bed'],
        ['file' => 'room-details.php', 'label' => 'My Room Details', 'icon' => 'ti ti-home'],
        ['file' => 'log-activities.php', 'label' => 'Log Activities', 'icon' => 'ti ti-history'],
      ];
?>
<div>
  <div class="brand-logo d-flex align-items-center justify-content-between px-3 py-4">
    <a href="dashboard.php" class="text-nowrap logo-img text-decoration-none d-flex align-items-center gap-2">
      <img src="<?php echo $logoPath; ?>" width="34" alt="logo">
      <span class="fw-bold text-dark">HOSTEL MANAGEMENT</span>
    </a>
  </div>
  <nav class="sidebar-nav scroll-sidebar" data-simplebar>
    <div class="feature-label">Features</div>
    <ul id="sidebarnav">
      <?php foreach ($menuItems as $item): ?>
      <li class="sidebar-item">
        <a class="sidebar-link <?php echo $activePage === $item['file'] ? 'active' : ''; ?>" href="<?php echo $item['file']; ?>">
          <i class="<?php echo $item['icon']; ?>"></i>
          <span class="hide-menu"><?php echo $item['label']; ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </nav>
</div>

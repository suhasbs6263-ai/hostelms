<?php
$portalRole = $portalRole ?? 'student';
$pageHeading = $pageHeading ?? ($portalRole === 'admin' ? 'Admin Portal' : 'Student Portal');
$homePath = 'dashboard.php';
$logoutPath = $portalRole === 'admin' ? 'logout.php' : '../logout.php';
$accountLabel = $portalRole === 'admin'
    ? ($_SESSION['admin'] ?? 'Administrator')
    : ($_SESSION['login'] ?? 'Student');
?>
<nav class="navbar navbar-expand-lg navbar-light px-3 py-3">
  <div class="container-fluid px-0 justify-content-between">
    <button class="btn btn-light-primary d-xl-none d-inline-flex align-items-center justify-content-center p-2" type="button" id="headerCollapse">
      <i class="ti ti-menu-2"></i>
    </button>
    <div class="navbar-collapse justify-content-end px-0">
      <div class="topbar-user">
        <img src="../assets/images/profile/user-1.jpg" alt="user">
        <div class="text-end">
          <p class="mb-0 fw-semibold text-dark">Hi, <?php echo htmlspecialchars($accountLabel); ?></p>
          <small class="text-muted"><?php echo ucfirst($portalRole); ?></small>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="<?php echo $logoutPath; ?>">Logout</a>
      </div>
    </div>
  </div>
</nav>

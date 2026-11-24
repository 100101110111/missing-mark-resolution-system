<nav class="navbar navbar-expand-lg kt-navbar kt-navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand kt-brand text-white" href="<?= BASE_URL ?>dashboard.php">
      <span class="kt-brand-badge"><i class="fa-solid fa-graduation-cap"></i></span>
      <span>Missing Marks <span style="color:var(--kt-gold)">Resolution</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navStudent">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navStudent">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'fw-bold':'' ?>" href="<?= BASE_URL ?>dashboard.php"><i class="fa-solid fa-gauge me-1"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='my_reports.php'?'fw-bold':'' ?>" href="<?= BASE_URL ?>my_reports.php"><i class="fa-solid fa-file-lines me-1"></i> My Reports</a></li>
        <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='profile.php'?'fw-bold':'' ?>" href="<?= BASE_URL ?>profile.php"><i class="fa-solid fa-user me-1"></i> Profile</a></li>
        <?php
          $__navUnread = [];
          $__navUnreadCount = 0;
          if (isset($pdo) && isset($_SESSION['user_id'])) {
              $__navStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
              $__navStmt->execute([$_SESSION['user_id']]);
              $__navUnread = $__navStmt->fetchAll();
              foreach ($__navUnread as $__n) { if (!$__n['is_read']) $__navUnreadCount++; }
          }
        ?>
        <li class="nav-item ms-lg-1 dropdown">
          <a class="nav-link kt-bell-btn" href="#" data-bs-toggle="dropdown" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($__navUnreadCount > 0): ?><span class="kt-bell-badge"><?= $__navUnreadCount ?></span><?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end shadow-sm kt-notif-dropdown">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-semibold small">Notifications</span>
              <a href="<?= BASE_URL ?>dashboard.php" class="small">View all</a>
            </div>
            <?php if (empty($__navUnread)): ?>
              <p class="small kt-muted mb-0 text-center py-3">No notifications yet.</p>
            <?php else: ?>
              <?php foreach ($__navUnread as $__n): ?>
                <div class="kt-notif-item <?= !$__n['is_read'] ? 'kt-notif-unread' : '' ?>">
                  <div class="kt-notif-icon channel-<?= $__n['channel'] ?>"><i class="fa-solid <?= notificationChannelIcon($__n['channel']) ?>"></i></div>
                  <div class="flex-grow-1">
                    <div class="small fw-semibold"><?= clean($__n['title']) ?></div>
                    <div class="small kt-muted" style="font-size:.78rem;"><?= clean(mb_strimwidth($__n['message'], 0, 70, '...')) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </li>
        <li class="nav-item ms-lg-2 dropdown">
          <a class="nav-link d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <span class="kt-avatar" style="width:34px;height:34px;font-size:.75rem"><?= initials($_SESSION['full_name'] ?? 'S') ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><span class="dropdown-item-text small kt-muted"><?= clean($_SESSION['full_name'] ?? '') ?><br><?= clean($_SESSION['admission_no'] ?? '') ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="fa-solid fa-user me-2"></i>My Profile</a></li>
            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

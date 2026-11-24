<?php if (!isset($pageTitle)) $pageTitle = 'Admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?> | Admin — Kenya School of TVET</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Chart.js CDN Added Below -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="kt-sidebar">
  <div class="brand">
    <a href="dashboard.php" class="d-flex align-items-center gap-2 text-white text-decoration-none">
      <span class="kt-brand-badge"><i class="fa-solid fa-graduation-cap"></i></span>
      <span class="fw-bold">MMRS Admin</span>
    </a>
  </div>
  <nav class="nav flex-column py-3">
    <a class="nav-link <?= $current=='dashboard.php'?'active':'' ?>" href="dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a class="nav-link <?= $current=='reports.php'?'active':'' ?>" href="reports.php"><i class="fa-solid fa-folder-open"></i> All Reports</a>
    <a class="nav-link <?= $current=='students.php'?'active':'' ?>" href="students.php"><i class="fa-solid fa-user-graduate"></i> Students</a>
    <a class="nav-link <?= $current=='trainers.php'?'active':'' ?>" href="trainers.php"><i class="fa-solid fa-chalkboard-user"></i> Trainers</a>
    <a class="nav-link <?= $current=='staff.php'?'active':'' ?>" href="staff.php"><i class="fa-solid fa-user-shield"></i> Staff Accounts</a>
    <hr class="text-white-50 mx-3">
    <a class="nav-link" href="<?= BASE_URL ?>index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Public Site</a>
    <a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </nav>
</aside>

<main class="kt-main">
  <div class="kt-topbar d-flex justify-content-between align-items-center mb-4">
    <button id="sidebarToggle" class="btn btn-kt-soft d-lg-none"><i class="fa-solid fa-bars"></i></button>
    <h5 class="mb-0 d-none d-lg-block"><?= clean($pageTitle) ?></h5>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-light text-dark border d-none d-md-inline-flex align-items-center gap-1">
        <i class="fa-solid <?= staffRoleIcon($_SESSION['staff_role'] ?? null) ?>"></i> <?= clean(staffRoleLabel($_SESSION['staff_role'] ?? null)) ?>
      </span>
      <span class="kt-avatar"><?= initials($_SESSION['full_name'] ?? 'A') ?></span>
      <span class="fw-semibold small d-none d-md-inline"><?= clean($_SESSION['full_name'] ?? 'Admin') ?></span>
    </div>
  </div>
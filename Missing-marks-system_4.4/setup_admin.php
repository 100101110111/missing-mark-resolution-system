<?php
/**
 * ============================================================
 * ONE-TIME SETUP SCRIPT
 * Visit this file once in your browser (e.g.
 * http://localhost/missing-marks-system/setup_admin.php)
 * right after importing the database, to safely create the
 * default Administrator account using PHP's password_hash().
 *
 * You can delete this file afterwards for extra security.
 * ============================================================
 */
require_once 'config/db.php';
require_once 'includes/functions.php';

$defaultEmail = 'admin@kstvet.ac.ke';
$defaultAdmissionNo = 'ADMIN001';
$defaultPassword = 'admin123';

$stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$existingAdmin = $stmt->fetch();

$created = false;
if (!$existingAdmin) {
    $hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, admission_no, email, phone, course, password, role, staff_role)
                            VALUES (?, ?, ?, ?, ?, ?, 'admin', 'Registrar')");
    $stmt->execute(['System Administrator', $defaultAdmissionNo, $defaultEmail, '0700000000', 'Examinations Office', $hash]);
    $created = true;
}

$pageTitle = "Admin Setup";
require 'includes/header.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="kt-card p-5 text-center fade-in">
        <?php if ($created): ?>
          <div class="kt-feature-icon mx-auto mb-3" style="width:70px;height:70px;font-size:1.8rem;"><i class="fa-solid fa-circle-check"></i></div>
          <h3>Administrator account created!</h3>
          <p class="kt-muted">You can now log in to the admin panel using the credentials below.</p>
          <div class="kt-card-flat p-3 text-start my-3">
            <div class="mb-1"><b>Email:</b> <?= clean($defaultEmail) ?></div>
            <div class="mb-1"><b>Admission No (as username):</b> <?= clean($defaultAdmissionNo) ?></div>
            <div><b>Password:</b> <?= clean($defaultPassword) ?></div>
          </div>
          <div class="alert alert-warning text-start"><i class="fa-solid fa-triangle-exclamation me-2"></i>For security, please change this password after your first login, and delete <code>setup_admin.php</code> from the server.</div>
        <?php else: ?>
          <div class="kt-feature-icon gold mx-auto mb-3" style="width:70px;height:70px;font-size:1.8rem;"><i class="fa-solid fa-circle-info"></i></div>
          <h3>An administrator account already exists</h3>
          <p class="kt-muted">No changes were made. You can proceed to log in.</p>
        <?php endif; ?>
        <a href="admin/login.php" class="btn btn-kt-primary btn-lg mt-3"><i class="fa-solid fa-user-shield me-2"></i>Go to Staff Portal Login</a>
        <p class="small kt-muted mt-3 mb-0">This account can create more Registrar / HOD / Trainer accounts from <b>Staff Accounts</b> in the admin sidebar.</p>
      </div>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>

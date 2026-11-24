<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid($_POST['csrf_token'] ?? '')) {

    if (isset($_POST['update_profile'])) {
        $full_name = clean($_POST['full_name'] ?? '');
        $phone     = clean($_POST['phone'] ?? '');
        $course    = clean($_POST['course'] ?? '');

        if ($full_name === '') $errors[] = "Full name cannot be empty.";

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, course = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $course, $userId]);
            $_SESSION['full_name'] = $full_name;
            flash('main', 'Profile updated successfully.', 'success');
            redirect(BASE_URL . 'profile.php');
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors[] = "Your current password is incorrect.";
        } elseif (strlen($new) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($new !== $confirm) {
            $errors[] = "New passwords do not match.";
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            flash('main', 'Password changed successfully.', 'success');
            redirect(BASE_URL . 'profile.php');
        }
    }
}

// Quick stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM missing_marks_reports WHERE user_id = ?");
$stmt->execute([$userId]);
$totalReports = $stmt->fetchColumn();

$pageTitle = "My Profile";
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo '<li>'.$e.'</li>'; ?></ul></div>
  <?php endif; ?>

  <h3 class="mb-4">My Profile</h3>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="kt-card-flat p-4 text-center">
        <div class="kt-avatar kt-avatar-lg mx-auto mb-3"><?= initials($user['full_name']) ?></div>
        <h5 class="mb-0"><?= clean($user['full_name']) ?></h5>
        <p class="kt-muted small"><?= clean($user['admission_no']) ?></p>
        <hr>
        <div class="d-flex justify-content-around">
          <div><div class="fw-bold fs-4"><?= (int)$totalReports ?></div><div class="small kt-muted">Reports Filed</div></div>
          <div><div class="fw-bold fs-4"><?= date('Y', strtotime($user['created_at'])) ?></div><div class="small kt-muted">Member Since</div></div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="kt-card-flat p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-user-pen me-2 text-success"></i>Edit Details</h5>
        <form method="POST" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Admission Number</label>
              <input type="text" class="form-control" value="<?= clean($user['admission_no']) ?>" disabled>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="text" class="form-control" value="<?= clean($user['email']) ?>" disabled>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Phone Number</label>
              <input type="text" name="phone" class="form-control" value="<?= clean($user['phone']) ?>">
            </div>
            <div class="col-md-12 mb-3">
              <label class="form-label">Course / Program</label>
              <input type="text" name="course" class="form-control" value="<?= clean($user['course']) ?>">
            </div>
          </div>
          <button type="submit" name="update_profile" class="btn btn-kt-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
        </form>
      </div>

      <div class="kt-card-flat p-4">
        <h5 class="mb-3"><i class="fa-solid fa-lock me-2 text-success"></i>Change Password</h5>
        <form method="POST" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_new_password" class="form-control" minlength="6" required>
            </div>
          </div>
          <button type="submit" name="change_password" class="btn btn-kt-soft"><i class="fa-solid fa-key me-2"></i>Update Password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>

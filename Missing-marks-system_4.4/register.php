<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
}

$errors = [];
$old = ['full_name' => '', 'admission_no' => '', 'email' => '', 'phone' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? '')) {
        $errors[] = "Your session expired. Please try submitting the form again.";
    }

    $old['full_name']    = clean($_POST['full_name'] ?? '');
    $old['admission_no'] = clean($_POST['admission_no'] ?? '');
    $old['email']        = clean($_POST['email'] ?? '');
    $old['phone']        = clean($_POST['phone'] ?? '');
    $old['course']       = clean($_POST['course'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '')             $errors[] = "Full name is required.";
    if ($old['admission_no'] === '')          $errors[] = "Admission number is required.";
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (strlen($password) < 6)                $errors[] = "Password must be at least 6 characters long.";
    if ($password !== $confirm_password)      $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // Uniqueness checks
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR admission_no = ?");
        $stmt->execute([$old['email'], $old['admission_no']]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email or admission number already exists.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, admission_no, email, phone, course, password, role)
                                VALUES (?, ?, ?, ?, ?, ?, 'student')");
        $stmt->execute([$old['full_name'], $old['admission_no'], $old['email'], $old['phone'], $old['course'], $hash]);

        flash('main', 'Account created successfully! You can now log in.', 'success');
        redirect(BASE_URL . 'login.php');
    }
}

$pageTitle = "Create Account";
require 'includes/header.php';
?>

<div class="kt-auth-wrap">
  <div class="kt-auth-side d-none d-lg-flex align-items-center justify-content-center p-5">
    <div class="content text-center">
      <div class="kt-brand-badge mx-auto mb-4" style="width:64px;height:64px;font-size:1.8rem;"><i class="fa-solid fa-graduation-cap"></i></div>
      <h2 class="text-white">Join the Missing Marks Resolution System</h2>
      <p class="mt-3" style="color:rgba(255,255,255,.8)">Create your account to start reporting and tracking missing marks with the examinations office — quick, transparent, and paperless.</p>
      <div class="d-flex justify-content-center gap-4 mt-5">
        <div class="text-center"><i class="fa-solid fa-shield-halved fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">Secure</div></div>
        <div class="text-center"><i class="fa-solid fa-bolt fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">Fast</div></div>
        <div class="text-center"><i class="fa-solid fa-eye fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">Transparent</div></div>
      </div>
    </div>
  </div>

  <div class="kt-auth-form">
    <div class="kt-form-card fade-in">
      <a href="index.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to home</a>
      <h3 class="mb-1">Create your student account</h3>
      <p class="kt-muted mb-4">Fill in your details to get started.</p>

      <?php renderFlash(); ?>
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e) echo '<li>' . $e . '</li>'; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="full_name" class="form-control" placeholder="e.g. Wanjiku Kamau" value="<?= $old['full_name'] ?>" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Admission Number</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
              <input type="text" name="admission_no" class="form-control" placeholder="e.g. TVET/2024/1234" value="<?= $old['admission_no'] ?>" required>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
              <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= $old['phone'] ?>">
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= $old['email'] ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Course / Program</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-book"></i></span>
            <input type="text" name="course" class="form-control" placeholder="e.g. Diploma in ICT" value="<?= $old['course'] ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-1">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input type="password" id="password" name="password" class="form-control" minlength="6" required>
              <button class="btn btn-kt-soft kt-toggle-pass" type="button" data-target="#password"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div class="progress mt-2" style="height:5px;"><div id="pwStrengthBar" class="progress-bar" style="width:0%"></div></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
              <button class="btn btn-kt-soft kt-toggle-pass" type="button" data-target="#confirm_password"><i class="fa-solid fa-eye"></i></button>
            </div>
            <div id="passMatchHint" class="form-text"></div>
          </div>
        </div>

        <button type="submit" class="btn btn-kt-primary w-100 btn-lg mt-2"><i class="fa-solid fa-user-plus me-2"></i>Create Account</button>

        <p class="text-center kt-muted mt-4 mb-0">Already have an account? <a href="login.php" class="fw-semibold">Login here</a></p>
      </form>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>

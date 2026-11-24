<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
}

$errors = [];
$old_identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid($_POST['csrf_token'] ?? '')) {
        $errors[] = "Your session expired. Please try again.";
    }

    $identifier = clean($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $old_identifier = $identifier;

    if ($identifier === '' || $password === '') {
        $errors[] = "Please enter both your email/admission number and password.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR admission_no = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = "Incorrect credentials. Please check and try again.";
        } elseif ($user['status'] === 'disabled') {
            $errors[] = "Your account has been disabled. Please contact the registrar's office.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['full_name']    = $user['full_name'];
            $_SESSION['admission_no'] = $user['admission_no'];
            $_SESSION['email']        = $user['email'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['staff_role']   = $user['staff_role'] ?? null;

            flash('main', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!', 'success');
            redirect(BASE_URL . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
        }
    }
}

$pageTitle = "Login";
require 'includes/header.php';
?>

<div class="kt-auth-wrap">
  <div class="kt-auth-side d-none d-lg-flex align-items-center justify-content-center p-5">
    <div class="content text-center">
      <div class="kt-brand-badge mx-auto mb-4" style="width:64px;height:64px;font-size:1.8rem;"><i class="fa-solid fa-graduation-cap"></i></div>
      <h2 class="text-white">Welcome back</h2>
      <p class="mt-3" style="color:rgba(255,255,255,.8)">Log in to report a new missing marks case or follow up on one you already submitted.</p>
      <div class="kt-card p-3 mt-4 text-start" style="background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.2);">
        <div class="d-flex align-items-center gap-2 text-white mb-2"><i class="fa-solid fa-circle-info" style="color:var(--kt-gold)"></i><span class="fw-semibold small">Academic Registrar, HOD or Trainer?</span></div>
        <p class="small mb-2" style="color:rgba(255,255,255,.75)">Staff have their own dedicated portal to review, approve/reject and grade missing marks cases.</p>
        <a href="admin/login.php" class="btn btn-kt-outline btn-sm w-100"><i class="fa-solid fa-user-shield me-2"></i>Go to Staff Portal</a>
      </div>
    </div>
  </div>

  <div class="kt-auth-form">
    <div class="kt-form-card fade-in">
      <a href="index.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to home</a>
      <h3 class="mb-1">Log in to your account</h3>
      <p class="kt-muted mb-4">Enter your credentials to continue.</p>

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
          <label class="form-label">Email or Admission Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="identifier" class="form-control" placeholder="you@example.com or TVET/2024/1234"
                   value="<?= clean($old_identifier) ?>" required autofocus>
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="password" name="password" class="form-control" required>
            <button class="btn btn-kt-soft kt-toggle-pass" type="button" data-target="#password"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>

        <button type="submit" class="btn btn-kt-primary w-100 btn-lg mt-3"><i class="fa-solid fa-right-to-bracket me-2"></i>Login</button>

        <p class="text-center kt-muted mt-4 mb-0">Don't have an account? <a href="register.php" class="fw-semibold">Register here</a></p>
      </form>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>

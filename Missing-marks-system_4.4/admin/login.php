<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

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
        $errors[] = "Please enter both your email/staff number and password.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR admission_no = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = "Incorrect credentials. Please check and try again.";
        } elseif ($user['role'] !== 'admin') {
            $errors[] = "This portal is reserved for staff (Academic Registrar, HOD &amp; Trainers). Students should use the student login page.";
        } elseif ($user['status'] === 'disabled') {
            $errors[] = "Your account has been disabled. Please contact the system administrator.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['full_name']    = $user['full_name'];
            $_SESSION['admission_no'] = $user['admission_no'];
            $_SESSION['email']        = $user['email'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['staff_role']   = $user['staff_role'];

            flash('main', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!', 'success');
            redirect(BASE_URL . 'admin/dashboard.php');
        }
    }
}

$pageTitle = "Staff Login";
require '../includes/header.php';
?>

<div class="kt-auth-wrap">
  <div class="kt-auth-side d-none d-lg-flex align-items-center justify-content-center p-5" style="background:linear-gradient(150deg,#0a0f0d,#0b3d2c 65%,var(--kt-green));">
    <div class="content text-center">
      <div class="kt-brand-badge mx-auto mb-4" style="width:64px;height:64px;font-size:1.8rem; background:linear-gradient(135deg,#2c3e50,#4a6572);"><i class="fa-solid fa-user-shield"></i></div>
      <h2 class="text-white">Staff Portal</h2>
      <p class="mt-3" style="color:rgba(255,255,255,.8)">Reserved access for the Academic Registrar, Heads of Department and Trainers to review, approve, reject and grade missing marks cases.</p>
      <div class="d-flex justify-content-center gap-4 mt-5">
        <div class="text-center"><i class="fa-solid fa-user-tie fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">Registrar</div></div>
        <div class="text-center"><i class="fa-solid fa-user-shield fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">HOD</div></div>
        <div class="text-center"><i class="fa-solid fa-chalkboard-user fs-3 mb-2" style="color:var(--kt-gold)"></i><div class="small">Trainer</div></div>
      </div>
    </div>
  </div>

  <div class="kt-auth-form">
    <div class="kt-form-card fade-in">
      <a href="../index.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to home</a>
      <span class="kt-pill mb-3" style="background:#eef3f1;border-color:#dfe7e3;color:var(--kt-charcoal);"><i class="fa-solid fa-shield-halved"></i> Staff / Admin Access</span>
      <h3 class="mb-1 mt-3">Sign in to the Staff Portal</h3>
      <p class="kt-muted mb-4">For Academic Registrar, HOD &amp; Trainer accounts only.</p>

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
          <label class="form-label">Email or Staff/Admission Number</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-user-tie"></i></span>
            <input type="text" name="identifier" class="form-control" placeholder="you@kstvet.ac.ke or ADMIN001"
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

        <button type="submit" class="btn btn-kt-primary w-100 btn-lg mt-3"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign In to Staff Portal</button>

        <p class="text-center kt-muted mt-4 mb-0 small">Are you a student? <a href="../login.php" class="fw-semibold">Go to student login</a></p>
      </form>
    </div>
  </div>
</div>

<?php require '../includes/footer.php'; ?>

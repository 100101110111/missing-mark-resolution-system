<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid($_POST['csrf_token'] ?? '')) {

    if (isset($_POST['add_staff'])) {
        $full_name    = clean($_POST['full_name'] ?? '');
        $staff_no     = clean($_POST['staff_no'] ?? '');
        $email        = clean($_POST['email'] ?? '');
        $phone        = clean($_POST['phone'] ?? '');
        $department   = clean($_POST['department'] ?? '');
        $staff_role   = clean($_POST['staff_role'] ?? '');
        $password     = $_POST['password'] ?? '';
        $allowedRoles = ['Registrar', 'HOD', 'Trainer'];

        if ($full_name === '')                       $errors[] = 'Full name is required.';
        if ($staff_no === '')                         $errors[] = 'Staff/ID number is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (!in_array($staff_role, $allowedRoles, true)) $errors[] = 'Please select a valid staff position.';
        if (strlen($password) < 6)                    $errors[] = 'Password must be at least 6 characters.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR admission_no = ?");
            $stmt->execute([$email, $staff_no]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email or staff number already exists.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, admission_no, email, phone, course, password, role, staff_role)
                                    VALUES (?, ?, ?, ?, ?, ?, 'admin', ?)");
            $stmt->execute([$full_name, $staff_no, $email, $phone ?: null, $department ?: null, $hash, $staff_role]);
            flash('main', "Staff account for \"$full_name\" ($staff_role) created successfully.", 'success');
            redirect('staff.php');
        }
    }

    if (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['user_id'];
        if ($id === (int)$_SESSION['user_id']) {
            flash('main', 'You cannot disable your own account.', 'danger');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active','disabled','active') WHERE id = ? AND role = 'admin'");
            $stmt->execute([$id]);
            flash('main', 'Staff account status updated.', 'info');
        }
        redirect('staff.php');
    }
}

$staffMembers = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Staff Management";
require 'includes/admin_header.php';
?>

<?php renderFlash(); ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $e) echo '<li>'.$e.'</li>'; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="kt-card-flat p-4">
      <h6 class="mb-3"><i class="fa-solid fa-user-plus me-2 text-success"></i>Add Staff Account</h6>
      <p class="small kt-muted">Onboard more Academic Registrar, HOD or Trainer accounts as your team grows — the system scales to as many staff and students as you need.</p>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" placeholder="e.g. Mr. Daniel Mwangi" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Staff / ID No.</label>
            <input type="text" name="staff_no" class="form-control" placeholder="STAFF001" required>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Position</label>
            <select name="staff_role" class="form-select" required>
              <option value="" selected disabled>-- Select --</option>
              <option>Registrar</option>
              <option>HOD</option>
              <option>Trainer</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="staff@kstvet.ac.ke" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX">
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control" placeholder="e.g. ICT">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Temporary Password</label>
          <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <button type="submit" name="add_staff" class="btn btn-kt-primary w-100"><i class="fa-solid fa-user-plus me-2"></i>Create Staff Account</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="kt-card-flat p-4">
      <h6 class="mb-3">Staff Directory (<?= count($staffMembers) ?>)</h6>
      <?php if (empty($staffMembers)): ?>
        <div class="kt-empty"><i class="fa-solid fa-user-shield"></i><p class="mb-0">No staff accounts yet.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-kt align-middle mb-0">
          <thead><tr><th>Staff</th><th>Position</th><th>Email</th><th>Department</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($staffMembers as $s): ?>
            <tr>
              <td class="d-flex align-items-center gap-2">
                <span class="kt-avatar" style="width:34px;height:34px;font-size:.7rem;"><?= initials($s['full_name']) ?></span>
                <div><?= clean($s['full_name']) ?><div class="small kt-muted"><?= clean($s['admission_no']) ?></div></div>
              </td>
              <td><span class="badge bg-light text-dark border"><i class="fa-solid <?= staffRoleIcon($s['staff_role']) ?> me-1"></i><?= clean(staffRoleLabel($s['staff_role'])) ?></span></td>
              <td><?= clean($s['email']) ?></td>
              <td><?= clean($s['course'] ?: '—') ?></td>
              <td><?= $s['status']==='active' ? '<span class="badge-status-resolved">Active</span>' : '<span class="badge-status-rejected">Disabled</span>' ?></td>
              <td class="text-end">
                <?php if ((int)$s['id'] !== (int)$_SESSION['user_id']): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Change status for this staff account?');">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                  <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-secondary">
                    <?= $s['status']==='active' ? '<i class="fa-solid fa-ban"></i>' : '<i class="fa-solid fa-check"></i>' ?>
                  </button>
                </form>
                <?php else: ?>
                  <span class="small kt-muted">You</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require 'includes/admin_footer.php'; ?>

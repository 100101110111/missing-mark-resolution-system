<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['add_trainer'])) {
        $name = clean($_POST['trainer_name'] ?? '');
        $dept = clean($_POST['department'] ?? '');
        if ($name !== '') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO trainers (trainer_name, department) VALUES (?, ?)");
            $stmt->execute([$name, $dept ?: null]);
            flash('main', 'Trainer added successfully.', 'success');
        }
        redirect('trainers.php');
    }
    if (isset($_POST['delete_trainer'])) {
        $id = (int)$_POST['trainer_id'];
        $stmt = $pdo->prepare("DELETE FROM trainers WHERE id = ?");
        $stmt->execute([$id]);
        flash('main', 'Trainer removed.', 'info');
        redirect('trainers.php');
    }
}

$trainers = $pdo->query("
    SELECT t.*, (SELECT COUNT(*) FROM missing_marks_reports r WHERE r.trainer_name = t.trainer_name) AS case_count
    FROM trainers t ORDER BY t.trainer_name
")->fetchAll();

$pageTitle = "Trainers";
require 'includes/admin_header.php';
?>

<?php renderFlash(); ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="kt-card-flat p-4">
      <h6 class="mb-3"><i class="fa-solid fa-user-plus me-2 text-success"></i>Add Trainer</h6>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label">Trainer Name</label>
          <input type="text" name="trainer_name" class="form-control" placeholder="e.g. Ms. Faith Njeri" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control" placeholder="e.g. Business Studies">
        </div>
        <button type="submit" name="add_trainer" class="btn btn-kt-primary w-100"><i class="fa-solid fa-plus me-2"></i>Add Trainer</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="kt-card-flat p-4">
      <h6 class="mb-3">Trainer Directory (<?= count($trainers) ?>)</h6>
      <?php if (empty($trainers)): ?>
        <div class="kt-empty"><i class="fa-solid fa-chalkboard-user"></i><p class="mb-0">No trainers added yet.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-kt align-middle mb-0">
          <thead><tr><th>Trainer</th><th>Department</th><th>Missing Marks Cases</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($trainers as $t): ?>
            <tr>
              <td class="fw-semibold"><?= clean($t['trainer_name']) ?></td>
              <td><?= clean($t['department'] ?: '—') ?></td>
              <td><span class="badge bg-light text-dark border"><?= (int)$t['case_count'] ?></span></td>
              <td class="text-end">
                <form method="POST" onsubmit="return confirm('Remove this trainer from the list?');" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="trainer_id" value="<?= $t['id'] ?>">
                  <button type="submit" name="delete_trainer" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                </form>
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

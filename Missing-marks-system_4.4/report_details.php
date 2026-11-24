<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM missing_marks_reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    flash('main', 'Report not found, or you do not have permission to view it.', 'danger');
    redirect(BASE_URL . 'my_reports.php');
}

$stmt = $pdo->prepare("SELECT h.*, u.full_name AS changed_by_name, u.role AS changed_by_role
                        FROM report_status_history h
                        LEFT JOIN users u ON u.id = h.changed_by
                        WHERE h.report_id = ? ORDER BY h.changed_at ASC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$pageTitle = "Report " . $report['reference_no'];
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-4">
  <a href="my_reports.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to my reports</a>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1">Report <?= clean($report['reference_no']) ?></h3>
      <p class="kt-muted mb-0">Submitted on <?= formatDate($report['created_at']) ?></p>
    </div>
    <span class="<?= statusBadgeClass($report['status']) ?> fs-6"><i class="fa-solid <?= statusIcon($report['status']) ?> me-1"></i><?= $report['status'] ?></span>
  </div>

  <div class="kt-card-flat p-4 mb-4">
    <h5 class="mb-3 text-center"><i class="fa-solid fa-route me-2 text-success"></i>Verification Progress</h5>
    <div class="d-flex justify-content-center">
      <?php renderStatusTracker($report['status']); ?>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="kt-card-flat p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-circle-info me-2 text-success"></i>Case Details</h5>
        <div class="row g-3">
          <div class="col-md-6"><div class="small kt-muted">Student Name</div><div class="fw-semibold"><?= clean($report['student_name']) ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">Admission No.</div><div class="fw-semibold"><?= clean($report['admission_no']) ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">Trainer</div><div class="fw-semibold"><?= clean($report['trainer_name']) ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">Semester</div><div class="fw-semibold"><?= clean($report['semester']) ?></div></div>
          <div class="col-md-8"><div class="small kt-muted">Unit</div><div class="fw-semibold"><?= clean($report['unit_name']) ?></div></div>
          <div class="col-md-4"><div class="small kt-muted">Unit Code</div><div class="fw-semibold"><?= clean($report['unit_code'] ?: '—') ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">Academic Year</div><div class="fw-semibold"><?= clean($report['academic_year']) ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">Assessment Type</div><div class="fw-semibold"><?= clean($report['exam_type']) ?></div></div>
          <?php if ($report['exam_type'] === 'CAT'): ?>
          <div class="col-md-6"><div class="small kt-muted">CAT 1</div><div class="fw-semibold"><?= $report['cat1_mark'] !== null ? clean(rtrim(rtrim((string)$report['cat1_mark'], '0'), '.')) . ' / 30' : '—' ?></div></div>
          <div class="col-md-6"><div class="small kt-muted">CAT 2</div><div class="fw-semibold"><?= $report['cat2_mark'] !== null ? clean(rtrim(rtrim((string)$report['cat2_mark'], '0'), '.')) . ' / 70' : '—' ?></div></div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($report['cat_grade'] || $report['exam_grade']): ?>
      <div class="kt-card-flat p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-graduation-cap me-2 text-success"></i>Awarded Grades</h5>
        <div class="row g-3">
          <div class="col-6">
            <div class="small kt-muted">CAT Grade</div>
            <span class="badge <?= gradeBadgeClass($report['cat_grade']) ?> fs-6"><?= clean($report['cat_grade'] ?: 'Not awarded') ?></span>
          </div>
          <div class="col-6">
            <div class="small kt-muted">End Term Grade</div>
            <span class="badge <?= gradeBadgeClass($report['exam_grade']) ?> fs-6"><?= clean($report['exam_grade'] ?: 'Not awarded') ?></span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($report['description']): ?>
      <div class="kt-card-flat p-4 mb-4">
        <h5 class="mb-3"><i class="fa-solid fa-align-left me-2 text-success"></i>Description</h5>
        <p class="mb-0"><?= nl2br(clean($report['description'])) ?></p>
      </div>
      <?php endif; ?>

      <div class="kt-card-flat p-4" style="background:#f0f6f3;">
        <h5 class="mb-2"><i class="fa-solid fa-comment-dots me-2 text-success"></i>Registrar's Remarks</h5>
        <p class="mb-0"><?= $report['admin_remarks'] ? nl2br(clean($report['admin_remarks'])) : '<span class="kt-muted">No remarks yet — your case is being processed.</span>' ?></p>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="kt-card-flat p-4">
        <h5 class="mb-4"><i class="fa-solid fa-timeline me-2 text-success"></i>Status Timeline</h5>
        <div class="kt-timeline">
          <?php foreach ($history as $h): ?>
            <?php $cls = strtolower(str_replace(' ', '', $h['status'])); if ($cls === 'inreview') $cls = 'review'; ?>
            <div class="kt-timeline-item <?= $cls === 'rejected' ? 'rejected' : ($cls === 'review' ? 'review' : ($cls === 'pending' ? 'pending' : '')) ?>">
              <div class="kt-timeline-dot"><i class="fa-solid <?= statusIcon($h['status']) ?> fa-2xs"></i></div>
              <div class="fw-semibold"><?= clean($h['status']) ?></div>
              <div class="small kt-muted mb-1"><?= formatDate($h['changed_at']) ?> &middot; <?= $h['changed_by_role'] === 'admin' ? 'Registrar' : 'You' ?></div>
              <?php if ($h['remarks']): ?><div class="small"><?= clean($h['remarks']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>

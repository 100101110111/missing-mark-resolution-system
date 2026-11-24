<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, u.email AS student_email, u.phone AS student_phone
                        FROM missing_marks_reports r JOIN users u ON u.id = r.user_id
                        WHERE r.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    flash('main', 'Report not found.', 'danger');
    redirect('reports.php');
}

$stmt = $pdo->prepare("SELECT h.*, u.full_name AS changed_by_name, u.role AS changed_by_role
                        FROM report_status_history h LEFT JOIN users u ON u.id = h.changed_by
                        WHERE h.report_id = ? ORDER BY h.changed_at ASC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$pageTitle = "Review Report";
require 'includes/admin_header.php';
?>

<a href="reports.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to all reports</a>

<?php renderFlash(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h4 class="mb-1">Report <?= clean($report['reference_no']) ?></h4>
    <p class="kt-muted mb-0">Submitted <?= formatDate($report['created_at']) ?> by <?= clean($report['student_name']) ?></p>
  </div>
  <span class="<?= statusBadgeClass($report['status']) ?> fs-6"><i class="fa-solid <?= statusIcon($report['status']) ?> me-1"></i><?= $report['status'] ?></span>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="kt-card-flat p-4 mb-4">
      <h6 class="mb-3"><i class="fa-solid fa-circle-info me-2 text-success"></i>Case Details</h6>
      <div class="row g-3">
        <div class="col-md-6"><div class="small kt-muted">Student Name</div><div class="fw-semibold"><?= clean($report['student_name']) ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Admission No.</div><div class="fw-semibold"><?= clean($report['admission_no']) ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Email</div><div class="fw-semibold"><?= clean($report['student_email']) ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Phone</div><div class="fw-semibold"><?= clean($report['student_phone'] ?: '—') ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Trainer</div><div class="fw-semibold"><?= clean($report['trainer_name']) ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Semester</div><div class="fw-semibold"><?= clean($report['semester']) ?></div></div>
        <div class="col-md-8"><div class="small kt-muted">Unit</div><div class="fw-semibold"><?= clean($report['unit_name']) ?></div></div>
        <div class="col-md-4"><div class="small kt-muted">Unit Code</div><div class="fw-semibold"><?= clean($report['unit_code'] ?: '—') ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Academic Year</div><div class="fw-semibold"><?= clean($report['academic_year']) ?></div></div>
        <div class="col-md-6"><div class="small kt-muted">Assessment Type</div><div class="fw-semibold"><?= clean($report['exam_type']) ?></div></div>
        <div class="col-md-4"><div class="small kt-muted">CAT 1</div><div><?php if ($report['exam_type'] === 'Both CATS & End Term'): ?><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6"><i class="fa-solid fa-clock me-1"></i>Pending award</span><?php elseif ($report['exam_type'] === 'CAT 1'): ?><span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6"><?= $report['cat1_mark'] !== null ? clean(rtrim(rtrim((string)$report['cat1_mark'], '0'), '.')) . ' / 30' : 'Pending award' ?></span><?php else: ?><span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="fa-solid fa-lock me-1"></i>AWARDED</span><?php endif; ?></div></div>
        <div class="col-md-4"><div class="small kt-muted">CAT 2</div><div><?php if ($report['exam_type'] === 'Both CATS & End Term'): ?><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6"><i class="fa-solid fa-clock me-1"></i>Pending award</span><?php elseif ($report['exam_type'] === 'CAT 2'): ?><span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6"><?= $report['cat2_mark'] !== null ? clean(rtrim(rtrim((string)$report['cat2_mark'], '0'), '.')) . ' / 70' : 'Pending award' ?></span><?php else: ?><span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="fa-solid fa-lock me-1"></i>AWARDED</span><?php endif; ?></div></div>
        <div class="col-md-4"><div class="small kt-muted">End Term</div><div><?php if ($report['exam_type'] === 'Both CATS & End Term'): ?><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6"><i class="fa-solid fa-clock me-1"></i>Pending award</span><?php elseif ($report['exam_type'] === 'CAT 1' || $report['exam_type'] === 'CAT 2'): ?><span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><i class="fa-solid fa-lock me-1"></i>AWARDED</span><?php else: ?><span class="badge <?= gradeBadgeClass($report['exam_grade']) ?> fs-6"><?= clean($report['exam_grade'] ?: 'Pending award') ?></span><?php endif; ?></div></div>
      </div>
      <?php if ($report['description']): ?>
        <hr>
        <div class="small kt-muted mb-1">Student's Description</div>
        <p class="mb-0"><?= nl2br(clean($report['description'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="kt-card-flat p-4 mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h6 class="mb-1"><i class="fa-solid fa-graduation-cap me-2 text-success"></i>Award Missing Result</h6>
          <p class="small kt-muted mb-0">The workflow opens the assessment(s) selected in the student's report. For <strong>Both CATS &amp; End Term</strong>, all three tabs and fields are fully open.</p>
        </div>
        <span class="badge bg-dark-subtle text-dark border"><i class="fa-solid fa-route me-1"></i><?= clean($report['exam_type']) ?></span>
      </div>

      <form method="POST" action="actions/update_report.php" id="awardForm" class="award-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
        <input type="hidden" name="status" value="<?= clean($report['status']) ?>">
        <input type="hidden" name="admin_remarks" value="<?= clean($report['admin_remarks']) ?>">

        <ul class="nav nav-pills award-tabs mb-3" role="tablist">
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= $report['exam_type']==='CAT 1' || $report['exam_type']==='Both CATS & End Term'?'active':'' ?>" data-award-tab="cat1">
              <i class="fa-solid fa-1 me-1"></i> CAT 1 <span class="tab-range">/ 30</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= $report['exam_type']==='CAT 2' || $report['exam_type']==='Both CATS & End Term'?'active':'' ?>" data-award-tab="cat2">
              <i class="fa-solid fa-2 me-1"></i> CAT 2 <span class="tab-range">/ 70</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= in_array($report['exam_type'], ['END TERM','Both CATS & End Term'], true)?'active':'' ?>" data-award-tab="endterm">
              <i class="fa-solid fa-file-circle-check me-1"></i> End Term
            </button>
          </li>
        </ul>

        <div class="award-panel" data-panel="cat1">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 fw-semibold">CAT 1 Mark</label>
            <span class="badge bg-success-subtle text-success border border-success-subtle">0–30</span>
          </div>
          <input type="number" name="cat1_mark" id="admin_cat1_mark" class="form-control form-control-lg"
                 min="0" max="30" step="0.01" inputmode="decimal"
                 value="<?= $report['cat1_mark'] !== null ? clean($report['cat1_mark']) : '' ?>"
                 placeholder="Enter CAT 1 mark (0–30)">
          <div class="form-text">CAT 1 accepts <strong>0–30</strong>. It is open for a combined <strong>Both CATS &amp; End Term</strong> report.</div>
        </div>

        <div class="award-panel" data-panel="cat2">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 fw-semibold">CAT 2 Mark</label>
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">0–70</span>
          </div>
          <input type="number" name="cat2_mark" id="admin_cat2_mark" class="form-control form-control-lg"
                 min="0" max="70" step="0.01" inputmode="decimal"
                 value="<?= $report['cat2_mark'] !== null ? clean($report['cat2_mark']) : '' ?>"
                 placeholder="Enter CAT 2 mark (0–70)">
          <div class="form-text">CAT 2 accepts <strong>0–70</strong>. It is open for a combined <strong>Both CATS &amp; End Term</strong> report.</div>
        </div>

        <div class="award-panel" data-panel="endterm">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 fw-semibold">End Term Grade</label>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">A to E</span>
          </div>
          <select name="exam_grade" id="admin_exam_grade" class="form-select form-select-lg">
            <option value="">-- Select grade --</option>
            <?php foreach (gradeOptions() as $g): ?>
              <option value="<?= $g ?>" <?= $report['exam_grade']===$g?'selected':'' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">End Term is open for <strong>Both CATS &amp; End Term</strong> and accepts: <strong>A, A-, B+, B, B-, C+, C, C-, D+, D, D-, E</strong>.</div>
        </div>

        <div id="lockedAssessmentNotice" class="locked-assessment mt-3"></div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
          <span class="small kt-muted"><i class="fa-solid fa-unlock-keyhole me-1"></i>Open assessment fields can be entered or changed by the administrator.</span>
          <button type="submit" name="save_grades" class="btn btn-kt-primary">
            <i class="fa-solid fa-floppy-disk me-2"></i>Save Award
          </button>
        </div>
      </form>
    </div>

    <div class="kt-card-flat p-4">
      <h6 class="mb-3"><i class="fa-solid fa-pen-to-square me-2 text-success"></i>Update Status &amp; Remarks</h6>

      <div class="d-flex flex-wrap gap-2 mb-4">
        <form method="POST" action="actions/update_report.php" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
          <input type="hidden" name="cat1_mark" value="<?= clean($report['cat1_mark']) ?>">
          <input type="hidden" name="cat2_mark" value="<?= clean($report['cat2_mark']) ?>">
          <input type="hidden" name="exam_grade" value="<?= clean($report['exam_grade']) ?>">
          <input type="hidden" name="status" value="Resolved">
          <input type="hidden" name="admin_remarks" value="<?= clean($report['admin_remarks'] ?: 'Your request has been approved and resolved.') ?>">
          <button type="submit" class="btn btn-kt-primary"><i class="fa-solid fa-circle-check me-2"></i>Approve &amp; Resolve</button>
        </form>
        <form method="POST" action="actions/update_report.php" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
          <input type="hidden" name="cat1_mark" value="<?= clean($report['cat1_mark']) ?>">
          <input type="hidden" name="cat2_mark" value="<?= clean($report['cat2_mark']) ?>">
          <input type="hidden" name="exam_grade" value="<?= clean($report['exam_grade']) ?>">
          <input type="hidden" name="status" value="Rejected">
          <input type="hidden" name="admin_remarks" value="<?= clean($report['admin_remarks'] ?: 'This request has been rejected. Please contact the examinations office for details.') ?>">
          <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Reject this request?');"><i class="fa-solid fa-circle-xmark me-2"></i>Reject</button>
        </form>
      </div>

      <form method="POST" action="actions/update_report.php">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
        <input type="hidden" name="cat1_mark" value="<?= clean($report['cat1_mark']) ?>">
        <input type="hidden" name="cat2_mark" value="<?= clean($report['cat2_mark']) ?>">
        <input type="hidden" name="exam_grade" value="<?= clean($report['exam_grade']) ?>">
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <?php foreach (['Pending','In Review','Resolved','Rejected'] as $s): ?>
              <option value="<?= $s ?>" <?= $report['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Remarks to Student</label>
          <textarea name="admin_remarks" class="form-control" rows="3" placeholder="e.g. Marks have been updated on the portal. Please check within 24 hours."><?= clean($report['admin_remarks']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-kt-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Update</button>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="kt-card-flat p-4">
      <h6 class="mb-4"><i class="fa-solid fa-timeline me-2 text-success"></i>Status Timeline</h6>
      <div class="kt-timeline">
        <?php foreach ($history as $h): ?>
          <?php $cls = strtolower(str_replace(' ', '', $h['status'])); if ($cls === 'inreview') $cls = 'review'; ?>
          <div class="kt-timeline-item <?= $cls==='rejected'?'rejected':($cls==='review'?'review':($cls==='pending'?'pending':'')) ?>">
            <div class="kt-timeline-dot"><i class="fa-solid <?= statusIcon($h['status']) ?> fa-2xs"></i></div>
            <div class="fw-semibold"><?= clean($h['status']) ?></div>
            <div class="small kt-muted mb-1"><?= formatDate($h['changed_at']) ?> &middot; <?= $h['changed_by_role']==='admin' ? clean($h['changed_by_name'] ?: 'Registrar') : 'Student' ?></div>
            <?php if ($h['remarks']): ?><div class="small"><?= clean($h['remarks']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = <?= json_encode($report['exam_type']) ?>;
    const cat1 = document.getElementById('admin_cat1_mark');
    const cat2 = document.getElementById('admin_cat2_mark');
    const endterm = document.getElementById('admin_exam_grade');
    const notice = document.getElementById('lockedAssessmentNotice');

    const active = {
        'CAT 1': ['cat1'],
        'CAT 2': ['cat2'],
        'END TERM': ['endterm'],
        'Both CATS & End Term': ['cat1', 'cat2', 'endterm']
    }[type] || [];

    const labels = {cat1: 'CAT 1', cat2: 'CAT 2', endterm: 'End Term'};
    document.querySelectorAll('[data-award-tab]').forEach(btn => {
        const key = btn.dataset.awardTab;
        const isOpen = active.includes(key);
        btn.disabled = !isOpen;
        btn.classList.toggle('is-locked', !isOpen);
        btn.classList.toggle('is-open', isOpen);
        btn.classList.toggle('active', isOpen);
        const range = key === 'cat1' ? '/ 30' : key === 'cat2' ? '/ 70' : '';
        btn.innerHTML = isOpen
            ? '<i class="fa-solid fa-circle-check me-1"></i>' + labels[key] + ' <span class="tab-range">' + range + ' · OPEN</span>'
            : '<i class="fa-solid fa-lock me-1"></i>' + labels[key] + ' <span class="tab-range">' + range + ' · AWARDED</span>';
    });

    [cat1, cat2, endterm].forEach(el => {
        if (!el) return;
        const key = el.id === 'admin_cat1_mark' ? 'cat1' : el.id === 'admin_cat2_mark' ? 'cat2' : 'endterm';
        el.disabled = !active.includes(key);
    });

    const locked = Object.keys(labels).filter(k => !active.includes(k)).map(k => labels[k]);
    notice.classList.toggle('all-open', locked.length === 0);
    notice.innerHTML = locked.length
        ? '<i class="fa-solid fa-lock me-2"></i><strong>AWARDED:</strong> ' + locked.join(' &amp; ') + ' is closed for this report.'
        : '<i class="fa-solid fa-unlock-keyhole me-2"></i><strong>ALL THREE ASSESSMENTS ARE OPEN</strong> — Admin can award CAT 1 (0–30), CAT 2 (0–70), and End Term grades on this combined report.';

    document.getElementById('awardForm').addEventListener('submit', function (e) {
        if (active.includes('cat1') && cat1 && (cat1.value === '' || Number(cat1.value) < 0 || Number(cat1.value) > 30)) {
            e.preventDefault(); cat1.focus(); alert('CAT 1 must be a number from 0 to 30.'); return;
        }
        if (active.includes('cat2') && cat2 && (cat2.value === '' || Number(cat2.value) < 0 || Number(cat2.value) > 70)) {
            e.preventDefault(); cat2.focus(); alert('CAT 2 must be a number from 0 to 70.'); return;
        }
        if (active.includes('endterm') && endterm && !['A','A-','B+','B','B-','C+','C','C-','D+','D','D-','E'].includes(endterm.value)) {
            e.preventDefault(); endterm.focus(); alert('Select an End Term grade from A through E, including A-, B+, B-, C+, C-, D+ and D-.'); return;
        }
    });
});
</script>

<?php require 'includes/admin_footer.php'; ?>

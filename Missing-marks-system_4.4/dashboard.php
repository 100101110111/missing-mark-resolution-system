<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$userId = $_SESSION['user_id'];

// Stats
$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM missing_marks_reports WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$counts = ['Pending' => 0, 'In Review' => 0, 'Resolved' => 0, 'Rejected' => 0];
foreach ($stmt->fetchAll() as $row) { $counts[$row['status']] = (int)$row['c']; }
$total = array_sum($counts);

// Recent reports (last 5)
$stmt = $pdo->prepare("SELECT * FROM missing_marks_reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recent = $stmt->fetchAll();

// Active (non-final) reports to show on the live status tracker
$stmt = $pdo->prepare("SELECT * FROM missing_marks_reports WHERE user_id = ? AND status IN ('Pending','In Review') ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$userId]);
$activeReports = $stmt->fetchAll();

// Recent notifications (sample email/SMS/system) for this student
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();
$unreadCount = 0;
foreach ($notifications as $n) { if (!$n['is_read']) $unreadCount++; }

$pageTitle = "Student Dashboard";
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1">Welcome, <?= clean(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h3>
      <p class="kt-muted mb-0">Here's an overview of your missing marks cases.</p>
    </div>
    <a href="report_new.php" class="btn btn-kt-primary btn-lg">
      <i class="fa-solid fa-circle-plus me-2"></i>Report Missing Marks
    </a>
  </div>

  <!-- Stat cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="kt-stat bg-1">
        <div class="num kt-count" data-count="<?= $total ?>">0</div>
        <div>Total Cases</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kt-stat bg-2">
        <div class="num kt-count" data-count="<?= $counts['Pending'] ?>">0</div>
        <div>Pending</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kt-stat bg-3">
        <div class="num kt-count" data-count="<?= $counts['Resolved'] ?>">0</div>
        <div>Resolved</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="kt-stat bg-4">
        <div class="num kt-count" data-count="<?= $counts['Rejected'] ?>">0</div>
        <div>Rejected</div>
      </div>
    </div>
  </div>

  <!-- Live Status Tracking -->
  <?php if (!empty($activeReports)): ?>
  <div class="kt-card-flat p-4 mb-4">
    <h5 class="mb-3"><i class="fa-solid fa-route me-2 text-success"></i>Status Tracking</h5>
    <p class="kt-muted small mb-4">Follow your active case(s) from submission through verification to a final decision.</p>
    <?php foreach ($activeReports as $ar): ?>
      <div class="mb-4 pb-4 <?= $ar !== end($activeReports) ? 'border-bottom' : '' ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <div>
            <span class="fw-semibold"><?= clean($ar['reference_no']) ?></span>
            <span class="small kt-muted"> — <?= clean($ar['unit_name']) ?></span>
          </div>
          <span class="<?= statusBadgeClass($ar['status']) ?>"><i class="fa-solid <?= statusIcon($ar['status']) ?> me-1"></i><?= $ar['status'] ?></span>
        </div>
        <div class="d-flex justify-content-center">
          <?php renderStatusTracker($ar['status']); ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="kt-card-flat p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-success"></i>Recent Reports</h5>
          <a href="my_reports.php" class="small fw-semibold">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
        </div>

        <?php if (empty($recent)): ?>
          <div class="kt-empty">
            <i class="fa-solid fa-inbox"></i>
            <p class="mb-2">You haven't reported any missing marks yet.</p>
            <a href="report_new.php" class="btn btn-kt-primary">Report your first case</a>
          </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-kt align-middle mb-0">
            <thead><tr><th>Reference</th><th>Unit</th><th>Trainer</th><th>Semester</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td class="fw-semibold"><?= clean($r['reference_no']) ?></td>
                <td><?= clean($r['unit_name']) ?></td>
                <td><?= clean($r['trainer_name']) ?></td>
                <td><?= clean($r['semester']) ?></td>
                <td><span class="<?= statusBadgeClass($r['status']) ?>"><i class="fa-solid <?= statusIcon($r['status']) ?> me-1"></i><?= $r['status'] ?></span></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-kt-soft"
                    data-bs-toggle="modal" data-bs-target="#viewReportModal"
                    data-id="<?= $r['id'] ?>"
                    data-ref="<?= clean($r['reference_no']) ?>"
                    data-student="<?= clean($r['student_name']) ?>"
                    data-admission="<?= clean($r['admission_no']) ?>"
                    data-trainer="<?= clean($r['trainer_name']) ?>"
                    data-unit="<?= clean($r['unit_name']) ?>"
                    data-unitcode="<?= clean($r['unit_code'] ?: '—') ?>"
                    data-semester="<?= clean($r['semester']) ?>"
                    data-year="<?= clean($r['academic_year']) ?>"
                    data-examtype="<?= clean($r['exam_type']) ?>"
                    data-catgrade="<?= clean($r['cat_grade'] ?: 'Not awarded yet') ?>"
                    data-cat1="<?= $r['cat1_mark'] !== null ? clean($r['cat1_mark']) : '—' ?>"
                    data-cat2="<?= $r['cat2_mark'] !== null ? clean($r['cat2_mark']) : '—' ?>"
                    data-catmarks="<?= $r['cat1_mark'] !== null || $r['cat2_mark'] !== null ? clean(($r['cat1_mark'] !== null ? rtrim(rtrim((string)$r['cat1_mark'], '0'), '.') : '—') . '/30, ' . ($r['cat2_mark'] !== null ? rtrim(rtrim((string)$r['cat2_mark'], '0'), '.') : '—') . '/70') : '—' ?>'
                    data-examgrade="<?= clean($r['exam_grade'] ?: 'Not awarded yet') ?>"
                    data-status="<?= $r['status'] ?>"
                    data-statusclass="<?= strtolower(str_replace(' ', '', $r['status'])) ?>"
                    data-description="<?= clean($r['description'] ?: 'No additional description provided.') ?>"
                    data-remarks="<?= clean($r['admin_remarks'] ?: '—') ?>"
                    data-created="<?= formatDate($r['created_at']) ?>">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <div class="kt-card-flat p-4 h-60">
        <h5 class="mb-3"><i class="fa-solid fa-lightbulb me-2 text-warning"></i>Tips for a fast resolution</h5>
        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
          <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-success mt-1"></i><span class="small kt-muted">Double check the trainer's name and unit before submitting.</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-success mt-1"></i><span class="small kt-muted">Include the exact semester and academic year the unit was done.</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-success mt-1"></i><span class="small kt-muted">Add a clear description — e.g. "CAT 2 mark missing on portal".</span></li>
          <li class="d-flex gap-2"><i class="fa-solid fa-circle-check text-success mt-1"></i><span class="small kt-muted">Track your reference number to follow up with the registrar.</span></li>
        </ul>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="kt-card-flat p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0"><i class="fa-solid fa-bell me-2 text-success"></i>Notifications</h5>
          <?php if ($unreadCount > 0): ?>
            <form method="POST" action="actions/mark_notifications_read.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <button type="submit" class="btn btn-sm btn-kt-soft">Mark all read</button>
            </form>
          <?php endif; ?>
        </div>
        <p class="small kt-muted mb-3">A live sample of the email/SMS/system alerts sent to you as your case moves through verification.</p>

        <?php if (empty($notifications)): ?>
          <div class="kt-empty py-4">
            <i class="fa-solid fa-bell-slash"></i>
            <p class="mb-0 small">No notifications yet. You'll be notified here once your report is reviewed.</p>
          </div>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="kt-notif-item <?= !$n['is_read'] ? 'kt-notif-unread' : '' ?>">
              <div class="kt-notif-icon channel-<?= $n['channel'] ?>"><i class="fa-solid <?= notificationChannelIcon($n['channel']) ?>"></i></div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="small fw-semibold"><?= clean($n['title']) ?><?php if (!$n['is_read']): ?><span class="kt-notif-dot"></span><?php endif; ?></div>
                </div>
                <div class="small kt-muted mb-1"><?= clean($n['message']) ?></div>
                <div class="small kt-muted" style="font-size:.7rem;">
                  <i class="fa-solid <?= notificationChannelIcon($n['channel']) ?> me-1"></i><?= ucfirst($n['channel']) ?>
                  <?php if ($n['recipient']): ?> to <?= clean($n['recipient']) ?><?php endif; ?>
                  &middot; <?= formatDate($n['created_at']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
require 'includes/modal_view_report.php';
require 'includes/footer.php';
?>

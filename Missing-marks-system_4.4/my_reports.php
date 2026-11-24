<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$userId = $_SESSION['user_id'];

$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['Pending', 'In Review', 'Resolved', 'Rejected'];

$where = "WHERE user_id = ?";
$params = [$userId];
if (in_array($statusFilter, $allowedStatuses, true)) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM missing_marks_reports $where");
$countStmt->execute($params);
$pg = paginate((int)$countStmt->fetchColumn(), 10);

$sql = "SELECT * FROM missing_marks_reports $where ORDER BY created_at DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$trainers = $pdo->query("SELECT trainer_name FROM trainers ORDER BY trainer_name")->fetchAll();

$pageTitle = "My Reports";
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1">My Reports</h3>
      <p class="kt-muted mb-0">All the missing marks cases you have submitted.</p>
    </div>
    <a href="report_new.php" class="btn btn-kt-primary">
      <i class="fa-solid fa-circle-plus me-2"></i>Report Missing Marks
    </a>
  </div>

  <div class="kt-card-flat p-3 mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input type="text" id="tableSearch" class="form-control" placeholder="Search by unit, trainer, reference...">
        </div>
      </div>
      <div class="col-md-5 d-flex flex-wrap gap-2">
        <a href="my_reports.php" class="btn btn-sm <?= $statusFilter==''?'btn-kt-primary':'btn-kt-soft' ?>">All</a>
        <a href="?status=Pending" class="btn btn-sm <?= $statusFilter=='Pending'?'btn-kt-primary':'btn-kt-soft' ?>">Pending</a>
        <a href="?status=In+Review" class="btn btn-sm <?= $statusFilter=='In Review'?'btn-kt-primary':'btn-kt-soft' ?>">In Review</a>
        <a href="?status=Resolved" class="btn btn-sm <?= $statusFilter=='Resolved'?'btn-kt-primary':'btn-kt-soft' ?>">Resolved</a>
        <a href="?status=Rejected" class="btn btn-sm <?= $statusFilter=='Rejected'?'btn-kt-primary':'btn-kt-soft' ?>">Rejected</a>
      </div>
    </div>
  </div>

  <div class="kt-card-flat p-3">
    <?php if (empty($reports)): ?>
      <div class="kt-empty">
        <i class="fa-solid fa-inbox"></i>
        <p class="mb-2">No reports found<?= $statusFilter ? ' for status "' . clean($statusFilter) . '"' : '' ?>.</p>
        <a href="report_new.php" class="btn btn-kt-primary">Report a case</a>
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-kt align-middle mb-0" id="reportsTable">
        <thead><tr><th>Reference</th><th>Unit</th><th>Trainer</th><th>Semester</th><th>Year</th><th>Grades</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td class="fw-semibold"><?= clean($r['reference_no']) ?></td>
            <td><?= clean($r['unit_name']) ?><?php if($r['unit_code']): ?><div class="small kt-muted"><?= clean($r['unit_code']) ?></div><?php endif; ?></td>
            <td><?= clean($r['trainer_name']) ?></td>
            <td><?= clean($r['semester']) ?></td>
            <td><?= clean($r['academic_year']) ?></td>
            <td>
              <?php if ($r['exam_type'] === 'CAT' && $r['cat1_mark'] !== null): ?><span class="badge text-bg-light border" title="CAT 1">CAT 1: <?= clean(rtrim(rtrim((string)$r['cat1_mark'], '0'), '.')) ?>/30</span><?php endif; ?>
              <?php if ($r['exam_type'] === 'CAT' && $r['cat2_mark'] !== null): ?><span class="badge text-bg-light border" title="CAT 2">CAT 2: <?= clean(rtrim(rtrim((string)$r['cat2_mark'], '0'), '.')) ?>/70</span><?php endif; ?>
              <?php if ($r['cat_grade']): ?><span class="badge <?= gradeBadgeClass($r['cat_grade']) ?>" title="CAT Grade">CAT Grade: <?= clean($r['cat_grade']) ?></span><?php endif; ?>
              <?php if ($r['exam_grade']): ?><span class="badge <?= gradeBadgeClass($r['exam_grade']) ?>" title="End Term">ET: <?= clean($r['exam_grade']) ?></span><?php endif; ?>
              <?php if (!$r['cat_grade'] && !$r['exam_grade']): ?><span class="small kt-muted">—</span><?php endif; ?>
            </td>
            <td class="small kt-muted"><?= formatDate($r['created_at']) ?></td>
            <td><span class="<?= statusBadgeClass($r['status']) ?>"><i class="fa-solid <?= statusIcon($r['status']) ?> me-1"></i><?= $r['status'] ?></span></td>
            <td class="text-end"><a href="report_details.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-kt-soft"><i class="fa-solid fa-eye"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-center mt-3">
      <?php renderPagination($pg); ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
require 'includes/footer.php';
?>

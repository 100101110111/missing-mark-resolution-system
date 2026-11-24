<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$statusFilter   = $_GET['status'] ?? '';
$semesterFilter = $_GET['semester'] ?? '';
$allowedStatuses = ['Pending', 'In Review', 'Resolved', 'Rejected'];
$allowedSemesters = ['Semester 1', 'Semester 2', 'Semester 3'];

$where = "WHERE 1=1";
$params = [];
if (in_array($statusFilter, $allowedStatuses, true)) { $where .= " AND status = ?"; $params[] = $statusFilter; }
if (in_array($semesterFilter, $allowedSemesters, true)) { $where .= " AND semester = ?"; $params[] = $semesterFilter; }

// Total count for pagination (keeps the page fast and scalable as reports grow)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM missing_marks_reports $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pg = paginate($totalRows, 15);

$sql = "SELECT * FROM missing_marks_reports $where ORDER BY created_at DESC LIMIT {$pg['perPage']} OFFSET {$pg['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$pageTitle = "All Reports";
require 'includes/admin_header.php';
?>

<?php renderFlash(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">All Missing Marks Reports</h4>
    <p class="kt-muted mb-0">Review, approve/reject, grade and resolve student cases. (<?= $totalRows ?> total)</p>
  </div>
</div>

<div class="kt-card-flat p-3 mb-4">
  <div class="row g-2 align-items-center">
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" id="tableSearch" class="form-control" placeholder="Search student, trainer, unit, reference...">
      </div>
    </div>
    <div class="col-md-4 d-flex flex-wrap gap-2">
      <a href="reports.php" class="btn btn-sm <?= $statusFilter==''?'btn-kt-primary':'btn-kt-soft' ?>">All</a>
      <a href="?status=Pending" class="btn btn-sm <?= $statusFilter=='Pending'?'btn-kt-primary':'btn-kt-soft' ?>">Pending</a>
      <a href="?status=In+Review" class="btn btn-sm <?= $statusFilter=='In Review'?'btn-kt-primary':'btn-kt-soft' ?>">In Review</a>
      <a href="?status=Resolved" class="btn btn-sm <?= $statusFilter=='Resolved'?'btn-kt-primary':'btn-kt-soft' ?>">Resolved</a>
      <a href="?status=Rejected" class="btn btn-sm <?= $statusFilter=='Rejected'?'btn-kt-primary':'btn-kt-soft' ?>">Rejected</a>
    </div>
    <div class="col-md-4">
      <select class="form-select" onchange="window.location.href='?semester='+this.value">
        <option value="">All Semesters</option>
        <?php foreach ($allowedSemesters as $s): ?>
          <option value="<?= $s ?>" <?= $semesterFilter===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
</div>

<form method="POST" action="actions/bulk_update.php" id="bulkForm">
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
<input type="hidden" name="return_qs" value="<?= clean(http_build_query($_GET)) ?>">

<div class="kt-card-flat p-3 mb-3 d-none align-items-center justify-content-between flex-wrap gap-2" id="bulkBar">
  <span class="small fw-semibold"><span id="bulkCount">0</span> selected</span>
  <div class="d-flex gap-2">
    <button type="submit" name="bulk_action" value="Resolved" class="btn btn-sm btn-kt-primary"><i class="fa-solid fa-circle-check me-1"></i>Approve Selected</button>
    <button type="submit" name="bulk_action" value="Rejected" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject all selected requests?');"><i class="fa-solid fa-circle-xmark me-1"></i>Reject Selected</button>
  </div>
</div>

<div class="kt-card-flat p-3">
  <?php if (empty($reports)): ?>
    <div class="kt-empty"><i class="fa-solid fa-inbox"></i><p class="mb-0">No reports match this filter.</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-kt align-middle mb-0" id="reportsTable">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
        <th>Reference</th><th>Student</th><th>Trainer</th><th>Unit</th><th>Semester</th><th>Submitted</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <tr>
          <td><input type="checkbox" name="report_ids[]" value="<?= $r['id'] ?>" class="form-check-input row-check"></td>
          <td class="fw-semibold"><?= clean($r['reference_no']) ?></td>
          <td><?= clean($r['student_name']) ?><div class="small kt-muted"><?= clean($r['admission_no']) ?></div></td>
          <td><?= clean($r['trainer_name']) ?></td>
          <td><?= clean($r['unit_name']) ?></td>
          <td><?= clean($r['semester']) ?></td>
          <td class="small kt-muted"><?= formatDate($r['created_at']) ?></td>
          <td><span class="<?= statusBadgeClass($r['status']) ?>"><?= $r['status'] ?></span></td>
          <td class="text-end"><a href="report_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-kt-primary"><i class="fa-solid fa-eye me-1"></i>Review</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
</form>

<div class="d-flex justify-content-center mt-3">
  <?php renderPagination($pg); ?>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});
document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));
function updateBulkBar() {
    var checked = document.querySelectorAll('.row-check:checked').length;
    var bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked;
    bar.classList.toggle('d-none', checked === 0);
    bar.classList.toggle('d-flex', checked > 0);
}
</script>

<?php require 'includes/admin_footer.php'; ?>

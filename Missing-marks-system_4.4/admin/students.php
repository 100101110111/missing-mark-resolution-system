<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

$search = trim($_GET['q'] ?? '');
$where = "WHERE u.role = 'student'";
$params = [];
if ($search !== '') {
    $where .= " AND (u.full_name LIKE ? OR u.admission_no LIKE ? OR u.email LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$pg = paginate($totalRows, 15);

$sql = "
    SELECT u.*, COUNT(r.id) AS report_count
    FROM users u
    LEFT JOIN missing_marks_reports r ON r.user_id = u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = "Students";
require 'includes/admin_header.php';
?>

<?php renderFlash(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">Registered Students</h4>
    <p class="kt-muted mb-0">All students who have signed up on the platform. (<?= $totalRows ?> total)</p>
  </div>
  <form method="GET" class="input-group" style="max-width:280px;">
    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
    <input type="text" name="q" class="form-control" placeholder="Search students..." value="<?= clean($search) ?>">
  </form>
</div>

<div class="kt-card-flat p-3">
  <?php if (empty($students)): ?>
    <div class="kt-empty"><i class="fa-solid fa-user-graduate"></i><p class="mb-0">No students found.</p></div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-kt align-middle mb-0">
      <thead><tr><th>Student</th><th>Admission No.</th><th>Email</th><th>Course</th><th>Reports Filed</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($students as $s): ?>
        <tr>
          <td class="d-flex align-items-center gap-2">
            <span class="kt-avatar" style="width:34px;height:34px;font-size:.7rem;"><?= initials($s['full_name']) ?></span>
            <?= clean($s['full_name']) ?>
          </td>
          <td><?= clean($s['admission_no']) ?></td>
          <td><?= clean($s['email']) ?></td>
          <td><?= clean($s['course'] ?: '—') ?></td>
          <td><span class="badge bg-light text-dark border"><?= (int)$s['report_count'] ?></span></td>
          <td class="small kt-muted"><?= formatDate($s['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="d-flex justify-content-center mt-3">
  <?php renderPagination($pg); ?>
</div>

<?php require 'includes/admin_footer.php'; ?>

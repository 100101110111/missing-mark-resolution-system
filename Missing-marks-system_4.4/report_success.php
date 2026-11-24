<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM missing_marks_reports WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    flash('main', 'Report not found.', 'danger');
    redirect(BASE_URL . 'dashboard.php');
}

$pageTitle = "Report Submitted";
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="text-center mb-4 fade-in">
        <div class="kt-success-icon mb-4"><i class="fa-solid fa-check"></i></div>
        <h2 class="mb-2">Your Missing Mark report has been submitted successfully</h2>
        <p class="kt-muted">Keep your ticket/reference number below to track this case at any time.</p>
      </div>

      <div class="kt-ticket mb-4 fade-in">
        <div class="text-center small kt-muted mb-2"><i class="fa-solid fa-ticket me-1"></i>YOUR TICKET / REFERENCE NUMBER</div>
        <div class="kt-ticket-ref mb-3" id="refNumber"><?= clean($report['reference_no']) ?></div>
        <div class="text-center mb-3">
          <button class="btn btn-kt-soft btn-sm" id="copyRefBtn" onclick="copyRef()"><i class="fa-solid fa-copy me-1"></i>Copy reference number</button>
        </div>
        <hr class="my-3">
        <div class="row g-2 small">
          <div class="col-6"><span class="kt-muted">Student:</span> <span class="fw-semibold"><?= clean($report['student_name']) ?></span></div>
          <div class="col-6"><span class="kt-muted">Admission No:</span> <span class="fw-semibold"><?= clean($report['admission_no']) ?></span></div>
          <div class="col-6"><span class="kt-muted">Trainer:</span> <span class="fw-semibold"><?= clean($report['trainer_name']) ?></span></div>
          <div class="col-6"><span class="kt-muted">Semester:</span> <span class="fw-semibold"><?= clean($report['semester']) ?></span></div>
          <div class="col-12"><span class="kt-muted">Unit:</span> <span class="fw-semibold"><?= clean($report['unit_name']) ?></span></div>
          <div class="col-6"><span class="kt-muted">Submitted:</span> <span class="fw-semibold"><?= formatDate($report['created_at']) ?></span></div>
          <div class="col-6"><span class="kt-muted">Status:</span> <span class="<?= statusBadgeClass($report['status']) ?>"><?= $report['status'] ?></span></div>
        </div>
      </div>

      <div class="kt-card-flat p-4 mb-4">
        <h6 class="mb-3 text-center"><i class="fa-solid fa-route me-2 text-success"></i>What happens next?</h6>
        <div class="d-flex align-items-center justify-content-center">
          <?php renderStatusTracker($report['status']); ?>
        </div>
        <p class="text-center small kt-muted mt-3 mb-0">
          You'll get a notification the moment your case moves to verification and again once it's approved — check your dashboard's notification bell, plus a sample email/SMS notice.
        </p>
      </div>

      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="report_details.php?id=<?= $report['id'] ?>" class="btn btn-kt-primary"><i class="fa-solid fa-magnifying-glass-chart me-2"></i>Track This Report</a>
        <a href="my_reports.php" class="btn btn-kt-soft"><i class="fa-solid fa-file-lines me-2"></i>View All My Reports</a>
        <a href="report_new.php" class="btn btn-kt-soft"><i class="fa-solid fa-plus me-2"></i>Submit Another Report</a>
      </div>
    </div>
  </div>
</div>

<script>
function copyRef() {
    var ref = document.getElementById('refNumber').textContent.trim();
    navigator.clipboard.writeText(ref).then(function () {
        var btn = document.getElementById('copyRefBtn');
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copied!';
        setTimeout(function () { btn.innerHTML = original; }, 1800);
    });
}
</script>

<?php require 'includes/footer.php'; ?>

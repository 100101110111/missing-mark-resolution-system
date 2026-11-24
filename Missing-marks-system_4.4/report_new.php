<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
requireStudent();

$trainers = $pdo->query("SELECT trainer_name FROM trainers ORDER BY trainer_name")->fetchAll();

$pageTitle = "Report Missing Marks";
require 'includes/header.php';
require 'includes/navbar_student.php';
?>

<div class="container py-4">
  <a href="dashboard.php" class="kt-muted small mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to dashboard</a>

  <div class="row justify-content-center">
    <div class="col-lg-9">

      <div class="text-center mb-4">
        <div class="kt-feature-icon mx-auto mb-3" style="width:64px;height:64px;font-size:1.6rem;"><i class="fa-solid fa-file-circle-plus"></i></div>
        <h3 class="mb-1">Report Missing Marks</h3>
        <p class="kt-muted">Fill in the details below exactly as they appear on your examination card / results slip, then submit — you'll receive a ticket number to track your case.</p>
      </div>

      <!-- Mini preview of what happens after submission -->
      <div class="kt-card-flat p-3 mb-4">
        <div class="d-flex align-items-center justify-content-center">
          <?php renderStatusTracker('Pending'); ?>
        </div>
        <p class="text-center small kt-muted mt-2 mb-0">This is the verification workflow your report will go through once submitted.</p>
      </div>

      <div class="kt-card-flat p-4 p-md-5">
        <form method="POST" action="actions/submit_report.php" class="needs-validation" novalidate id="reportForm">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <h6 class="kt-section-title mb-3"><i class="fa-solid fa-user me-2"></i>Student Details</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Student Name</label>
              <input type="text" name="student_name" class="form-control" required
                     value="<?= clean($_SESSION['full_name']) ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Admission Number</label>
              <input type="text" name="admission_no" class="form-control" required
                     value="<?= clean($_SESSION['admission_no']) ?>">
            </div>
          </div>

          <hr class="my-4">
          <h6 class="kt-section-title mb-3"><i class="fa-solid fa-chalkboard-user me-2"></i>Case Details</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Trainer's Name <span class="text-danger">*</span></label>
              <select class="form-select" id="trainer_select" name="trainer_select" required>
                <option value="" selected disabled>-- Select trainer --</option>
                <?php foreach ($trainers as $t): ?>
                  <option value="<?= clean($t['trainer_name']) ?>"><?= clean($t['trainer_name']) ?></option>
                <?php endforeach; ?>
                <option value="__other__">➕ Trainer not listed (type name)</option>
              </select>
              <div id="trainer_custom_wrap" class="d-none mt-2">
                <input type="text" name="trainer_custom" class="form-control" placeholder="Type trainer's full name">
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Semester the Unit was Done <span class="text-danger">*</span></label>
              <select class="form-select" name="semester" required>
                <option value="" selected disabled>-- Select semester --</option>
                <option>Semester 1</option>
                <option>Semester 2</option>
                <option>Semester 3</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Unit Name (with missing marks) <span class="text-danger">*</span></label>
              <input type="text" name="unit_name" class="form-control" placeholder="e.g. Database Design &amp; Development" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Unit Code</label>
              <input type="text" name="unit_code" class="form-control" placeholder="e.g. ICT 2201">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Academic Year <span class="text-danger">*</span></label>
              <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025/2026" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Type of Assessment</label>
              <select class="form-select" name="exam_type" id="exam_type" required>
                <option value="" selected disabled>-- Select missing assessment --</option>
                <option value="CAT 1">CAT 1</option>
                <option value="CAT 2">CAT 2</option>
                <option value="END TERM">END TERM</option>
              </select>
              <div class="form-text">Choose exactly which assessment result is missing.</div>
            </div>
          </div>

          <div id="assessmentInfo" class="assessment-selection-card mb-4">
            <div class="d-flex align-items-start gap-3">
              <div class="assessment-icon"><i class="fa-solid fa-shield-check"></i></div>
              <div>
                <div class="fw-semibold" id="assessmentInfoTitle">Assessment selection</div>
                <div class="small kt-muted" id="assessmentInfoText">Select the missing assessment above. The administrator will only be allowed to award the applicable mark range.</div>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Additional Description (optional)</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Any extra detail that can help the registrar's office resolve this case faster..."></textarea>
          </div>

          <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a href="dashboard.php" class="btn btn-kt-soft">Cancel</a>
            <button type="submit" id="submitReportBtn" class="btn btn-kt-primary btn-lg">
              <i class="fa-solid fa-paper-plane me-2"></i>Submit Report
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
var examType = document.getElementById('exam_type');
var infoCard = document.getElementById('assessmentInfo');
var infoTitle = document.getElementById('assessmentInfoTitle');
var infoText = document.getElementById('assessmentInfoText');

function updateAssessmentInfo() {
    var value = examType.value;
    var messages = {
        'CAT 1': ['CAT 1 selected', 'The administrator will award a numeric CAT 1 mark from 0 to 30. CAT 2 and End Term will remain closed.'],
        'CAT 2': ['CAT 2 selected', 'The administrator will award a numeric CAT 2 mark from 0 to 70. CAT 1 and End Term will remain closed.'],
        'END TERM': ['END TERM selected', 'The administrator will award an End Term grade only. CAT 1 and CAT 2 will remain closed as AWARDED.'],
        'Both CATS & End Term': ['Both CATS & End Term selected', 'CAT 1 accepts 0–30, CAT 2 accepts 0–70, and End Term keeps its letter-grade workflow.']
    };
    if (messages[value]) {
        infoTitle.textContent = messages[value][0];
        infoText.textContent = messages[value][1];
        infoCard.classList.add('is-selected');
    } else {
        infoTitle.textContent = 'Assessment selection';
        infoText.textContent = 'Select the missing assessment above. The administrator will only be allowed to award the applicable mark range.';
        infoCard.classList.remove('is-selected');
    }
}

examType.addEventListener('change', updateAssessmentInfo);
updateAssessmentInfo();

document.getElementById('reportForm').addEventListener('submit', function () {
    var btn = document.getElementById('submitReportBtn');
    if (!this.checkValidity()) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
});
</script>

<?php require 'includes/footer.php'; ?>

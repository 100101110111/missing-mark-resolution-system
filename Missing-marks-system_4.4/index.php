<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// If already logged in, send straight to the right dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
}

// Live counters for social proof on the homepage
try {
    $totalReports   = $pdo->query("SELECT COUNT(*) FROM missing_marks_reports")->fetchColumn();
    $resolvedCount  = $pdo->query("SELECT COUNT(*) FROM missing_marks_reports WHERE status='Resolved'")->fetchColumn();
    $studentsCount  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
} catch (Exception $e) {
    $totalReports = $resolvedCount = $studentsCount = 0;
}

$pageTitle = "Home";
require 'includes/header.php';
require 'includes/navbar_public.php';
?>

<!-- ================= HERO ================= -->
<section class="kt-hero">
  <div class="container kt-hero-inner py-5">
    <div class="row align-items-center py-5">
      <div class="col-lg-6">
        <span class="kt-pill mb-3"><i class="fa-solid fa-bolt"></i> Fast &middot; Transparent &middot; Trackable</span>
        <h1 class="text-white mt-3 mb-3">Report &amp; resolve <span style="color:var(--kt-gold)">missing marks</span> the structured way.</h1>
        <p class="lead">Kenya School of TVET's official platform for students to log, track and resolve missing marks cases with their trainers &mdash; no more lost forms.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="register.php" class="btn btn-kt-gold btn-lg"><i class="fa-solid fa-user-plus me-2"></i>Get Started — It's Free</a>
          <a href="login.php" class="btn btn-kt-outline btn-lg"><i class="fa-solid fa-right-to-bracket me-2"></i>I already have an account</a>
        </div>
        <div class="kt-pill mt-4" style="background:rgba(255,255,255,.08);">
          <i class="fa-solid fa-user-shield"></i>
          <span>Academic Registrar, HOD or Trainer? <a href="admin/login.php" class="fw-bold" style="color:var(--kt-gold)">Enter the Staff Portal <i class="fa-solid fa-arrow-right ms-1"></i></a></span>
        </div>
        <div class="row mt-5 g-3">
          <div class="col-4">
            <div class="text-white"><span class="kt-count fs-3 fw-bold" data-count="<?= (int)$studentsCount ?>">0</span><span class="fs-3 fw-bold">+</span></div>
            <div class="small text-white-50">Registered Students</div>
          </div>
          <div class="col-4">
            <div class="text-white"><span class="kt-count fs-3 fw-bold" data-count="<?= (int)$totalReports ?>">0</span></div>
            <div class="small text-white-50">Cases Reported</div>
          </div>
          <div class="col-4">
            <div class="text-white"><span class="kt-count fs-3 fw-bold" data-count="<?= (int)$resolvedCount ?>">0</span></div>
            <div class="small text-white-50">Cases Resolved</div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 d-none d-lg-block">
        <div class="kt-hero-art p-4 fade-in">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-white fw-semibold"><i class="fa-solid fa-file-circle-plus me-2"></i>New Report</span>
            <span class="badge-status-pending">Pending</span>
          </div>
          <div class="bg-white bg-opacity-10 rounded-3 p-3 mb-2 text-white-50 small">Student Name<div class="text-white fw-semibold">Wanjiku Kamau</div></div>
          <div class="row g-2 mb-2">
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-3 text-white-50 small">Trainer<div class="text-white fw-semibold">Mr. James Otieno</div></div></div>
            <div class="col-6"><div class="bg-white bg-opacity-10 rounded-3 p-3 text-white-50 small">Semester<div class="text-white fw-semibold">Semester 2</div></div></div>
          </div>
          <div class="bg-white bg-opacity-10 rounded-3 p-3 mb-3 text-white-50 small">Unit<div class="text-white fw-semibold">Database Design &amp; Development</div></div>
          <button class="btn btn-kt-gold w-100" disabled><i class="fa-solid fa-paper-plane me-2"></i>Submit Report</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= HOW IT WORKS ================= -->
<section id="how-it-works" class="py-5 py-lg-6">
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="kt-section-title">How the system works</h2>
      <div class="kt-divider-gold mx-auto my-3"></div>
      <p class="kt-muted col-lg-6 mx-auto">A simple four-step flow that takes the Hardwork out of chasing missing marks.</p>
    </div>
    <div class="row g-4 kt-steps">
      <div class="col-md-6 col-lg-3">
        <div class="kt-step">
          <h5 class="mt-1">Create an account</h5>
          <p class="kt-muted small">Register with your admission number and email in under a minute.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="kt-step">
          <h5 class="mt-1">Log the missing marks</h5>
          <p class="kt-muted small">Key in the trainer, unit, semester and a short description of the issue.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="kt-step">
          <h5 class="mt-1">Track the status</h5>
          <p class="kt-muted small">Follow your case live &mdash; Pending, In Review, Resolved or Rejected.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="kt-step">
          <h5 class="mt-1">Get resolved</h5>
          <p class="kt-muted small">The registrar's office updates your marks and closes the case with remarks.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= FEATURES ================= -->
<section id="features" class="py-5 bg-white">
  <div class="container py-4">
    <div class="text-center mb-5">
      <h2 class="kt-section-title">Everything you need, in one place</h2>
      <div class="kt-divider-gold mx-auto my-3"></div>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon mb-3"><i class="fa-solid fa-file-circle-plus"></i></div>
          <h5>Structured Reporting</h5>
          <p class="kt-muted small mb-0">A guided form captures exactly what the registrar needs: student, trainer, unit, semester and exam type.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon gold mb-3"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
          <h5>Real-time Tracking</h5>
          <p class="kt-muted small mb-0">Every report gets a unique reference number and a live status you can check anytime.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon red mb-3"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <h5>Full Audit Trail</h5>
          <p class="kt-muted small mb-0">A timeline records every status change, so both students and admin can see what happened, when.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon mb-3"><i class="fa-solid fa-user-shield"></i></div>
          <h5>Secure Accounts</h5>
          <p class="kt-muted small mb-0">Passwords are encrypted and each student only ever sees their own cases.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon gold mb-3"><i class="fa-solid fa-chart-pie"></i></div>
          <h5>Staff Analytics</h5>
          <p class="kt-muted small mb-0">A dedicated dashboard for Registrar, HOD &amp; Trainer accounts, visualising cases by status, semester and trainer.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon mb-3"><i class="fa-solid fa-graduation-cap"></i></div>
          <h5>Approve, Reject &amp; Grade</h5>
          <p class="kt-muted small mb-0">Staff can approve or reject each case and award CAT &amp; End Term letter grades (A to E) directly on the case.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="kt-card p-4 h-100">
          <div class="kt-feature-icon red mb-3"><i class="fa-solid fa-mobile-screen-button"></i></div>
          <h5>Works on Any Device</h5>
          <p class="kt-muted small mb-0">A fully responsive interface &mdash; report a missing mark right from your phone.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= ABOUT / CTA ================= -->
<section id="about" class="py-5">
  <div class="container py-4">
    <div class="kt-card p-4 p-lg-5" style="background:linear-gradient(120deg,#0b6e4f,#0f8560); color:#fff; border:none;">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h3 class="text-white">Kenya School of TVET &mdash; committed to academic integrity</h3>
          <p class="mb-0" style="color:rgba(255,255,255,.85)">This system was built to close the gap between students and the examinations office, ensuring no missing mark goes unreported or unresolved.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="register.php" class="btn btn-kt-gold btn-lg"><i class="fa-solid fa-arrow-right me-2"></i>Report a Missing Mark</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>

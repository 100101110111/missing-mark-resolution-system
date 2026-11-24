<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdmin();

// Stat counts
$statusCounts = ['Pending' => 0, 'In Review' => 0, 'Resolved' => 0, 'Rejected' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM missing_marks_reports GROUP BY status") as $row) {$statusCounts[$row['status']] = (int)$row['c'];
}
$totalReports  = array_sum($statusCounts);
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();

// By semester
$bySemester = ['Semester 1' => 0, 'Semester 2' => 0, 'Semester 3' => 0];
foreach ($pdo->query("SELECT semester, COUNT(*) c FROM missing_marks_reports GROUP BY semester") as $row) {$bySemester[$row['semester']] = (int)$row['c'];
}

// Top trainers by number of missing-marks cases
$topTrainers =$pdo->query("SELECT trainer_name, COUNT(*) c FROM missing_marks_reports GROUP BY trainer_name ORDER BY c DESC LIMIT 6")->fetchAll();

// Recent reports
$recent =$pdo->query("SELECT r.*, u.email FROM missing_marks_reports r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC LIMIT 8")->fetchAll();

$pageTitle = "Dashboard";
require 'includes/admin_header.php';
?>

<?php renderFlash(); ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="kt-stat bg-1"><div class="num kt-count" data-count="<?= $totalReports ?>">0</div><div>Total Reports</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kt-stat bg-2"><div class="num kt-count" data-count="<?= $statusCounts['Pending'] ?>">0</div><div>Pending</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kt-stat bg-3"><div class="num kt-count" data-count="<?= $statusCounts['Resolved'] ?>">0</div><div>Resolved</div></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="kt-stat bg-4"><div class="num kt-count" data-count="<?= $totalStudents ?>">0</div><div>Registered Students</div></div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-7">
    <div class="kt-card-flat p-4 h-100">
      <h6 class="mb-3">Reports by Status</h6>
      <div style="position: relative; height: 220px; width: 100%;">
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="kt-card-flat p-4 h-100">
      <h6 class="mb-3">Reports by Semester</h6>
      <div style="position: relative; height: 220px; width: 100%;">
        <canvas id="semesterChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="kt-card-flat p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Recent Reports</h6>
        <a href="reports.php" class="small fw-semibold">View all <i class="fa-solid fa-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="kt-empty"><i class="fa-solid fa-inbox"></i><p class="mb-0">No reports submitted yet.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-kt align-middle mb-0">
          <thead><tr><th>Reference</th><th>Student</th><th>Trainer</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($recent as$r): ?>
            <tr>
              <td class="fw-semibold"><?= clean($r['reference_no']) ?></td>
              <td><?= clean($r['student_name']) ?></td>
              <td><?= clean($r['trainer_name']) ?></td>
              <td><span class="<?= statusBadgeClass($r['status']) ?>"><?= $r['status'] ?></span></td>
              <td class="text-end"><a href="report_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-kt-soft"><i class="fa-solid fa-eye"></i></a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="kt-card-flat p-4">
      <h6 class="mb-3">Trainers with Most Cases</h6>
      <?php if (empty($topTrainers)): ?>
        <p class="kt-muted small mb-0">No data yet.</p>
      <?php else: ?>
        <?php $max = max(array_column($topTrainers, 'c')) ?: 1; ?>
        <?php foreach ($topTrainers as$t): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
              <span class="fw-semibold"><?= clean($t['trainer_name']) ?></span>
              <span class="kt-muted"><?= (int)$t['c'] ?> case<?=$t['c'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="progress" style="height:8px;">
              <div class="progress-bar" style="width:<?= round(($t['c'] /$max) * 100) ?>%; background:linear-gradient(135deg,var(--kt-green),var(--kt-green-light));"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Safely pass PHP data arrays into JavaScript using json_encode
const statusCounts = <?= json_encode([
    $statusCounts['Pending'],$statusCounts['In Review'],
    $statusCounts['Resolved'],$statusCounts['Rejected']
]) ?>;

const semesterCounts = <?= json_encode([
    $bySemester['Semester 1'],
    $bySemester['Semester 2'],$bySemester['Semester 3']
]) ?>;

const totalStatus = statusCounts.reduce((a, b) => a + b, 0);

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
  type: 'doughnut',
  data: {
    labels: ['Pending', 'In Review', 'Resolved', 'Rejected'],
    datasets: [{
      data: statusCounts,
      backgroundColor: ['#f2a70b', '#0a5aa8', '#0b6e4f', '#d92b3a'],
      borderWidth: 0
    }]
  },
  options: { 
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
      legend: { 
        position: 'right',
        labels: {
          boxWidth: 10,
          usePointStyle: true,
          pointStyle: 'circle',
          font: { size: 11 },
          generateLabels: (chart) => {
            const dataset = chart.data.datasets[0];
            return chart.data.labels.map((label, i) => {
              const value = dataset.data[i];
              const percentage = totalStatus > 0 ? Math.round((value / totalStatus) * 100) : 0;
              return {
                text: `${label} ${percentage}%`,
                fillStyle: dataset.backgroundColor[i],
                strokeStyle: dataset.backgroundColor[i],
                hidden: false,
                index: i
              };
            });
          }
        }
      } 
    }, 
    cutout: '70%' 
  }
});

const semCtx = document.getElementById('semesterChart').getContext('2d');
new Chart(semCtx, {
  type: 'bar',
  data: {
    labels: ['Sem 1', 'Sem 2', 'Sem 3'],
    datasets: [{
      label: 'Reports',
      data: semesterCounts,
      backgroundColor: '#0b6e4f',
      borderRadius: 6
    }]
  },
  options: { 
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }, 
    scales: { 
      y: { beginAtZero: true, ticks: { precision: 0 } },
      x: { grid: { display: false } }
    } 
  }
});
</script>

<?php require 'includes/admin_footer.php'; ?>
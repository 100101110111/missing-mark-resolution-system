<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../reports.php');
}

if (!csrfValid($_POST['csrf_token'] ?? '')) {
    flash('main', 'Your session expired. Please try again.', 'danger');
    redirect('../reports.php');
}

$reportId = (int)($_POST['report_id'] ?? 0);
$status   = clean($_POST['status'] ?? '');
$remarks  = clean($_POST['admin_remarks'] ?? '');
$allowedStatuses = ['Pending', 'In Review', 'Resolved', 'Rejected'];

if (!$reportId || !in_array($status, $allowedStatuses, true)) {
    flash('main', 'Invalid update request.', 'danger');
    redirect('../reports.php');
}

$stmt = $pdo->prepare("
    SELECT r.*, u.email, u.phone
    FROM missing_marks_reports r
    JOIN users u ON u.id = r.user_id
    WHERE r.id = ?
");
$stmt->execute([$reportId]);
$current = $stmt->fetch();

if (!$current) {
    flash('main', 'Report not found.', 'danger');
    redirect('../reports.php');
}

$type = $current['exam_type'];
$cat1 = $current['cat1_mark'];
$cat2 = $current['cat2_mark'];
$examGrade = $current['exam_grade'];
$errors = [];
$shouldAward = isset($_POST['save_grades']) || $status === 'Resolved';

/*
 * The assessment type controls exactly which fields may be changed.
 * Closed fields always retain their existing database values.
 */
if ($shouldAward && ($type === 'CAT 1' || $type === 'Both CATS & End Term')) {
    $raw = trim($_POST['cat1_mark'] ?? '');
    if ($raw === '' || !is_numeric($raw) || (float)$raw < 0 || (float)$raw > 30) {
        $errors[] = 'CAT 1 must be a number from 0 to 30.';
    } else {
        $cat1 = round((float)$raw, 2);
    }
}

if ($shouldAward && ($type === 'CAT 2' || $type === 'Both CATS & End Term')) {
    $raw = trim($_POST['cat2_mark'] ?? '');
    if ($raw === '' || !is_numeric($raw) || (float)$raw < 0 || (float)$raw > 70) {
        $errors[] = 'CAT 2 must be a number from 0 to 70.';
    } else {
        $cat2 = round((float)$raw, 2);
    }
}

if ($shouldAward && ($type === 'END TERM' || $type === 'Both CATS & End Term')) {
    $postedGrade = clean($_POST['exam_grade'] ?? '');
    if (!in_array($postedGrade, gradeOptions(), true)) {
        $errors[] = 'End Term grade must be selected from A, A-, B+, B, B-, C+, C, C-, D+, D, D- or E.';
    } else {
        $examGrade = $postedGrade;
    }
}

if (!empty($errors)) {
    flash('main', implode(' ', $errors), 'danger');
    redirect('../report_view.php?id=' . $reportId);
}

/* Legacy CAT letter grades are no longer used by the new awarding workflow. */
$catGrade = null;

$statusChanged = $current['status'] !== $status;
$gradesChanged = ((string)$current['cat1_mark'] !== (string)$cat1)
    || ((string)$current['cat2_mark'] !== (string)$cat2)
    || ((string)$current['exam_grade'] !== (string)$examGrade);

$resolvedBy = ($status === 'Resolved' || $status === 'Rejected') ? $_SESSION['user_id'] : null;

$stmt = $pdo->prepare("
    UPDATE missing_marks_reports
    SET status = ?, admin_remarks = ?, cat1_mark = ?, cat2_mark = ?,
        cat_grade = ?, exam_grade = ?, resolved_by = ?
    WHERE id = ?
");
$stmt->execute([
    $status, $remarks ?: null, $cat1, $cat2, $catGrade, $examGrade,
    $resolvedBy, $reportId
]);

if ($statusChanged || $gradesChanged || $remarks !== '') {
    $parts = [];
    if ($shouldAward && ($type === 'CAT 1' || $type === 'Both CATS & End Term')) {
        $parts[] = 'CAT 1: ' . rtrim(rtrim((string)$cat1, '0'), '.') . '/30';
    }
    if ($shouldAward && ($type === 'CAT 2' || $type === 'Both CATS & End Term')) {
        $parts[] = 'CAT 2: ' . rtrim(rtrim((string)$cat2, '0'), '.') . '/70';
    }
    if ($type === 'END TERM' || $type === 'Both CATS & End Term') {
        $parts[] = 'End Term: ' . ($examGrade ?: 'Not awarded');
    }

    $historyRemarks = trim($remarks);
    if ($gradesChanged && $parts) {
        $gradeNote = 'Awarded (' . implode(', ', $parts) . ')';
        $historyRemarks = trim($historyRemarks . ($historyRemarks ? ' — ' : '') . $gradeNote);
    }

    $stmt = $pdo->prepare("
        INSERT INTO report_status_history (report_id, status, remarks, changed_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$reportId, $status, $historyRemarks ?: null, $_SESSION['user_id']]);
}

if ($statusChanged) {
    notifyReportStatusChange($pdo, $current, $status, $remarks);
}

flash('main', 'Report updated successfully.', 'success');
redirect('../report_view.php?id=' . $reportId);

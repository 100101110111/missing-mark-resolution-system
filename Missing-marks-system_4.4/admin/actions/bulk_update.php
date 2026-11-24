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

$ids    = array_map('intval', $_POST['report_ids'] ?? []);
$action = $_POST['bulk_action'] ?? '';
$returnQs = $_POST['return_qs'] ?? '';
$redirectTo = '../reports.php' . ($returnQs ? '?' . $returnQs : '');

$allowedActions = ['Resolved' => 'Approved in bulk by staff.', 'Rejected' => 'Rejected in bulk by staff.'];

if (empty($ids) || !isset($allowedActions[$action])) {
    flash('main', 'Please select at least one report and a valid bulk action.', 'danger');
    redirect($redirectTo);
}

$remarkText = $allowedActions[$action];
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Fetch report + student contact details first (needed for notifications after the update)
$fetchStmt = $pdo->prepare("
    SELECT r.*, u.email, u.phone
    FROM missing_marks_reports r
    JOIN users u ON u.id = r.user_id
    WHERE r.id IN ($placeholders)
");
$fetchStmt->execute($ids);
$affectedReports = $fetchStmt->fetchAll();

// Update all selected reports in one go
$stmt = $pdo->prepare("UPDATE missing_marks_reports SET status = ?, admin_remarks = ?, resolved_by = ? WHERE id IN ($placeholders)");
$stmt->execute(array_merge([$action, $remarkText, $_SESSION['user_id']], $ids));

// Log a history entry for each affected report so every timeline stays accurate
$historyStmt = $pdo->prepare("INSERT INTO report_status_history (report_id, status, remarks, changed_by) VALUES (?, ?, ?, ?)");
foreach ($ids as $id) {
    $historyStmt->execute([$id, $action, $remarkText, $_SESSION['user_id']]);
}

// Fire verification-workflow notifications (system/email/sms) for every affected student
foreach ($affectedReports as $report) {
    if ($report['status'] !== $action) {
        notifyReportStatusChange($pdo, $report, $action, $remarkText);
    }
}

flash('main', count($ids) . ' report(s) marked as "' . $action . '".', 'success');
redirect($redirectTo);

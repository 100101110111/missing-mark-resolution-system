<?php
/**
 * Core helper functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Sanitize a text input */
function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Redirect helper */
function redirect($path) {
    header("Location: $path");
    exit;
}

/** Is a user currently logged in? */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/** Is the logged-in user an admin? */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Force login for protected student pages */
function requireStudent() {
    if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
        redirect(BASE_URL . 'login.php');
    }
}

/** Force login for protected admin pages */
function requireAdmin() {
    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        redirect(BASE_URL . 'login.php');
    }
}

/** Generate / validate a simple CSRF token per session */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrfValid($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/** Flash messages (one-time alerts across redirects) */
function flash($key, $message = null, $type = 'info') {
    if ($message === null) {
        if (isset($_SESSION['flash'][$key])) {
            $data = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $data;
        }
        return null;
    }
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

/** Render any pending flash message as a Bootstrap alert */
function renderFlash($key = 'main') {
    $f = flash($key);
    if (!$f) return;
    $type = $f['type'] === 'error' ? 'danger' : $f['type'];
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid ' . flashIcon($type) . ' me-2"></i>' . $f['message'] . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
function flashIcon($type) {
    switch ($type) {
        case 'success': return 'fa-circle-check';
        case 'danger':  return 'fa-circle-exclamation';
        case 'warning': return 'fa-triangle-exclamation';
        default:        return 'fa-circle-info';
    }
}

/** Generate a unique, human-friendly reference number e.g. MM-2026-00042 */
function generateReferenceNo($pdo) {
    $year = date('Y');
    do {
        $candidate = 'MM-' . $year . '-' . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM missing_marks_reports WHERE reference_no = ?");
        $stmt->execute([$candidate]);
    } while ($stmt->fetchColumn() > 0);
    return $candidate;
}

/** Return a Bootstrap badge class for a given report status */
function statusBadgeClass($status) {
    switch ($status) {
        case 'Pending':    return 'badge-status-pending';
        case 'In Review':  return 'badge-status-review';
        case 'Resolved':   return 'badge-status-resolved';
        case 'Rejected':   return 'badge-status-rejected';
        default:           return 'bg-secondary';
    }
}

/** Return a Font Awesome icon for a given report status */
function statusIcon($status) {
    switch ($status) {
        case 'Pending':    return 'fa-hourglass-half';
        case 'In Review':  return 'fa-magnifying-glass';
        case 'Resolved':   return 'fa-circle-check';
        case 'Rejected':   return 'fa-circle-xmark';
        default:           return 'fa-circle';
    }
}

/** Nicely format a timestamp */
function formatDate($ts) {
    return date('d M Y, h:i A', strtotime($ts));
}

/** End Term letter grades. CATs are numeric in the updated workflow. */
function gradeOptions() {
    return ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'E'];
}

/** Backward-compatible list for legacy CAT grade records. */
function legacyGradeOptions() {
    return ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'E'];
}

/** Bootstrap-ish color class for a letter grade badge */
function gradeBadgeClass($grade) {
    if (!$grade) return 'bg-light text-muted border';
    if (in_array($grade, ['A', 'A-'], true))                 return 'bg-success-subtle text-success border border-success-subtle';
    if (in_array($grade, ['B+', 'B', 'B-'], true))            return 'bg-info-subtle text-info-emphasis border border-info-subtle';
    if (in_array($grade, ['C+', 'C', 'C-'], true))            return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
    return 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'; // D+, D, D-, E
}

/** Human label for a staff account's position; falls back gracefully for the base admin */
function staffRoleLabel($staffRole) {
    return $staffRole ?: 'Administrator';
}

/** Icon per staff role, for a bit of visual flair in the admin UI */
function staffRoleIcon($staffRole) {
    switch ($staffRole) {
        case 'Registrar': return 'fa-user-tie';
        case 'HOD':       return 'fa-user-shield';
        case 'Trainer':   return 'fa-chalkboard-user';
        default:          return 'fa-user-gear';
    }
}

/** Simple pagination helper: returns [offset, totalPages, currentPage] */
function paginate($totalRows, $perPage = 15, $paramName = 'page') {
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $current = isset($_GET[$paramName]) ? max(1, (int)$_GET[$paramName]) : 1;
    $current = min($current, $totalPages);
    $offset = ($current - 1) * $perPage;
    return ['offset' => $offset, 'totalPages' => $totalPages, 'current' => $current, 'perPage' => $perPage];
}

/** Render a Bootstrap pagination nav, preserving existing query params */
function renderPagination($pagination, $paramName = 'page') {
    if ($pagination['totalPages'] <= 1) return;
    $params = $_GET;
    echo '<nav><ul class="pagination pagination-sm mb-0 flex-wrap">';
    for ($i = 1; $i <= $pagination['totalPages']; $i++) {
        $params[$paramName] = $i;
        $url = '?' . http_build_query($params);
        $active = $i === $pagination['current'] ? 'active' : '';
        echo "<li class=\"page-item $active\"><a class=\"page-link\" href=\"$url\">$i</a></li>";
    }
    echo '</ul></nav>';
}
/**
 * Best-effort "real" email send via PHP's mail(). On stock XAMPP this usually
 * needs sendmail/SMTP configured (php.ini) to actually deliver — see README.
 * Failures are swallowed on purpose: the in-app notification row (see
 * createNotification()) is always the reliable source of truth/demo.
 */
function attemptEmailSend($to, $subject, $body) {
    if (!$to) return false;
    $headers = "From: " . APP_SHORT . " <no-reply@kstvet.ac.ke>\r\nContent-Type: text/plain; charset=UTF-8";
    try {
        return @mail($to, $subject, $body, $headers);
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Log a sample notification (system / email / sms) for a user, tied to a report.
 * This is always recorded regardless of whether a real email/SMS gateway is
 * configured, so the student dashboard can reliably display a live sample.
 */
function createNotification($pdo, $userId, $reportId, $channel, $title, $message, $recipient = null) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, report_id, channel, title, message, recipient) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $reportId, $channel, $title, $message, $recipient]);
}

/**
 * Fire the full verification-workflow notification set (system + email + sms)
 * whenever a report's status changes to something the student should know about.
 * Call this right after updating missing_marks_reports.status.
 */
function notifyReportStatusChange($pdo, $report, $newStatus, $remarks = '') {
    $copy = [
        'In Review' => [
            'title' => 'Your report is under verification',
            'body'  => "Your missing marks report {REF} for \"{UNIT}\" is now under verification by the examinations office.",
        ],
        'Resolved' => [
            'title' => 'Your report has been approved ✅',
            'body'  => "Good news! Your missing marks report {REF} for \"{UNIT}\" has been approved and resolved.{REMARKS}",
        ],
        'Rejected' => [
            'title' => 'Your report was rejected',
            'body'  => "Your missing marks report {REF} for \"{UNIT}\" has been rejected.{REMARKS} Please visit the examinations office for details.",
        ],
    ];
    if (!isset($copy[$newStatus])) return;

    $tpl = $copy[$newStatus];
    $remarksLine = $remarks ? " Remarks: \"$remarks\"." : '';
    $message = str_replace(
        ['{REF}', '{UNIT}', '{REMARKS}'],
        [$report['reference_no'], $report['unit_name'], $remarksLine],
        $tpl['body']
    );

    // 1. In-app / system notification — always recorded (this is the reliable "sample")
    createNotification($pdo, $report['user_id'], $report['id'], 'system', $tpl['title'], $message);

    // 2. Sample email notification
    $emailTo = $report['email'] ?? null;
    if ($emailTo) {
        createNotification($pdo, $report['user_id'], $report['id'], 'email', $tpl['title'], $message, $emailTo);
        attemptEmailSend($emailTo, APP_SHORT . ': ' . $tpl['title'], $message);
    }

    // 3. Sample SMS notification (shorter copy, as an SMS gateway would send)
    $phone = $report['phone'] ?? null;
    if ($phone) {
        $smsMessage = "KSTVET: " . strip_tags(str_replace('✅', '', $tpl['title'])) . " Ref: {$report['reference_no']}. Log in to the portal for details.";
        createNotification($pdo, $report['user_id'], $report['id'], 'sms', $tpl['title'], $smsMessage, $phone);
        // NOTE: Plug a real gateway (e.g. Africa's Talking, Twilio) in here to send a live SMS.
    }
}

/** Icon for a notification channel */
function notificationChannelIcon($channel) {
    switch ($channel) {
        case 'email': return 'fa-envelope';
        case 'sms':   return 'fa-comment-sms';
        default:      return 'fa-bell';
    }
}

/**
 * The 3-stage verification workflow shown to students:
 * Submitted → Under Verification → Approved / Rejected.
 * Returns an array describing each stage's state for rendering a stepper.
 */
function statusTrackerStages($status) {
    switch ($status) {
        case 'Pending':    $s2 = 'current'; $s3 = 'pending'; break;
        case 'In Review':  $s2 = 'current'; $s3 = 'pending'; break; // verification actively in progress
        case 'Resolved':   $s2 = 'done';    $s3 = 'done';    break;
        case 'Rejected':   $s2 = 'done';    $s3 = 'rejected'; break;
        default:           $s2 = 'pending'; $s3 = 'pending';
    }
    $rejected = $status === 'Rejected';

    return [
        ['label' => 'Submitted',          'icon' => 'fa-file-circle-plus', 'state' => 'done'],
        ['label' => 'Under Verification', 'icon' => 'fa-magnifying-glass', 'state' => $s2],
        ['label' => $rejected ? 'Rejected' : 'Approved', 'icon' => $rejected ? 'fa-circle-xmark' : 'fa-circle-check', 'state' => $s3],
    ];
}

/** Render a compact horizontal status tracker for one report */
function renderStatusTracker($status) {
    $stages = statusTrackerStages($status);
    echo '<div class="kt-tracker">';
    foreach ($stages as $i => $s) {
        $cls = 'kt-tracker-step kt-tracker-' . $s['state'];
        echo '<div class="' . $cls . '">';
        echo '<div class="kt-tracker-dot"><i class="fa-solid ' . $s['icon'] . '"></i></div>';
        echo '<div class="kt-tracker-label">' . $s['label'] . '</div>';
        echo '</div>';
        if ($i < count($stages) - 1) {
            $lineDone = $s['state'] === 'done' || $s['state'] === 'rejected';
            echo '<div class="kt-tracker-line ' . ($lineDone ? 'done' : '') . '"></div>';
        }
    }
    echo '</div>';
}

/** Get initials from a full name, for avatar bubbles */
function initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $initials ?: 'U';
}

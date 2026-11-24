<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
requireStudent();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid($_POST['csrf_token'] ?? '')) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
}

redirect(BASE_URL . 'dashboard.php');

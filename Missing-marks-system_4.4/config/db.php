<?php
/**
 * ============================================================
 * MISSING MARKS RESOLUTION SYSTEM - Kenya School of TVET
 * Database connection (PDO)
 * ============================================================
 * Default XAMPP settings: host=localhost, user=root, password=""
 * Change below if your MySQL setup is different.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'missing_marks_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// App-wide constants
define('APP_NAME', 'Missing Marks Resolution System');
define('APP_SHORT', 'MMRS - Kenya School of TVET');

/**
 * BASE_URL is computed automatically as an absolute path (e.g. "/missing-marks-system/")
 * by comparing this file's folder (the project root, since db.php lives in /config)
 * to the web server's document root. This makes every link, asset path and redirect()
 * call work correctly no matter how deep the current script is nested
 * (root pages, /actions, /admin, /admin/actions...) and no matter what folder
 * name you place the project under in htdocs.
 */
$__appRootFs = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
$__docRoot   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$__basePath  = ($__docRoot !== '' && strpos($__appRootFs, $__docRoot) === 0)
    ? substr($__appRootFs, strlen($__docRoot))
    : '';
define('BASE_URL', ($__basePath !== '' ? $__basePath : '') . '/');
unset($__appRootFs, $__docRoot, $__basePath);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("
        <div style='font-family:Poppins,Arial,sans-serif;max-width:600px;margin:80px auto;
                    padding:30px;border-radius:12px;background:#fff3f3;border:1px solid #ffb3b3;color:#7a0000'>
        <h2>⚠️ Database Connection Failed</h2>
        <p>The system could not connect to <b>" . DB_NAME . "</b>.</p>
        <p><b>Details:</b> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr>
        <p>Please make sure:</p>
        <ol>
          <li>XAMPP's <b>Apache</b> and <b>MySQL</b> modules are running</li>
          <li>You have imported <code>database/missing_marks_db.sql</code> via phpMyAdmin</li>
          <li>The credentials in <code>config/db.php</code> match your MySQL setup</li>
        </ol>
        </div>
    ");
}

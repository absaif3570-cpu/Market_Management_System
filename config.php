<?php
// ============================================================
//  config.php — Database + App Configuration
//  Edit credentials here for your XAMPP setup
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // XAMPP default: empty
define('DB_NAME', 'market_db');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Market Management System');
define('APP_VERSION', '2.0');

// ── Connect ──────────────────────────────────────────────────
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="font-family:sans-serif;padding:40px;background:#1a0a0a;color:#ff6b6b;min-height:100vh;">
        <h2>&#9888; Database Connection Failed</h2>
        <p>' . mysqli_connect_error() . '</p>
        <p style="color:#aaa;font-size:13px;">Check your XAMPP MySQL service is running and credentials in config.php match.</p>
    </div>');
}

mysqli_set_charset($conn, DB_CHARSET);

// ── CSRF helpers ─────────────────────────────────────────────
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;background:#0a0a1a;color:#ff6b6b;">
            <h2>&#128274; Invalid Request</h2>
            <p>CSRF token mismatch. Please go back and try again.</p>
            <a href="javascript:history.back()" style="color:#6b9fff;">&#8592; Go Back</a>
        </div>');
    }
}

// ── Flash message helpers ────────────────────────────────────
function flash_set(string $type, string $msg): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

<?php
// ============================================================
//  auth.php — Session guard for all protected pages
//  Include at the very top of every page that needs login
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['username']) || empty($_SESSION['role'])) {
    // Destroy any partial session
    session_unset();
    session_destroy();
    header('Location: login.php?error=' . urlencode('Please log in to continue.'));
    exit();
}

// ── Role-gate helper ─────────────────────────────────────────
// Usage: require_role('admin');  or  require_role(['admin','user']);
function require_role($roles): void {
    $roles = (array)$roles;
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;background:#0a0a1a;color:#ff6b6b;">
            <h2>&#128274; Access Denied</h2>
            <p>You do not have permission to view this page.</p>
            <a href="index.php" style="color:#6b9fff;">&#8592; Dashboard</a>
        </div>');
    }
}

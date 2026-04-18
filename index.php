<?php
// ============================================================
//  index.php — Central Router
//  All pages load through this single file.
//  URL pattern: index.php?page=dashboard  (or just index.php)
//  No .htaccess / mod_rewrite required — works on plain XAMPP.
// ============================================================

session_start();
require_once __DIR__ . '/config.php';

// Make $conn available globally so all included page files can use it
global $conn;

$page = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_GET['page'] ?? 'home')));

// ── Public pages (no auth required) ─────────────────────────
$public_pages = ['home', 'login', 'register'];

if (!in_array($page, $public_pages, true)) {
    require_once __DIR__ . '/auth.php';  // Will redirect to login.php if not logged in
}

// ── Route map ────────────────────────────────────────────────
$route_map = [
    // Public
    'home'             => 'pages/home.php',
    // Admin / General
    'dashboard'        => 'pages/dashboard.php',
    'vendor_report'    => 'pages/vendor_report.php',
    'add_vendor'       => 'pages/add_vendor.php',
    'edit_vendor'      => 'pages/edit_vendor.php',
    'delete_vendor'    => 'pages/delete_vendor.php',
    'vendor_profile'   => 'pages/vendor_profile.php',
    'search_vendor'    => 'pages/search_vendor.php',
    'vendor_dashboard' => 'pages/vendor_dashboard.php',
    'manage_shop'      => 'pages/manage_shop.php',
    'add_shop'         => 'pages/add_shop.php',
    'edit_shop'        => 'pages/edit_shop.php',
    'delete_shop'      => 'pages/delete_shop.php',
    'add_payment'      => 'pages/add_payment.php',
    'delete_payment'   => 'pages/delete_payment.php',
    'rent_record'      => 'pages/rent_record.php',
    'report'           => 'pages/report.php',
    'pdf_report'       => 'pages/pdf_report.php',
    'backup'           => 'pages/backup.php',
];

// ── Dispatch ─────────────────────────────────────────────────
if (isset($route_map[$page]) && file_exists(__DIR__ . '/' . $route_map[$page])) {
    require __DIR__ . '/' . $route_map[$page];
} elseif ($page === 'home' || $page === '') {
    // Logged-in users → dashboard
    if (!empty($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'vendor') {
            header('Location: index.php?page=vendor_dashboard'); exit();
        }
        header('Location: index.php?page=dashboard'); exit();
    }
    require __DIR__ . '/pages/home.php';
} else {
    http_response_code(404);
    require_once __DIR__ . '/config.php';
    $page_title = '404 Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="home-hero"><h1>404</h1><p>Page not found.</p><a href="index.php" class="btn btn-primary">Go Home</a></div>';
    require __DIR__ . '/includes/footer.php';
}

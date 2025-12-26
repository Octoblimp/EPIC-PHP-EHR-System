<?php
/**
 * Configuration file for Openspace EHR Frontend
 * HIPAA-compliant security configuration
 */

// Check if setup is needed (first-time installation)

// --- Setup redirect logic ---
$setupCompletePath = __DIR__ . '/.setup_complete';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Helper: check if users exist in DB (copied from setup.php)
function config_hasExistingData() {
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) return false;
    $env = parse_ini_file($envFile);
    if (!$env || empty($env['DB_HOST']) || empty($env['DB_NAME']) || empty($env['DB_USER'])) return false;
    try {
        $dsn = "mysql:host={$env['DB_HOST']};port=" . ($env['DB_PORT'] ?? '3306') . ";dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Only redirect if:
// - Setup is not complete
// - Not already on setup.php
// - No users exist in DB (setup not blocked)
if (!file_exists($setupCompletePath)
    && $currentScript !== 'setup.php'
    && !config_hasExistingData()) {
    // Get the base path for redirection
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    $setupPath = rtrim($basePath, '/') . '/setup.php';
    // For files in subdirectories, adjust path
    if (strpos($_SERVER['SCRIPT_NAME'], '/includes/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ||
        strpos($_SERVER['SCRIPT_NAME'], '/activities/') !== false) {
        $setupPath = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/setup.php';
    }
    header('Location: ' . $setupPath);
    exit;
}

// Include security module first
require_once __DIR__ . '/security.php';

// API Configuration
// Use localhost to bypass Cloudflare when PHP makes internal API calls
define('API_BASE_URL', 'http://127.0.0.1:5000/api');
define('API_TIMEOUT', 30);

// For JavaScript/browser calls, use relative URL (goes through nginx proxy)
define('API_BASE_URL_JS', '/api');

// Application Configuration
define('APP_NAME', 'Openspace EHR');
define('APP_VERSION', '1.0.0');

// Asset paths
define('ASSETS_PATH', 'assets');

// Initialize secure session
SecureSession::init();
SecureSession::start();

// Send security headers for all pages
SecurityHeaders::send();

// Build user array from session if authenticated
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    // User is logged in - build user array from session data
    $_SESSION['user'] = [
        'id' => $_SESSION['user_id'] ?? 0,
        'username' => $_SESSION['username'] ?? 'unknown',
        'name' => $_SESSION['full_name'] ?? 'Unknown User',
        'display_name' => $_SESSION['full_name'] ?? 'Unknown User',
        'role' => $_SESSION['role'] ?? 'User',
        'department' => $_SESSION['department'] ?? 'General',
        'permissions' => $_SESSION['permissions'] ?? []
    ];
} elseif (!isset($_SESSION['user'])) {
    // Default demo user (only if not authenticated and no user set)
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'demo',
        'name' => 'Demo User',
        'display_name' => 'Demo User',
        'role' => 'Demo',
        'department' => 'Demo Mode'
    ];
}

// Helper functions
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function formatDate($dateString, $format = 'm/d/Y') {
    if (empty($dateString)) return '';
    $date = new DateTime($dateString);
    return $date->format($format);
}

function formatDateTime($dateString, $format = 'm/d/Y H:i') {
    if (empty($dateString)) return '';
    $date = new DateTime($dateString);
    return $date->format($format);
}

function formatTime($dateString, $format = 'H:i') {
    if (empty($dateString)) return '';
    $date = new DateTime($dateString);
    return $date->format($format);
}

function sanitize($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

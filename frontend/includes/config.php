<?php
/**
 * Configuration file for Openspace EHR Frontend
 */

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

// Session Configuration
session_start();

// Default user (for demo purposes)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'username' => 'nursejones',
        'name' => 'Jones, Sarah RN',
        'display_name' => 'Sarah Jones, RN',
        'role' => 'Nurse',
        'department' => 'Medical/Surgical'
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

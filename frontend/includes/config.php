<?php
/**
 * Configuration file for Epic EHR Frontend
 */

// API Configuration
define('API_BASE_URL', 'http://localhost:5000/api');
define('API_TIMEOUT', 30);

// Application Configuration
define('APP_NAME', 'Epic Hyperspace');
define('APP_VERSION', '1.0.0');

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

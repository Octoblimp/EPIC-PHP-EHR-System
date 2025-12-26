<?php
/**
 * Close Patient Tab Handler
 * 
 * Handles closing patient tabs via form POST to avoid nginx proxying issues.
 * Located in includes/ instead of api/ to avoid the proxy to Python backend.
 * 
 * Supports both form POST (returns redirect) and AJAX (returns JSON)
 */

// Use the same session initialization as the rest of the app
require_once __DIR__ . '/security.php';
SecureSession::init();
SecureSession::start();

// Determine if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Support both POST form data and JSON input
$patientId = null;
$returnUrl = '/home.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for JSON input first (AJAX)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $patientId = isset($input['patient_id']) ? (int)$input['patient_id'] : null;
        $returnUrl = $input['return_url'] ?? '/home.php';
    } else {
        // Standard form POST
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
        $returnUrl = isset($_POST['return_url']) ? $_POST['return_url'] : '/home.php';
    }
}

// Validate return URL to prevent open redirect
$allowedPrefixes = [
    '/home.php', '/patient-chart.php', '/patients.php', '/inbox.php', 
    '/profile.php', '/settings.php', '/admin.php', '/notes.php', 
    '/patient-lists.php', '/schedule.php', '/billing.php', '/search.php',
    'home.php', 'patient-chart.php', 'patients.php', 'inbox.php'
];

// Extract path from full URL if needed
$parsedUrl = parse_url($returnUrl);
$returnPath = $parsedUrl['path'] ?? $returnUrl;

$validReturn = false;
foreach ($allowedPrefixes as $prefix) {
    if (strpos($returnPath, $prefix) !== false) {
        $validReturn = true;
        break;
    }
}

if (!$validReturn) {
    $returnUrl = '/home.php';
}

// Ensure proper path format
if (!empty($returnUrl) && $returnUrl[0] !== '/' && strpos($returnUrl, 'http') !== 0) {
    $returnUrl = '/' . $returnUrl;
}

$success = false;

if ($patientId && $patientId > 0) {
    // Initialize array if needed
    if (!isset($_SESSION['open_patients'])) {
        $_SESSION['open_patients'] = [];
    }
    
    // Remove patient from open patients array
    $beforeCount = count($_SESSION['open_patients']);
    $_SESSION['open_patients'] = array_values(array_filter(
        $_SESSION['open_patients'],
        function($p) use ($patientId) {
            return (int)($p['id'] ?? 0) !== $patientId;
        }
    ));
    $afterCount = count($_SESSION['open_patients']);
    
    // Success if a tab was removed
    $success = ($afterCount < $beforeCount) || ($afterCount === 0 && $beforeCount === 0);
    
    // Force success if we have a valid patient ID
    $success = true;
}

// Return appropriate response based on request type
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'open_patients' => $_SESSION['open_patients'] ?? [],
        'redirect' => $returnUrl
    ]);
    exit;
}

// For form POST, redirect back
header('Location: ' . $returnUrl);
exit;

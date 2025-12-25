<?php
/**
 * Close Patient Tab Handler
 * 
 * Handles closing patient tabs via form POST to avoid nginx proxying issues.
 * Located in includes/ instead of api/ to avoid the proxy to Python backend.
 */

session_start();

// Get patient ID from POST
$patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
$returnUrl = isset($_POST['return_url']) ? $_POST['return_url'] : 'home.php';

// Validate return URL to prevent open redirect
$allowedPrefixes = ['home.php', 'patient-chart.php', 'patients.php', 'inbox.php', 'profile.php', 'settings.php', 'admin.php', 'notes.php', 'patient-lists.php'];
$validReturn = false;
foreach ($allowedPrefixes as $prefix) {
    if (strpos($returnUrl, $prefix) === 0 || strpos($returnUrl, '/' . $prefix) !== false) {
        $validReturn = true;
        break;
    }
}

if (!$validReturn) {
    $returnUrl = '/home.php';
}

if ($patientId) {
    // Initialize array if needed
    if (!isset($_SESSION['open_patients'])) {
        $_SESSION['open_patients'] = [];
    }
    
    // Remove patient from open patients array
    $_SESSION['open_patients'] = array_values(array_filter(
        $_SESSION['open_patients'],
        function($p) use ($patientId) {
            return (int)$p['id'] !== $patientId;
        }
    ));
}

// Redirect back
header('Location: ' . $returnUrl);
exit;

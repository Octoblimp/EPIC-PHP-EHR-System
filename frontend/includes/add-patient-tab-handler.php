<?php
/**
 * Add Patient Tab Handler
 * 
 * Handles adding patient tabs via form POST to avoid nginx proxying issues.
 * Located in includes/ instead of api/ to avoid the proxy to Python backend.
 */

session_start();

// Get patient ID and name from POST
$patientId = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;
$patientName = isset($_POST['patient_name']) ? $_POST['patient_name'] : 'Patient ' . $patientId;

if ($patientId) {
    // Initialize array if needed
    if (!isset($_SESSION['open_patients'])) {
        $_SESSION['open_patients'] = [];
    }
    
    // Check if patient is already in open tabs
    $found = false;
    foreach ($_SESSION['open_patients'] as $p) {
        if ($p['id'] == $patientId) {
            $found = true;
            break;
        }
    }
    
    // Add if not found
    if (!$found) {
        $_SESSION['open_patients'][] = [
            'id' => $patientId,
            'name' => $patientName
        ];
    }
}

// Redirect to patient chart
header('Location: /patient-chart.php?id=' . urlencode($patientId));
exit;

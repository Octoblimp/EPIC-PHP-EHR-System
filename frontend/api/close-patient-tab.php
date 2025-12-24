<?php
/**
 * API endpoint to close a patient tab
 */
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'error' => 'Patient ID required']);
    exit;
}

// Initialize open patients array if not exists
if (!isset($_SESSION['open_patients'])) {
    $_SESSION['open_patients'] = [];
}

$patient_id = $input['id'];

// Remove patient from open tabs
$_SESSION['open_patients'] = array_values(array_filter(
    $_SESSION['open_patients'],
    function($p) use ($patient_id) {
        return $p['id'] != $patient_id;
    }
));

echo json_encode(['success' => true, 'open_patients' => $_SESSION['open_patients']]);

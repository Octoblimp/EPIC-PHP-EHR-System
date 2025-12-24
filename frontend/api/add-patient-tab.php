<?php
/**
 * API endpoint to add a patient to open tabs
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
$patient_name = $input['name'] ?? 'Patient ' . $patient_id;

// Check if patient is already in open tabs
$found = false;
foreach ($_SESSION['open_patients'] as $p) {
    if ($p['id'] == $patient_id) {
        $found = true;
        break;
    }
}

// Add if not found
if (!$found) {
    $_SESSION['open_patients'][] = [
        'id' => $patient_id,
        'name' => $patient_name
    ];
}

echo json_encode(['success' => true, 'open_patients' => $_SESSION['open_patients']]);

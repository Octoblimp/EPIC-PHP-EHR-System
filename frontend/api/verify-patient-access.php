<?php
/**
 * Openspace EHR - Verify Patient Access API
 * Handles DOB verification for patient record protection
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/patient_protection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$patient_id = $input['patient_id'] ?? null;
$entered_dob = $input['dob'] ?? null;

if (!$patient_id || !$entered_dob) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Patient ID and DOB are required']);
    exit;
}

// Check if protection is enabled
if (!isPatientProtectionEnabled()) {
    echo json_encode(['success' => true, 'message' => 'Protection disabled']);
    exit;
}

// Try to get patient from API first
$patientData = null;
try {
    $result = $patientService->getById($patient_id);
    if ($result['success'] ?? false) {
        $patientData = $result['data'];
    }
} catch (Exception $e) {
    // API not available, will use demo verification
}

// If we have real patient data, verify DOB
if ($patientData) {
    $actual_dob = $patientData['date_of_birth'] ?? '';
    
    // Convert from YYYY-MM-DD to MMDDYYYY for comparison
    if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $actual_dob, $matches)) {
        $formatted_dob = $matches[2] . $matches[3] . $matches[1]; // MMDDYYYY
    } else {
        $formatted_dob = str_replace(['-', '/'], '', $actual_dob);
    }
    
    // Clean entered DOB (remove any separators)
    $entered_clean = preg_replace('/[^0-9]/', '', $entered_dob);
    
    // Compare DOBs
    if ($entered_clean === $formatted_dob) {
        // Grant access - store in session
        grantPatientAccess($patient_id);
        
        // Log successful verification
        logPatientAccessAttempt($patient_id, true);
        
        echo json_encode(['success' => true, 'message' => 'Access verified']);
        exit;
    } else {
        // Log failed verification
        logPatientAccessAttempt($patient_id, false);
        
        echo json_encode(['success' => false, 'error' => 'Date of birth does not match our records']);
        exit;
    }
} else {
    // Demo mode - use default DOB for demo patients
    // Demo patient DOB: 03151955 (March 15, 1955 - John Smith)
    $demo_dobs = [
        '1' => '03151955',
        '2' => '07221948', 
        '3' => '11051962',
        'default' => '03151955'
    ];
    
    $expected_dob = $demo_dobs[$patient_id] ?? $demo_dobs['default'];
    $entered_clean = preg_replace('/[^0-9]/', '', $entered_dob);
    
    if ($entered_clean === $expected_dob) {
        grantPatientAccess($patient_id);
        logPatientAccessAttempt($patient_id, true);
        echo json_encode(['success' => true, 'message' => 'Access verified (demo mode)']);
        exit;
    } else {
        logPatientAccessAttempt($patient_id, false);
        echo json_encode([
            'success' => false, 
            'error' => 'Date of birth does not match our records',
            'hint' => 'Demo hint: Try 03151955 for patient ID 1'
        ]);
        exit;
    }
}

/**
 * Grant patient access in session
 */
function grantPatientAccess($patient_id) {
    if (!isset($_SESSION['verified_patients'])) {
        $_SESSION['verified_patients'] = [];
        $_SESSION['verified_patients_expiry'] = [];
    }
    
    if (!in_array($patient_id, $_SESSION['verified_patients'])) {
        $_SESSION['verified_patients'][] = $patient_id;
    }
    $_SESSION['verified_patients_expiry'][$patient_id] = time() + (30 * 60); // 30 minutes
}

/**
 * Log patient access attempt
 */
function logPatientAccessAttempt($patient_id, $success) {
    global $db;
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Try to log to database if available
    try {
        if (isset($db) && $db) {
            $stmt = $db->prepare("
                INSERT INTO patient_access_log 
                (patient_id, user_id, action, success, ip_address, user_agent, created_at)
                VALUES (?, ?, 'DOB_VERIFICATION', ?, ?, ?, NOW())
            ");
            $stmt->execute([$patient_id, $user_id, $success ? 1 : 0, $ip_address, $user_agent]);
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist
        error_log("Patient access logging failed: " . $e->getMessage());
    }
    
    // Also use audit function if available
    if (function_exists('logAudit')) {
        $action = $success ? 'PATIENT_ACCESS_VERIFIED' : 'PATIENT_ACCESS_DENIED';
        logAudit($action, 'Patient Record', 'DOB verification ' . ($success ? 'successful' : 'failed'), $patient_id);
    }
}

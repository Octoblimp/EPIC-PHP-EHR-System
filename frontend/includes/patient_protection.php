<?php
/**
 * Openspace EHR - Patient Record Protection
 * HIPAA-Compliant DOB-based access verification for patient records
 * 
 * SECURITY NOTES:
 * - Verification MUST happen server-side before ANY patient data is loaded
 * - Uses constant-time comparison to prevent timing attacks
 * - Sessions are cryptographically signed to prevent tampering
 * - Rate limiting prevents brute force attacks
 */

/**
 * Check if patient record protection is enabled
 */
function isPatientProtectionEnabled() {
    $settings = $_SESSION['system_settings'] ?? [];
    return ($settings['patient_record_protection'] ?? false) === true 
        || ($settings['patient_record_protection'] ?? 'false') === 'true';
}

/**
 * Check if user has verified access to patient
 * Enhanced with signature verification to prevent session tampering
 */
function hasVerifiedPatientAccess($patient_id) {
    if (!isPatientProtectionEnabled()) {
        return true; // Protection disabled, allow access
    }
    
    $verified_patients = $_SESSION['verified_patients'] ?? [];
    $expiry_time = $_SESSION['verified_patients_expiry'][$patient_id] ?? 0;
    
    // Check if patient ID is in verified list
    if (!in_array($patient_id, $verified_patients)) {
        return false;
    }
    
    // Check expiry (30 minute sessions)
    if (time() >= $expiry_time) {
        // Expired - remove verification
        clearPatientVerification($patient_id);
        return false;
    }
    
    // Verify signature to prevent tampering
    $expected_sig = hash_hmac(
        'sha256',
        $patient_id . $expiry_time,
        session_id()
    );
    
    $stored_sig = $_SESSION['verified_patients_sig'][$patient_id] ?? '';
    
    // Constant-time comparison
    if (!hash_equals($expected_sig, $stored_sig)) {
        // Signature mismatch - possible tampering
        clearPatientVerification($patient_id);
        
        // Log security event
        if (function_exists('logAudit')) {
            logAudit('SECURITY_ALERT', 'Patient Record', 'Verification signature mismatch detected', $patient_id);
        }
        
        return false;
    }
    
    return true;
}

/**
 * Grant patient access with secure session storage
 * Called after successful DOB verification
 */
function grantSecurePatientAccess($patient_id) {
    if (!isset($_SESSION['verified_patients'])) {
        $_SESSION['verified_patients'] = [];
        $_SESSION['verified_patients_expiry'] = [];
        $_SESSION['verified_patients_sig'] = [];
    }
    
    // Store verification
    if (!in_array($patient_id, $_SESSION['verified_patients'])) {
        $_SESSION['verified_patients'][] = $patient_id;
    }
    
    // Set expiry (30 minutes)
    $expiry_time = time() + (30 * 60);
    $_SESSION['verified_patients_expiry'][$patient_id] = $expiry_time;
    
    // Generate cryptographic signature to prevent tampering
    $_SESSION['verified_patients_sig'][$patient_id] = hash_hmac(
        'sha256',
        $patient_id . $expiry_time,
        session_id()
    );
    
    // Log successful verification
    if (function_exists('logAudit')) {
        logAudit('PATIENT_ACCESS_VERIFIED', 'Patient Record', 'Secure DOB verification successful', $patient_id);
    }
}

/**
 * Verify patient access with DOB (legacy function - use verify-patient.php instead)
 * This remains for API compatibility but new code should use the secure verification page
 */
function verifyPatientAccess($patient_id, $entered_dob) {
    // Get patient's actual DOB from API
    global $patientService;
    
    if (!$patientService) {
        return ['success' => false, 'error' => 'Patient service not available'];
    }
    
    try {
        $patient = $patientService->getPatient($patient_id);
        
        if (!$patient) {
            return ['success' => false, 'error' => 'Patient not found'];
        }
        
        // Format the patient's DOB to match expected format (MMDDYYYY)
        $actual_dob = $patient['date_of_birth'] ?? '';
        
        // Convert from YYYY-MM-DD to MMDDYYYY
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $actual_dob, $matches)) {
            $formatted_dob = $matches[2] . $matches[3] . $matches[1];
        } else {
            $formatted_dob = str_replace(['-', '/'], '', $actual_dob);
        }
        
        // Clean entered DOB (remove any separators)
        $entered_clean = preg_replace('/[^0-9]/', '', $entered_dob);
        
        // SECURITY: Use constant-time comparison to prevent timing attacks
        if (hash_equals($formatted_dob, $entered_clean)) {
            // Grant secure access
            grantSecurePatientAccess($patient_id);
            return ['success' => true];
        } else {
            // Log failed verification attempt
            if (function_exists('logAudit')) {
                logAudit('PATIENT_ACCESS_DENIED', 'Patient Record', 'DOB verification failed', $patient_id);
            }
            
            return ['success' => false, 'error' => 'Date of birth does not match'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Unable to verify patient: ' . $e->getMessage()];
    }
}

/**
 * Clear patient verification
 */
function clearPatientVerification($patient_id = null) {
    if ($patient_id) {
        // Remove from array
        $key = array_search($patient_id, $_SESSION['verified_patients'] ?? []);
        if ($key !== false) {
            unset($_SESSION['verified_patients'][$key]);
            $_SESSION['verified_patients'] = array_values($_SESSION['verified_patients']); // Re-index
        }
        unset($_SESSION['verified_patients_expiry'][$patient_id]);
        unset($_SESSION['verified_patients_sig'][$patient_id]);
    } else {
        // Clear all
        $_SESSION['verified_patients'] = [];
        $_SESSION['verified_patients_expiry'] = [];
        $_SESSION['verified_patients_sig'] = [];
    }
}

/**
 * Get URL for secure verification page
 */
function getVerificationUrl($patient_id, $return_tab = 'summary') {
    return 'verify-patient.php?id=' . (int)$patient_id . '&tab=' . urlencode($return_tab);
}

/**
 * Check if verification is about to expire (within 5 minutes)
 */
function isVerificationExpiringSoon($patient_id) {
    $expiry_time = $_SESSION['verified_patients_expiry'][$patient_id] ?? 0;
    return ($expiry_time - time()) < 300; // Less than 5 minutes
}

/**
 * Extend verification time (reset to 30 minutes)
 */
function extendVerification($patient_id) {
    if (!hasVerifiedPatientAccess($patient_id)) {
        return false;
    }
    
    $expiry_time = time() + (30 * 60);
    $_SESSION['verified_patients_expiry'][$patient_id] = $expiry_time;
    
    // Update signature
    $_SESSION['verified_patients_sig'][$patient_id] = hash_hmac(
        'sha256',
        $patient_id . $expiry_time,
        session_id()
    );
    
    return true;
}

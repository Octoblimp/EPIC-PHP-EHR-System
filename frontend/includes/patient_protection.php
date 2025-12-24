<?php
/**
 * Openspace EHR - Patient Record Protection
 * DOB-based access verification for patient records
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
 */
function hasVerifiedPatientAccess($patient_id) {
    if (!isPatientProtectionEnabled()) {
        return true; // Protection disabled, allow access
    }
    
    $verified_patients = $_SESSION['verified_patients'] ?? [];
    $expiry_time = $_SESSION['verified_patients_expiry'][$patient_id] ?? 0;
    
    // Check if verified and not expired (30 minute sessions)
    if (in_array($patient_id, $verified_patients) && time() < $expiry_time) {
        return true;
    }
    
    return false;
}

/**
 * Verify patient access with DOB
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
        
        // Compare
        if ($entered_clean === $formatted_dob) {
            // Grant access
            if (!isset($_SESSION['verified_patients'])) {
                $_SESSION['verified_patients'] = [];
                $_SESSION['verified_patients_expiry'] = [];
            }
            
            $_SESSION['verified_patients'][] = $patient_id;
            $_SESSION['verified_patients'][$patient_id] = true;
            $_SESSION['verified_patients_expiry'][$patient_id] = time() + (30 * 60); // 30 minutes
            
            // Log successful verification
            if (function_exists('logAudit')) {
                logAudit('PATIENT_ACCESS_VERIFIED', 'Patient Record', 'DOB verification successful', $patient_id);
            }
            
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
        $key = array_search($patient_id, $_SESSION['verified_patients'] ?? []);
        if ($key !== false) {
            unset($_SESSION['verified_patients'][$key]);
        }
        unset($_SESSION['verified_patients_expiry'][$patient_id]);
    } else {
        // Clear all
        $_SESSION['verified_patients'] = [];
        $_SESSION['verified_patients_expiry'] = [];
    }
}

/**
 * Generate patient verification modal HTML
 */
function renderPatientVerificationModal() {
    if (!isPatientProtectionEnabled()) {
        return '';
    }
    
    return '
    <!-- Patient Verification Modal -->
    <div id="patientVerifyModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-shield-alt"></i> Patient Record Protection</h3>
                <button type="button" class="modal-close" onclick="closeVerifyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">Please verify your access to this patient\'s record by entering their date of birth.</p>
                <div class="form-group">
                    <label>Patient Date of Birth</label>
                    <input type="text" id="verifyDOB" class="form-control" placeholder="MMDDYYYY" maxlength="8" pattern="[0-9]*" inputmode="numeric">
                    <small style="color: #888;">Format: MMDDYYYY (e.g., 01311990)</small>
                </div>
                <div id="verifyError" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeVerifyModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitVerification()">
                    <i class="fas fa-check"></i> Verify
                </button>
            </div>
        </div>
    </div>
    
    <script>
    var pendingPatientId = null;
    var pendingRedirectUrl = null;
    
    function requirePatientVerification(patientId, redirectUrl) {
        pendingPatientId = patientId;
        pendingRedirectUrl = redirectUrl || null;
        document.getElementById("verifyDOB").value = "";
        document.getElementById("verifyError").style.display = "none";
        document.getElementById("patientVerifyModal").style.display = "flex";
        document.getElementById("verifyDOB").focus();
    }
    
    function closeVerifyModal() {
        document.getElementById("patientVerifyModal").style.display = "none";
        pendingPatientId = null;
        pendingRedirectUrl = null;
    }
    
    function submitVerification() {
        var dob = document.getElementById("verifyDOB").value;
        var errorDiv = document.getElementById("verifyError");
        
        if (!dob || dob.length !== 8) {
            errorDiv.textContent = "Please enter date of birth in MMDDYYYY format";
            errorDiv.style.display = "block";
            return;
        }
        
        fetch("/api/verify-patient-access", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ patient_id: pendingPatientId, dob: dob })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                closeVerifyModal();
                if (pendingRedirectUrl) {
                    window.location.href = pendingRedirectUrl;
                } else {
                    window.location.reload();
                }
            } else {
                errorDiv.textContent = data.error || "Verification failed";
                errorDiv.style.display = "block";
            }
        })
        .catch(function(err) {
            errorDiv.textContent = "Error verifying access: " + err.message;
            errorDiv.style.display = "block";
        });
    }
    
    // Handle enter key
    document.addEventListener("DOMContentLoaded", function() {
        var dobField = document.getElementById("verifyDOB");
        if (dobField) {
            dobField.addEventListener("keypress", function(e) {
                if (e.key === "Enter") {
                    submitVerification();
                }
            });
        }
    });
    </script>
    ';
}

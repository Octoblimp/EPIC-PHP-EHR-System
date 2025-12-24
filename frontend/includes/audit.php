<?php
/**
 * Openspace EHR - Audit Logging System
 * HIPAA-compliant audit logging for all system activities
 */

// Audit log file path
define('AUDIT_LOG_PATH', __DIR__ . '/../../logs/audit.log');
define('AUDIT_DB_ENABLED', true);

/**
 * Log an audit event
 */
function logAudit($action, $resource, $details = '', $patient_id = null) {
    global $api;
    
    // Get user info
    $user = $_SESSION['user'] ?? ['username' => 'anonymous', 'role' => 'none'];
    $username = $user['username'] ?? 'anonymous';
    $user_id = $user['id'] ?? 0;
    
    // Get request info
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = [
        'timestamp' => $timestamp,
        'user_id' => $user_id,
        'username' => $username,
        'action' => $action,
        'resource' => $resource,
        'details' => $details,
        'patient_id' => $patient_id,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'request_uri' => $request_uri,
        'request_method' => $request_method
    ];
    
    // Log to file
    $log_dir = dirname(AUDIT_LOG_PATH);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_line = json_encode($log_entry) . PHP_EOL;
    file_put_contents(AUDIT_LOG_PATH, $log_line, FILE_APPEND | LOCK_EX);
    
    // Also try to log to API/database
    if (AUDIT_DB_ENABLED && isset($api)) {
        try {
            $api->post('/audit', $log_entry);
        } catch (Exception $e) {
            // Silently fail - file log is primary
        }
    }
    
    // Store in session for recent activity
    if (!isset($_SESSION['recent_audit'])) {
        $_SESSION['recent_audit'] = [];
    }
    array_unshift($_SESSION['recent_audit'], $log_entry);
    $_SESSION['recent_audit'] = array_slice($_SESSION['recent_audit'], 0, 50);
    
    return true;
}

/**
 * Log page access
 */
function logPageAccess($page_name = null) {
    if (!$page_name) {
        $page_name = basename($_SERVER['PHP_SELF'], '.php');
    }
    
    $details = "Accessed page: {$page_name}";
    
    // Check if patient context
    $patient_id = null;
    if (isset($_GET['id']) && strpos($_SERVER['PHP_SELF'], 'patient') !== false) {
        $patient_id = $_GET['id'];
        $details .= " (Patient ID: {$patient_id})";
    }
    
    logAudit('PAGE_ACCESS', $page_name, $details, $patient_id);
}

/**
 * Log patient record access
 */
function logPatientAccess($patient_id, $action = 'VIEW') {
    $details = "Patient record accessed";
    logAudit($action, 'Patient Record', $details, $patient_id);
}

/**
 * Log authentication events
 */
function logAuthEvent($event_type, $username, $success = true) {
    $status = $success ? 'successful' : 'failed';
    $details = "{$event_type} {$status} for user: {$username}";
    logAudit($event_type, 'Authentication', $details);
}

/**
 * Log data modifications
 */
function logDataChange($resource, $action, $record_id, $changes = []) {
    $details = "{$action} on {$resource} ID: {$record_id}";
    if (!empty($changes)) {
        $details .= " | Changes: " . json_encode($changes);
    }
    logAudit($action, $resource, $details);
}

/**
 * Get recent audit logs from session
 */
function getRecentAuditLogs($limit = 20) {
    return array_slice($_SESSION['recent_audit'] ?? [], 0, $limit);
}

/**
 * Read audit logs from file
 */
function readAuditLogs($start_date = null, $end_date = null, $user = null, $action = null, $limit = 100) {
    $logs = [];
    
    if (!file_exists(AUDIT_LOG_PATH)) {
        return $logs;
    }
    
    $file = new SplFileObject(AUDIT_LOG_PATH);
    $file->seek(PHP_INT_MAX);
    $total_lines = $file->key();
    
    // Read from end of file
    $start = max(0, $total_lines - $limit * 10);
    $file->seek($start);
    
    while (!$file->eof()) {
        $line = trim($file->fgets());
        if (empty($line)) continue;
        
        $entry = json_decode($line, true);
        if (!$entry) continue;
        
        // Apply filters
        if ($start_date && $entry['timestamp'] < $start_date) continue;
        if ($end_date && $entry['timestamp'] > $end_date) continue;
        if ($user && $entry['username'] !== $user) continue;
        if ($action && $entry['action'] !== $action) continue;
        
        $logs[] = $entry;
        
        if (count($logs) >= $limit) break;
    }
    
    return array_reverse($logs);
}

// Auto-log page access on include
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['authenticated']) && $_SESSION['authenticated']) {
    logPageAccess();
}

<?php
/**
 * Openspace EHR - Patient Data API
 * Handles patient-related data operations (sticky notes, status changes, code status, etc.)
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$patient_id = $_GET['patient_id'] ?? null;

// Get JSON input for POST/PUT requests
$input = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $patient_id = $input['patient_id'] ?? $patient_id;
}

if (!$patient_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Patient ID is required']);
    exit;
}

// Route to appropriate handler
switch ($action) {
    case 'sticky-note':
        handleStickyNote($method, $patient_id, $input);
        break;
        
    case 'status':
        handleStatusChange($method, $patient_id, $input);
        break;
        
    case 'code-status':
        handleCodeStatus($method, $patient_id, $input);
        break;
        
    case 'encounter':
        handleEncounter($method, $patient_id, $input);
        break;
    
    case 'note':
        handleClinicalNote($method, $patient_id, $input);
        break;
        
    case 'order':
        handleOrder($method, $patient_id, $input);
        break;
        
    case 'order-set':
        handleOrderSet($method, $patient_id, $input);
        break;
        
    case 'medication-admin':
        handleMedicationAdmin($method, $patient_id, $input);
        break;
        
    case 'demographics':
        handleDemographics($method, $patient_id, $input);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}

/**
 * Handle sticky notes CRUD operations
 */
function handleStickyNote($method, $patient_id, $input) {
    global $db, $apiClient;
    
    if ($method === 'POST') {
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $color = $input['color'] ?? 'yellow';
        $priority = $input['priority'] ?? 'Normal';
        
        if (!$title || !$content) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Title and content are required']);
            return;
        }
        
        $user_id = $_SESSION['user_id'] ?? 0;
        $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'System';
        
        // Try to save to backend API first
        try {
            $response = $apiClient->post('/patients/' . $patient_id . '/notes', [
                'type' => 'sticky',
                'title' => $title,
                'content' => $content,
                'color' => $color,
                'priority' => $priority
            ]);
            
            if ($response['success'] ?? false) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sticky note saved',
                    'data' => $response['data'] ?? [
                        'id' => $response['data']['id'] ?? uniqid(),
                        'title' => $title,
                        'content' => $content,
                        'color' => $color,
                        'priority' => $priority,
                        'created_by' => $user_name,
                        'created_at' => date('m/d/Y H:i')
                    ]
                ]);
                return;
            }
        } catch (Exception $e) {
            // API not available, try local database
        }
        
        // Try local database
        try {
            if (isset($db) && $db) {
                $stmt = $db->prepare("
                    INSERT INTO patient_sticky_notes 
                    (patient_id, title, content, color, priority, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$patient_id, $title, $content, $color, $priority, $user_id]);
                $note_id = $db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sticky note saved',
                    'data' => [
                        'id' => $note_id,
                        'title' => $title,
                        'content' => $content,
                        'color' => $color,
                        'priority' => $priority,
                        'created_by' => $user_name,
                        'created_at' => date('m/d/Y H:i')
                    ]
                ]);
                return;
            }
        } catch (Exception $e) {
            // Database not available
        }
        
        // Demo mode fallback - store in session
        if (!isset($_SESSION['patient_sticky_notes'])) {
            $_SESSION['patient_sticky_notes'] = [];
        }
        if (!isset($_SESSION['patient_sticky_notes'][$patient_id])) {
            $_SESSION['patient_sticky_notes'][$patient_id] = [];
        }
        
        $note = [
            'id' => uniqid('note_'),
            'title' => $title,
            'content' => $content,
            'color' => $color,
            'priority' => $priority,
            'created_by' => $user_name,
            'created_at' => date('m/d/Y H:i')
        ];
        
        array_unshift($_SESSION['patient_sticky_notes'][$patient_id], $note);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sticky note saved (session)',
            'data' => $note
        ]);
        
    } elseif ($method === 'DELETE') {
        $note_id = $input['note_id'] ?? $_GET['note_id'] ?? null;
        
        if (!$note_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Note ID is required']);
            return;
        }
        
        // Try to delete from backend
        try {
            $response = $apiClient->delete('/patients/' . $patient_id . '/notes/' . $note_id);
            if ($response['success'] ?? false) {
                echo json_encode(['success' => true, 'message' => 'Note deleted']);
                return;
            }
        } catch (Exception $e) {
            // Continue to local storage
        }
        
        // Delete from session
        if (isset($_SESSION['patient_sticky_notes'][$patient_id])) {
            $_SESSION['patient_sticky_notes'][$patient_id] = array_filter(
                $_SESSION['patient_sticky_notes'][$patient_id],
                function($note) use ($note_id) { return $note['id'] != $note_id; }
            );
        }
        
        echo json_encode(['success' => true, 'message' => 'Note deleted']);
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
}

/**
 * Handle patient status changes
 */
function handleStatusChange($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST' && $method !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $new_status = $input['status'] ?? '';
    $encounter_type = $input['encounter_type'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if (!$new_status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Status is required']);
        return;
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'System';
    
    // Try backend API
    try {
        $response = $apiClient->put('/patients/' . $patient_id . '/status', [
            'status' => $new_status,
            'encounter_type' => $encounter_type,
            'reason' => $reason
        ]);
        
        if ($response['success'] ?? false) {
            // Log audit
            if (function_exists('logAudit')) {
                logAudit('PATIENT_STATUS_CHANGE', 'Patient', 
                    "Status changed to: $new_status" . ($reason ? " - Reason: $reason" : ""), 
                    $patient_id);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient status updated',
                'data' => [
                    'status' => $new_status,
                    'encounter_type' => $encounter_type,
                    'changed_by' => $user_name,
                    'changed_at' => date('Y-m-d H:i:s')
                ]
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_status_changes'])) {
        $_SESSION['patient_status_changes'] = [];
    }
    
    $change = [
        'patient_id' => $patient_id,
        'status' => $new_status,
        'encounter_type' => $encounter_type,
        'reason' => $reason,
        'changed_by' => $user_name,
        'changed_at' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['patient_status_changes'][$patient_id] = $change;
    
    // Also update the patient's current status in session
    if (isset($_SESSION['patient_encounters'])) {
        $_SESSION['patient_encounters'][$patient_id] = [
            'type' => $encounter_type ?: 'Inpatient',
            'status' => $new_status
        ];
    }
    
    // Log audit
    if (function_exists('logAudit')) {
        logAudit('PATIENT_STATUS_CHANGE', 'Patient', 
            "Status changed to: $new_status" . ($reason ? " - Reason: $reason" : ""), 
            $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Patient status updated (session)',
        'data' => $change
    ]);
}

/**
 * Handle code status changes
 */
function handleCodeStatus($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST' && $method !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $code_status = $input['code_status'] ?? '';
    $documentation = $input['documentation'] ?? '';
    
    if (!$code_status) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Code status is required']);
        return;
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'System';
    
    // Try backend API
    try {
        $response = $apiClient->put('/patients/' . $patient_id . '/code-status', [
            'code_status' => $code_status,
            'documentation' => $documentation
        ]);
        
        if ($response['success'] ?? false) {
            // Log audit
            if (function_exists('logAudit')) {
                logAudit('CODE_STATUS_CHANGE', 'Patient', 
                    "Code status changed to: $code_status", 
                    $patient_id);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Code status updated',
                'data' => [
                    'code_status' => $code_status,
                    'documentation' => $documentation,
                    'changed_by' => $user_name,
                    'changed_at' => date('Y-m-d H:i:s')
                ]
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_code_status'])) {
        $_SESSION['patient_code_status'] = [];
    }
    
    $change = [
        'patient_id' => $patient_id,
        'code_status' => $code_status,
        'documentation' => $documentation,
        'changed_by' => $user_name,
        'changed_at' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['patient_code_status'][$patient_id] = $change;
    
    // Log audit
    if (function_exists('logAudit')) {
        logAudit('CODE_STATUS_CHANGE', 'Patient', 
            "Code status changed to: $code_status", 
            $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Code status updated (session)',
        'data' => $change
    ]);
}

/**
 * Handle encounter updates
 */
function handleEncounter($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST' && $method !== 'PUT') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'System';
    
    // Try backend API
    try {
        $response = $apiClient->put('/encounters/' . $patient_id, $input);
        
        if ($response['success'] ?? false) {
            echo json_encode([
                'success' => true,
                'message' => 'Encounter updated',
                'data' => $response['data'] ?? $input
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_encounters'])) {
        $_SESSION['patient_encounters'] = [];
    }
    
    $_SESSION['patient_encounters'][$patient_id] = array_merge(
        $_SESSION['patient_encounters'][$patient_id] ?? [],
        $input,
        ['updated_by' => $user_name, 'updated_at' => date('Y-m-d H:i:s')]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Encounter updated (session)',
        'data' => $_SESSION['patient_encounters'][$patient_id]
    ]);
}

/**
 * Handle clinical notes
 */
function handleClinicalNote($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'Provider';
    $note_type = $input['type'] ?? 'progress';
    $content = $input['content'] ?? '';
    $status = $input['status'] ?? 'draft';
    
    if (!$content) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Note content is required']);
        return;
    }
    
    // Try backend API
    try {
        $response = $apiClient->post('/patients/' . $patient_id . '/notes', [
            'type' => $note_type,
            'content' => $content,
            'status' => $status
        ]);
        
        if ($response['success'] ?? false) {
            if (function_exists('logAudit')) {
                logAudit('CLINICAL_NOTE_' . strtoupper($status), 'Notes', 
                    "$note_type note $status", $patient_id);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Note saved',
                'data' => $response['data'] ?? []
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_notes'])) {
        $_SESSION['patient_notes'] = [];
    }
    if (!isset($_SESSION['patient_notes'][$patient_id])) {
        $_SESSION['patient_notes'][$patient_id] = [];
    }
    
    $note = [
        'id' => uniqid('note_'),
        'type' => $note_type,
        'content' => $content,
        'status' => $status,
        'author' => $user_name,
        'created_at' => date('Y-m-d H:i:s'),
        'signed_at' => $status === 'signed' ? date('Y-m-d H:i:s') : null
    ];
    
    array_unshift($_SESSION['patient_notes'][$patient_id], $note);
    
    if (function_exists('logAudit')) {
        logAudit('CLINICAL_NOTE_' . strtoupper($status), 'Notes', 
            "$note_type note $status", $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Note saved (session)',
        'data' => $note
    ]);
}

/**
 * Handle orders
 */
function handleOrder($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'Provider';
    $order_type = $input['order_type'] ?? 'unknown';
    $order_name = $input['order_name'] ?? '';
    
    if (!$order_name) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order name is required']);
        return;
    }
    
    // Try backend API
    try {
        $response = $apiClient->post('/orders', [
            'patient_id' => $patient_id,
            'type' => $order_type,
            'name' => $order_name,
            'status' => 'pending'
        ]);
        
        if ($response['success'] ?? false) {
            if (function_exists('logAudit')) {
                logAudit('ORDER_PLACED', 'Orders', "$order_name ($order_type)", $patient_id);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Order submitted',
                'data' => $response['data'] ?? []
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_orders'])) {
        $_SESSION['patient_orders'] = [];
    }
    if (!isset($_SESSION['patient_orders'][$patient_id])) {
        $_SESSION['patient_orders'][$patient_id] = [];
    }
    
    $order = [
        'id' => uniqid('ORD-'),
        'type' => $order_type,
        'name' => $order_name,
        'status' => 'pending',
        'ordered_by' => $user_name,
        'ordered_at' => date('Y-m-d H:i:s')
    ];
    
    array_unshift($_SESSION['patient_orders'][$patient_id], $order);
    
    if (function_exists('logAudit')) {
        logAudit('ORDER_PLACED', 'Orders', "$order_name ($order_type)", $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Order submitted (session)',
        'data' => $order
    ]);
}

/**
 * Handle order sets
 */
function handleOrderSet($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'Provider';
    $order_set = $input['order_set'] ?? '';
    
    // Try backend API
    try {
        $response = $apiClient->post('/order-sets/apply', [
            'patient_id' => $patient_id,
            'order_set' => $order_set
        ]);
        
        if ($response['success'] ?? false) {
            if (function_exists('logAudit')) {
                logAudit('ORDER_SET_APPLIED', 'Orders', "Order set: $order_set", $patient_id);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Order set applied',
                'data' => $response['data'] ?? []
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode
    if (function_exists('logAudit')) {
        logAudit('ORDER_SET_APPLIED', 'Orders', "Order set: $order_set", $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Order set applied (session)',
        'data' => ['order_set' => $order_set, 'applied_by' => $user_name]
    ]);
}

/**
 * Handle medication administration
 */
function handleMedicationAdmin($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'Nurse';
    $med_id = $input['medication_id'] ?? '';
    $action = $input['action'] ?? 'given';
    $time = $input['time'] ?? date('H:i');
    
    // Try backend API
    try {
        $response = $apiClient->post('/medications/administration', $input);
        
        if ($response['success'] ?? false) {
            if (function_exists('logAudit')) {
                logAudit('MEDICATION_ADMIN', 'MAR', "Med $med_id: $action at $time", $patient_id);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Administration recorded',
                'data' => $response['data'] ?? []
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['medication_admin'])) {
        $_SESSION['medication_admin'] = [];
    }
    
    $admin = [
        'id' => uniqid('admin_'),
        'medication_id' => $med_id,
        'action' => $action,
        'time' => $time,
        'dose' => $input['dose'] ?? '',
        'site' => $input['site'] ?? '',
        'reason' => $input['reason'] ?? '',
        'comments' => $input['comments'] ?? '',
        'administered_by' => $user_name,
        'recorded_at' => date('Y-m-d H:i:s')
    ];
    
    $_SESSION['medication_admin'][] = $admin;
    
    if (function_exists('logAudit')) {
        logAudit('MEDICATION_ADMIN', 'MAR', "Med $med_id: $action at $time", $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Administration recorded (session)',
        'data' => $admin
    ]);
}

/**
 * Handle demographics updates
 */
function handleDemographics($method, $patient_id, $input) {
    global $apiClient;
    
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    $user_name = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'User';
    $section = $input['section'] ?? 'unknown';
    
    // Try backend API
    try {
        $response = $apiClient->put('/patients/' . $patient_id, $input);
        
        if ($response['success'] ?? false) {
            if (function_exists('logAudit')) {
                logAudit('DEMOGRAPHICS_UPDATED', 'Patient', "Section: $section", $patient_id);
            }
            echo json_encode([
                'success' => true,
                'message' => 'Demographics updated',
                'data' => $response['data'] ?? []
            ]);
            return;
        }
    } catch (Exception $e) {
        // API not available
    }
    
    // Demo mode - store in session
    if (!isset($_SESSION['patient_demographics'])) {
        $_SESSION['patient_demographics'] = [];
    }
    
    $_SESSION['patient_demographics'][$patient_id] = array_merge(
        $_SESSION['patient_demographics'][$patient_id] ?? [],
        $input,
        ['updated_by' => $user_name, 'updated_at' => date('Y-m-d H:i:s')]
    );
    
    if (function_exists('logAudit')) {
        logAudit('DEMOGRAPHICS_UPDATED', 'Patient', "Section: $section", $patient_id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Demographics updated (session)',
        'data' => $_SESSION['patient_demographics'][$patient_id]
    ]);
}
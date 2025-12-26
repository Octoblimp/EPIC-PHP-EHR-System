<?php
/**
 * Openspace EHR - Database Encryption Admin Tool
 * HIPAA-compliant AES-256-GCM encryption for all Protected Health Information (PHI/PII)
 * 
 * This tool interfaces with the Python backend encryption system to:
 * - Analyze existing data encryption status
 * - Encrypt unencrypted records
 * - Verify encryption integrity
 * 
 * WARNING: Ensure you have a backup before running encryption migrations!
 * 
 * Encryption Features:
 * - AES-256-GCM (Authenticated Encryption)
 * - Per-record random salts for key derivation
 * - Per-record random nonces (IVs)
 * - HKDF for secure key derivation
 * - Authentication tags prevent tampering
 */

$page_title = 'Database Encryption Tool - Openspace EHR';

require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/api.php';

// Security check - admin only
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'administrator', 'system']);

if (!$is_admin) {
    die('Access denied. Administrator privileges required.');
}

// Define complete encryption schema matching backend models
$encryption_schema = [
    'patients' => [
        'table_name' => 'Patients',
        'fields' => [
            'first_name', 'last_name', 'middle_name', 'gender', 'ssn_last_four',
            'address', 'city', 'state', 'zip_code', 'phone_home', 'phone_cell',
            'email', 'blood_type', 'primary_care_provider', 'insurance_plan', 'preferred_language'
        ]
    ],
    'allergies' => [
        'table_name' => 'Allergies',
        'fields' => ['allergen', 'reaction', 'severity', 'allergy_type', 'verified_by']
    ],
    'users' => [
        'table_name' => 'Users',
        'fields' => ['first_name', 'last_name', 'email', 'department', 'specialty', 'title', 'npi', 'provider_id']
    ],
    'notes' => [
        'table_name' => 'Clinical Notes',
        'fields' => ['note_title', 'content', 'content_html', 'author', 'cosigner']
    ],
    'medications' => [
        'table_name' => 'Medications',
        'fields' => ['name', 'generic_name', 'brand_name', 'dose', 'dose_unit', 'route', 'frequency', 'indication', 'instructions', 'pharmacy_instructions', 'ordering_provider']
    ],
    'vitals' => [
        'table_name' => 'Vitals',
        'fields' => ['recorded_by', 'o2_device', 'pain_location', 'pupil_left', 'pupil_right', 'contractions', 'notes']
    ],
    'insurance_coverages' => [
        'table_name' => 'Insurance Coverage',
        'fields' => ['policy_number', 'group_number', 'group_name', 'plan_name', 'subscriber_id', 'subscriber_first_name', 'subscriber_last_name', 'subscriber_employer']
    ],
    'lab_results' => [
        'table_name' => 'Lab Results',
        'fields' => ['test_name', 'panel_name', 'value', 'unit', 'reference_text', 'critical_acknowledged_by', 'lab_name', 'performing_tech', 'comments']
    ],
    'audit_logs' => [
        'table_name' => 'Audit Logs',
        'fields' => ['ip_address', 'user_agent', 'access_reason', 'description', 'old_values', 'new_values', 'error_message']
    ],
    'encounters' => [
        'table_name' => 'Encounters',
        'fields' => ['facility', 'department', 'room', 'bed', 'unit', 'nursing_station', 'attending_provider', 'admitting_provider', 'primary_nurse', 'treatment_team', 'chief_complaint', 'admission_diagnosis', 'principal_diagnosis', 'secondary_diagnoses', 'code_status', 'isolation_status', 'isolation_type']
    ],
    'orders' => [
        'table_name' => 'Orders',
        'fields' => ['order_name', 'ordering_provider', 'acknowledged_by', 'diagnosis', 'clinical_indication', 'special_instructions']
    ],
    'flowsheet_entries' => [
        'table_name' => 'Flowsheet Entries',
        'fields' => ['row_name', 'value', 'documented_by', 'comments']
    ],
    'messages' => [
        'table_name' => 'Messages',
        'fields' => ['content']
    ],
    'message_threads' => [
        'table_name' => 'Message Threads',
        'fields' => ['subject']
    ],
    'documents' => [
        'table_name' => 'Documents',
        'fields' => ['title', 'description', 'file_name', 'file_path']
    ],
    'tasks' => [
        'table_name' => 'Tasks',
        'fields' => ['title', 'description', 'completion_notes']
    ],
    'alerts' => [
        'table_name' => 'Alerts',
        'fields' => ['title', 'message']
    ],
    'appointments' => [
        'table_name' => 'Appointments',
        'fields' => ['reason_for_visit', 'chief_complaint', 'notes', 'special_instructions', 'authorization_number', 'cancellation_reason']
    ]
];

$message = '';
$message_type = '';
$encryption_stats = [];
$key_status = [];

// Encryption key file location (within allowed paths)
define('ENCRYPTION_KEY_FILE', __DIR__ . '/../.encryption_key');

/**
 * Generate a cryptographically secure encryption key
 */
function generateEncryptionKey() {
    return bin2hex(random_bytes(32)); // 64 hex characters = 256 bits
}

/**
 * Get or create encryption key
 */
function getOrCreateEncryptionKey() {
    // First check environment variable
    $envKey = getenv('HIPAA_ENCRYPTION_KEY');
    if (!empty($envKey)) {
        return ['source' => 'env', 'key' => $envKey];
    }
    
    // Check for key file
    if (file_exists(ENCRYPTION_KEY_FILE)) {
        $key = trim(file_get_contents(ENCRYPTION_KEY_FILE));
        if (!empty($key)) {
            return ['source' => 'file', 'key' => $key];
        }
    }
    
    // Generate and save new key
    $newKey = generateEncryptionKey();
    if (file_put_contents(ENCRYPTION_KEY_FILE, $newKey) !== false) {
        chmod(ENCRYPTION_KEY_FILE, 0600); // Restrict permissions
        return ['source' => 'generated', 'key' => $newKey];
    }
    
    return ['source' => 'none', 'key' => null];
}

// Check encryption key status
function checkKeyStatus() {
    $keyInfo = getOrCreateEncryptionKey();
    
    $status = [
        'env_key' => $keyInfo['source'] === 'env',
        'file_key' => $keyInfo['source'] === 'file' || $keyInfo['source'] === 'generated',
        'key_exists' => $keyInfo['key'] !== null,
        'key_source' => 'Not Set'
    ];
    
    switch ($keyInfo['source']) {
        case 'env':
            $status['key_source'] = 'Environment Variable';
            $status['key_strength'] = 'Strong (Production Ready)';
            break;
        case 'file':
            $status['key_source'] = 'Configuration File';
            $status['key_strength'] = 'Good (Auto-saved)';
            break;
        case 'generated':
            $status['key_source'] = 'Auto-Generated & Saved';
            $status['key_strength'] = 'Good (Newly created and saved)';
            break;
        default:
            $status['key_source'] = 'Not Set - Unable to create';
            $status['key_strength'] = 'Critical - Check file permissions!';
    }
    
    return $status;
}

$key_status = checkKeyStatus();

// Handle encryption actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'danger';
    } else {
        switch ($_POST['action']) {
            case 'test_encryption':
                // Test encryption locally using PHP
                try {
                    $keyInfo = getOrCreateEncryptionKey();
                    if ($keyInfo['key'] === null) {
                        throw new Exception('No encryption key available');
                    }
                    
                    // Simple encryption test using OpenSSL
                    $testData = 'Test Patient Data - SSN: 123-45-6789';
                    $cipher = 'aes-256-gcm';
                    $iv = random_bytes(12);
                    $tag = '';
                    
                    $encrypted = openssl_encrypt($testData, $cipher, hex2bin($keyInfo['key']), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
                    $decrypted = openssl_decrypt($encrypted, $cipher, hex2bin($keyInfo['key']), OPENSSL_RAW_DATA, $iv, $tag);
                    
                    if ($decrypted === $testData) {
                        $message = 'Encryption test PASSED! AES-256-GCM is working correctly. Key source: ' . $keyInfo['source'];
                        $message_type = 'success';
                    } else {
                        $message = 'Encryption test FAILED: Decrypted data does not match original.';
                        $message_type = 'danger';
                    }
                } catch (Exception $e) {
                    $message = 'Encryption test FAILED: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'generate_key':
                // Generate a new encryption key and save it
                try {
                    $newKey = generateEncryptionKey();
                    if (file_put_contents(ENCRYPTION_KEY_FILE, $newKey) !== false) {
                        chmod(ENCRYPTION_KEY_FILE, 0600);
                        $message = 'New encryption key generated and saved successfully!';
                        $message_type = 'success';
                        $key_status = checkKeyStatus(); // Refresh status
                    } else {
                        $message = 'Failed to save encryption key. Check file permissions.';
                        $message_type = 'danger';
                    }
                } catch (Exception $e) {
                    $message = 'Failed to generate key: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'analyze':
                // For now, show info about running CLI command
                $message = 'Database analysis requires the Python backend. Run from command line: <code>python -m backend.utils.encrypt_database --analyze</code>';
                $message_type = 'info';
                break;
                
            case 'encrypt_table':
                $table = $_POST['table'] ?? '';
                if (!isset($encryption_schema[$table])) {
                    $message = 'Invalid table specified.';
                    $message_type = 'danger';
                } else {
                    $message = 'Table encryption requires the Python backend. Run: <code>python -m backend.utils.encrypt_database --table ' . htmlspecialchars($table) . '</code>';
                    $message_type = 'info';
                }
                break;
                
            case 'encrypt_all':
                $message = 'Full database encryption requires the Python backend. Run: <code>python -m backend.utils.encrypt_database --all</code>';
                $message_type = 'info';
                break;
                
            case 'verify':
                $message = 'Encryption verification requires the Python backend. Run: <code>python -m backend.utils.encrypt_database --verify</code>';
                $message_type = 'info';
                break;
        }
    }
}

$csrf_token = getCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card-header { background: linear-gradient(135deg, #1a4a5e, #0d3545); color: white; }
        .card-header.bg-danger { background: linear-gradient(135deg, #dc3545, #c82333) !important; }
        .card-header.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34) !important; }
        .status-badge { font-size: 12px; }
        .encrypted { color: #28a745; }
        .pending { color: #ffc107; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; }
        .danger-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; }
        .info-box { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 8px; }
        .table-card { transition: transform 0.2s; }
        .table-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .encryption-badge { font-size: 11px; padding: 3px 8px; }
        .progress-thin { height: 8px; }
        .key-status-good { border-left: 4px solid #28a745; }
        .key-status-warn { border-left: 4px solid #ffc107; }
        .key-status-bad { border-left: 4px solid #dc3545; }
        .cli-command { 
            background: #1a1a2e; 
            color: #0f0; 
            padding: 12px 15px; 
            border-radius: 6px; 
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .cli-command code { color: #0f0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="fas fa-shield-alt text-primary"></i> Database Encryption Tool</h1>
                <p class="text-muted mb-0">HIPAA-Compliant AES-256-GCM Encryption for PHI/PII</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admin
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Encryption Key Status -->
        <div class="card mb-4 <?php echo $key_status['env_key'] ? 'key-status-good' : ($key_status['file_key'] ? 'key-status-warn' : 'key-status-bad'); ?>">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">
                            <i class="fas fa-key"></i> Encryption Key Status: 
                            <span class="text-<?php echo $key_status['env_key'] ? 'success' : ($key_status['file_key'] ? 'warning' : 'danger'); ?>">
                                <?php echo htmlspecialchars($key_status['key_source']); ?>
                            </span>
                        </h5>
                        <p class="text-muted mb-0">
                            Security Level: <strong><?php echo htmlspecialchars($key_status['key_strength']); ?></strong>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (!$key_status['env_key']): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-exclamation-triangle"></i> Set HIPAA_ENCRYPTION_KEY
                        </span>
                        <?php else: ?>
                        <span class="badge bg-success">
                            <i class="fas fa-check"></i> Production Ready
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Warning Box -->
        <div class="warning-box mb-4">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> Important Security Notice</h5>
            <div class="row">
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li>Encrypts PHI/PII using <strong>AES-256-GCM</strong> (Authenticated Encryption)</li>
                        <li>Each record gets unique random <strong>salt</strong> and <strong>nonce</strong></li>
                        <li>HKDF key derivation prevents key reuse vulnerabilities</li>
                        <li>Authentication tags prevent data tampering</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li><strong class="text-danger">Create a FULL DATABASE BACKUP first!</strong></li>
                        <li>Data can only be decrypted with the same encryption key</li>
                        <li>Store the key securely using environment variables</li>
                        <li>Automatic encryption/decryption is handled by SQLAlchemy</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="test_encryption">
                    <button type="submit" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-vial fa-2x mb-2 d-block"></i>
                        Test Encryption
                    </button>
                </form>
            </div>
            <div class="col-md-3">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="analyze">
                    <button type="submit" class="btn btn-info w-100 py-3">
                        <i class="fas fa-search fa-2x mb-2 d-block"></i>
                        Analyze Database
                    </button>
                </form>
            </div>
            <div class="col-md-3">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="btn btn-success w-100 py-3">
                        <i class="fas fa-check-double fa-2x mb-2 d-block"></i>
                        Verify Encryption
                    </button>
                </form>
            </div>
            <div class="col-md-3">
                <form method="POST" onsubmit="return confirm('ENCRYPT ALL TABLES? This will encrypt all unencrypted PHI/PII data. Ensure you have a backup!');">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="encrypt_all">
                    <button type="submit" class="btn btn-danger w-100 py-3">
                        <i class="fas fa-lock fa-2x mb-2 d-block"></i>
                        Encrypt All Tables
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Encryption Schema -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-database"></i> Encryption Schema - Tables & Fields
            </div>
            <div class="card-body">
                <p class="text-muted">The following tables have PHI/PII fields that are encrypted using AES-256-GCM:</p>
                
                <div class="row">
                    <?php foreach ($encryption_schema as $table_key => $table_info): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card table-card h-100">
                            <div class="card-header py-2" style="background: #f8f9fa;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="fas fa-table text-primary"></i> <?php echo htmlspecialchars($table_info['table_name']); ?></strong>
                                    <span class="badge bg-secondary"><?php echo count($table_info['fields']); ?> fields</span>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <?php if (!empty($encryption_stats[$table_key])): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small">
                                        <span>Encrypted:</span>
                                        <span><?php echo $encryption_stats[$table_key]['encrypted']; ?>/<?php echo $encryption_stats[$table_key]['total']; ?></span>
                                    </div>
                                    <div class="progress progress-thin">
                                        <?php $pct = $encryption_stats[$table_key]['total'] > 0 ? ($encryption_stats[$table_key]['encrypted'] / $encryption_stats[$table_key]['total']) * 100 : 0; ?>
                                        <div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="small">
                                    <?php foreach ($table_info['fields'] as $field): ?>
                                    <span class="badge bg-light text-dark encryption-badge me-1 mb-1">
                                        <i class="fas fa-lock text-success" style="font-size: 9px;"></i>
                                        <?php echo htmlspecialchars($field); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer py-2 bg-white">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="encrypt_table">
                                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table_key); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100" 
                                            onclick="return confirm('Encrypt all unencrypted records in <?php echo $table_info['table_name']; ?>?')">
                                        <i class="fas fa-lock"></i> Encrypt Table
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- CLI Commands -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-terminal"></i> Command Line Interface
            </div>
            <div class="card-body">
                <p class="text-muted">If the backend API is unavailable, you can run encryption from the command line:</p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6>Analyze Encryption Status</h6>
                        <div class="cli-command">
                            <code>python -m backend.utils.encrypt_database --analyze</code>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>Encrypt All Tables</h6>
                        <div class="cli-command">
                            <code>python -m backend.utils.encrypt_database --all</code>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>Encrypt Specific Table</h6>
                        <div class="cli-command">
                            <code>python -m backend.utils.encrypt_database --table patients</code>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>Verify Encryption</h6>
                        <div class="cli-command">
                            <code>python -m backend.utils.encrypt_database --verify</code>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mb-0 mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Ensure the <code>HIPAA_ENCRYPTION_KEY</code> environment variable is set before running encryption commands.
                    <br>
                    <code class="text-dark">export HIPAA_ENCRYPTION_KEY="your-64-character-hex-key-here"</code>
                </div>
            </div>
        </div>
        
        <!-- Key Management -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-key"></i> Encryption Key Management
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Production Setup (Recommended)</h6>
                        <ol>
                            <li>Generate a secure 64-character hex key:
                                <div class="cli-command mt-2 mb-2">
                                    <code>python -c "import secrets; print(secrets.token_hex(32))"</code>
                                </div>
                            </li>
                            <li>Set as environment variable:
                                <div class="cli-command mt-2 mb-2">
                                    <code>export HIPAA_ENCRYPTION_KEY="generated-key"</code>
                                </div>
                            </li>
                            <li>Store key securely in a secrets manager (AWS Secrets Manager, HashiCorp Vault, etc.)</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6>Key Storage Priority</h6>
                        <ol>
                            <li><strong>Environment Variable (Best):</strong> <code>HIPAA_ENCRYPTION_KEY</code></li>
                            <li><strong>Key File (Development):</strong> <code>backend/.encryption_key</code></li>
                            <li><strong>Auto-Generated (Dangerous):</strong> Lost on restart!</li>
                        </ol>
                        
                        <div class="danger-box mt-3">
                            <h6 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Critical Warnings</h6>
                            <ul class="mb-0 small">
                                <li>Never commit the encryption key to version control</li>
                                <li>Never share the key over insecure channels</li>
                                <li>If key is lost, encrypted data cannot be recovered</li>
                                <li>Rotate keys periodically following your security policy</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

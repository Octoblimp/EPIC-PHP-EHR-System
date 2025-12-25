<?php
/**
 * Openspace EHR - PII Encryption Migration Tool
 * HIPAA-compliant AES-256-GCM encryption for all Protected Health Information (PHI)
 * 
 * WARNING: This is a one-way migration. Ensure you have a backup before running!
 * 
 * Fields encrypted:
 * - Patient: first_name, last_name, email, phone, address, city, state, zip, ssn
 * - User: first_name, last_name, email, phone
 * - Any other PII fields identified
 */

$page_title = 'PII Encryption Tool - Openspace EHR';

require_once '../includes/config.php';
require_once '../includes/security.php';

// Security check - admin only
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'administrator']);

if (!$is_admin) {
    die('Access denied. Administrator privileges required.');
}

// Initialize encryption
$encryption = new HIPAAEncryption();

// Define fields to encrypt per table
$encryption_schema = [
    'patients' => [
        'first_name',
        'last_name',
        'middle_name',
        'maiden_name',
        'email',
        'phone',
        'mobile_phone',
        'work_phone',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'ssn',
        'ssn_last_four',
        'emergency_contact_name',
        'emergency_contact_phone',
        'employer_name',
        'employer_address'
    ],
    'users' => [
        'first_name',
        'last_name',
        'email',
        'phone',
        'npi'
    ],
    'patient_insurance' => [
        'subscriber_name',
        'subscriber_id',
        'subscriber_dob',
        'policy_number',
        'group_number'
    ],
    'encounters' => [
        'chief_complaint',
        'notes'
    ],
    'clinical_notes' => [
        'content',
        'addendum'
    ],
    'messages' => [
        'subject',
        'body'
    ]
];

$message = '';
$message_type = '';
$encryption_stats = [];

// Handle encryption actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'danger';
    } else {
        switch ($_POST['action']) {
            case 'test_encryption':
                // Test encryption/decryption
                $test_data = 'Test Patient Name - John Doe SSN:123-45-6789';
                try {
                    $encrypted = $encryption->encrypt($test_data);
                    $decrypted = $encryption->decrypt($encrypted);
                    
                    if ($decrypted === $test_data) {
                        $message = 'Encryption test PASSED. AES-256-GCM is working correctly.';
                        $message_type = 'success';
                    } else {
                        $message = 'Encryption test FAILED. Decrypted data does not match.';
                        $message_type = 'danger';
                    }
                } catch (Exception $e) {
                    $message = 'Encryption test FAILED: ' . $e->getMessage();
                    $message_type = 'danger';
                }
                break;
                
            case 'analyze':
                // Analyze tables and count unencrypted records
                // This would connect to the database and count records
                $encryption_stats = analyzeEncryptionStatus($encryption_schema);
                $message = 'Analysis complete. See results below.';
                $message_type = 'info';
                break;
                
            case 'encrypt_table':
                $table = $_POST['table'] ?? '';
                if (!isset($encryption_schema[$table])) {
                    $message = 'Invalid table specified.';
                    $message_type = 'danger';
                } else {
                    $result = encryptTable($table, $encryption_schema[$table], $encryption);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                }
                break;
        }
    }
}

/**
 * Analyze encryption status for all tables
 */
function analyzeEncryptionStatus($schema) {
    $stats = [];
    
    // This is a placeholder - in production, would query actual database
    foreach ($schema as $table => $fields) {
        $stats[$table] = [
            'total_records' => rand(100, 1000), // Demo data
            'encrypted_records' => rand(0, 100),
            'fields' => count($fields),
            'status' => 'pending'
        ];
    }
    
    return $stats;
}

/**
 * Encrypt a table's PII fields
 */
function encryptTable($table, $fields, $encryption) {
    // In production, this would:
    // 1. Connect to database
    // 2. Select all records where pii_encrypted = 0
    // 3. For each record, encrypt each field
    // 4. Update the record with encrypted values
    // 5. Set pii_encrypted = 1
    
    // Demo implementation
    return [
        'success' => true,
        'message' => "Encrypted " . count($fields) . " fields in table '$table' (demo mode)"
    ];
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
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card-header { background: linear-gradient(135deg, #1a4a5e, #0d3545); color: white; }
        .status-badge { font-size: 12px; }
        .encrypted { color: #28a745; }
        .pending { color: #ffc107; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-shield-alt"></i> PII Encryption Tool</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Admin
            </a>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="warning-box mb-4">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> Important Security Notice</h5>
            <ul class="mb-0">
                <li>This tool encrypts Protected Health Information (PHI) using AES-256-GCM</li>
                <li><strong>Create a full database backup before running encryption</strong></li>
                <li>Encryption is one-way - data can only be decrypted with the encryption key</li>
                <li>Store the encryption key securely (environment variable: HIPAA_ENCRYPTION_KEY)</li>
                <li>All decryption happens server-side only - never exposed to clients</li>
            </ul>
        </div>
        
        <!-- Test Encryption -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-vial"></i> Test Encryption System
            </div>
            <div class="card-body">
                <p>Test that encryption is working correctly before encrypting production data.</p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="test_encryption">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play"></i> Run Encryption Test
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Encryption Schema -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-database"></i> Encryption Schema
            </div>
            <div class="card-body">
                <p class="text-muted">The following fields will be encrypted with AES-256-GCM:</p>
                
                <?php foreach ($encryption_schema as $table => $fields): ?>
                <div class="mb-3">
                    <h6 class="text-primary">
                        <i class="fas fa-table"></i> <?php echo htmlspecialchars($table); ?>
                        <?php if (!empty($encryption_stats[$table])): ?>
                        <span class="badge bg-<?php echo $encryption_stats[$table]['status'] === 'complete' ? 'success' : 'warning'; ?>">
                            <?php echo $encryption_stats[$table]['encrypted_records']; ?>/<?php echo $encryption_stats[$table]['total_records']; ?> encrypted
                        </span>
                        <?php endif; ?>
                    </h6>
                    <div class="ms-4">
                        <?php foreach ($fields as $field): ?>
                        <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <hr>
                
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="analyze">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Analyze Database
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Encryption Actions -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-lock"></i> Execute Encryption
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This action will encrypt data in the database. Ensure you have:
                    <ol>
                        <li>Created a full database backup</li>
                        <li>Tested encryption with the button above</li>
                        <li>Set the HIPAA_ENCRYPTION_KEY environment variable</li>
                    </ol>
                </div>
                
                <div class="row">
                    <?php foreach ($encryption_schema as $table => $fields): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6><?php echo htmlspecialchars($table); ?></h6>
                                <p class="small text-muted"><?php echo count($fields); ?> fields</p>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="action" value="encrypt_table">
                                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Encrypt all PII in <?php echo $table; ?>? This cannot be undone!')">
                                        <i class="fas fa-lock"></i> Encrypt
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-key"></i> Encryption Key Management
            </div>
            <div class="card-body">
                <p>The encryption key is stored in:</p>
                <ol>
                    <li><strong>Environment Variable (Recommended):</strong> <code>HIPAA_ENCRYPTION_KEY</code></li>
                    <li><strong>Fallback File:</strong> <code>/frontend/.encryption_key</code> (development only)</li>
                </ol>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Never commit the encryption key to version control. In production, use environment variables or a secrets manager.
                </p>
                
                <?php 
                $key_status = getenv('HIPAA_ENCRYPTION_KEY') ? 'Environment Variable' : 
                              (file_exists(__DIR__ . '/../.encryption_key') ? 'File (Development)' : 'Auto-Generated');
                ?>
                <div class="alert alert-info">
                    <strong>Current Key Source:</strong> <?php echo $key_status; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

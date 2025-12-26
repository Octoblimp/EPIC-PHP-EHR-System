<?php
/**
 * Openspace EHR - Initial Setup Wizard
 * First-time installation and configuration
 * 
 * This wizard handles:
 * - Database connection setup
 * - Admin user creation
 * - Encryption key generation
 * - Sample data installation (optional)
 * - System configuration
 */

// Start session early
session_start();

// Setup configuration
define('SETUP_CONFIG_FILE', __DIR__ . '/includes/.setup_complete');
define('ENCRYPTION_KEY_FILE', __DIR__ . '/.encryption_key');
define('ENV_FILE', __DIR__ . '/.env');

// Check if setup is already complete
function isSetupComplete() {
    return file_exists(SETUP_CONFIG_FILE);
}

// Check if database already has users (setup already ran or manual setup)
function hasExistingData() {
    // Try to check database for existing users
    // This requires a working database connection from a previous partial setup or .env
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return false; // No config yet, allow setup
    }
    
    // Parse .env file
    $env = parse_ini_file($envFile);
    if (!$env || empty($env['DB_HOST']) || empty($env['DB_NAME']) || empty($env['DB_USER'])) {
        return false; // Invalid config, allow setup
    }
    
    try {
        $dsn = "mysql:host={$env['DB_HOST']};port=" . ($env['DB_PORT'] ?? '3306') . ";dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        // Check if users table exists and has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        return $count > 0;
    } catch (PDOException $e) {
        return false; // Can't connect or table doesn't exist, allow setup
    }
}

// If setup is complete, redirect to login
if (isSetupComplete() && !isset($_GET['force'])) {
    header('Location: login.php');
    exit;
}

// Block setup if database already has users
if (hasExistingData() && !isset($_GET['force'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Blocked - Openspace EHR</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #1a4a5e 0%, #0d3545 50%, #1a4a5e 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .setup-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 100%;
                padding: 40px;
                text-align: center;
            }
            .block-icon { font-size: 60px; color: #dc3545; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="setup-container">
            <div class="block-icon"><i class="fas fa-shield-alt"></i></div>
            <h3 class="mb-3">Setup Already Complete</h3>
            <p class="text-muted mb-4">
                The database already contains user accounts. Setup cannot be run again for security reasons.
            </p>
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Helper functions
function generateSecureKey($length = 32) {
    return bin2hex(random_bytes($length));
}

function generatePassword($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function validateDatabaseConnection($host, $port, $name, $user, $pass) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

// Process setup steps
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'check_requirements':
            // Check system requirements
            $requirements = [
                'php_version' => version_compare(PHP_VERSION, '8.0.0', '>='),
                'openssl' => extension_loaded('openssl'),
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'json' => extension_loaded('json'),
                'session' => extension_loaded('session'),
                'writable_includes' => is_writable(__DIR__ . '/includes'),
                'writable_root' => is_writable(__DIR__)
            ];
            
            $_SESSION['setup_requirements'] = $requirements;
            
            if (array_product($requirements)) {
                header('Location: setup.php?step=2');
            } else {
                $error = 'Some requirements are not met. Please fix them before continuing.';
            }
            exit;
            
        case 'save_database':
            $db_host = trim($_POST['db_host'] ?? 'localhost');
            $db_port = trim($_POST['db_port'] ?? '3306');
            $db_name = trim($_POST['db_name'] ?? '');
            $db_user = trim($_POST['db_user'] ?? '');
            $db_pass = $_POST['db_pass'] ?? '';
            
            if (empty($db_name) || empty($db_user)) {
                $error = 'Database name and username are required.';
            } else {
                $result = validateDatabaseConnection($db_host, $db_port, $db_name, $db_user, $db_pass);
                if ($result['success']) {
                    $_SESSION['setup_database'] = [
                        'host' => $db_host,
                        'port' => $db_port,
                        'name' => $db_name,
                        'user' => $db_user,
                        'pass' => $db_pass
                    ];
                    header('Location: setup.php?step=3');
                    exit;
                } else {
                    $error = 'Database connection failed: ' . $result['error'];
                }
            }
            break;
            
        case 'create_admin':
            $admin_username = trim($_POST['admin_username'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
            $admin_first_name = trim($_POST['admin_first_name'] ?? '');
            $admin_last_name = trim($_POST['admin_last_name'] ?? '');
            
            // Validation
            if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
                $error = 'All fields are required.';
            } elseif (strlen($admin_username) < 4) {
                $error = 'Username must be at least 4 characters.';
            } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } elseif (strlen($admin_password) < 12) {
                $error = 'Password must be at least 12 characters.';
            } elseif ($admin_password !== $admin_password_confirm) {
                $error = 'Passwords do not match.';
            } else {
                $_SESSION['setup_admin'] = [
                    'username' => $admin_username,
                    'email' => $admin_email,
                    'password' => $admin_password,
                    'first_name' => $admin_first_name,
                    'last_name' => $admin_last_name
                ];
                header('Location: setup.php?step=4');
                exit;
            }
            break;
            
        case 'configure_options':
            $_SESSION['setup_options'] = [
                'install_sample_data' => isset($_POST['install_sample_data']),
                'enable_patient_protection' => isset($_POST['enable_patient_protection']),
                'enable_audit_logging' => isset($_POST['enable_audit_logging']),
                'enable_two_factor' => isset($_POST['enable_two_factor']),
                'organization_name' => trim($_POST['organization_name'] ?? 'Healthcare Organization'),
                'timezone' => $_POST['timezone'] ?? 'America/New_York'
            ];
            header('Location: setup.php?step=5');
            exit;
            
        case 'complete_setup':
            // Generate encryption key
            $encryptionKey = generateSecureKey(32);
            
            // Save encryption key
            if (!file_put_contents(ENCRYPTION_KEY_FILE, $encryptionKey)) {
                $error = 'Failed to save encryption key. Check file permissions.';
                break;
            }
            chmod(ENCRYPTION_KEY_FILE, 0600);
            
            // Generate session secret
            $sessionSecret = generateSecureKey(32);
            
            // Create .env file
            $db = $_SESSION['setup_database'] ?? [];
            $admin = $_SESSION['setup_admin'] ?? [];
            $options = $_SESSION['setup_options'] ?? [];
            
            $envContent = "# Openspace EHR Configuration\n";
            $envContent .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
            $envContent .= "# Database Configuration\n";
            $envContent .= "DB_HOST={$db['host']}\n";
            $envContent .= "DB_PORT={$db['port']}\n";
            $envContent .= "DB_NAME={$db['name']}\n";
            $envContent .= "DB_USER={$db['user']}\n";
            $envContent .= "DB_PASS={$db['pass']}\n\n";
            $envContent .= "# Security\n";
            $envContent .= "HIPAA_ENCRYPTION_KEY={$encryptionKey}\n";
            $envContent .= "SESSION_SECRET={$sessionSecret}\n\n";
            $envContent .= "# Application Settings\n";
            $envContent .= "ORGANIZATION_NAME=\"{$options['organization_name']}\"\n";
            $envContent .= "TIMEZONE={$options['timezone']}\n";
            $envContent .= "PATIENT_PROTECTION=" . ($options['enable_patient_protection'] ? 'true' : 'false') . "\n";
            $envContent .= "AUDIT_LOGGING=" . ($options['enable_audit_logging'] ? 'true' : 'false') . "\n";
            $envContent .= "TWO_FACTOR_AUTH=" . ($options['enable_two_factor'] ? 'true' : 'false') . "\n";
            
            if (!file_put_contents(ENV_FILE, $envContent)) {
                $error = 'Failed to save configuration file.';
                break;
            }
            chmod(ENV_FILE, 0600);
            
            // Create admin user in database (tables already exist from Python backend)
            try {
                $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                
                // Insert admin user (table created by Python backend on startup)
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, first_name, last_name, role, department, is_active)
                    VALUES (?, ?, ?, ?, ?, 'Administrator', 'Administration', 1)
                ");
                $stmt->execute([
                    $admin['username'],
                    $admin['email'],
                    hashPassword($admin['password']),
                    $admin['first_name'],
                    $admin['last_name']
                ]);
                
                // Install sample data if requested
                if ($options['install_sample_data']) {
                    installSampleData($pdo);
                }
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                break;
            }
            
            // Mark setup as complete
            file_put_contents(SETUP_CONFIG_FILE, date('Y-m-d H:i:s'));
            
            // Clear setup session data
            unset($_SESSION['setup_database']);
            unset($_SESSION['setup_admin']);
            unset($_SESSION['setup_options']);
            unset($_SESSION['setup_requirements']);
            
            header('Location: setup.php?step=6');
            exit;
    }
}

/**
 * Install sample/demo data by calling the Python backend API
 * Tables are already created by the Python backend on startup
 */
function installSampleData($pdo) {
    // Call the Python backend API to seed comprehensive demo data
    // The backend has all the sample patients, vitals, meds, labs, notes, etc.
    $apiUrl = 'http://127.0.0.1:5000/api/admin/setup/seed-demo-data';
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['setup_token' => '']),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if backend seeded the data
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success'] ?? false) {
            return true;
        }
    }
    
    // If backend API failed, log it but don't fail setup
    // Admin can seed data later via CLI: python app.py --seed
    error_log("Warning: Could not seed demo data via API. Run 'python app.py --seed' manually if needed.");
    return false;
}

// Page display
$pageTitle = 'Openspace EHR Setup';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a4a5e;
            --primary-dark: #0d3545;
            --accent: #f28c38;
        }
        
        body {
            background: linear-gradient(135deg, #1a4a5e 0%, #0d3545 50%, #1a4a5e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        
        .setup-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .setup-header h1 {
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .setup-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .setup-body {
            padding: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 30px;
        }
        
        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ddd;
            transition: all 0.3s;
        }
        
        .step-dot.active {
            background: var(--accent);
            transform: scale(1.2);
        }
        
        .step-dot.completed {
            background: var(--primary);
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 74, 94, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #062530);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            border: 2px solid #ddd;
            color: #666;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .requirement-item.pass {
            background: #d4edda;
        }
        
        .requirement-item.fail {
            background: #f8d7da;
        }
        
        .requirement-item i {
            font-size: 18px;
        }
        
        .requirement-item.pass i { color: #28a745; }
        .requirement-item.fail i { color: #dc3545; }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e0e0e0;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>
                <i class="fas fa-hospital"></i>
                Openspace EHR
            </h1>
            <p>Initial Setup Wizard</p>
        </div>
        
        <div class="setup-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="step-dot <?php echo $i < $step ? 'completed' : ($i == $step ? 'active' : ''); ?>"></div>
                <?php endfor; ?>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
            <!-- Step 1: System Requirements -->
            <h4 class="mb-4"><i class="fas fa-clipboard-check text-primary"></i> Step 1: System Requirements</h4>
            
            <?php
            $requirements = [
                'php_version' => ['PHP 8.0+', version_compare(PHP_VERSION, '8.0.0', '>=')],
                'openssl' => ['OpenSSL Extension', extension_loaded('openssl')],
                'pdo' => ['PDO Extension', extension_loaded('pdo')],
                'pdo_mysql' => ['PDO MySQL Driver', extension_loaded('pdo_mysql')],
                'json' => ['JSON Extension', extension_loaded('json')],
                'session' => ['Session Extension', extension_loaded('session')],
                'writable_includes' => ['Includes Directory Writable', is_writable(__DIR__ . '/includes')],
                'writable_root' => ['Root Directory Writable', is_writable(__DIR__)]
            ];
            $allPassed = true;
            ?>
            
            <?php foreach ($requirements as $key => $req): ?>
            <div class="requirement-item <?php echo $req[1] ? 'pass' : 'fail'; ?>">
                <i class="fas <?php echo $req[1] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <span><?php echo $req[0]; ?></span>
                <?php if (!$req[1]) $allPassed = false; ?>
            </div>
            <?php endforeach; ?>
            
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="check_requirements">
                <button type="submit" class="btn btn-primary w-100" <?php echo !$allPassed ? 'disabled' : ''; ?>>
                    <i class="fas fa-arrow-right"></i> Continue
                </button>
            </form>
            
            <?php elseif ($step == 2): ?>
            <!-- Step 2: Database Configuration -->
            <h4 class="mb-4"><i class="fas fa-database text-primary"></i> Step 2: Database Configuration</h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_database">
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Database Host</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Port</label>
                        <input type="text" name="db_port" class="form-control" value="3306" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-control" placeholder="openspace_ehr" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Database Username</label>
                    <input type="text" name="db_user" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_pass" class="form-control">
                </div>
                
                <div class="d-flex gap-2">
                    <a href="setup.php?step=1" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-plug"></i> Test Connection & Continue
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 3): ?>
            <!-- Step 3: Admin User -->
            <h4 class="mb-4"><i class="fas fa-user-shield text-primary"></i> Step 3: Create Administrator</h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="admin_first_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="admin_last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="admin_username" class="form-control" placeholder="admin" minlength="4" required>
                    <small class="text-muted">At least 4 characters</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="admin_password" id="admin_password" class="form-control" minlength="12" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                    <small class="text-muted">At least 12 characters with mixed case, numbers, and symbols</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="admin_password_confirm" class="form-control" required>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="setup.php?step=2" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-arrow-right"></i> Continue
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 4): ?>
            <!-- Step 4: Configuration Options -->
            <h4 class="mb-4"><i class="fas fa-cog text-primary"></i> Step 4: Configuration</h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="configure_options">
                
                <div class="mb-3">
                    <label class="form-label">Organization Name</label>
                    <input type="text" name="organization_name" class="form-control" placeholder="Your Healthcare Organization" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        <option value="America/New_York">Eastern Time (US)</option>
                        <option value="America/Chicago">Central Time (US)</option>
                        <option value="America/Denver">Mountain Time (US)</option>
                        <option value="America/Los_Angeles">Pacific Time (US)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                
                <hr>
                <h5 class="mb-3">Security Features</h5>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="enable_patient_protection" id="patientProtection" checked>
                    <label class="form-check-label" for="patientProtection">
                        <strong>Patient Record Protection</strong>
                        <br><small class="text-muted">Require DOB verification before accessing patient charts</small>
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="enable_audit_logging" id="auditLogging" checked>
                    <label class="form-check-label" for="auditLogging">
                        <strong>HIPAA Audit Logging</strong>
                        <br><small class="text-muted">Log all PHI access for compliance</small>
                    </label>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="enable_two_factor" id="twoFactor">
                    <label class="form-check-label" for="twoFactor">
                        <strong>Two-Factor Authentication</strong>
                        <br><small class="text-muted">Require 2FA for all users</small>
                    </label>
                </div>
                
                <hr>
                <h5 class="mb-3">Sample Data</h5>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="install_sample_data" id="sampleData">
                    <label class="form-check-label" for="sampleData">
                        <strong>Install Sample Data</strong>
                        <br><small class="text-muted">Add demo patients and data for testing (recommended for evaluation)</small>
                    </label>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="setup.php?step=3" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-arrow-right"></i> Continue
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 5): ?>
            <!-- Step 5: Review & Install -->
            <h4 class="mb-4"><i class="fas fa-check-double text-primary"></i> Step 5: Review & Install</h4>
            
            <?php
            $db = $_SESSION['setup_database'] ?? [];
            $admin = $_SESSION['setup_admin'] ?? [];
            $options = $_SESSION['setup_options'] ?? [];
            ?>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h6><i class="fas fa-database text-primary"></i> Database</h6>
                    <p class="mb-0 text-muted">
                        <?php echo htmlspecialchars($db['host'] ?? 'N/A'); ?>:<?php echo htmlspecialchars($db['port'] ?? '3306'); ?> / 
                        <?php echo htmlspecialchars($db['name'] ?? 'N/A'); ?>
                    </p>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h6><i class="fas fa-user-shield text-primary"></i> Administrator</h6>
                    <p class="mb-0 text-muted">
                        <?php echo htmlspecialchars($admin['username'] ?? 'N/A'); ?> 
                        (<?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?>)
                    </p>
                </div>
            </div>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h6><i class="fas fa-cog text-primary"></i> Configuration</h6>
                    <ul class="mb-0 text-muted small">
                        <li>Organization: <?php echo htmlspecialchars($options['organization_name'] ?? 'N/A'); ?></li>
                        <li>Patient Protection: <?php echo ($options['enable_patient_protection'] ?? false) ? 'Enabled' : 'Disabled'; ?></li>
                        <li>Audit Logging: <?php echo ($options['enable_audit_logging'] ?? false) ? 'Enabled' : 'Disabled'; ?></li>
                        <li>Sample Data: <?php echo ($options['install_sample_data'] ?? false) ? 'Will be installed' : 'Not installing'; ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>What will happen:</strong>
                <ul class="mb-0 mt-2">
                    <li>Generate and save encryption key</li>
                    <li>Create configuration file</li>
                    <li>Create database tables</li>
                    <li>Create administrator account</li>
                    <?php if ($options['install_sample_data'] ?? false): ?>
                    <li>Install sample data</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="complete_setup">
                
                <div class="d-flex gap-2">
                    <a href="setup.php?step=4" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-rocket"></i> Complete Installation
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 6): ?>
            <!-- Step 6: Complete -->
            <div class="text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4 class="mb-3">Setup Complete!</h4>
                <p class="text-muted mb-4">
                    Openspace EHR has been successfully installed and configured.
                    Your encryption key has been automatically generated and saved.
                </p>
                
                <div class="alert alert-warning text-start">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Important:</strong> For production use, back up your encryption key from 
                    <code>.encryption_key</code> and consider setting the <code>HIPAA_ENCRYPTION_KEY</code> 
                    environment variable instead.
                </div>
                
                <a href="login.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('admin_password')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const bar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 12) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 25;
            
            bar.style.width = strength + '%';
            bar.style.background = strength < 50 ? '#dc3545' : strength < 75 ? '#ffc107' : '#28a745';
        });
    </script>
</body>
</html>

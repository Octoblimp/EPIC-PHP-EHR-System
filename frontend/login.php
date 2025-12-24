<?php
/**
 * Login Page
 * HIPAA-compliant authentication with MFA support
 */

// Redirect if already logged in
session_start();
if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
    header('Location: /index.php');
    exit;
}

// Check for session timeout message
$timeout_message = isset($_GET['timeout']) ? 'Your session has expired due to inactivity.' : '';
$logout_message = isset($_GET['logout']) ? 'You have been successfully logged out.' : '';
$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $mfa_code = $_POST['mfa_code'] ?? '';
    
    // Call Python backend DIRECTLY on localhost (bypasses Cloudflare)
    $api_url = 'http://127.0.0.1:5000/api/auth/login';
    
    // Debug info array
    $debug_info = [];
    $debug_info['api_url'] = $api_url;
    $debug_info['request_time'] = date('Y-m-d H:i:s');
    
    // Call backend authentication API
    $ch = curl_init();
    
    $debug_info['full_url'] = $api_url;
    
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $username,
        'password' => $password,
        'mfa_code' => $mfa_code
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    // Collect debug info
    $debug_info['http_code'] = $http_code;
    $debug_info['effective_url'] = $effective_url;
    $debug_info['curl_error'] = $curl_error;
    $debug_info['curl_errno'] = $curl_errno;
    $debug_info['response_length'] = strlen($response);
    $debug_info['response_preview'] = substr($response, 0, 500);
    
    $result = json_decode($response, true);
    $debug_info['json_decode_error'] = json_last_error_msg();
    
    if ($http_code === 200 && isset($result['success']) && $result['success']) {
        // Check if MFA is required
        if (isset($result['mfa_required']) && $result['mfa_required']) {
            $_SESSION['pending_mfa'] = true;
            $_SESSION['pending_username'] = $username;
            header('Location: /login.php?mfa=required');
            exit;
        }
        
        // Successful login
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['role'] = $result['user']['role'];
        $_SESSION['permissions'] = $result['user']['permissions'] ?? [];
        $_SESSION['session_token'] = $result['session_token'];
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Redirect to intended page or home
        $redirect = $_SESSION['intended_url'] ?? '/index.php';
        unset($_SESSION['intended_url']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $error_message = $result['error'] ?? 'Invalid credentials. Please try again.';
        // Store debug info for display
        $_SESSION['login_debug'] = $debug_info;
    }
}

// Get debug info if available
$debug_info = $_SESSION['login_debug'] ?? null;
unset($_SESSION['login_debug']);

$show_mfa = isset($_GET['mfa']) && $_GET['mfa'] === 'required';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EPIC EHR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #004499;
            --accent-color: #00aa55;
            --warning-color: #ff9900;
            --danger-color: #cc0000;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .login-header .logo i {
            font-size: 40px;
            color: var(--primary-color);
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating label {
            color: #666;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            height: auto;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 102, 204, 0.3);
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #fff5f5;
            color: var(--danger-color);
        }
        
        .alert-warning {
            background: #fff9e6;
            color: #996600;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #006633;
        }
        
        .security-notice {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        
        .security-notice i {
            color: var(--accent-color);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .mfa-section {
            display: none;
        }
        
        .mfa-section.active {
            display: block;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
        }
        
        .input-with-icon .form-control {
            border-left: none;
        }
        
        .password-toggle {
            cursor: pointer;
            border-left: none;
            background: white;
        }
        
        .hipaa-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        
        .hipaa-badge i {
            color: var(--accent-color);
            margin-right: 5px;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 10px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <h1>EPIC EHR System</h1>
                <p>Electronic Health Records</p>
            </div>
            
            <div class="login-body">
                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($debug_info): ?>
                <div class="alert alert-secondary" style="font-size: 12px; text-align: left;">
                    <strong><i class="bi bi-bug me-2"></i>Debug Info:</strong>
                    <hr style="margin: 8px 0;">
                    <div><strong>API URL:</strong> <?= htmlspecialchars($debug_info['api_url'] ?? 'N/A') ?></div>
                    <div><strong>Full URL:</strong> <?= htmlspecialchars($debug_info['full_url'] ?? 'N/A') ?></div>
                    <div><strong>Effective URL:</strong> <?= htmlspecialchars($debug_info['effective_url'] ?? 'N/A') ?></div>
                    <div><strong>HTTP Code:</strong> <?= htmlspecialchars($debug_info['http_code'] ?? 'N/A') ?></div>
                    <div><strong>cURL Error:</strong> <?= htmlspecialchars($debug_info['curl_error'] ?: 'None') ?></div>
                    <div><strong>cURL Errno:</strong> <?= htmlspecialchars($debug_info['curl_errno'] ?? 'N/A') ?></div>
                    <div><strong>Response Length:</strong> <?= htmlspecialchars($debug_info['response_length'] ?? 'N/A') ?> bytes</div>
                    <div><strong>JSON Decode:</strong> <?= htmlspecialchars($debug_info['json_decode_error'] ?? 'N/A') ?></div>
                    <details style="margin-top: 8px;">
                        <summary>Response Preview</summary>
                        <pre style="white-space: pre-wrap; word-break: break-all; font-size: 11px; max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 8px; margin-top: 5px;"><?= htmlspecialchars($debug_info['response_preview'] ?? 'Empty') ?></pre>
                    </details>
                </div>
                <?php endif; ?>
                
                <?php if ($timeout_message): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-clock-history me-2"></i>
                    <?= htmlspecialchars($timeout_message) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($logout_message): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($logout_message) ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div id="credentialsSection" class="<?= $show_mfa ? 'd-none' : '' ?>">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username" required autocomplete="username"
                                   value="<?= htmlspecialchars($_SESSION['pending_username'] ?? '') ?>">
                            <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                        </div>
                        
                        <div class="form-floating">
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required autocomplete="current-password">
                                <span class="input-group-text password-toggle" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div id="mfaSection" class="mfa-section <?= $show_mfa ? 'active' : '' ?>">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-shield-lock me-2"></i>
                            Enter the verification code from your authenticator app
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control text-center" id="mfa_code" name="mfa_code" 
                                   placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                                   style="font-size: 24px; letter-spacing: 8px;">
                            <label for="mfa_code"><i class="bi bi-key me-2"></i>Verification Code</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        <?= $show_mfa ? 'Verify' : 'Sign In' ?>
                    </button>
                </form>
                
                <div class="forgot-password">
                    <a href="/forgot-password.php"><i class="bi bi-question-circle me-1"></i>Forgot password?</a>
                </div>
                
                <div class="security-notice">
                    <i class="bi bi-shield-check me-2"></i>
                    <strong>Security Notice:</strong> This system contains protected health information (PHI). 
                    Unauthorized access is prohibited and may result in civil and criminal penalties.
                    All access is logged and monitored.
                </div>
                
                <div class="hipaa-badge">
                    <i class="bi bi-patch-check-fill"></i>
                    HIPAA Compliant System
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Auto-focus MFA input if visible
        <?php if ($show_mfa): ?>
        document.getElementById('mfa_code').focus();
        <?php else: ?>
        document.getElementById('username').focus();
        <?php endif; ?>
        
        // Session timeout warning (15 minutes HIPAA requirement)
        let lastActivity = Date.now();
        const SESSION_TIMEOUT = 15 * 60 * 1000; // 15 minutes
        
        document.addEventListener('mousemove', () => lastActivity = Date.now());
        document.addEventListener('keypress', () => lastActivity = Date.now());
        
        // Auto-format MFA code input
        document.getElementById('mfa_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>

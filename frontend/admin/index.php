<?php
/**
 * Openspace EHR - Admin Panel
 */
$page_title = 'Admin Panel - Openspace EHR';

require_once '../includes/config.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'administrator']);

if (!$is_admin) {
    header('Location: ../home.php');
    exit;
}

$success_message = '';
$error_message = '';

// Get system settings from session/defaults
$system_settings = $_SESSION['system_settings'] ?? [
    'skin' => 'hyperspace-teal',
    'logo_text' => 'Openspace',
    'show_logo_icon' => true,
    'header_color' => '#1a4a5e',
    'accent_color' => '#f28c38',
    'enable_dark_mode' => true,
    'enable_patient_photos' => true,
    'enable_two_factor' => false,
    'session_timeout' => 30,
    'password_expiry' => 90,
    'max_login_attempts' => 5,
    'enable_audit_log' => true,
    'enable_hipaa_logging' => true
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_appearance':
            $system_settings['skin'] = $_POST['skin'] ?? 'hyperspace-teal';
            $system_settings['logo_text'] = $_POST['logo_text'] ?? 'Openspace';
            $system_settings['show_logo_icon'] = isset($_POST['show_logo_icon']);
            $system_settings['header_color'] = $_POST['header_color'] ?? '#1a4a5e';
            $system_settings['accent_color'] = $_POST['accent_color'] ?? '#f28c38';
            $success_message = 'Appearance settings saved successfully.';
            break;
        case 'save_features':
            $system_settings['enable_dark_mode'] = isset($_POST['enable_dark_mode']);
            $system_settings['enable_patient_photos'] = isset($_POST['enable_patient_photos']);
            $system_settings['enable_two_factor'] = isset($_POST['enable_two_factor']);
            $success_message = 'Feature settings saved successfully.';
            break;
        case 'save_security':
            $system_settings['session_timeout'] = intval($_POST['session_timeout'] ?? 30);
            $system_settings['password_expiry'] = intval($_POST['password_expiry'] ?? 90);
            $system_settings['max_login_attempts'] = intval($_POST['max_login_attempts'] ?? 5);
            $system_settings['enable_audit_log'] = isset($_POST['enable_audit_log']);
            $system_settings['enable_hipaa_logging'] = isset($_POST['enable_hipaa_logging']);
            $success_message = 'Security settings saved successfully.';
            break;
    }
    $_SESSION['system_settings'] = $system_settings;
}

// Demo stats
$stats = [
    'total_users' => 24,
    'active_sessions' => 8,
    'patients_today' => 47,
    'orders_today' => 156
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/openspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(to right, #1a4a5e, #0d3545);
            color: white;
            padding: 0 20px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .admin-logo i {
            font-size: 24px;
        }
        
        .admin-nav {
            display: flex;
            gap: 5px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-nav a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-user span {
            font-size: 13px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            padding: 8px 15px;
            border-radius: 4px;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-container {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: calc(100vh - 55px);
        }
        
        .admin-sidebar {
            background: white;
            border-right: 1px solid #e0e0e0;
            padding: 20px 0;
        }
        
        .sidebar-section {
            margin-bottom: 20px;
        }
        
        .sidebar-title {
            padding: 10px 20px;
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            font-weight: 600;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: #f5f8fa;
        }
        
        .sidebar-menu a.active {
            background: #f0f8ff;
            border-left-color: #1a4a5e;
            color: #1a4a5e;
            font-weight: 500;
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }
        
        .admin-content {
            padding: 25px;
        }
        
        .admin-page-header {
            margin-bottom: 25px;
        }
        
        .admin-page-header h1 {
            font-size: 24px;
            color: #1a4a5e;
            margin: 0 0 5px;
        }
        
        .admin-page-header p {
            color: #666;
            margin: 0;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card.users .stat-icon { background: #e3f2fd; color: #1976d2; }
        .stat-card.sessions .stat-icon { background: #e8f5e9; color: #388e3c; }
        .stat-card.patients .stat-icon { background: #fff3e0; color: #f57c00; }
        .stat-card.orders .stat-icon { background: #fce4ec; color: #c2185b; }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
        }
        
        .stat-label {
            color: #888;
            font-size: 13px;
            margin-top: 5px;
        }
        
        /* Settings Sections */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        
        .settings-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-card.full-width {
            grid-column: 1 / -1;
        }
        
        .card-header {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 15px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .setting-row:last-child {
            border-bottom: none;
        }
        
        .setting-label strong {
            display: block;
            color: #333;
        }
        
        .setting-label span {
            font-size: 12px;
            color: #888;
        }
        
        .setting-control select,
        .setting-control input[type="text"],
        .setting-control input[type="number"],
        .setting-control input[type="color"] {
            padding: 8px 12px;
            border: 2px solid #d0d8e0;
            border-radius: 4px;
            font-size: 13px;
            min-width: 150px;
        }
        
        .setting-control input[type="color"] {
            width: 50px;
            height: 35px;
            padding: 3px;
            cursor: pointer;
        }
        
        /* Toggle Switch */
        .switch {
            position: relative;
            width: 46px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            border-radius: 24px;
            transition: 0.3s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        input:checked + .slider {
            background: #1a4a5e;
        }
        
        input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        /* Skin Options */
        .skin-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .skin-option {
            cursor: pointer;
        }
        
        .skin-option input {
            display: none;
        }
        
        .skin-preview {
            width: 70px;
            height: 50px;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid #ddd;
        }
        
        .skin-option.selected .skin-preview {
            border-color: #1a4a5e;
            box-shadow: 0 0 0 2px rgba(26, 74, 94, 0.3);
        }
        
        .skin-preview .preview-header {
            height: 14px;
        }
        
        .skin-preview .preview-body {
            height: 36px;
            display: flex;
        }
        
        .skin-preview .preview-sidebar {
            width: 15px;
        }
        
        .skin-preview .preview-content {
            flex: 1;
        }
        
        /* Skin color schemes */
        .skin-preview.hyperspace-teal .preview-header { background: #1a4a5e; }
        .skin-preview.hyperspace-teal .preview-sidebar { background: #e8f0f4; }
        .skin-preview.hyperspace-teal .preview-content { background: #f0f4f8; }
        
        .skin-preview.hyperspace-blue .preview-header { background: #1565c0; }
        .skin-preview.hyperspace-blue .preview-sidebar { background: #e3f2fd; }
        .skin-preview.hyperspace-blue .preview-content { background: #f5f9fc; }
        
        .skin-preview.hyperspace-green .preview-header { background: #2e7d32; }
        .skin-preview.hyperspace-green .preview-sidebar { background: #e8f5e9; }
        .skin-preview.hyperspace-green .preview-content { background: #f5faf5; }
        
        .skin-preview.hyperspace-purple .preview-header { background: #6a1b9a; }
        .skin-preview.hyperspace-purple .preview-sidebar { background: #f3e5f5; }
        .skin-preview.hyperspace-purple .preview-content { background: #faf5fc; }
        
        .skin-preview.hyperspace-dark .preview-header { background: #1a1a2e; }
        .skin-preview.hyperspace-dark .preview-sidebar { background: #2a2a40; }
        .skin-preview.hyperspace-dark .preview-content { background: #16213e; }
        
        .skin-option span {
            display: block;
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .card-actions {
            padding: 15px 20px;
            background: #fafbfc;
            border-top: 1px solid #e0e0e0;
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #1a4a5e;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0d3545;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .quick-action {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action:hover {
            border-color: #1a4a5e;
            transform: translateY(-2px);
        }
        
        .quick-action i {
            font-size: 28px;
            color: #1a4a5e;
            margin-bottom: 10px;
        }
        
        .quick-action strong {
            display: block;
            color: #333;
            margin-bottom: 5px;
        }
        
        .quick-action span {
            font-size: 12px;
            color: #888;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .admin-sidebar {
                display: none;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="roles.php"><i class="fas fa-user-shield"></i> Roles</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="audit.php"><i class="fas fa-clipboard-list"></i> Audit Log</a>
        </nav>
        <div class="admin-user">
            <span><?php echo htmlspecialchars($user['name'] ?? 'Admin'); ?></span>
            <a href="../home.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to EHR</a>
        </div>
    </header>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">General</div>
                <div class="sidebar-menu">
                    <a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="#appearance"><i class="fas fa-palette"></i> Appearance</a>
                    <a href="#features"><i class="fas fa-puzzle-piece"></i> Features</a>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">Security</div>
                <div class="sidebar-menu">
                    <a href="roles.php"><i class="fas fa-user-shield"></i> Roles & Permissions</a>
                    <a href="users.php"><i class="fas fa-users"></i> User Management</a>
                    <a href="#security"><i class="fas fa-lock"></i> Security Settings</a>
                    <a href="audit.php"><i class="fas fa-clipboard-list"></i> Audit Log</a>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">System</div>
                <div class="sidebar-menu">
                    <a href="integrations.php"><i class="fas fa-plug"></i> Integrations</a>
                    <a href="backups.php"><i class="fas fa-database"></i> Backups</a>
                    <a href="logs.php"><i class="fas fa-file-alt"></i> System Logs</a>
                </div>
            </div>
        </aside>
        
        <main class="admin-content">
            <div class="admin-page-header">
                <h1>Admin Dashboard</h1>
                <p>Manage system settings, appearance, and security</p>
            </div>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Overview -->
            <div class="stats-grid">
                <div class="stat-card users">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card sessions">
                    <div class="stat-icon"><i class="fas fa-desktop"></i></div>
                    <div class="stat-value"><?php echo $stats['active_sessions']; ?></div>
                    <div class="stat-label">Active Sessions</div>
                </div>
                <div class="stat-card patients">
                    <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
                    <div class="stat-value"><?php echo $stats['patients_today']; ?></div>
                    <div class="stat-label">Patients Today</div>
                </div>
                <div class="stat-card orders">
                    <div class="stat-icon"><i class="fas fa-file-medical"></i></div>
                    <div class="stat-value"><?php echo $stats['orders_today']; ?></div>
                    <div class="stat-label">Orders Today</div>
                </div>
            </div>
            
            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- Appearance Settings -->
                <div id="appearance" class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-palette"></i>
                        <h2>Appearance & Branding</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_appearance">
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>System Theme</strong>
                                    <span>Choose a color scheme for the application</span>
                                </div>
                            </div>
                            <div class="skin-options">
                                <label class="skin-option <?php echo $system_settings['skin'] === 'hyperspace-teal' ? 'selected' : ''; ?>">
                                    <input type="radio" name="skin" value="hyperspace-teal" <?php echo $system_settings['skin'] === 'hyperspace-teal' ? 'checked' : ''; ?>>
                                    <div class="skin-preview hyperspace-teal">
                                        <div class="preview-header"></div>
                                        <div class="preview-body">
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                    </div>
                                    <span>Teal (Default)</span>
                                </label>
                                <label class="skin-option <?php echo $system_settings['skin'] === 'hyperspace-blue' ? 'selected' : ''; ?>">
                                    <input type="radio" name="skin" value="hyperspace-blue" <?php echo $system_settings['skin'] === 'hyperspace-blue' ? 'checked' : ''; ?>>
                                    <div class="skin-preview hyperspace-blue">
                                        <div class="preview-header"></div>
                                        <div class="preview-body">
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                    </div>
                                    <span>Blue</span>
                                </label>
                                <label class="skin-option <?php echo $system_settings['skin'] === 'hyperspace-green' ? 'selected' : ''; ?>">
                                    <input type="radio" name="skin" value="hyperspace-green" <?php echo $system_settings['skin'] === 'hyperspace-green' ? 'checked' : ''; ?>>
                                    <div class="skin-preview hyperspace-green">
                                        <div class="preview-header"></div>
                                        <div class="preview-body">
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                    </div>
                                    <span>Green</span>
                                </label>
                                <label class="skin-option <?php echo $system_settings['skin'] === 'hyperspace-purple' ? 'selected' : ''; ?>">
                                    <input type="radio" name="skin" value="hyperspace-purple" <?php echo $system_settings['skin'] === 'hyperspace-purple' ? 'checked' : ''; ?>>
                                    <div class="skin-preview hyperspace-purple">
                                        <div class="preview-header"></div>
                                        <div class="preview-body">
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                    </div>
                                    <span>Purple</span>
                                </label>
                                <label class="skin-option <?php echo $system_settings['skin'] === 'hyperspace-dark' ? 'selected' : ''; ?>">
                                    <input type="radio" name="skin" value="hyperspace-dark" <?php echo $system_settings['skin'] === 'hyperspace-dark' ? 'checked' : ''; ?>>
                                    <div class="skin-preview hyperspace-dark">
                                        <div class="preview-header"></div>
                                        <div class="preview-body">
                                            <div class="preview-sidebar"></div>
                                            <div class="preview-content"></div>
                                        </div>
                                    </div>
                                    <span>Dark</span>
                                </label>
                            </div>
                            
                            <div class="setting-row" style="margin-top: 20px;">
                                <div class="setting-label">
                                    <strong>Application Name</strong>
                                    <span>Shown in header and title</span>
                                </div>
                                <div class="setting-control">
                                    <input type="text" name="logo_text" value="<?php echo htmlspecialchars($system_settings['logo_text']); ?>">
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Show Logo Icon</strong>
                                    <span>Display hospital icon before name</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="show_logo_icon" <?php echo $system_settings['show_logo_icon'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Appearance
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Feature Settings -->
                <div id="features" class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-puzzle-piece"></i>
                        <h2>Features</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_features">
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Dark Mode Option</strong>
                                    <span>Allow users to switch to dark theme</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="enable_dark_mode" <?php echo $system_settings['enable_dark_mode'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Patient Photos</strong>
                                    <span>Display patient photos in charts</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="enable_patient_photos" <?php echo $system_settings['enable_patient_photos'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Two-Factor Authentication</strong>
                                    <span>Require 2FA for all users</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="enable_two_factor" <?php echo $system_settings['enable_two_factor'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Features
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Security Settings -->
                <div id="security" class="settings-card full-width">
                    <div class="card-header">
                        <i class="fas fa-lock"></i>
                        <h2>Security Settings</h2>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_security">
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Session Timeout</strong>
                                    <span>Minutes of inactivity before auto-logout</span>
                                </div>
                                <div class="setting-control">
                                    <select name="session_timeout">
                                        <option value="15" <?php echo $system_settings['session_timeout'] == 15 ? 'selected' : ''; ?>>15 minutes</option>
                                        <option value="30" <?php echo $system_settings['session_timeout'] == 30 ? 'selected' : ''; ?>>30 minutes</option>
                                        <option value="60" <?php echo $system_settings['session_timeout'] == 60 ? 'selected' : ''; ?>>1 hour</option>
                                        <option value="120" <?php echo $system_settings['session_timeout'] == 120 ? 'selected' : ''; ?>>2 hours</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Password Expiry</strong>
                                    <span>Days until password must be changed</span>
                                </div>
                                <div class="setting-control">
                                    <select name="password_expiry">
                                        <option value="30" <?php echo $system_settings['password_expiry'] == 30 ? 'selected' : ''; ?>>30 days</option>
                                        <option value="60" <?php echo $system_settings['password_expiry'] == 60 ? 'selected' : ''; ?>>60 days</option>
                                        <option value="90" <?php echo $system_settings['password_expiry'] == 90 ? 'selected' : ''; ?>>90 days</option>
                                        <option value="180" <?php echo $system_settings['password_expiry'] == 180 ? 'selected' : ''; ?>>180 days</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Max Login Attempts</strong>
                                    <span>Failed attempts before lockout</span>
                                </div>
                                <div class="setting-control">
                                    <input type="number" name="max_login_attempts" value="<?php echo $system_settings['max_login_attempts']; ?>" min="3" max="10">
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Audit Logging</strong>
                                    <span>Track all user actions</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="enable_audit_log" <?php echo $system_settings['enable_audit_log'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>HIPAA Compliance Logging</strong>
                                    <span>Log all PHI access for compliance</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="enable_hipaa_logging" <?php echo $system_settings['enable_hipaa_logging'] ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Security Settings
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Quick Actions -->
                <div class="settings-card full-width">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="users.php" class="quick-action">
                                <i class="fas fa-user-plus"></i>
                                <strong>Add New User</strong>
                                <span>Create a new system user</span>
                            </a>
                            <a href="roles.php" class="quick-action">
                                <i class="fas fa-user-shield"></i>
                                <strong>Manage Roles</strong>
                                <span>Edit roles and permissions</span>
                            </a>
                            <a href="audit.php" class="quick-action">
                                <i class="fas fa-history"></i>
                                <strong>View Audit Log</strong>
                                <span>Review system activity</span>
                            </a>
                            <a href="backups.php" class="quick-action">
                                <i class="fas fa-download"></i>
                                <strong>Create Backup</strong>
                                <span>Backup system data</span>
                            </a>
                            <a href="logs.php" class="quick-action">
                                <i class="fas fa-file-alt"></i>
                                <strong>System Logs</strong>
                                <span>View error and access logs</span>
                            </a>
                            <a href="integrations.php" class="quick-action">
                                <i class="fas fa-plug"></i>
                                <strong>Integrations</strong>
                                <span>Configure external systems</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Skin option selection
        document.querySelectorAll('.skin-option input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.skin-option').forEach(opt => opt.classList.remove('selected'));
                this.closest('.skin-option').classList.add('selected');
            });
        });
        
        // Sidebar navigation highlighting
        document.querySelectorAll('.sidebar-menu a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                const target = this.getAttribute('href');
                document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>

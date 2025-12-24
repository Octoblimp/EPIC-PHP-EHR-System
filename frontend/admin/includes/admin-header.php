<?php
/**
 * Openspace EHR - Admin Header Include
 * Shared header styling and layout for admin pages
 * 
 * Usage: Include this file at the top of admin pages after authentication check
 * This provides consistent styling matching the main Openspace theme
 */

// Ensure this is only included from admin pages
if (!defined('ADMIN_PAGE')) {
    define('ADMIN_PAGE', true);
}

// Get current admin page for nav highlighting
$admin_current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin - Openspace EHR'); ?></title>
    <link rel="stylesheet" href="../assets/css/openspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Openspace Admin Theme - Matching Main Site */
        :root {
            --admin-primary: #1a4a5e;
            --admin-primary-dark: #0d3545;
            --admin-accent: #f28c38;
            --admin-bg: #d8e0e8;
            --admin-card-bg: #ffffff;
            --admin-border: #c0c8d0;
            --admin-text: #333;
            --admin-text-muted: #666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--admin-bg);
            min-height: 100vh;
        }
        
        /* Admin Header - Matching Openspace Header */
        .admin-header {
            background: linear-gradient(to right, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 0 20px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            color: white;
        }
        
        .admin-logo i {
            font-size: 20px;
            color: var(--admin-accent);
        }
        
        .admin-logo:hover {
            opacity: 0.9;
        }
        
        .admin-badge {
            background: var(--admin-accent);
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .admin-nav {
            display: flex;
            gap: 2px;
            margin-left: 30px;
        }
        
        .admin-nav a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-nav a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .admin-header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: rgba(255,255,255,0.9);
        }
        
        .admin-user i {
            font-size: 14px;
        }
        
        .back-to-app {
            display: flex;
            align-items: center;
            gap: 6px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
        }
        
        .back-to-app:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Admin Layout */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 48px);
        }
        
        /* Admin Sidebar */
        .admin-sidebar {
            width: 220px;
            background: var(--admin-card-bg);
            border-right: 1px solid var(--admin-border);
            padding: 15px 0;
            flex-shrink: 0;
        }
        
        .sidebar-section {
            margin-bottom: 15px;
        }
        
        .sidebar-title {
            padding: 8px 20px;
            font-size: 10px;
            text-transform: uppercase;
            color: #888;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--admin-text-muted);
            text-decoration: none;
            font-size: 13px;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        
        .sidebar-menu a:hover {
            background: #f0f4f8;
            color: var(--admin-text);
        }
        
        .sidebar-menu a.active {
            background: #e8f4f8;
            border-left-color: var(--admin-primary);
            color: var(--admin-primary);
            font-weight: 500;
        }
        
        .sidebar-menu a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }
        
        /* Admin Content */
        .admin-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .admin-page-header {
            margin-bottom: 20px;
        }
        
        .admin-page-header h1 {
            font-size: 22px;
            color: var(--admin-primary);
            margin: 0 0 5px;
            font-weight: 600;
        }
        
        .admin-page-header p {
            color: var(--admin-text-muted);
            margin: 0;
            font-size: 13px;
        }
        
        /* Cards */
        .admin-card {
            background: var(--admin-card-bg);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .admin-card-header {
            background: linear-gradient(to bottom, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-card-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .admin-card-body {
            padding: 18px;
        }
        
        .admin-card-footer {
            padding: 12px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e8e8e8;
        }
        
        /* Alerts */
        .admin-alert {
            padding: 12px 18px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        
        .admin-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .admin-alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .admin-alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .admin-alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Buttons */
        .admin-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
            text-decoration: none;
        }
        
        .admin-btn-primary {
            background: var(--admin-primary);
            color: white;
        }
        
        .admin-btn-primary:hover {
            background: var(--admin-primary-dark);
        }
        
        .admin-btn-secondary {
            background: #e0e4e8;
            color: var(--admin-text);
        }
        
        .admin-btn-secondary:hover {
            background: #d0d4d8;
        }
        
        .admin-btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .admin-btn-danger:hover {
            background: #c82333;
        }
        
        /* Form Controls */
        .admin-form-group {
            margin-bottom: 15px;
        }
        
        .admin-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 13px;
            color: var(--admin-text);
        }
        
        .admin-form-control {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #d0d8e0;
            border-radius: 4px;
            font-size: 13px;
            transition: border-color 0.15s;
        }
        
        .admin-form-control:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        
        /* Stats Grid */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .admin-stat-card {
            background: var(--admin-card-bg);
            border-radius: 6px;
            padding: 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .admin-stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .admin-stat-card.users .stat-icon { background: #e3f2fd; color: #1976d2; }
        .admin-stat-card.sessions .stat-icon { background: #e8f5e9; color: #388e3c; }
        .admin-stat-card.patients .stat-icon { background: #fff3e0; color: #f57c00; }
        .admin-stat-card.orders .stat-icon { background: #fce4ec; color: #c2185b; }
        
        .admin-stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--admin-text);
        }
        
        .admin-stat-card .stat-label {
            color: var(--admin-text-muted);
            font-size: 12px;
            margin-top: 4px;
        }
        
        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .admin-table th {
            background: #f5f7f9;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--admin-text-muted);
            border-bottom: 2px solid #e0e4e8;
        }
        
        .admin-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .admin-table tr:hover td {
            background: #f8f9fa;
        }
        
        /* Toggle Switch */
        .admin-switch {
            position: relative;
            width: 44px;
            height: 22px;
        }
        
        .admin-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .admin-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            border-radius: 22px;
            transition: 0.3s;
        }
        
        .admin-switch .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        .admin-switch input:checked + .slider {
            background: var(--admin-primary);
        }
        
        .admin-switch input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--admin-border);
            }
            
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                padding: 10px;
            }
            
            .sidebar-menu a {
                flex: 1 1 auto;
                justify-content: center;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .sidebar-menu a.active {
                border-left: none;
                border-bottom-color: var(--admin-primary);
            }
            
            .sidebar-title {
                display: none;
            }
        }
    </style>
    <?php if (isset($extra_admin_css)): ?>
    <style><?php echo $extra_admin_css; ?></style>
    <?php endif; ?>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-left">
            <a href="index.php" class="admin-logo">
                <i class="fas fa-hospital"></i>
                <span>Openspace</span>
                <span class="admin-badge">Admin</span>
            </a>
            <nav class="admin-nav">
                <a href="index.php" class="<?php echo $admin_current_page === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="<?php echo $admin_current_page === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="roles.php" class="<?php echo $admin_current_page === 'roles' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i> Roles
                </a>
                <a href="audit-log.php" class="<?php echo $admin_current_page === 'audit-log' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i> Audit Log
                </a>
                <a href="db-updater.php" class="<?php echo $admin_current_page === 'db-updater' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i> Database
                </a>
            </nav>
        </div>
        <div class="admin-header-right">
            <div class="admin-user">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'Admin'); ?></span>
            </div>
            <a href="../home.php" class="back-to-app">
                <i class="fas fa-arrow-left"></i> Back to App
            </a>
        </div>
    </header>
    
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">General</div>
                <div class="sidebar-menu">
                    <a href="index.php" class="<?php echo $admin_current_page === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="index.php#appearance" class="<?php echo $admin_current_page === 'index' && isset($_GET['section']) && $_GET['section'] === 'appearance' ? 'active' : ''; ?>">
                        <i class="fas fa-palette"></i> Appearance
                    </a>
                    <a href="index.php#features" class="<?php echo $admin_current_page === 'index' && isset($_GET['section']) && $_GET['section'] === 'features' ? 'active' : ''; ?>">
                        <i class="fas fa-toggle-on"></i> Features
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">User Management</div>
                <div class="sidebar-menu">
                    <a href="users.php" class="<?php echo $admin_current_page === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> All Users
                    </a>
                    <a href="roles.php" class="<?php echo $admin_current_page === 'roles' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Roles & Permissions
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-title">System</div>
                <div class="sidebar-menu">
                    <a href="audit-log.php" class="<?php echo $admin_current_page === 'audit-log' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list"></i> Audit Log
                    </a>
                    <a href="db-updater.php" class="<?php echo $admin_current_page === 'db-updater' ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i> Database Updates
                    </a>
                    <a href="index.php#security">
                        <i class="fas fa-shield-alt"></i> Security
                    </a>
                </div>
            </div>
        </aside>
        
        <main class="admin-content">

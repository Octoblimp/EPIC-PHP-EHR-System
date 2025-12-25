<?php
/**
 * Openspace EHR - Admin Header Include
 * Shared header styling and layout for admin pages
 * Matches employee-facing layout with left sidebar navigation
 */

// Ensure this is only included from admin pages
if (!defined('ADMIN_PAGE')) {
    define('ADMIN_PAGE', true);
}

// Get current admin page for nav highlighting
$admin_current_page = basename($_SERVER['PHP_SELF'], '.php');

// Admin sidebar navigation items
$admin_sidebar_items = [
    'index' => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
    'users' => ['icon' => 'fa-users', 'label' => 'Users'],
    'roles' => ['icon' => 'fa-user-shield', 'label' => 'Roles'],
    'page-access' => ['icon' => 'fa-lock', 'label' => 'Page Access'],
    'shortcodes' => ['icon' => 'fa-keyboard', 'label' => 'Shortcodes'],
    'integrations' => ['icon' => 'fa-plug', 'label' => 'Integrations'],
    'audit' => ['icon' => 'fa-clipboard-list', 'label' => 'Audit Log'],
    'db-updater' => ['icon' => 'fa-database', 'label' => 'Database'],
];
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
        /* Admin Theme - Matching Employee-Facing Layout */
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
            margin: 0;
            padding: 0;
            overflow: hidden;
            height: 100vh;
        }
        
        .app-layout {
            display: flex;
            height: 100vh;
            flex-direction: column;
        }
        
        .app-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            margin-top: 28px;
            height: calc(100vh - 28px);
        }
        
        /* Main Header Bar - Matching Employee Header */
        .openspace-header {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            height: 28px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .openspace-logo-wrapper {
            position: relative;
        }
        
        .openspace-logo {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 2px 8px;
            border-radius: 3px;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }
        
        .openspace-logo:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .openspace-logo .logo-icon {
            color: var(--admin-accent);
        }
        
        .openspace-logo .admin-badge {
            background: var(--admin-accent);
            color: white;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 4px;
        }
        
        .openspace-logo .dropdown-arrow {
            font-size: 10px;
            margin-left: 4px;
            opacity: 0.7;
        }
        
        /* Openspace Dropdown Menu */
        .openspace-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 4px;
            min-width: 220px;
            display: none;
            z-index: 1001;
        }
        
        .openspace-menu.show {
            display: block;
        }
        
        .openspace-menu-header {
            padding: 12px 15px;
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            font-size: 12px;
        }
        
        .openspace-menu-header strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .openspace-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #333;
            font-size: 13px;
            text-decoration: none;
        }
        
        .openspace-menu a:hover {
            background: #f0f4f8;
        }
        
        .openspace-menu a i {
            width: 18px;
            text-align: center;
            color: #1a4a5e;
        }
        
        .openspace-menu .menu-divider {
            border-top: 1px solid #e0e0e0;
            margin: 5px 0;
        }
        
        /* Header Search */
        .header-search {
            margin-left: 20px;
            position: relative;
        }
        
        .header-search input {
            width: 200px;
            padding: 3px 10px 3px 28px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 3px;
            font-size: 11px;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .header-search input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .header-search input:focus {
            outline: none;
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
        }
        
        .header-search .search-icon {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }
        
        /* Header Toolbar */
        .header-toolbar {
            display: flex;
            align-items: center;
            margin-left: auto;
            gap: 2px;
        }
        
        .toolbar-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.85);
            font-size: 11px;
            cursor: pointer;
            border-radius: 3px;
            text-decoration: none;
        }
        
        .toolbar-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .toolbar-btn i {
            font-size: 12px;
        }
        
        /* Header User */
        .header-user {
            display: flex;
            align-items: center;
            margin-left: 15px;
            padding-left: 15px;
            border-left: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-name {
            color: rgba(255,255,255,0.9);
            font-size: 11px;
            margin-right: 5px;
        }
        
        .header-dropdown {
            position: relative;
        }
        
        .header-dropdown-btn {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            padding: 4px;
        }
        
        .header-dropdown-btn:hover {
            color: white;
        }
        
        .header-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            display: none;
            z-index: 1001;
        }
        
        .header-dropdown-menu.show {
            display: block;
        }
        
        .header-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            font-size: 13px;
        }
        
        .header-dropdown-menu a:hover {
            background: #f0f4f8;
        }
        
        .header-dropdown-menu a i {
            width: 16px;
            text-align: center;
            color: #1a4a5e;
        }
        
        .header-dropdown-menu .divider {
            border-top: 1px solid #e0e0e0;
            margin: 5px 0;
        }
        
        /* Left Sidebar Navigation - Matching Employee Layout */
        .left-sidebar {
            width: 52px;
            min-width: 52px;
            height: 100%;
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid #0a2a35;
            z-index: 100;
            transition: width 0.2s ease, min-width 0.2s ease;
            overflow: visible;
        }
        
        .left-sidebar.collapsed {
            width: 0;
            min-width: 0;
            overflow: hidden;
            border-right: none;
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            position: fixed;
            left: 52px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 48px;
            background: #1a4a5e;
            border: 1px solid #0a2a35;
            border-left: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.7);
            font-size: 8px;
            z-index: 101;
            transition: left 0.2s ease, background 0.15s;
        }
        
        .sidebar-toggle:hover {
            background: #0d3545;
            color: white;
        }
        
        .sidebar-toggle.collapsed {
            left: 0;
        }
        
        .sidebar-toggle i {
            transition: transform 0.2s;
        }
        
        .sidebar-toggle.collapsed i {
            transform: rotate(180deg);
        }
        
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 4px 0;
        }
        
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 4px;
            color: rgba(255,255,255,0.85);
            cursor: pointer;
            transition: background 0.15s ease;
            text-decoration: none;
            font-size: 9px;
            text-align: center;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            text-decoration: none;
            color: white;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: var(--admin-accent);
            color: white;
        }
        
        .nav-item .nav-icon {
            font-size: 18px;
            margin-bottom: 2px;
        }
        
        .nav-item .nav-label {
            font-size: 8px;
            line-height: 1.1;
            max-width: 46px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.2);
            margin: 4px 8px;
        }
        
        /* Back to App button at bottom */
        .nav-bottom {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding: 4px 0;
        }
        
        .nav-bottom .nav-item {
            border-left: none;
            color: rgba(255,255,255,0.7);
        }
        
        .nav-bottom .nav-item:hover {
            color: white;
        }
        
        /* Main Content Wrapper */
        .main-content-wrapper {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: var(--admin-bg);
            min-width: 0;
        }
        
        .main-content {
            flex: 1;
            overflow: auto;
            padding: 20px;
        }
        
        /* Admin Page Header */
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
        
        /* =============================================
           INDEX.PHP SPECIFIC STYLES
           ============================================= */
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: var(--admin-card-bg);
            border-radius: 6px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .stat-card.users .stat-icon { background: #e3f2fd; color: #1976d2; }
        .stat-card.sessions .stat-icon { background: #e8f5e9; color: #388e3c; }
        .stat-card.patients .stat-icon { background: #fff3e0; color: #f57c00; }
        .stat-card.orders .stat-icon { background: #fce4ec; color: #c2185b; }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--admin-text);
        }
        
        .stat-card .stat-label {
            color: var(--admin-text-muted);
            font-size: 12px;
            margin-top: 4px;
        }
        
        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .settings-card {
            background: var(--admin-card-bg);
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-card .card-header {
            background: linear-gradient(to bottom, var(--admin-primary), var(--admin-primary-dark));
            color: white;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .settings-card .card-header i {
            font-size: 16px;
        }
        
        .settings-card .card-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
        }
        
        .settings-card .card-body {
            padding: 15px;
        }
        
        .settings-card .card-actions {
            padding: 12px 15px;
            background: #f8f9fa;
            border-top: 1px solid #e8e8e8;
        }
        
        /* Setting Rows */
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .setting-row:last-child {
            border-bottom: none;
        }
        
        .setting-label strong {
            display: block;
            font-size: 13px;
            color: var(--admin-text);
            margin-bottom: 2px;
        }
        
        .setting-label span {
            font-size: 11px;
            color: var(--admin-text-muted);
        }
        
        .setting-control input[type="text"],
        .setting-control input[type="number"],
        .setting-control select {
            padding: 6px 10px;
            border: 1px solid #d0d8e0;
            border-radius: 4px;
            font-size: 13px;
            min-width: 150px;
        }
        
        .setting-control input:focus,
        .setting-control select:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        
        /* Skin Options */
        .skin-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .skin-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        
        .skin-option:hover {
            border-color: #d0d8e0;
        }
        
        .skin-option.selected {
            border-color: var(--admin-primary);
        }
        
        .skin-option input[type="radio"] {
            display: none;
        }
        
        .skin-option span {
            font-size: 11px;
            margin-top: 6px;
            color: var(--admin-text);
        }
        
        .skin-preview {
            width: 60px;
            height: 40px;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #ccc;
        }
        
        .skin-preview .preview-header {
            height: 8px;
        }
        
        .skin-preview .preview-body {
            display: flex;
            height: 32px;
        }
        
        .skin-preview .preview-sidebar {
            width: 12px;
        }
        
        .skin-preview .preview-content {
            flex: 1;
            background: #e8e8e8;
        }
        
        /* Skin color themes */
        .skin-preview.hyperspace-teal .preview-header,
        .skin-preview.hyperspace-teal .preview-sidebar { background: #1a4a5e; }
        
        .skin-preview.hyperspace-blue .preview-header,
        .skin-preview.hyperspace-blue .preview-sidebar { background: #1a3a6e; }
        
        .skin-preview.hyperspace-green .preview-header,
        .skin-preview.hyperspace-green .preview-sidebar { background: #1a5e3a; }
        
        .skin-preview.hyperspace-purple .preview-header,
        .skin-preview.hyperspace-purple .preview-sidebar { background: #4a1a5e; }
        
        .skin-preview.hyperspace-dark .preview-header,
        .skin-preview.hyperspace-dark .preview-sidebar { background: #2a2a2a; }
        .skin-preview.hyperspace-dark .preview-content { background: #3a3a3a; }
        
        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .switch .slider {
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
        
        .switch .slider:before {
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
        
        .switch input:checked + .slider {
            background: var(--admin-primary);
        }
        
        .switch input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        /* Buttons */
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--admin-primary-dark);
        }
        
        .btn-secondary {
            background: #e0e4e8;
            color: var(--admin-text);
        }
        
        .btn-secondary:hover {
            background: #d0d4d8;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
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
        
        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php if (isset($extra_admin_css)): ?>
    <style><?php echo $extra_admin_css; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="app-layout">
        <!-- Main Header Bar - Matching Employee Header -->
        <header class="openspace-header">
            <div class="openspace-logo-wrapper">
                <div class="openspace-logo" onclick="toggleOpenspaceMenu()">
                    <span class="logo-icon"><i class="fas fa-hospital"></i></span>
                    <span>Openspace</span>
                    <span class="admin-badge">Admin</span>
                    <i class="fas fa-caret-down dropdown-arrow"></i>
                </div>
                <div class="openspace-menu" id="openspaceMenu">
                    <div class="openspace-menu-header">
                        <strong>Openspace EHR</strong>
                        Administration Panel
                    </div>
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a href="roles.php"><i class="fas fa-user-shield"></i> Roles</a>
                    <div class="menu-divider"></div>
                    <a href="../home.php"><i class="fas fa-arrow-left"></i> Back to App</a>
                </div>
            </div>
            
            <div class="header-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search admin..." id="adminSearch">
            </div>
            
            <div class="header-toolbar">
                <a href="users.php" class="toolbar-btn" title="Users">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="audit.php" class="toolbar-btn" title="Audit Log">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Audit</span>
                </a>
                <a href="db-updater.php" class="toolbar-btn" title="Database">
                    <i class="fas fa-database"></i>
                    <span>Database</span>
                </a>
            </div>
            
            <div class="header-user">
                <span class="user-name"><?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'Admin'); ?></span>
                <div class="header-dropdown">
                    <button class="header-dropdown-btn" onclick="toggleUserMenu()">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="header-dropdown-menu" id="userDropdown">
                        <a href="../profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <div class="divider"></div>
                        <a href="../home.php"><i class="fas fa-arrow-left"></i> Back to App</a>
                        <div class="divider"></div>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="app-body">
            <!-- Sidebar Toggle Button -->
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Toggle Sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <!-- Left Sidebar Navigation -->
            <nav class="left-sidebar" id="leftSidebar">
                <div class="sidebar-nav">
                    <?php foreach ($admin_sidebar_items as $key => $item): 
                        $is_active = ($admin_current_page === $key);
                    ?>
                    <a href="<?php echo $key; ?>.php" 
                       class="nav-item <?php echo $is_active ? 'active' : ''; ?>" 
                       title="<?php echo $item['label']; ?>">
                        <i class="fas <?php echo $item['icon']; ?> nav-icon"></i>
                        <span class="nav-label"><?php echo $item['label']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="nav-bottom">
                    <a href="../home.php" class="nav-item" title="Back to App">
                        <i class="fas fa-arrow-left nav-icon"></i>
                        <span class="nav-label">Back</span>
                    </a>
                </div>
            </nav>
            
            <!-- Main Content Wrapper -->
            <div class="main-content-wrapper">
                <div class="main-content">

<script>
// Openspace menu toggle
function toggleOpenspaceMenu() {
    const menu = document.getElementById('openspaceMenu');
    menu.classList.toggle('show');
}

// User menu toggle
function toggleUserMenu() {
    const menu = document.getElementById('userDropdown');
    menu.classList.toggle('show');
}

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('leftSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const isCollapsed = sidebar.classList.toggle('collapsed');
    toggle.classList.toggle('collapsed', isCollapsed);
    localStorage.setItem('adminSidebarCollapsed', isCollapsed);
}

// Restore sidebar state on load
document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('leftSidebar')?.classList.add('collapsed');
        document.getElementById('sidebarToggle')?.classList.add('collapsed');
    }
});

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const openspaceWrapper = document.querySelector('.openspace-logo-wrapper');
    if (openspaceWrapper && !openspaceWrapper.contains(event.target)) {
        document.getElementById('openspaceMenu')?.classList.remove('show');
    }
    
    const userDropdown = document.querySelector('.header-dropdown');
    if (userDropdown && !userDropdown.contains(event.target)) {
        document.getElementById('userDropdown')?.classList.remove('show');
    }
});
</script>

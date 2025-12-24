<?php
/**
 * Openspace EHR - Header with Left Sidebar Navigation
 * Epic Hyperspace-inspired layout with sidebar navigation
 */

// Include config if not already included
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Include audit logging
if (file_exists(__DIR__ . '/audit.php')) {
    require_once __DIR__ . '/audit.php';
}
if (file_exists(__DIR__ . '/patient_protection.php')) {
    require_once __DIR__ . '/patient_protection.php';
}
// Include permissions system
if (file_exists(__DIR__ . '/permissions.php')) {
    require_once __DIR__ . '/permissions.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user from session
$current_user = $_SESSION['user'] ?? null;
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Initialize open patients in session if not exists
if (!isset($_SESSION['open_patients'])) {
    $_SESSION['open_patients'] = [];
}

// If we're on patient-chart page, add patient to open tabs
if ($current_page === 'patient-chart' && isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    $patient_name = $patient['last_name'] ?? 'Patient';
    if (isset($patient['first_name'])) {
        $patient_name .= ', ' . $patient['first_name'];
    }
    
    // Add to open patients if not already there
    $found = false;
    foreach ($_SESSION['open_patients'] as $p) {
        if ($p['id'] == $patient_id) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['open_patients'][] = [
            'id' => $patient_id,
            'name' => $patient_name
        ];
    }
}

// Use session open patients
$open_patients = $_SESSION['open_patients'] ?? [];
$current_patient = isset($_GET['id']) ? ['id' => $_GET['id']] : null;

// Check if user is admin
$is_admin = in_array(strtolower($current_user['role'] ?? ''), ['admin', 'administrator']);

// Determine if we're on a patient page (sidebar only shows on patient pages)
$patient_pages = ['patient-chart', 'patient', 'medications', 'orders', 'notes', 'results', 'flowsheets', 'history', 'demographics'];
$show_sidebar = in_array($current_page, $patient_pages) || isset($_GET['id']);

// Get user settings for sidebar customization
$user_settings = $_SESSION['user_settings'] ?? [];
$sidebar_favorites = $user_settings['sidebar_favorites'] ?? ['summary', 'flowsheets', 'notes', 'mar', 'orders', 'results'];

// Patient chart sidebar items (these link to tabs within patient-chart.php)
$patient_sidebar_items = [
    'summary' => ['icon' => 'fa-clipboard', 'label' => 'Summary'],
    'chart-review' => ['icon' => 'fa-file-medical', 'label' => 'Chart Review'],
    'results' => ['icon' => 'fa-flask', 'label' => 'Results'],
    'work-list' => ['icon' => 'fa-tasks', 'label' => 'Work List'],
    'mar' => ['icon' => 'fa-pills', 'label' => 'MAR'],
    'flowsheets' => ['icon' => 'fa-chart-line', 'label' => 'Flowsheets'],
    'intake-output' => ['icon' => 'fa-balance-scale', 'label' => 'Intake/O'],
    'notes' => ['icon' => 'fa-sticky-note', 'label' => 'Notes'],
    'education' => ['icon' => 'fa-graduation-cap', 'label' => 'Education'],
    'care-plan' => ['icon' => 'fa-clipboard-list', 'label' => 'Care Plan'],
    'orders' => ['icon' => 'fa-prescription', 'label' => 'Orders'],
    'demographics' => ['icon' => 'fa-id-card', 'label' => 'Demographics'],
    'history' => ['icon' => 'fa-history', 'label' => 'History'],
];

// More menu items with submenus (Epic Hyperspace style)
$more_menu_items = [
    ['label' => 'Summary', 'tab' => 'summary', 'icon' => 'fa-clipboard'],
    ['label' => 'Chart Review', 'tab' => 'chart-review', 'icon' => 'fa-file-medical'],
    ['label' => 'Results', 'tab' => 'results', 'icon' => 'fa-flask', 'submenu' => [
        ['label' => 'All Results', 'tab' => 'results'],
        ['label' => 'Lab', 'tab' => 'results&sub=lab'],
        ['label' => 'Imaging', 'tab' => 'results&sub=imaging'],
    ]],
    ['label' => 'Work List', 'tab' => 'work-list', 'icon' => 'fa-tasks'],
    ['label' => 'MAR', 'tab' => 'mar', 'icon' => 'fa-pills'],
    ['label' => 'Flowsheets', 'tab' => 'flowsheets', 'icon' => 'fa-chart-line'],
    ['label' => 'Intake/Output', 'tab' => 'intake-output', 'icon' => 'fa-balance-scale'],
    ['label' => 'Notes', 'tab' => 'notes', 'icon' => 'fa-sticky-note', 'submenu' => [
        ['label' => 'All Notes', 'tab' => 'notes'],
        ['label' => 'Progress Notes', 'tab' => 'notes&sub=progress'],
        ['label' => 'H&P', 'tab' => 'notes&sub=hp'],
    ]],
    ['label' => 'Education', 'tab' => 'education', 'icon' => 'fa-graduation-cap'],
    ['label' => 'Care Plan', 'tab' => 'care-plan', 'icon' => 'fa-clipboard-list'],
    ['label' => 'Orders', 'tab' => 'orders', 'icon' => 'fa-prescription'],
    ['label' => 'Demographics', 'tab' => 'demographics', 'icon' => 'fa-id-card'],
    ['label' => 'History', 'tab' => 'history', 'icon' => 'fa-history'],
];

// Get current patient ID and tab for sidebar
$sidebar_patient_id = $_GET['id'] ?? null;
$current_tab = $_GET['tab'] ?? 'summary';

// Function to check if user has permission
function hasNavPermission($permission) {
    global $current_user, $is_admin;
    if ($is_admin || $permission === null) return true;
    if (function_exists('hasPermission')) {
        return hasPermission($permission);
    }
    return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/openspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php endforeach; endif; ?>
    <style>
        /* Left Sidebar Layout */
        body {
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
        
        /* Left Sidebar Navigation */
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
            border-left-color: #f0a030;
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
        
        /* More Button */
        .nav-bottom {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .nav-customize {
            padding: 4px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-customize .nav-item {
            border-left: none;
        }
        
        .nav-more {
            padding: 4px 0;
        }
        
        .nav-more .nav-item {
            border-left: none;
        }
        
        /* More Menu - Windows Context Menu Style */
        .more-menu {
            position: fixed;
            bottom: 50px;
            left: 60px;
            background: #f0f0f0;
            border: 1px solid #999;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.25);
            min-width: 180px;
            z-index: 2000;
            display: none;
            padding: 2px 0;
            font-size: 12px;
        }
        
        .more-menu.show {
            display: block;
        }
        
        .more-menu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 20px 4px 8px;
            color: #000;
            cursor: default;
            text-decoration: none;
            position: relative;
        }
        
        .more-menu-item:hover {
            background: #0078d4;
            color: white;
            text-decoration: none;
        }
        
        .more-menu-item:hover i {
            color: white;
        }
        
        .more-menu-item i {
            width: 16px;
            text-align: center;
            color: #333;
            font-size: 11px;
        }
        
        .more-menu-item .submenu-arrow {
            margin-left: auto;
            font-size: 8px;
        }
        
        .more-menu-divider {
            height: 1px;
            background: #ccc;
            margin: 2px 2px;
        }
        
        /* Submenu */
        .more-menu-item .submenu {
            display: none;
            position: absolute;
            left: 100%;
            top: -2px;
            background: #f0f0f0;
            border: 1px solid #999;
            box-shadow: 2px 2px 8px rgba(0,0,0,0.25);
            min-width: 140px;
            padding: 2px 0;
        }
        
        .more-menu-item:hover > .submenu {
            display: block;
        }
        
        .submenu .more-menu-item {
            padding: 4px 15px 4px 8px;
        }
        
        /* Main Content Area */
        .main-content-wrapper {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #d8e0e8;
            min-width: 0;
        }
        
        .main-content {
            flex: 1;
            overflow: auto;
            padding: 10px;
        }
        
        /* Ensure app-body takes full height */
        .app-body {
            display: flex;
            flex: 1;
            overflow: hidden;
            margin-top: 28px;
            height: calc(100vh - 28px);
        }
        
        .has-patient-tabs .app-body {
            margin-top: 54px;
            height: calc(100vh - 54px);
        }
        
        /* Patient Tab Bar - Now beside sidebar */
        .patient-tab-bar {
            background: linear-gradient(to bottom, #4a7a9a, #3a6080);
            height: 26px;
            display: flex;
            align-items: flex-end;
            padding: 0 8px;
            position: fixed;
            top: 28px;
            left: 52px;
            right: 0;
            z-index: 999;
        }
        
        .patient-tab {
            background: linear-gradient(to bottom, #e0e8ec, #c8d4dc);
            border: 1px solid #8898a8;
            border-bottom: none;
            padding: 3px 12px;
            margin-right: 1px;
            font-size: 11px;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #333;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .patient-tab.active {
            background: linear-gradient(to bottom, #ffffff, #f8f8f8);
            border-color: #8898a8;
            font-weight: 600;
            z-index: 1;
        }
        
        .patient-tab .close-tab {
            font-size: 14px;
            color: #888;
            margin-left: auto;
        }
        
        .patient-tab .close-tab:hover {
            color: #c00;
        }
        
        .add-patient-tab {
            background: transparent;
            border: none;
            color: white;
            padding: 3px 10px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .add-patient-tab:hover {
            background: rgba(255,255,255,0.1);
        }
        
        /* Openspace Logo Dropdown */
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
        }
        .openspace-logo:hover {
            background: rgba(255,255,255,0.1);
        }
        .openspace-logo .dropdown-arrow {
            font-size: 10px;
            margin-left: 4px;
            opacity: 0.7;
        }
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
        .openspace-menu a, .openspace-menu button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #333;
            font-size: 13px;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .openspace-menu a:hover, .openspace-menu button:hover {
            background: #f0f4f8;
        }
        .openspace-menu a i, .openspace-menu button i {
            width: 18px;
            text-align: center;
            color: #1a4a5e;
        }
        .openspace-menu .menu-divider {
            border-top: 1px solid #e0e0e0;
            margin: 5px 0;
        }
        .openspace-menu .menu-section {
            padding: 5px 15px;
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* Patient Search Modal */
        .patient-search-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
        }
        .patient-search-modal.show {
            display: flex;
        }
        .patient-search-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 500px;
            max-height: 70vh;
            overflow: hidden;
        }
        .patient-search-header {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .patient-search-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .patient-search-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
        }
        .patient-search-header .close-btn:hover {
            opacity: 1;
        }
        .patient-search-input {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .patient-search-input input {
            width: 100%;
            padding: 10px 15px;
            font-size: 14px;
            border: 2px solid #d0d8e0;
            border-radius: 4px;
        }
        .patient-search-input input:focus {
            outline: none;
            border-color: #1a4a5e;
        }
        .patient-search-results {
            max-height: 400px;
            overflow-y: auto;
        }
        .patient-search-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .patient-search-item:hover {
            background: #f0f8ff;
        }
        .patient-search-item .patient-icon {
            width: 40px;
            height: 40px;
            background: #e8f0f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a4a5e;
        }
        .patient-search-item .patient-info strong {
            display: block;
            color: #333;
        }
        .patient-search-item .patient-info span {
            font-size: 12px;
            color: #666;
        }
        
        /* Modal & Form Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        .modal.show, .modal[style*="flex"] {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.8;
        }
        .modal-close:hover {
            opacity: 1;
        }
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 15px 20px;
            background: #f5f5f5;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-control:focus {
            outline: none;
            border-color: #1a4a5e;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: #1a4a5e;
            color: white;
        }
        .btn-primary:hover {
            background: #0d3545;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-dialog {
            background: white;
            border-radius: 6px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #1a4a5e;
            color: white;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 14px;
        }
        .modal-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }
        .modal-body {
            padding: 15px;
            overflow-y: auto;
            flex: 1;
        }
        .modal-body p {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 12px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 15px;
            border-top: 1px solid #ddd;
        }
        
        /* Sidebar Items List */
        .sidebar-items-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .sidebar-item-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }
        .sidebar-item-option:hover {
            background: #f0f0f0;
        }
        .sidebar-item-option input[type="checkbox"] {
            margin: 0;
        }
        .sidebar-item-option i {
            width: 20px;
            text-align: center;
            color: #1a4a5e;
        }
    </style>
</head>
<body class="<?php echo !empty($open_patients) ? 'has-patient-tabs' : ''; ?> <?php echo $show_sidebar ? 'has-sidebar' : ''; ?>">
    <div class="app-layout">
        <!-- Main Header Bar -->
        <header class="openspace-header">
            <div class="openspace-logo-wrapper">
                <div class="openspace-logo" onclick="toggleOpenspaceMenu()">
                    <span class="logo-icon"><i class="fas fa-hospital"></i></span>
                    <span>Openspace</span>
                    <i class="fas fa-caret-down dropdown-arrow"></i>
                </div>
                <div class="openspace-menu" id="openspaceMenu">
                    <div class="openspace-menu-header">
                        <strong><?php echo APP_NAME; ?></strong>
                        Version <?php echo APP_VERSION; ?>
                    </div>
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <div class="menu-divider"></div>
                    <div class="menu-section">Help & Support</div>
                    <a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a>
                    <a href="about.php"><i class="fas fa-info-circle"></i> About Openspace</a>
                    <?php if ($is_admin): ?>
                    <div class="menu-divider"></div>
                    <div class="menu-section">Administration</div>
                    <a href="admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                    <?php endif; ?>
                    <div class="menu-divider"></div>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
            
            <div class="header-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search..." id="globalSearch" onkeypress="handleGlobalSearch(event)">
            </div>
            
            <div class="header-toolbar">
                <a href="home.php" class="toolbar-btn" title="Home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="patients.php" class="toolbar-btn" title="Patient Lists">
                    <i class="fas fa-users"></i>
                    <span>Patient Lists</span>
                </a>
                <a href="schedule.php" class="toolbar-btn" title="Schedule">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Schedule</span>
                </a>
                <a href="inbox.php" class="toolbar-btn" title="In Basket">
                    <i class="fas fa-inbox"></i>
                    <span>In Basket</span>
                </a>
                <button class="toolbar-btn" onclick="showPatientSearch()" title="Find Patient">
                    <i class="fas fa-search"></i>
                    <span>Find Patient</span>
                </button>
            </div>
            
            <div class="header-user">
                <?php if ($current_user): ?>
                <span class="user-name"><?php echo htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? 'User'); ?></span>
                <div class="header-dropdown">
                    <button class="header-dropdown-btn" onclick="toggleUserMenu()">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="header-dropdown-menu" id="userDropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <?php if ($is_admin): ?>
                        <div class="divider"></div>
                        <a href="admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                        <?php endif; ?>
                        <div class="divider"></div>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="toolbar-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </a>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Patient Tab Bar -->
        <?php if (!empty($open_patients)): ?>
        <div class="patient-tab-bar">
            <?php foreach ($open_patients as $idx => $pt): 
                $is_active = $current_patient && ($pt['id'] == $current_patient['id']);
            ?>
            <div class="patient-tab <?php echo $is_active ? 'active' : ''; ?>" 
                 onclick="openPatient(<?php echo $pt['id']; ?>)">
                <span><?php echo htmlspecialchars($pt['name'] ?? 'Unknown'); ?></span>
                <span class="close-tab" onclick="event.stopPropagation(); closePatientTab(<?php echo $pt['id']; ?>)">×</span>
            </div>
            <?php endforeach; ?>
            <button class="add-patient-tab" onclick="showPatientSearch()" title="Open another patient">+</button>
        </div>
        <?php endif; ?>
        
        <div class="app-body">
            <?php if ($show_sidebar && $sidebar_patient_id): ?>
            <!-- Sidebar Toggle Button -->
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Toggle Sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <!-- Left Sidebar Navigation (Patient Chart Only) -->
            <nav class="left-sidebar" id="leftSidebar">
                <div class="sidebar-nav">
                    <?php 
                    foreach ($sidebar_favorites as $key):
                        if (isset($patient_sidebar_items[$key])):
                            $item = $patient_sidebar_items[$key];
                            $is_active = ($current_tab === $key);
                    ?>
                    <a href="patient-chart.php?id=<?php echo $sidebar_patient_id; ?>&tab=<?php echo $key; ?>" 
                       class="nav-item <?php echo $is_active ? 'active' : ''; ?>" 
                       title="<?php echo $item['label']; ?> (Ctrl+Click to open in new window)"
                       onclick="return handleNavClick(event, this)">
                        <i class="fas <?php echo $item['icon']; ?> nav-icon"></i>
                        <span class="nav-label"><?php echo $item['label']; ?></span>
                    </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="nav-bottom">
                    <div class="nav-customize">
                        <div class="nav-item" onclick="openCustomizeSidebarModal()" title="Customize">
                            <i class="fas fa-cog nav-icon"></i>
                            <span class="nav-label">Customize</span>
                        </div>
                    </div>
                    <div class="nav-more">
                        <div class="nav-item" onclick="toggleMoreMenu(event)" title="More">
                            <i class="fas fa-chevron-right nav-icon"></i>
                            <span class="nav-label">More</span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- More Menu - Context Menu Style -->
            <div class="more-menu" id="moreMenu">
                <?php foreach ($more_menu_items as $item): ?>
                    <?php if (isset($item['divider'])): ?>
                        <div class="more-menu-divider"></div>
                    <?php elseif (isset($item['action'])): ?>
                        <div class="more-menu-item" onclick="handleMenuAction('<?php echo $item['action']; ?>')">
                            <i class="fas <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="more-menu-item" onclick="navigateToTab('<?php echo $item['tab']; ?>', event)">
                            <i class="fas <?php echo $item['icon']; ?>"></i>
                            <span><?php echo $item['label']; ?></span>
                            <?php if (isset($item['submenu'])): ?>
                            <span class="submenu-arrow"><i class="fas fa-chevron-right"></i></span>
                            <div class="submenu">
                                <?php foreach ($item['submenu'] as $sub): ?>
                                <div class="more-menu-item" onclick="event.stopPropagation(); navigateToTab('<?php echo $sub['tab']; ?>', event)">
                                    <span><?php echo $sub['label']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Main Content Wrapper -->
            <div class="main-content-wrapper">
                <div class="main-content">
                    <!-- Page content goes here -->
    <div class="patient-search-modal" id="patientSearchModal">
        <div class="patient-search-box">
            <div class="patient-search-header">
                <h3><i class="fas fa-search"></i> Find Patient</h3>
                <button class="close-btn" onclick="hidePatientSearch()">×</button>
            </div>
            <div class="patient-search-input">
                <input type="text" id="patientSearchInput" placeholder="Search by name, MRN, or DOB..." 
                       onkeyup="searchPatients(this.value)" autofocus>
            </div>
            <div class="patient-search-results" id="patientSearchResults">
                <!-- Results populated by JS -->
            </div>
        </div>
    </div>
    
    <?php if (function_exists('renderPatientVerificationModal')) echo renderPatientVerificationModal(); ?>

<script>
// Demo patients for search (in production, this would be an API call)
const demoPatients = [
    { id: 1, name: 'Smith, John', mrn: 'MRN000001', dob: '03/15/1955', room: '412-A' },
    { id: 2, name: 'Johnson, Mary', mrn: 'MRN000002', dob: '07/22/1948', room: '415-B' },
    { id: 3, name: 'Williams, Robert', mrn: 'MRN000003', dob: '11/08/1960', room: '420-A' },
    { id: 4, name: 'Davis, Linda', mrn: 'MRN000004', dob: '02/14/1972', room: '418-A' },
    { id: 5, name: 'Wilson, James', mrn: 'MRN000005', dob: '09/30/1945', room: '422-B' },
];

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

// More menu toggle
function toggleMoreMenu(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('moreMenu');
    menu.classList.toggle('show');
}

function closeMoreMenu() {
    document.getElementById('moreMenu').classList.remove('show');
}

// Sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('leftSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const isCollapsed = sidebar.classList.toggle('collapsed');
    toggle.classList.toggle('collapsed', isCollapsed);
    
    // Save preference
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Restore sidebar state on load
document.addEventListener('DOMContentLoaded', function() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.getElementById('leftSidebar')?.classList.add('collapsed');
        document.getElementById('sidebarToggle')?.classList.add('collapsed');
    }
});

// Navigate to patient chart tab
function navigateToTab(tab, event) {
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('id');
    if (patientId) {
        const url = 'patient-chart.php?id=' + patientId + '&tab=' + tab;
        // If Ctrl/Cmd key is held, open in new window/tab
        if (event && (event.ctrlKey || event.metaKey)) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }
    closeMoreMenu();
}

// Handle navigation click - support Ctrl+Click for new window
function handleNavClick(event, element) {
    // If Ctrl/Cmd key is held or middle mouse button, open in new window/tab
    if (event.ctrlKey || event.metaKey || event.button === 1) {
        event.preventDefault();
        window.open(element.href, '_blank');
        return false;
    }
    // Normal click - let default behavior happen
    return true;
}

// Handle menu actions (Customize, etc.)
function handleMenuAction(action) {
    closeMoreMenu();
    if (action === 'customize') {
        // Open customize sidebar modal
        openCustomizeSidebarModal();
    }
}

// Open customize sidebar modal
function openCustomizeSidebarModal() {
    // Create modal if it doesn't exist
    let modal = document.getElementById('customizeSidebarModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customizeSidebarModal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-dialog" style="max-width: 400px;">
                <div class="modal-header">
                    <h3>Customize Sidebar</h3>
                    <button class="close-btn" onclick="closeCustomizeSidebarModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Drag items to reorder. Check/uncheck to show/hide.</p>
                    <div id="sidebarItemsList" class="sidebar-items-list"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeCustomizeSidebarModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="saveSidebarCustomization()">Save</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Populate items list
    const itemsList = document.getElementById('sidebarItemsList');
    const allItems = <?php echo json_encode($patient_sidebar_items); ?>;
    const favorites = <?php echo json_encode($sidebar_favorites); ?>;
    
    let html = '';
    for (const [key, item] of Object.entries(allItems)) {
        const checked = favorites.includes(key) ? 'checked' : '';
        html += `
            <label class="sidebar-item-option">
                <input type="checkbox" name="sidebar_item" value="${key}" ${checked}>
                <i class="fas ${item.icon}"></i>
                <span>${item.label}</span>
            </label>
        `;
    }
    itemsList.innerHTML = html;
    
    modal.classList.add('show');
}

function closeCustomizeSidebarModal() {
    document.getElementById('customizeSidebarModal')?.classList.remove('show');
}

function saveSidebarCustomization() {
    const checkboxes = document.querySelectorAll('#sidebarItemsList input[type="checkbox"]:checked');
    const selectedItems = Array.from(checkboxes).map(cb => cb.value);
    
    fetch('api/update-sidebar-favorites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ favorites: selectedItems })
    }).then(() => {
        window.location.reload();
    }).catch(err => {
        console.error('Failed to save sidebar customization:', err);
    });
}

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
    
    const moreMenu = document.getElementById('moreMenu');
    const moreBtn = document.querySelector('.nav-more');
    if (moreMenu && moreBtn && !moreMenu.contains(event.target) && !moreBtn.contains(event.target)) {
        moreMenu.classList.remove('show');
    }
});

// Global search
function handleGlobalSearch(event) {
    if (event.key === 'Enter') {
        const query = document.getElementById('globalSearch').value;
        if (query.trim()) {
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        }
    }
}

// Patient Search Modal
function showPatientSearch() {
    document.getElementById('patientSearchModal').classList.add('show');
    document.getElementById('patientSearchInput').focus();
    searchPatients(''); // Show all initially
}

function hidePatientSearch() {
    document.getElementById('patientSearchModal').classList.remove('show');
    document.getElementById('patientSearchInput').value = '';
}

function searchPatients(query) {
    const results = document.getElementById('patientSearchResults');
    const filtered = demoPatients.filter(p => 
        p.name.toLowerCase().includes(query.toLowerCase()) ||
        p.mrn.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length === 0) {
        results.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">No patients found</div>';
        return;
    }
    
    results.innerHTML = filtered.map(p => `
        <div class="patient-search-item" onclick="addPatientTab(${p.id}, '${p.name}')">
            <div class="patient-icon"><i class="fas fa-user"></i></div>
            <div class="patient-info">
                <strong>${p.name}</strong>
                <span>${p.mrn} | DOB: ${p.dob} | Room: ${p.room}</span>
            </div>
        </div>
    `).join('');
}

// Add patient to tabs and navigate
function addPatientTab(patientId, patientName) {
    // Store in session via AJAX, then navigate
    fetch('api/add-patient-tab.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: patientId, name: patientName })
    }).then(() => {
        window.location.href = 'patient-chart.php?id=' + patientId;
    }).catch(() => {
        // If API fails, just navigate
        window.location.href = 'patient-chart.php?id=' + patientId;
    });
}

// Open patient chart
function openPatient(patientId) {
    window.location.href = 'patient-chart.php?id=' + patientId;
}

// Close patient tab
function closePatientTab(patientId) {
    fetch('api/close-patient-tab.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: patientId })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            // If we're on that patient's chart, go home
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('id') == patientId) {
                window.location.href = 'home.php';
            } else {
                window.location.reload();
            }
        }
    }).catch(() => {
        // Fallback: reload page
        window.location.reload();
    });
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hidePatientSearch();
        closeMoreMenu();
    }
});

// Close modal on background click
document.getElementById('patientSearchModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hidePatientSearch();
    }
});
</script>


<?php
/**
 * Admin Dashboard - User, Role, Theme, and System Management
 * Epic EHR Administration Interface
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/api.php';

$api = new ApiClient();
$section = $_GET['section'] ?? 'dashboard';

// Check admin access (simplified - in production use proper auth)
$isAdmin = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Epic EHR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/epic-styles.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .admin-header {
            background: linear-gradient(180deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-title .icon {
            font-size: 24px;
        }
        
        .admin-nav {
            display: flex;
            gap: 8px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .admin-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-nav a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .admin-container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 240px;
            background: #fff;
            border-right: 1px solid #ddd;
            padding: 16px 0;
        }
        
        .sidebar-section {
            margin-bottom: 8px;
        }
        
        .sidebar-section-title {
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            font-size: 13px;
        }
        
        .sidebar-link:hover {
            background: #f5f5f5;
        }
        
        .sidebar-link.active {
            background: #e3f2fd;
            color: #1565c0;
            border-right: 3px solid #1565c0;
        }
        
        .sidebar-link .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        /* Main Content */
        .admin-main {
            flex: 1;
            padding: 24px;
        }
        
        .admin-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Dashboard Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .stat-icon.blue { background: #e3f2fd; color: #1565c0; }
        .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.orange { background: #fff3e0; color: #e65100; }
        .stat-icon.purple { background: #f3e5f5; color: #7b1fa2; }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: #333;
        }
        
        .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
        
        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f5f5f5;
            font-weight: 600;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            border-bottom: 2px solid #ddd;
        }
        
        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .data-table tr:hover td {
            background: #f9f9f9;
        }
        
        .data-table .actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-icon.edit { background: #e3f2fd; color: #1565c0; }
        .btn-icon.delete { background: #ffebee; color: #c62828; }
        .btn-icon:hover { opacity: 0.8; }
        
        /* Status Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge.active { background: #e8f5e9; color: #2e7d32; }
        .badge.inactive { background: #ffebee; color: #c62828; }
        .badge.admin { background: #fff3e0; color: #e65100; }
        .badge.clinical { background: #e3f2fd; color: #1565c0; }
        
        /* Theme Editor */
        .theme-preview {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .preview-header {
            padding: 12px 20px;
            color: white;
            font-weight: 600;
        }
        
        .preview-patient-header {
            padding: 8px 20px;
            font-size: 13px;
        }
        
        .preview-content {
            padding: 20px;
            background: #fff;
        }
        
        .color-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        
        .color-input {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .color-input label {
            font-size: 12px;
            color: #666;
        }
        
        .color-input .input-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .color-input input[type="color"] {
            width: 40px;
            height: 32px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .color-input input[type="text"] {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Theme Presets */
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .preset-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .preset-card:hover {
            border-color: #1565c0;
        }
        
        .preset-card.selected {
            border-color: #1565c0;
            background: #e3f2fd;
        }
        
        .preset-colors {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }
        
        .preset-swatch {
            width: 24px;
            height: 24px;
            border-radius: 4px;
        }
        
        .preset-name {
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #1565c0;
            outline: none;
            box-shadow: 0 0 0 2px rgba(21,101,192,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #1565c0;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0d47a1;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-danger {
            background: #c62828;
            color: white;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: #fff;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 16px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        /* Tabs */
        .tab-nav {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            font-size: 13px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            color: #666;
        }
        
        .tab-btn:hover {
            color: #333;
        }
        
        .tab-btn.active {
            color: #1565c0;
            border-bottom-color: #1565c0;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-title">
            <span class="icon">⚙️</span>
            Epic EHR Administration
        </div>
        <nav class="admin-nav">
            <a href="<?= BASE_URL ?>" class="btn btn-secondary">← Back to EHR</a>
            <a href="?section=help">Help</a>
            <a href="#">Logout</a>
        </nav>
    </header>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-section">
                <div class="sidebar-section-title">General</div>
                <a href="?section=dashboard" class="sidebar-link <?= $section === 'dashboard' ? 'active' : '' ?>">
                    <span class="icon">📊</span> Dashboard
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">User Management</div>
                <a href="?section=users" class="sidebar-link <?= $section === 'users' ? 'active' : '' ?>">
                    <span class="icon">👥</span> Users
                </a>
                <a href="?section=roles" class="sidebar-link <?= $section === 'roles' ? 'active' : '' ?>">
                    <span class="icon">🔐</span> Roles & Permissions
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Organization</div>
                <a href="?section=organization" class="sidebar-link <?= $section === 'organization' ? 'active' : '' ?>">
                    <span class="icon">🏥</span> Organization
                </a>
                <a href="?section=departments" class="sidebar-link <?= $section === 'departments' ? 'active' : '' ?>">
                    <span class="icon">🏢</span> Departments
                </a>
                <a href="?section=beds" class="sidebar-link <?= $section === 'beds' ? 'active' : '' ?>">
                    <span class="icon">🛏️</span> Bed Management
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Appearance</div>
                <a href="?section=theme" class="sidebar-link <?= $section === 'theme' ? 'active' : '' ?>">
                    <span class="icon">🎨</span> Theme & Branding
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">System</div>
                <a href="?section=settings" class="sidebar-link <?= $section === 'settings' ? 'active' : '' ?>">
                    <span class="icon">⚙️</span> System Settings
                </a>
                <a href="?section=audit" class="sidebar-link <?= $section === 'audit' ? 'active' : '' ?>">
                    <span class="icon">📋</span> Audit Logs
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <?php if ($section === 'dashboard'): ?>
            <!-- Dashboard -->
            <h1 style="font-size: 24px; margin-bottom: 24px;">Administration Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-value" id="userCount">42</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">🏥</div>
                    <div class="stat-value" id="deptCount">8</div>
                    <div class="stat-label">Departments</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">🛏️</div>
                    <div class="stat-value" id="bedCount">156</div>
                    <div class="stat-label">Total Beds</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">📊</div>
                    <div class="stat-value" id="patientCount">89</div>
                    <div class="stat-label">Current Patients</div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Recent Activity</span>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivity">
                            <tr>
                                <td>2 min ago</td>
                                <td>CINDY W.</td>
                                <td>Login</td>
                                <td>Successful login from 192.168.1.45</td>
                            </tr>
                            <tr>
                                <td>15 min ago</td>
                                <td>DR. SMITH</td>
                                <td>Order Signed</td>
                                <td>Signed medication order for patient MRN 4802001</td>
                            </tr>
                            <tr>
                                <td>32 min ago</td>
                                <td>ADMIN</td>
                                <td>User Created</td>
                                <td>Created new user: JOHNSON, RN</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($section === 'users'): ?>
            <!-- Users Management -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="font-size: 24px; margin: 0;">User Management</h1>
                <button class="btn btn-primary" onclick="openModal('addUser')">+ Add User</button>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">All Users</span>
                    <input type="text" placeholder="Search users..." style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Williams, Cindy</strong></td>
                                <td>cwilliams</td>
                                <td><span class="badge clinical">RN</span></td>
                                <td>Surgical Specialty</td>
                                <td><span class="badge active">Active</span></td>
                                <td>Today 1:48 PM</td>
                                <td class="actions">
                                    <button class="btn-icon edit" title="Edit">✏️</button>
                                    <button class="btn-icon delete" title="Deactivate">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Zeller, Timothy</strong></td>
                                <td>tzeller</td>
                                <td><span class="badge clinical">Physician</span></td>
                                <td>Internal Medicine</td>
                                <td><span class="badge active">Active</span></td>
                                <td>Today 11:23 AM</td>
                                <td class="actions">
                                    <button class="btn-icon edit" title="Edit">✏️</button>
                                    <button class="btn-icon delete" title="Deactivate">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Johnson, Sarah</strong></td>
                                <td>sjohnson</td>
                                <td><span class="badge clinical">NP</span></td>
                                <td>Emergency</td>
                                <td><span class="badge active">Active</span></td>
                                <td>Yesterday 4:15 PM</td>
                                <td class="actions">
                                    <button class="btn-icon edit" title="Edit">✏️</button>
                                    <button class="btn-icon delete" title="Deactivate">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Admin, System</strong></td>
                                <td>admin</td>
                                <td><span class="badge admin">Super Admin</span></td>
                                <td>IT</td>
                                <td><span class="badge active">Active</span></td>
                                <td>Today 9:00 AM</td>
                                <td class="actions">
                                    <button class="btn-icon edit" title="Edit">✏️</button>
                                    <button class="btn-icon delete" title="Deactivate" disabled>🗑️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($section === 'theme'): ?>
            <!-- Theme & Branding -->
            <h1 style="font-size: 24px; margin-bottom: 24px;">Theme & Branding</h1>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Color Presets</span>
                </div>
                <div class="card-body">
                    <div class="preset-grid">
                        <div class="preset-card selected" onclick="selectPreset('epic')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #c00;"></div>
                                <div class="preset-swatch" style="background: #0078d4;"></div>
                                <div class="preset-swatch" style="background: #e8f4fd;"></div>
                            </div>
                            <div class="preset-name">Epic Classic</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('blue')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #1976d2;"></div>
                                <div class="preset-swatch" style="background: #0288d1;"></div>
                                <div class="preset-swatch" style="background: #e3f2fd;"></div>
                            </div>
                            <div class="preset-name">Modern Blue</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('green')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #2e7d32;"></div>
                                <div class="preset-swatch" style="background: #388e3c;"></div>
                                <div class="preset-swatch" style="background: #e8f5e9;"></div>
                            </div>
                            <div class="preset-name">Forest Green</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('purple')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #7b1fa2;"></div>
                                <div class="preset-swatch" style="background: #8e24aa;"></div>
                                <div class="preset-swatch" style="background: #f3e5f5;"></div>
                            </div>
                            <div class="preset-name">Purple Health</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('navy')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #283593;"></div>
                                <div class="preset-swatch" style="background: #3949ab;"></div>
                                <div class="preset-swatch" style="background: #e8eaf6;"></div>
                            </div>
                            <div class="preset-name">Navy Medical</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('teal')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #00796b;"></div>
                                <div class="preset-swatch" style="background: #00897b;"></div>
                                <div class="preset-swatch" style="background: #e0f2f1;"></div>
                            </div>
                            <div class="preset-name">Teal Care</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('orange')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #e65100;"></div>
                                <div class="preset-swatch" style="background: #f57c00;"></div>
                                <div class="preset-swatch" style="background: #fff3e0;"></div>
                            </div>
                            <div class="preset-name">Orange Vibrant</div>
                        </div>
                        <div class="preset-card" onclick="selectPreset('dark')">
                            <div class="preset-colors">
                                <div class="preset-swatch" style="background: #212121;"></div>
                                <div class="preset-swatch" style="background: #424242;"></div>
                                <div class="preset-swatch" style="background: #424242;"></div>
                            </div>
                            <div class="preset-name">Dark Mode</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Live Preview</span>
                </div>
                <div class="card-body">
                    <div class="theme-preview">
                        <div class="preview-header" id="previewHeader" style="background: #c00;">
                            <span style="font-size: 16px;">Epic</span> ▼ &nbsp;&nbsp; Patient Lookup &nbsp; OR Cases &nbsp; House Census
                            <span style="float: right;">CINDY W. &nbsp; EpicCare</span>
                        </div>
                        <div class="preview-patient-header" id="previewPatientHeader" style="background: #e8f4fd;">
                            <strong style="color: #0066cc;">Smith, Mary</strong> &nbsp; MRN: 4802001 &nbsp; DOB: 03/15/1985 &nbsp; 
                            <span style="color: #c00;">⚠ Allergies: Penicillin, Codeine</span>
                        </div>
                        <div class="preview-content">
                            <div style="display: flex; gap: 20px;">
                                <div style="width: 150px; background: #f5f5f5; padding: 12px; border-radius: 4px;">
                                    <div style="padding: 6px; margin-bottom: 4px;">Summary</div>
                                    <div style="padding: 6px; background: #e3f2fd; border-radius: 4px; margin-bottom: 4px;">Chart Review</div>
                                    <div style="padding: 6px; margin-bottom: 4px;">Results</div>
                                    <div style="padding: 6px; margin-bottom: 4px;">MAR</div>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 12px 0;">Patient Chart</h3>
                                    <button id="previewBtn" style="background: #0078d4; color: white; padding: 8px 16px; border: none; border-radius: 4px; margin-right: 8px;">Primary Button</button>
                                    <button style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px;">Secondary</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Custom Colors</span>
                </div>
                <div class="card-body">
                    <div class="color-grid">
                        <div class="color-input">
                            <label>Primary Color (Header)</label>
                            <div class="input-group">
                                <input type="color" id="primaryColor" value="#cc0000" onchange="updateTheme()">
                                <input type="text" id="primaryColorText" value="#cc0000" onchange="updateColorFromText('primary')">
                            </div>
                        </div>
                        <div class="color-input">
                            <label>Secondary Color (Buttons)</label>
                            <div class="input-group">
                                <input type="color" id="secondaryColor" value="#0078d4" onchange="updateTheme()">
                                <input type="text" id="secondaryColorText" value="#0078d4" onchange="updateColorFromText('secondary')">
                            </div>
                        </div>
                        <div class="color-input">
                            <label>Patient Header Background</label>
                            <div class="input-group">
                                <input type="color" id="patientHeaderBg" value="#e8f4fd" onchange="updateTheme()">
                                <input type="text" id="patientHeaderBgText" value="#e8f4fd" onchange="updateColorFromText('patientHeader')">
                            </div>
                        </div>
                        <div class="color-input">
                            <label>Navigation Background</label>
                            <div class="input-group">
                                <input type="color" id="navBgColor" value="#f5f5f5" onchange="updateTheme()">
                                <input type="text" id="navBgColorText" value="#f5f5f5" onchange="updateColorFromText('navBg')">
                            </div>
                        </div>
                        <div class="color-input">
                            <label>Navigation Active</label>
                            <div class="input-group">
                                <input type="color" id="navActiveColor" value="#e3f2fd" onchange="updateTheme()">
                                <input type="text" id="navActiveColorText" value="#e3f2fd" onchange="updateColorFromText('navActive')">
                            </div>
                        </div>
                        <div class="color-input">
                            <label>Accent Color</label>
                            <div class="input-group">
                                <input type="color" id="accentColor" value="#107c10" onchange="updateTheme()">
                                <input type="text" id="accentColorText" value="#107c10" onchange="updateColorFromText('accent')">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Logo & Branding</span>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Organization Name</label>
                            <input type="text" value="General Hospital" id="orgName">
                        </div>
                        <div class="form-group">
                            <label>Short Name (Header)</label>
                            <input type="text" value="GH" id="orgShortName">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Logo URL</label>
                        <input type="text" placeholder="https://example.com/logo.png" id="logoUrl">
                    </div>
                    <div class="form-group">
                        <label>Favicon URL</label>
                        <input type="text" placeholder="https://example.com/favicon.ico" id="faviconUrl">
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button class="btn btn-secondary">Reset to Default</button>
                <button class="btn btn-primary" onclick="saveTheme()">Save Theme</button>
            </div>
            
            <?php elseif ($section === 'roles'): ?>
            <!-- Roles & Permissions -->
            <h1 style="font-size: 24px; margin-bottom: 24px;">Roles & Permissions</h1>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">System Roles</span>
                    <button class="btn btn-primary" onclick="openModal('addRole')">+ Add Role</button>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Type</th>
                                <th>Access Level</th>
                                <th>Users</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Super Administrator</strong></td>
                                <td><span class="badge admin">System</span></td>
                                <td>10</td>
                                <td>1</td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Physician (MD/DO)</strong></td>
                                <td><span class="badge clinical">Clinical</span></td>
                                <td>8</td>
                                <td>12</td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Registered Nurse</strong></td>
                                <td><span class="badge clinical">Clinical</span></td>
                                <td>6</td>
                                <td>24</td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Nurse Practitioner</strong></td>
                                <td><span class="badge clinical">Clinical</span></td>
                                <td>7</td>
                                <td>5</td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Pharmacist</strong></td>
                                <td><span class="badge clinical">Clinical</span></td>
                                <td>7</td>
                                <td>4</td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($section === 'departments'): ?>
            <!-- Departments -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h1 style="font-size: 24px; margin: 0;">Department Management</h1>
                <button class="btn btn-primary" onclick="openModal('addDept')">+ Add Department</button>
            </div>
            
            <div class="admin-card">
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Unit Code</th>
                                <th>Type</th>
                                <th>Floor/Building</th>
                                <th>Beds</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Surgical Specialty</strong></td>
                                <td>SURG</td>
                                <td>Nursing Unit</td>
                                <td>3rd Floor / Main</td>
                                <td>24</td>
                                <td><span class="badge active">Active</span></td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Emergency Department</strong></td>
                                <td>ED</td>
                                <td>Emergency</td>
                                <td>1st Floor / Main</td>
                                <td>18</td>
                                <td><span class="badge active">Active</span></td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Medical ICU</strong></td>
                                <td>MICU</td>
                                <td>ICU</td>
                                <td>4th Floor / Main</td>
                                <td>12</td>
                                <td><span class="badge active">Active</span></td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Labor & Delivery</strong></td>
                                <td>L&D</td>
                                <td>OB/GYN</td>
                                <td>2nd Floor / Women's</td>
                                <td>8</td>
                                <td><span class="badge active">Active</span></td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Pediatrics</strong></td>
                                <td>PEDS</td>
                                <td>Nursing Unit</td>
                                <td>2nd Floor / Main</td>
                                <td>16</td>
                                <td><span class="badge active">Active</span></td>
                                <td class="actions">
                                    <button class="btn-icon edit">✏️</button>
                                    <button class="btn-icon delete">🗑️</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($section === 'audit'): ?>
            <!-- Audit Logs -->
            <h1 style="font-size: 24px; margin-bottom: 24px;">Audit Logs</h1>
            
            <div class="admin-card">
                <div class="card-header">
                    <span class="card-title">Activity Log</span>
                    <div style="display: flex; gap: 8px;">
                        <select style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <option>All Actions</option>
                            <option>Login</option>
                            <option>View</option>
                            <option>Create</option>
                            <option>Update</option>
                            <option>Delete</option>
                        </select>
                        <input type="date" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <button class="btn btn-secondary">Export</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>12/23/2024 1:48:23 PM</td>
                                <td>cwilliams</td>
                                <td>View</td>
                                <td>Patient</td>
                                <td>Viewed patient MRN 4802001</td>
                                <td>192.168.1.45</td>
                            </tr>
                            <tr>
                                <td>12/23/2024 1:45:12 PM</td>
                                <td>tzeller</td>
                                <td>Sign</td>
                                <td>Order</td>
                                <td>Signed medication order #12345</td>
                                <td>192.168.1.67</td>
                            </tr>
                            <tr>
                                <td>12/23/2024 1:30:45 PM</td>
                                <td>admin</td>
                                <td>Update</td>
                                <td>Theme</td>
                                <td>Updated organization theme colors</td>
                                <td>192.168.1.10</td>
                            </tr>
                            <tr>
                                <td>12/23/2024 1:15:00 PM</td>
                                <td>sjohnson</td>
                                <td>Create</td>
                                <td>Note</td>
                                <td>Created progress note for MRN E1404907</td>
                                <td>192.168.1.89</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Add New User</span>
                <button class="modal-close" onclick="closeModal('addUser')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" id="newUserFirstName" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" id="newUserLastName" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" id="newUserUsername" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="newUserEmail">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select id="newUserRole">
                        <option value="">Select Role...</option>
                        <option value="physician">Physician (MD/DO)</option>
                        <option value="np">Nurse Practitioner</option>
                        <option value="pa">Physician Assistant</option>
                        <option value="rn">Registered Nurse</option>
                        <option value="lpn">Licensed Practical Nurse</option>
                        <option value="cna">Certified Nursing Assistant</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="newUserDept">
                        <option value="">Select Department...</option>
                        <option value="surg">Surgical Specialty</option>
                        <option value="ed">Emergency Department</option>
                        <option value="micu">Medical ICU</option>
                        <option value="ld">Labor & Delivery</option>
                        <option value="peds">Pediatrics</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" id="newUserPassword">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" id="newUserPasswordConfirm">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addUser')">Cancel</button>
                <button class="btn btn-primary" onclick="createUser()">Create User</button>
            </div>
        </div>
    </div>
    
    <script>
        // Theme presets
        const presets = {
            epic: { primary: '#cc0000', secondary: '#0078d4', patientHeader: '#e8f4fd' },
            blue: { primary: '#1976d2', secondary: '#0288d1', patientHeader: '#e3f2fd' },
            green: { primary: '#2e7d32', secondary: '#388e3c', patientHeader: '#e8f5e9' },
            purple: { primary: '#7b1fa2', secondary: '#8e24aa', patientHeader: '#f3e5f5' },
            navy: { primary: '#283593', secondary: '#3949ab', patientHeader: '#e8eaf6' },
            teal: { primary: '#00796b', secondary: '#00897b', patientHeader: '#e0f2f1' },
            orange: { primary: '#e65100', secondary: '#f57c00', patientHeader: '#fff3e0' },
            dark: { primary: '#212121', secondary: '#424242', patientHeader: '#424242' }
        };
        
        function selectPreset(presetName) {
            const preset = presets[presetName];
            
            // Update color inputs
            document.getElementById('primaryColor').value = preset.primary;
            document.getElementById('primaryColorText').value = preset.primary;
            document.getElementById('secondaryColor').value = preset.secondary;
            document.getElementById('secondaryColorText').value = preset.secondary;
            document.getElementById('patientHeaderBg').value = preset.patientHeader;
            document.getElementById('patientHeaderBgText').value = preset.patientHeader;
            
            // Update preset selection
            document.querySelectorAll('.preset-card').forEach(card => card.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Update preview
            updateTheme();
        }
        
        function updateTheme() {
            const primary = document.getElementById('primaryColor').value;
            const secondary = document.getElementById('secondaryColor').value;
            const patientHeader = document.getElementById('patientHeaderBg').value;
            
            // Update text inputs
            document.getElementById('primaryColorText').value = primary;
            document.getElementById('secondaryColorText').value = secondary;
            document.getElementById('patientHeaderBgText').value = patientHeader;
            
            // Update preview
            document.getElementById('previewHeader').style.background = primary;
            document.getElementById('previewPatientHeader').style.background = patientHeader;
            document.getElementById('previewBtn').style.background = secondary;
        }
        
        function updateColorFromText(type) {
            const mapping = {
                primary: ['primaryColor', 'primaryColorText'],
                secondary: ['secondaryColor', 'secondaryColorText'],
                patientHeader: ['patientHeaderBg', 'patientHeaderBgText'],
                navBg: ['navBgColor', 'navBgColorText'],
                navActive: ['navActiveColor', 'navActiveColorText'],
                accent: ['accentColor', 'accentColorText']
            };
            
            const [colorInput, textInput] = mapping[type];
            document.getElementById(colorInput).value = document.getElementById(textInput).value;
            updateTheme();
        }
        
        function saveTheme() {
            const themeData = {
                primary_color: document.getElementById('primaryColor').value,
                secondary_color: document.getElementById('secondaryColor').value,
                patient_header_bg: document.getElementById('patientHeaderBg').value,
                nav_bg_color: document.getElementById('navBgColor').value,
                nav_active_bg: document.getElementById('navActiveColor').value,
                accent_color: document.getElementById('accentColor').value,
                org_name: document.getElementById('orgName').value,
                org_short_name: document.getElementById('orgShortName').value,
                logo_url: document.getElementById('logoUrl').value
            };
            
            // Send to API
            fetch('<?= API_BASE_URL ?>/admin/themes/1', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(themeData)
            })
            .then(response => response.json())
            .then(data => {
                alert('Theme saved successfully!');
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Theme saved (demo mode)');
            });
        }
        
        // Modal functions
        function openModal(type) {
            document.getElementById(type + 'Modal').classList.add('active');
        }
        
        function closeModal(type) {
            document.getElementById(type + 'Modal').classList.remove('active');
        }
        
        function createUser() {
            const userData = {
                first_name: document.getElementById('newUserFirstName').value,
                last_name: document.getElementById('newUserLastName').value,
                username: document.getElementById('newUserUsername').value,
                email: document.getElementById('newUserEmail').value,
                role_id: document.getElementById('newUserRole').value,
                password: document.getElementById('newUserPassword').value
            };
            
            // Validate
            if (!userData.first_name || !userData.last_name || !userData.username) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Send to API
            fetch('<?= API_BASE_URL ?>/admin/users', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                alert('User created successfully!');
                closeModal('addUser');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('User created (demo mode)');
                closeModal('addUser');
            });
        }
        
        // Load dashboard stats
        function loadDashboardStats() {
            // In production, fetch from API
            // For now, using static values
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
        });
    </script>
</body>
</html>

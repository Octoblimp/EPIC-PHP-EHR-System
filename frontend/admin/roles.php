<?php
/**
 * Role & Permission Management
 * Granular permission control and custom role builder
 */
session_start();

// Check authentication
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ../login.php');
    exit;
}

// Check admin permission
$user = $_SESSION['user'] ?? [];
$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'administrator']);

if (!$is_admin) {
    header('Location: ../home.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$pageTitle = 'Role & Permission Management';

// Include admin header
include 'includes/admin-header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #004499;
            --accent-color: #00aa55;
            --warning-color: #ff9900;
            --danger-color: #cc0000;
            --bg-color: #f5f7fa;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            padding: 0;
            margin: 0;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-header h1 {
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-nav {
            display: flex;
            gap: 20px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
        }
        
        .admin-nav a:hover, .admin-nav a.active {
            opacity: 1;
        }
        
        .admin-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: calc(100vh - 70px);
        }
        
        /* Roles List */
        .roles-panel {
            background: white;
            border-right: 1px solid var(--border-color);
            padding: 20px;
        }
        
        .roles-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .roles-header h2 {
            font-size: 16px;
            margin: 0;
        }
        
        .btn-add-role {
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .roles-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .role-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        
        .role-item:hover {
            background: var(--bg-color);
        }
        
        .role-item.active {
            background: #e6f2ff;
            border-color: var(--primary-color);
        }
        
        .role-item.system {
            position: relative;
        }
        
        .role-item.system::after {
            content: 'System';
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 10px;
            background: var(--secondary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .role-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .role-description {
            font-size: 12px;
            color: #666;
        }
        
        .role-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 11px;
            color: #999;
        }
        
        /* Permissions Panel */
        .permissions-panel {
            padding: 30px;
        }
        
        .permissions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .permissions-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .permissions-title h2 {
            margin: 0;
        }
        
        .role-badge {
            padding: 5px 12px;
            background: var(--accent-color);
            color: white;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .role-badge.inactive {
            background: #999;
        }
        
        .permissions-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        
        .btn-secondary {
            background: var(--bg-color);
            color: #333;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        /* Role Form */
        .role-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        
        /* Permission Categories */
        .permission-categories {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .permission-category {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .category-header {
            padding: 15px 20px;
            background: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-header h3 {
            font-size: 14px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-header i {
            color: var(--primary-color);
        }
        
        .category-toggle {
            padding: 5px 10px;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .category-body {
            padding: 15px 20px;
        }
        
        .permission-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .permission-item:last-child {
            border-bottom: none;
        }
        
        .permission-checkbox {
            margin-right: 15px;
            margin-top: 3px;
        }
        
        .permission-checkbox input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .permission-info {
            flex: 1;
        }
        
        .permission-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .permission-code {
            font-size: 11px;
            color: #999;
            font-family: monospace;
        }
        
        .permission-description {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
        
        .permission-tags {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        
        .permission-tag {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            background: #f0f0f0;
        }
        
        .permission-tag.phi {
            background: #ffe6e6;
            color: var(--danger-color);
        }
        
        .permission-tag.audit {
            background: #e6f7ff;
            color: var(--primary-color);
        }
        
        .permission-tag.critical {
            background: #fff3e6;
            color: var(--warning-color);
        }
        
        /* Search */
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .modal-close {
            width: 30px;
            height: 30px;
            border: none;
            background: var(--bg-color);
            border-radius: 50%;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .roles-panel {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            
            .permission-categories {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-container">
        <!-- Roles List Panel -->
        <aside class="roles-panel">
            <div class="roles-header">
                <h2>Roles</h2>
                <button class="btn-add-role" onclick="showCreateRoleModal()">
                    <i class="bi bi-plus"></i> New Role
                </button>
            </div>
            
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search roles..." id="roleSearch" onkeyup="filterRoles()">
            </div>
            
            <ul class="roles-list" id="rolesList">
                <!-- System Roles -->
                <li class="role-item system active" data-role-id="1" onclick="selectRole(1)">
                    <div class="role-name">System Administrator</div>
                    <div class="role-description">Full system access with all permissions</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 150 permissions</span>
                        <span><i class="bi bi-people"></i> 3 users</span>
                    </div>
                </li>
                
                <li class="role-item system" data-role-id="2" onclick="selectRole(2)">
                    <div class="role-name">Physician</div>
                    <div class="role-description">Clinical access with prescribing privileges</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 85 permissions</span>
                        <span><i class="bi bi-people"></i> 24 users</span>
                    </div>
                </li>
                
                <li class="role-item system" data-role-id="3" onclick="selectRole(3)">
                    <div class="role-name">Nurse (RN)</div>
                    <div class="role-description">Clinical documentation and medication administration</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 65 permissions</span>
                        <span><i class="bi bi-people"></i> 45 users</span>
                    </div>
                </li>
                
                <li class="role-item system" data-role-id="4" onclick="selectRole(4)">
                    <div class="role-name">Medical Assistant</div>
                    <div class="role-description">Basic clinical support and vitals documentation</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 35 permissions</span>
                        <span><i class="bi bi-people"></i> 32 users</span>
                    </div>
                </li>
                
                <li class="role-item system" data-role-id="5" onclick="selectRole(5)">
                    <div class="role-name">Front Desk / Registration</div>
                    <div class="role-description">Patient registration and scheduling</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 25 permissions</span>
                        <span><i class="bi bi-people"></i> 18 users</span>
                    </div>
                </li>
                
                <li class="role-item system" data-role-id="6" onclick="selectRole(6)">
                    <div class="role-name">Billing Specialist</div>
                    <div class="role-description">Claims, payments, and financial access</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 30 permissions</span>
                        <span><i class="bi bi-people"></i> 8 users</span>
                    </div>
                </li>
                
                <!-- Custom Roles -->
                <li class="role-item" data-role-id="101" onclick="selectRole(101)">
                    <div class="role-name">Lab Technician</div>
                    <div class="role-description">Laboratory results and specimen management</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 22 permissions</span>
                        <span><i class="bi bi-people"></i> 12 users</span>
                    </div>
                </li>
                
                <li class="role-item" data-role-id="102" onclick="selectRole(102)">
                    <div class="role-name">Pharmacy Staff</div>
                    <div class="role-description">Medication dispensing and inventory</div>
                    <div class="role-stats">
                        <span><i class="bi bi-key"></i> 28 permissions</span>
                        <span><i class="bi bi-people"></i> 6 users</span>
                    </div>
                </li>
            </ul>
        </aside>

        <!-- Permissions Panel -->
        <main class="permissions-panel">
            <div class="permissions-header">
                <div class="permissions-title">
                    <h2 id="selectedRoleName">System Administrator</h2>
                    <span class="role-badge" id="roleBadge">Active</span>
                </div>
                <div class="permissions-actions">
                    <button class="btn btn-secondary" onclick="duplicateRole()">
                        <i class="bi bi-copy"></i> Duplicate
                    </button>
                    <button class="btn btn-primary" onclick="saveRole()">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </div>

            <!-- Role Basic Info -->
            <div class="role-form">
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" id="roleName" value="System Administrator">
                </div>
                <div class="form-group">
                    <label>Role Code</label>
                    <input type="text" id="roleCode" value="SYSADMIN" readonly>
                </div>
                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea id="roleDescription" rows="2">Full system access with all administrative permissions. Can manage users, roles, and system configuration.</textarea>
                </div>
                <div class="form-group">
                    <label>Role Level</label>
                    <select id="roleLevel">
                        <option value="10" selected>Level 10 - System Admin</option>
                        <option value="8">Level 8 - Department Admin</option>
                        <option value="6">Level 6 - Senior Staff</option>
                        <option value="4">Level 4 - Staff</option>
                        <option value="2">Level 2 - Limited Access</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="roleStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Permissions by Category -->
            <div class="permission-categories">
                <!-- Patient Access -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-person-badge"></i> Patient Access</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'patient')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="patients.view">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Patients</div>
                                <div class="permission-code">patients.view</div>
                                <div class="permission-description">Search and view patient demographics</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="patients.create">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Create Patients</div>
                                <div class="permission-code">patients.create</div>
                                <div class="permission-description">Register new patients in the system</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="patients.edit">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Edit Patients</div>
                                <div class="permission-code">patients.edit</div>
                                <div class="permission-description">Modify patient demographics and information</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="patients.merge">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Merge Patients</div>
                                <div class="permission-code">patients.merge</div>
                                <div class="permission-description">Merge duplicate patient records</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag critical">Critical</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Documentation -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-journal-medical"></i> Clinical Documentation</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'clinical')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="notes.view">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Clinical Notes</div>
                                <div class="permission-code">notes.view</div>
                                <div class="permission-description">Read clinical documentation</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="notes.create">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Create Clinical Notes</div>
                                <div class="permission-code">notes.create</div>
                                <div class="permission-description">Write new clinical documentation</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="notes.sign">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Sign Notes</div>
                                <div class="permission-code">notes.sign</div>
                                <div class="permission-description">Electronically sign clinical documentation</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="notes.amend">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Amend Notes</div>
                                <div class="permission-code">notes.amend</div>
                                <div class="permission-description">Add amendments to signed notes</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medications -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-capsule"></i> Medications</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'meds')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="medications.view">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Medications</div>
                                <div class="permission-code">medications.view</div>
                                <div class="permission-description">View medication lists and orders</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="medications.prescribe">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Prescribe Medications</div>
                                <div class="permission-code">medications.prescribe</div>
                                <div class="permission-description">Order new medications (requires prescribing license)</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag critical">Critical</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="medications.administer">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Administer Medications</div>
                                <div class="permission-code">medications.administer</div>
                                <div class="permission-description">Document medication administration</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="medications.controlled">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Controlled Substances</div>
                                <div class="permission-code">medications.controlled</div>
                                <div class="permission-description">Prescribe Schedule II-V controlled substances</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag critical">Critical</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-calendar"></i> Scheduling</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'scheduling')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="appointments.view">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Appointments</div>
                                <div class="permission-code">appointments.view</div>
                                <div class="permission-description">View appointment schedules</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="appointments.create">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Create Appointments</div>
                                <div class="permission-code">appointments.create</div>
                                <div class="permission-description">Schedule new appointments</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="appointments.edit">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Edit Appointments</div>
                                <div class="permission-code">appointments.edit</div>
                                <div class="permission-description">Modify or cancel appointments</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="schedules.manage">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Manage Provider Schedules</div>
                                <div class="permission-code">schedules.manage</div>
                                <div class="permission-description">Configure provider availability templates</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Billing -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-credit-card"></i> Billing</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'billing')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="billing.view">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Billing</div>
                                <div class="permission-code">billing.view</div>
                                <div class="permission-description">View charges, claims, and payments</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="billing.create">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Create Charges</div>
                                <div class="permission-code">billing.create</div>
                                <div class="permission-description">Post charges to patient accounts</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="billing.submit_claims">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Submit Claims</div>
                                <div class="permission-code">billing.submit_claims</div>
                                <div class="permission-description">Submit insurance claims</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="billing.post_payments">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Post Payments</div>
                                <div class="permission-code">billing.post_payments</div>
                                <div class="permission-description">Post payments and adjustments</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Administration -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-shield-lock"></i> Administration</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'admin')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="admin.access">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Admin Panel Access</div>
                                <div class="permission-code">admin.access</div>
                                <div class="permission-description">Access administrative functions</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="admin.users">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Manage Users</div>
                                <div class="permission-code">admin.users</div>
                                <div class="permission-description">Create, edit, and deactivate user accounts</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="admin.roles">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Manage Roles</div>
                                <div class="permission-code">admin.roles</div>
                                <div class="permission-description">Create and modify roles and permissions</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="admin.audit_log">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Audit Log</div>
                                <div class="permission-code">admin.audit_log</div>
                                <div class="permission-description">Access system audit trail</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="admin.system_config">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">System Configuration</div>
                                <div class="permission-code">admin.system_config</div>
                                <div class="permission-description">Modify system settings and configuration</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screen Access -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-window-stack"></i> Screen Access</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'screens')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.home">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Home Dashboard</div>
                                <div class="permission-code">screens.home</div>
                                <div class="permission-description">Access the main home dashboard</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.schedule">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Schedule</div>
                                <div class="permission-code">screens.schedule</div>
                                <div class="permission-description">Access the appointment schedule</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.patient_search">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Patient Search</div>
                                <div class="permission-code">screens.patient_search</div>
                                <div class="permission-description">Access patient lookup/search</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.patient_chart">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Patient Chart</div>
                                <div class="permission-code">screens.patient_chart</div>
                                <div class="permission-description">Access patient chart/demographics</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.inbox">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">In Basket / Inbox</div>
                                <div class="permission-code">screens.inbox</div>
                                <div class="permission-description">Access messaging and In Basket</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.orders">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Orders</div>
                                <div class="permission-code">screens.orders</div>
                                <div class="permission-description">Access order entry and results</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.reports">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Reports</div>
                                <div class="permission-code">screens.reports</div>
                                <div class="permission-description">Access clinical and administrative reports</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.billing">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Billing</div>
                                <div class="permission-code">screens.billing</div>
                                <div class="permission-description">Access billing and claims screens</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.admin">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Admin Panel</div>
                                <div class="permission-code">screens.admin</div>
                                <div class="permission-description">Access administrative dashboard</div>
                                <div class="permission-tags">
                                    <span class="permission-tag critical">Critical</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="screens.settings">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">User Settings</div>
                                <div class="permission-code">screens.settings</div>
                                <div class="permission-description">Access personal settings and profile</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lab & Results -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-droplet"></i> Lab & Results</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'lab')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="lab.view_results">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">View Lab Results</div>
                                <div class="permission-code">lab.view_results</div>
                                <div class="permission-description">View laboratory test results</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="lab.order_tests">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Order Lab Tests</div>
                                <div class="permission-code">lab.order_tests</div>
                                <div class="permission-description">Order laboratory tests</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="lab.enter_results">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Enter Lab Results</div>
                                <div class="permission-code">lab.enter_results</div>
                                <div class="permission-description">Enter and verify lab results</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="lab.manage_specimens">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Manage Specimens</div>
                                <div class="permission-code">lab.manage_specimens</div>
                                <div class="permission-description">Track and manage lab specimens</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports & Analytics -->
                <div class="permission-category">
                    <div class="category-header">
                        <h3><i class="bi bi-graph-up"></i> Reports & Analytics</h3>
                        <button class="category-toggle" onclick="toggleCategory(this, 'reports')">Select All</button>
                    </div>
                    <div class="category-body">
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="reports.clinical">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Clinical Reports</div>
                                <div class="permission-code">reports.clinical</div>
                                <div class="permission-description">Access clinical quality and outcome reports</div>
                                <div class="permission-tags">
                                    <span class="permission-tag phi">PHI</span>
                                </div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="reports.operational">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Operational Reports</div>
                                <div class="permission-code">reports.operational</div>
                                <div class="permission-description">Access scheduling and workflow reports</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="reports.financial">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Financial Reports</div>
                                <div class="permission-code">reports.financial</div>
                                <div class="permission-description">Access revenue and billing reports</div>
                            </div>
                        </div>
                        <div class="permission-item">
                            <div class="permission-checkbox">
                                <input type="checkbox" checked data-permission="reports.export">
                            </div>
                            <div class="permission-info">
                                <div class="permission-name">Export Data</div>
                                <div class="permission-code">reports.export</div>
                                <div class="permission-description">Export report data to CSV/Excel</div>
                                <div class="permission-tags">
                                    <span class="permission-tag audit">Audited</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Role Modal -->
    <div class="modal-overlay" id="createRoleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Role</h3>
                <button class="modal-close" onclick="closeModal('createRoleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" id="newRoleName" placeholder="e.g., Radiology Technician">
                </div>
                <div class="form-group">
                    <label>Role Code</label>
                    <input type="text" id="newRoleCode" placeholder="e.g., RAD_TECH">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="newRoleDescription" rows="3" placeholder="Describe the role's purpose and responsibilities..."></textarea>
                </div>
                <div class="form-group">
                    <label>Base Template</label>
                    <select id="newRoleTemplate">
                        <option value="">Start from scratch</option>
                        <option value="physician">Copy from: Physician</option>
                        <option value="nurse">Copy from: Nurse (RN)</option>
                        <option value="ma">Copy from: Medical Assistant</option>
                        <option value="frontdesk">Copy from: Front Desk</option>
                        <option value="billing">Copy from: Billing Specialist</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createRoleModal')">Cancel</button>
                <button class="btn btn-primary" onclick="createRole()">Create Role</button>
            </div>
        </div>
    </div>

    <script>
        // Role selection
        function selectRole(roleId) {
            document.querySelectorAll('.role-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-role-id="${roleId}"]`).classList.add('active');
            
            // Load role data (would fetch from API)
            loadRoleData(roleId);
        }
        
        function loadRoleData(roleId) {
            // Simulate loading role data
            console.log('Loading role:', roleId);
            // In production, fetch from /api/admin/roles/{roleId}
        }
        
        // Filter roles
        function filterRoles() {
            const search = document.getElementById('roleSearch').value.toLowerCase();
            document.querySelectorAll('.role-item').forEach(item => {
                const name = item.querySelector('.role-name').textContent.toLowerCase();
                const desc = item.querySelector('.role-description').textContent.toLowerCase();
                if (name.includes(search) || desc.includes(search)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Toggle category permissions
        function toggleCategory(btn, category) {
            const categoryEl = btn.closest('.permission-category');
            const checkboxes = categoryEl.querySelectorAll('input[type="checkbox"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            
            btn.textContent = allChecked ? 'Select All' : 'Deselect All';
        }
        
        // Modal functions
        function showCreateRoleModal() {
            document.getElementById('createRoleModal').classList.add('show');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }
        
        function createRole() {
            const name = document.getElementById('newRoleName').value;
            const code = document.getElementById('newRoleCode').value;
            const description = document.getElementById('newRoleDescription').value;
            const template = document.getElementById('newRoleTemplate').value;
            
            if (!name || !code) {
                alert('Please enter a role name and code');
                return;
            }
            
            // Submit to API
            fetch('/api/admin/roles', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, code, description, template })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('createRoleModal');
                    location.reload();
                } else {
                    alert('Failed to create role: ' + data.error);
                }
            });
        }
        
        function saveRole() {
            const permissions = [];
            document.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                permissions.push(cb.dataset.permission);
            });
            
            const roleData = {
                name: document.getElementById('roleName').value,
                description: document.getElementById('roleDescription').value,
                level: document.getElementById('roleLevel').value,
                status: document.getElementById('roleStatus').value,
                permissions: permissions
            };
            
            // Submit to API
            console.log('Saving role:', roleData);
            alert('Role saved successfully!');
        }
        
        function duplicateRole() {
            showCreateRoleModal();
            document.getElementById('newRoleName').value = document.getElementById('roleName').value + ' (Copy)';
            document.getElementById('newRoleDescription').value = document.getElementById('roleDescription').value;
        }
    </script>

<?php include 'includes/admin-footer.php'; ?>
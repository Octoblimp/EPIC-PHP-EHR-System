<?php
/**
 * Openspace EHR - Admin Page Access Management
 * Manage per-page, per-role access permissions
 */
$page_title = 'Page Access - Admin';

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

// Page definitions with categories
$page_definitions = [
    'Main' => [
        ['code' => 'home', 'name' => 'Home Dashboard', 'description' => 'Main dashboard and patient list'],
        ['code' => 'schedule', 'name' => 'Schedule', 'description' => 'Scheduling and appointments'],
        ['code' => 'inbox', 'name' => 'Inbox', 'description' => 'Messages and notifications'],
        ['code' => 'patient_search', 'name' => 'Patient Search', 'description' => 'Search and find patients'],
    ],
    'Patient Chart' => [
        ['code' => 'patient.summary', 'name' => 'Summary', 'description' => 'Patient summary view'],
        ['code' => 'patient.chart_review', 'name' => 'Chart Review', 'description' => 'Review patient chart history'],
        ['code' => 'patient.results', 'name' => 'Results', 'description' => 'Lab and test results'],
        ['code' => 'patient.mar', 'name' => 'MAR', 'description' => 'Medication Administration Record'],
        ['code' => 'patient.flowsheets', 'name' => 'Flowsheets', 'description' => 'Flowsheets and vital signs'],
        ['code' => 'patient.notes', 'name' => 'Notes', 'description' => 'Clinical notes'],
        ['code' => 'patient.orders', 'name' => 'Orders', 'description' => 'Orders management'],
        ['code' => 'patient.care_plan', 'name' => 'Care Plan', 'description' => 'Care planning'],
        ['code' => 'patient.education', 'name' => 'Education', 'description' => 'Patient education materials'],
        ['code' => 'patient.demographics', 'name' => 'Demographics', 'description' => 'Patient demographics'],
        ['code' => 'patient.insurance', 'name' => 'Insurance', 'description' => 'Insurance information'],
        ['code' => 'patient.history', 'name' => 'History', 'description' => 'Medical history'],
    ],
    'Admin' => [
        ['code' => 'admin.dashboard', 'name' => 'Admin Dashboard', 'description' => 'Administration dashboard'],
        ['code' => 'admin.users', 'name' => 'User Management', 'description' => 'Manage users'],
        ['code' => 'admin.roles', 'name' => 'Role Management', 'description' => 'Manage roles and permissions'],
        ['code' => 'admin.shortcodes', 'name' => 'Shortcode Management', 'description' => 'Manage Go To shortcodes'],
        ['code' => 'admin.page_access', 'name' => 'Page Access', 'description' => 'Manage page-level permissions'],
        ['code' => 'admin.audit', 'name' => 'Audit Log', 'description' => 'View audit logs'],
        ['code' => 'admin.database', 'name' => 'Database Tools', 'description' => 'Database management tools'],
        ['code' => 'admin.settings', 'name' => 'System Settings', 'description' => 'System configuration'],
    ],
    'Reports' => [
        ['code' => 'reports.clinical', 'name' => 'Clinical Reports', 'description' => 'Clinical reports'],
        ['code' => 'reports.operational', 'name' => 'Operational Reports', 'description' => 'Operational reports'],
        ['code' => 'reports.financial', 'name' => 'Financial Reports', 'description' => 'Financial reports'],
    ],
    'Billing' => [
        ['code' => 'billing.charges', 'name' => 'Charges', 'description' => 'Charge entry'],
        ['code' => 'billing.claims', 'name' => 'Claims', 'description' => 'Claims management'],
        ['code' => 'billing.payments', 'name' => 'Payments', 'description' => 'Payment posting'],
    ],
];

// Available roles
$available_roles = [
    ['id' => 1, 'name' => 'administrator', 'display_name' => 'Administrator'],
    ['id' => 2, 'name' => 'physician', 'display_name' => 'Physician'],
    ['id' => 3, 'name' => 'nurse', 'display_name' => 'Nurse'],
    ['id' => 4, 'name' => 'medical_assistant', 'display_name' => 'Medical Assistant'],
    ['id' => 5, 'name' => 'front_desk', 'display_name' => 'Front Desk'],
    ['id' => 6, 'name' => 'billing', 'display_name' => 'Billing Staff'],
    ['id' => 7, 'name' => 'lab_tech', 'display_name' => 'Lab Technician'],
];

// Access levels
$access_levels = [
    'none' => ['label' => 'No Access', 'color' => '#dc3545', 'icon' => 'fa-ban'],
    'view' => ['label' => 'View Only', 'color' => '#ffc107', 'icon' => 'fa-eye'],
    'edit' => ['label' => 'View & Edit', 'color' => '#17a2b8', 'icon' => 'fa-edit'],
    'full' => ['label' => 'Full Access', 'color' => '#28a745', 'icon' => 'fa-check-circle'],
];

// Get page access from session (simulating DB)
$page_access = $_SESSION['page_access'] ?? [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_access':
            $role_id = intval($_POST['role_id'] ?? 0);
            $page_code = $_POST['page_code'] ?? '';
            $access_level = $_POST['access_level'] ?? 'none';
            
            if ($role_id && $page_code) {
                if (!isset($page_access[$role_id])) {
                    $page_access[$role_id] = [];
                }
                $page_access[$role_id][$page_code] = [
                    'access_level' => $access_level,
                    'can_view' => in_array($access_level, ['view', 'edit', 'full']),
                    'can_edit' => in_array($access_level, ['edit', 'full']),
                    'can_delete' => $access_level === 'full',
                    'can_export' => in_array($access_level, ['edit', 'full']),
                ];
                $_SESSION['page_access'] = $page_access;
                $success_message = 'Access updated successfully.';
            }
            break;
            
        case 'bulk_save':
            $role_id = intval($_POST['role_id'] ?? 0);
            $access_data = $_POST['access'] ?? [];
            
            if ($role_id && !empty($access_data)) {
                if (!isset($page_access[$role_id])) {
                    $page_access[$role_id] = [];
                }
                foreach ($access_data as $page_code => $level) {
                    $page_access[$role_id][$page_code] = [
                        'access_level' => $level,
                        'can_view' => in_array($level, ['view', 'edit', 'full']),
                        'can_edit' => in_array($level, ['edit', 'full']),
                        'can_delete' => $level === 'full',
                        'can_export' => in_array($level, ['edit', 'full']),
                    ];
                }
                $_SESSION['page_access'] = $page_access;
                $success_message = 'Access permissions saved for role.';
            }
            break;
            
        case 'copy_from_role':
            $target_role_id = intval($_POST['target_role_id'] ?? 0);
            $source_role_id = intval($_POST['source_role_id'] ?? 0);
            
            if ($target_role_id && $source_role_id && isset($page_access[$source_role_id])) {
                $page_access[$target_role_id] = $page_access[$source_role_id];
                $_SESSION['page_access'] = $page_access;
                $success_message = 'Permissions copied successfully.';
            }
            break;
            
        case 'reset_role':
            $role_id = intval($_POST['role_id'] ?? 0);
            if ($role_id && isset($page_access[$role_id])) {
                unset($page_access[$role_id]);
                $_SESSION['page_access'] = $page_access;
                $success_message = 'Role permissions reset to defaults.';
            }
            break;
    }
}

// Get selected role
$selected_role_id = intval($_GET['role'] ?? 1);
$selected_role = null;
foreach ($available_roles as $role) {
    if ($role['id'] === $selected_role_id) {
        $selected_role = $role;
        break;
    }
}

// Get access for selected role
$role_access = $page_access[$selected_role_id] ?? [];

// Include admin header
include 'includes/admin-header.php';
?>

<style>
.page-access-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 20px;
    max-width: 1400px;
}

.role-selector {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.role-selector-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px;
    font-weight: 600;
}

.role-list {
    padding: 10px;
}

.role-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 5px;
    text-decoration: none;
    color: #333;
    transition: all 0.15s;
}

.role-item:hover {
    background: #e8f0f8;
}

.role-item.active {
    background: #1a4a5e;
    color: white;
}

.role-item i {
    width: 20px;
    text-align: center;
}

.access-panel {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.access-panel-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.access-panel-header h2 {
    margin: 0;
    font-size: 18px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.header-actions button,
.header-actions select {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.access-panel-body {
    padding: 20px;
}

.page-category {
    margin-bottom: 25px;
}

.category-header {
    font-size: 14px;
    font-weight: 600;
    color: #1a4a5e;
    border-bottom: 2px solid #1a4a5e;
    padding-bottom: 8px;
    margin-bottom: 12px;
}

.page-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
}

.page-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #ccc;
    gap: 12px;
}

.page-item.access-none { border-left-color: #dc3545; }
.page-item.access-view { border-left-color: #ffc107; }
.page-item.access-edit { border-left-color: #17a2b8; }
.page-item.access-full { border-left-color: #28a745; }

.page-info {
    flex: 1;
    min-width: 0;
}

.page-name {
    font-weight: 500;
    font-size: 13px;
    margin-bottom: 2px;
}

.page-code {
    font-size: 11px;
    color: #888;
    font-family: monospace;
}

.page-access-select {
    width: 130px;
    padding: 6px 8px;
    border: 1px solid #d0d8e0;
    border-radius: 4px;
    font-size: 12px;
    background: white;
}

.access-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.access-badge.none { background: #ffebee; color: #c62828; }
.access-badge.view { background: #fff8e1; color: #f57f17; }
.access-badge.edit { background: #e3f2fd; color: #1565c0; }
.access-badge.full { background: #e8f5e9; color: #2e7d32; }

.legend {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding: 12px 15px;
    background: #f0f4f8;
    border-radius: 6px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
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
    background: #e8e8e8;
    color: #333;
}

.btn-secondary:hover {
    background: #d0d0d0;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.save-bar {
    position: sticky;
    bottom: 0;
    background: white;
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.quick-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.quick-action-btn {
    padding: 8px 12px;
    border: 1px solid #d0d8e0;
    border-radius: 4px;
    background: white;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.quick-action-btn:hover {
    background: #f8f9fa;
}
</style>

<div class="admin-page-header">
    <h1><i class="fas fa-shield-alt"></i> Page Access Management</h1>
    <p>Configure which roles can access specific pages and features</p>
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

<div class="legend">
    <?php foreach ($access_levels as $level => $info): ?>
    <div class="legend-item">
        <div class="legend-color" style="background: <?php echo $info['color']; ?>"></div>
        <i class="fas <?php echo $info['icon']; ?>" style="color: <?php echo $info['color']; ?>"></i>
        <span><?php echo $info['label']; ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="page-access-layout">
    <!-- Role Selector -->
    <div class="role-selector">
        <div class="role-selector-header">
            <i class="fas fa-user-shield"></i> Select Role
        </div>
        <div class="role-list">
            <?php foreach ($available_roles as $role): ?>
            <a href="?role=<?php echo $role['id']; ?>" class="role-item <?php echo $role['id'] === $selected_role_id ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($role['display_name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Access Panel -->
    <div class="access-panel">
        <form method="POST" id="accessForm">
            <input type="hidden" name="action" value="bulk_save">
            <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
            
            <div class="access-panel-header">
                <h2>
                    <i class="fas fa-user-shield"></i>
                    <?php echo htmlspecialchars($selected_role['display_name'] ?? 'Role'); ?> - Page Access
                </h2>
                <div class="header-actions">
                    <select id="copyFromRole" onchange="copyFromRole(this.value)">
                        <option value="">Copy from role...</option>
                        <?php foreach ($available_roles as $role): ?>
                        <?php if ($role['id'] !== $selected_role_id): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo $role['display_name']; ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="access-panel-body">
                <div class="quick-actions">
                    <button type="button" class="quick-action-btn" onclick="setAllAccess('full')">
                        <i class="fas fa-check-circle" style="color: #28a745"></i> Grant All Full Access
                    </button>
                    <button type="button" class="quick-action-btn" onclick="setAllAccess('view')">
                        <i class="fas fa-eye" style="color: #ffc107"></i> Set All View Only
                    </button>
                    <button type="button" class="quick-action-btn" onclick="setAllAccess('none')">
                        <i class="fas fa-ban" style="color: #dc3545"></i> Revoke All Access
                    </button>
                    <button type="button" class="quick-action-btn" onclick="resetToDefaults()">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                </div>
                
                <?php foreach ($page_definitions as $category => $pages): ?>
                <div class="page-category">
                    <div class="category-header">
                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category); ?>
                    </div>
                    <div class="page-grid">
                        <?php foreach ($pages as $page): ?>
                        <?php 
                        $current_access = $role_access[$page['code']]['access_level'] ?? 'none';
                        // Default access for admin
                        if ($selected_role['name'] === 'administrator' && !isset($role_access[$page['code']])) {
                            $current_access = 'full';
                        }
                        ?>
                        <div class="page-item access-<?php echo $current_access; ?>">
                            <div class="page-info">
                                <div class="page-name"><?php echo htmlspecialchars($page['name']); ?></div>
                                <div class="page-code"><?php echo htmlspecialchars($page['code']); ?></div>
                            </div>
                            <select name="access[<?php echo $page['code']; ?>]" class="page-access-select" data-page="<?php echo $page['code']; ?>">
                                <?php foreach ($access_levels as $level => $info): ?>
                                <option value="<?php echo $level; ?>" <?php echo $current_access === $level ? 'selected' : ''; ?>>
                                    <?php echo $info['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="save-bar">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-times"></i> Cancel Changes
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Copy From Role Modal -->
<form method="POST" id="copyForm" style="display: none;">
    <input type="hidden" name="action" value="copy_from_role">
    <input type="hidden" name="target_role_id" value="<?php echo $selected_role_id; ?>">
    <input type="hidden" name="source_role_id" id="sourceRoleId" value="">
</form>

<form method="POST" id="resetForm" style="display: none;">
    <input type="hidden" name="action" value="reset_role">
    <input type="hidden" name="role_id" value="<?php echo $selected_role_id; ?>">
</form>

<script>
function setAllAccess(level) {
    document.querySelectorAll('.page-access-select').forEach(select => {
        select.value = level;
        updatePageItemClass(select);
    });
}

function updatePageItemClass(select) {
    const pageItem = select.closest('.page-item');
    pageItem.className = 'page-item access-' + select.value;
}

// Update styling when select changes
document.querySelectorAll('.page-access-select').forEach(select => {
    select.addEventListener('change', function() {
        updatePageItemClass(this);
    });
});

function copyFromRole(roleId) {
    if (!roleId) return;
    if (confirm('Copy all permissions from the selected role? This will overwrite current settings.')) {
        document.getElementById('sourceRoleId').value = roleId;
        document.getElementById('copyForm').submit();
    }
    document.getElementById('copyFromRole').value = '';
}

function resetToDefaults() {
    if (confirm('Reset permissions to defaults? Custom settings will be lost.')) {
        document.getElementById('resetForm').submit();
    }
}

function resetForm() {
    window.location.reload();
}
</script>

<?php include 'includes/admin-footer.php'; ?>

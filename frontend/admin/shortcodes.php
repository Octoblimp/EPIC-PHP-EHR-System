<?php
/**
 * Openspace EHR - Admin Shortcodes Management
 * Manage Go To shortcodes for patient chart navigation
 * Stores shortcodes in database via API
 */
$page_title = 'Manage Shortcodes - Admin';

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

// API base URL
$api_base = defined('API_URL') ? API_URL : 'http://127.0.0.1:5000/api';

// Helper function to call API
function callShortcodeApi($endpoint, $method = 'GET', $data = null) {
    global $api_base;
    $url = $api_base . $endpoint;
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json',
            'ignore_errors' => true,
            'timeout' => 10
        ]
    ];
    
    if ($data !== null) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'API connection failed'];
    }
    
    return json_decode($response, true) ?? ['success' => false, 'error' => 'Invalid response'];
}

// Default shortcodes (fallback when API unavailable)
$default_shortcodes = [
    ['id' => 1, 'code' => 'sum', 'name' => 'Summary', 'tab' => 'summary', 'icon' => 'fa-clipboard', 'category' => 'Main Views', 'is_system' => true],
    ['id' => 2, 'code' => 'cr', 'name' => 'Chart Review', 'tab' => 'chart-review', 'icon' => 'fa-file-medical', 'category' => 'Main Views', 'is_system' => true],
    ['id' => 3, 'code' => 'res', 'name' => 'Results', 'tab' => 'results', 'icon' => 'fa-flask', 'category' => 'Main Views', 'is_system' => true],
    ['id' => 4, 'code' => 'lab', 'name' => 'Lab Results', 'tab' => 'results', 'icon' => 'fa-vials', 'category' => 'Results', 'is_system' => true],
    ['id' => 5, 'code' => 'wl', 'name' => 'Work List', 'tab' => 'work-list', 'icon' => 'fa-tasks', 'category' => 'Main Views', 'is_system' => true],
    ['id' => 6, 'code' => 'mar', 'name' => 'MAR', 'tab' => 'mar', 'icon' => 'fa-pills', 'category' => 'Medications', 'is_system' => true],
    ['id' => 7, 'code' => 'med', 'name' => 'Medications', 'tab' => 'mar', 'icon' => 'fa-prescription-bottle', 'category' => 'Medications', 'is_system' => true],
    ['id' => 8, 'code' => 'fs', 'name' => 'Flowsheets', 'tab' => 'flowsheets', 'icon' => 'fa-chart-line', 'category' => 'Documentation', 'is_system' => true],
    ['id' => 9, 'code' => 'vs', 'name' => 'Vitals', 'tab' => 'flowsheets', 'icon' => 'fa-heartbeat', 'category' => 'Documentation', 'is_system' => true],
    ['id' => 10, 'code' => 'io', 'name' => 'Intake/Output', 'tab' => 'intake-output', 'icon' => 'fa-balance-scale', 'category' => 'Documentation', 'is_system' => true],
    ['id' => 11, 'code' => 'not', 'name' => 'Notes', 'tab' => 'notes', 'icon' => 'fa-sticky-note', 'category' => 'Documentation', 'is_system' => true],
    ['id' => 12, 'code' => 'pn', 'name' => 'Progress Notes', 'tab' => 'notes', 'icon' => 'fa-file-alt', 'category' => 'Documentation', 'is_system' => true],
    ['id' => 13, 'code' => 'edu', 'name' => 'Education', 'tab' => 'education', 'icon' => 'fa-graduation-cap', 'category' => 'Patient Info', 'is_system' => true],
    ['id' => 14, 'code' => 'cp', 'name' => 'Care Plan', 'tab' => 'care-plan', 'icon' => 'fa-clipboard-list', 'category' => 'Care Planning', 'is_system' => true],
    ['id' => 15, 'code' => 'ord', 'name' => 'Orders', 'tab' => 'orders', 'icon' => 'fa-prescription', 'category' => 'Orders', 'is_system' => true],
    ['id' => 16, 'code' => 'rx', 'name' => 'Prescriptions', 'tab' => 'orders', 'icon' => 'fa-capsules', 'category' => 'Orders', 'is_system' => true],
    ['id' => 17, 'code' => 'img', 'name' => 'Imaging', 'tab' => 'results', 'icon' => 'fa-x-ray', 'category' => 'Results', 'is_system' => true],
    ['id' => 18, 'code' => 'dx', 'name' => 'Diagnoses', 'tab' => 'chart-review', 'icon' => 'fa-diagnoses', 'category' => 'Clinical', 'is_system' => true],
    ['id' => 19, 'code' => 'hx', 'name' => 'History', 'tab' => 'history', 'icon' => 'fa-history', 'category' => 'Clinical', 'is_system' => true],
    ['id' => 20, 'code' => 'all', 'name' => 'Allergies', 'tab' => 'summary', 'icon' => 'fa-exclamation-triangle', 'category' => 'Clinical', 'is_system' => true],
    ['id' => 21, 'code' => 'prob', 'name' => 'Problem List', 'tab' => 'chart-review', 'icon' => 'fa-list-ul', 'category' => 'Clinical', 'is_system' => true],
    ['id' => 22, 'code' => 'imm', 'name' => 'Immunizations', 'tab' => 'chart-review', 'icon' => 'fa-syringe', 'category' => 'Clinical', 'is_system' => true],
    ['id' => 23, 'code' => 'dem', 'name' => 'Demographics', 'tab' => 'demographics', 'icon' => 'fa-id-card', 'category' => 'Patient Info', 'is_system' => true],
    ['id' => 24, 'code' => 'ins', 'name' => 'Insurance', 'tab' => 'insurance', 'icon' => 'fa-shield-alt', 'category' => 'Patient Info', 'is_system' => true],
];

// Get shortcodes from session (would be from DB in production)
$shortcodes = $_SESSION['system_shortcodes'] ?? $default_shortcodes;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $new_shortcode = [
                'code' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['code'] ?? '')),
                'name' => $_POST['name'] ?? '',
                'tab' => $_POST['tab'] ?? 'summary',
                'icon' => $_POST['icon'] ?? 'fa-file',
                'category' => $_POST['category'] ?? 'Custom'
            ];
            
            if (empty($new_shortcode['code']) || empty($new_shortcode['name'])) {
                $error_message = 'Code and Name are required.';
            } else {
                // Check for duplicate code
                $exists = false;
                foreach ($shortcodes as $sc) {
                    if ($sc['code'] === $new_shortcode['code']) {
                        $exists = true;
                        break;
                    }
                }
                
                if ($exists) {
                    $error_message = 'Shortcode "' . $new_shortcode['code'] . '" already exists.';
                } else {
                    $shortcodes[] = $new_shortcode;
                    $_SESSION['system_shortcodes'] = $shortcodes;
                    $success_message = 'Shortcode added successfully.';
                }
            }
            break;
            
        case 'delete':
            $delete_code = $_POST['code'] ?? '';
            $shortcodes = array_filter($shortcodes, function($sc) use ($delete_code) {
                return $sc['code'] !== $delete_code;
            });
            $shortcodes = array_values($shortcodes);
            $_SESSION['system_shortcodes'] = $shortcodes;
            $success_message = 'Shortcode deleted successfully.';
            break;
            
        case 'reset':
            $shortcodes = $default_shortcodes;
            $_SESSION['system_shortcodes'] = $shortcodes;
            $success_message = 'Shortcodes reset to defaults.';
            break;
            
        case 'update':
            $update_index = intval($_POST['index'] ?? -1);
            if ($update_index >= 0 && $update_index < count($shortcodes)) {
                $shortcodes[$update_index]['name'] = $_POST['name'] ?? $shortcodes[$update_index]['name'];
                $shortcodes[$update_index]['tab'] = $_POST['tab'] ?? $shortcodes[$update_index]['tab'];
                $shortcodes[$update_index]['icon'] = $_POST['icon'] ?? $shortcodes[$update_index]['icon'];
                $shortcodes[$update_index]['category'] = $_POST['category'] ?? $shortcodes[$update_index]['category'];
                $_SESSION['system_shortcodes'] = $shortcodes;
                $success_message = 'Shortcode updated successfully.';
            }
            break;
    }
}

// Available tabs for patient chart
$available_tabs = [
    'summary' => 'Summary',
    'chart-review' => 'Chart Review',
    'results' => 'Results',
    'work-list' => 'Work List',
    'mar' => 'MAR',
    'flowsheets' => 'Flowsheets',
    'intake-output' => 'Intake/Output',
    'notes' => 'Notes',
    'education' => 'Education',
    'care-plan' => 'Care Plan',
    'orders' => 'Orders'
];

// Available icons
$available_icons = [
    'fa-clipboard', 'fa-file-medical', 'fa-flask', 'fa-vials', 'fa-tasks',
    'fa-pills', 'fa-prescription-bottle', 'fa-chart-line', 'fa-heartbeat',
    'fa-balance-scale', 'fa-sticky-note', 'fa-file-alt', 'fa-graduation-cap',
    'fa-clipboard-list', 'fa-prescription', 'fa-capsules', 'fa-x-ray',
    'fa-diagnoses', 'fa-history', 'fa-exclamation-triangle', 'fa-list-ul',
    'fa-syringe', 'fa-user', 'fa-notes-medical', 'fa-file'
];

// Group shortcodes by category
$grouped_shortcodes = [];
foreach ($shortcodes as $index => $sc) {
    $category = $sc['category'] ?? 'Other';
    if (!isset($grouped_shortcodes[$category])) {
        $grouped_shortcodes[$category] = [];
    }
    $sc['_index'] = $index;
    $grouped_shortcodes[$category][] = $sc;
}

// Include admin header
include 'includes/admin-header.php';
?>

<style>
.shortcodes-page {
    max-width: 1200px;
}

.shortcode-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.shortcode-category {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.category-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 14px;
}

.shortcode-list {
    padding: 10px;
}

.shortcode-item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 4px;
    margin-bottom: 5px;
    background: #f8f9fa;
    transition: all 0.15s;
}

.shortcode-item:hover {
    background: #e8f0f8;
}

.shortcode-code {
    background: #1a4a5e;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    margin-right: 10px;
    min-width: 50px;
    text-align: center;
}

.shortcode-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    color: #666;
}

.shortcode-name {
    flex: 1;
    font-size: 13px;
}

.shortcode-tab {
    font-size: 11px;
    color: #888;
    margin-right: 10px;
}

.shortcode-actions {
    display: flex;
    gap: 5px;
}

.shortcode-actions button {
    padding: 4px 8px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 11px;
}

.btn-edit {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-delete {
    background: #ffebee;
    color: #c62828;
}

.add-shortcode-form {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.add-shortcode-form h3 {
    margin: 0 0 15px;
    font-size: 16px;
    color: #1a4a5e;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: #666;
    margin-bottom: 5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn-primary {
    background: #1a4a5e;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary:hover {
    background: #0d3545;
}

.btn-secondary {
    background: #e8e8e8;
    color: #333;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-secondary:hover {
    background: #d0d0d0;
}

.icon-preview {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.icon-selector {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 5px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-top: 10px;
}

.icon-option {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    cursor: pointer;
    border: 2px solid transparent;
}

.icon-option:hover {
    background: #e0e0e0;
}

.icon-option.selected {
    border-color: #1a4a5e;
    background: #e3f2fd;
}

/* Edit Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 450px;
    max-width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 15px 20px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h4 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-radius: 0 0 8px 8px;
}

.info-box {
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.info-box h4 {
    margin: 0 0 10px;
    color: #1565c0;
    font-size: 14px;
}

.info-box p {
    margin: 0;
    font-size: 13px;
    color: #1976d2;
}

.info-box code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

<div class="shortcodes-page">
    <div class="admin-page-header">
        <h1><i class="fas fa-keyboard"></i> Shortcode Management</h1>
        <p>Configure shortcodes for the "Go To" quick navigation in patient charts</p>
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
    
    <div class="info-box">
        <h4><i class="fas fa-info-circle"></i> How Shortcodes Work</h4>
        <p>
            Users can press <code>Ctrl+G</code> or click the "Go To" button in the sidebar to open the quick navigation. 
            Typing a shortcode (like <code>mar</code> or <code>vs</code>) will navigate directly to that section of the patient chart.
        </p>
    </div>
    
    <!-- Add New Shortcode Form -->
    <div class="add-shortcode-form">
        <h3><i class="fas fa-plus"></i> Add New Shortcode</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label>Shortcode</label>
                    <input type="text" name="code" placeholder="e.g., vs" maxlength="10" required>
                </div>
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="name" placeholder="e.g., Vitals" required>
                </div>
                <div class="form-group">
                    <label>Target Tab</label>
                    <select name="tab" required>
                        <?php foreach ($available_tabs as $tab_id => $tab_name): ?>
                        <option value="<?php echo $tab_id; ?>"><?php echo $tab_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g., Clinical" value="Custom">
                </div>
                <div class="form-group">
                    <label>Icon</label>
                    <select name="icon">
                        <?php foreach ($available_icons as $icon): ?>
                        <option value="<?php echo $icon; ?>"><?php echo $icon; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Add Shortcode</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit" class="btn-secondary" onclick="return confirm('Reset all shortcodes to defaults?')">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                </form>
            </div>
        </form>
    </div>
    
    <!-- Shortcodes Grid -->
    <h3 style="margin-bottom: 15px;">Current Shortcodes (<?php echo count($shortcodes); ?>)</h3>
    <div class="shortcode-grid">
        <?php foreach ($grouped_shortcodes as $category => $items): ?>
        <div class="shortcode-category">
            <div class="category-header">
                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($category); ?> (<?php echo count($items); ?>)
            </div>
            <div class="shortcode-list">
                <?php foreach ($items as $sc): ?>
                <div class="shortcode-item">
                    <span class="shortcode-code"><?php echo htmlspecialchars($sc['code']); ?></span>
                    <span class="shortcode-icon"><i class="fas <?php echo htmlspecialchars($sc['icon']); ?>"></i></span>
                    <span class="shortcode-name"><?php echo htmlspecialchars($sc['name']); ?></span>
                    <span class="shortcode-tab"><?php echo htmlspecialchars($available_tabs[$sc['tab']] ?? $sc['tab']); ?></span>
                    <div class="shortcode-actions">
                        <button class="btn-edit" onclick="editShortcode(<?php echo $sc['_index']; ?>, '<?php echo addslashes($sc['code']); ?>', '<?php echo addslashes($sc['name']); ?>', '<?php echo $sc['tab']; ?>', '<?php echo $sc['icon']; ?>', '<?php echo addslashes($sc['category']); ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="code" value="<?php echo htmlspecialchars($sc['code']); ?>">
                            <button type="submit" class="btn-delete" onclick="return confirm('Delete this shortcode?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fas fa-edit"></i> Edit Shortcode</h4>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="index" id="editIndex">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Shortcode</label>
                    <input type="text" id="editCode" disabled style="background: #f0f0f0;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Display Name</label>
                    <input type="text" name="name" id="editName" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Target Tab</label>
                    <select name="tab" id="editTab" required>
                        <?php foreach ($available_tabs as $tab_id => $tab_name): ?>
                        <option value="<?php echo $tab_id; ?>"><?php echo $tab_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Category</label>
                    <input type="text" name="category" id="editCategory">
                </div>
                
                <div class="form-group">
                    <label>Icon</label>
                    <select name="icon" id="editIcon">
                        <?php foreach ($available_icons as $icon): ?>
                        <option value="<?php echo $icon; ?>"><?php echo $icon; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editShortcode(index, code, name, tab, icon, category) {
    document.getElementById('editIndex').value = index;
    document.getElementById('editCode').value = code;
    document.getElementById('editName').value = name;
    document.getElementById('editTab').value = tab;
    document.getElementById('editIcon').value = icon;
    document.getElementById('editCategory').value = category;
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Close modal on outside click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
});
</script>

<?php include 'includes/admin-footer.php'; ?>

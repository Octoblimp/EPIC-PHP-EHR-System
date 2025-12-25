<?php
/**
 * Openspace EHR - Admin Integrations
 * Clearinghouse and external service configuration
 */
$page_title = 'Integrations - Admin Panel';

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
$active_tab = $_GET['tab'] ?? 'clearinghouse';

// Clearinghouse providers
$clearinghouse_providers = [
    'availity' => [
        'name' => 'Availity',
        'description' => 'Industry-leading health information network',
        'logo' => 'fa-cloud',
        'fields' => ['client_id', 'client_secret', 'customer_id', 'submitter_id'],
        'is_free' => false
    ],
    'change_healthcare' => [
        'name' => 'Change Healthcare',
        'description' => 'Comprehensive healthcare technology platform',
        'logo' => 'fa-exchange-alt',
        'fields' => ['api_key', 'api_secret', 'submitter_id'],
        'is_free' => false
    ],
    'waystar' => [
        'name' => 'Waystar',
        'description' => 'Cloud-based revenue cycle technology',
        'logo' => 'fa-star',
        'fields' => ['username', 'password', 'site_id'],
        'is_free' => false
    ],
    'trizetto' => [
        'name' => 'Trizetto (Cognizant)',
        'description' => 'Healthcare IT and services',
        'logo' => 'fa-cogs',
        'fields' => ['username', 'password', 'facility_id'],
        'is_free' => false
    ],
    'office_ally' => [
        'name' => 'Office Ally',
        'description' => 'Free healthcare solutions provider',
        'logo' => 'fa-handshake',
        'fields' => ['username', 'password', 'practice_id'],
        'is_free' => true
    ],
    'claims_md' => [
        'name' => 'Claims.MD',
        'description' => 'Affordable clearinghouse services',
        'logo' => 'fa-file-medical',
        'fields' => ['api_key', 'practice_id'],
        'is_free' => false
    ],
    'manual' => [
        'name' => 'Manual Verification Only',
        'description' => 'No automated integration - verify manually via phone/portal',
        'logo' => 'fa-phone-alt',
        'fields' => [],
        'is_free' => true
    ]
];

// Get saved clearinghouse configs from session (in production, from DB)
$saved_configs = $_SESSION['clearinghouse_configs'] ?? [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_clearinghouse':
            $provider = $_POST['provider'] ?? '';
            if ($provider && isset($clearinghouse_providers[$provider])) {
                $config = [
                    'provider' => $provider,
                    'name' => $clearinghouse_providers[$provider]['name'],
                    'is_active' => isset($_POST['is_active']),
                    'is_primary' => isset($_POST['is_primary']),
                    'test_mode' => isset($_POST['test_mode']),
                    'credentials' => []
                ];
                
                // Save all credential fields
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'cred_') === 0) {
                        $config['credentials'][substr($key, 5)] = $value;
                    }
                }
                
                // If set as primary, unset others
                if ($config['is_primary']) {
                    foreach ($saved_configs as &$existing) {
                        $existing['is_primary'] = false;
                    }
                }
                
                $saved_configs[$provider] = $config;
                $_SESSION['clearinghouse_configs'] = $saved_configs;
                $success_message = "Clearinghouse '{$config['name']}' configuration saved.";
            }
            break;
            
        case 'test_connection':
            $provider = $_POST['provider'] ?? '';
            // In production, actually test the connection
            $success_message = "Connection test for '{$clearinghouse_providers[$provider]['name']}' completed successfully! (Demo Mode)";
            break;
            
        case 'delete_config':
            $provider = $_POST['provider'] ?? '';
            if (isset($saved_configs[$provider])) {
                unset($saved_configs[$provider]);
                $_SESSION['clearinghouse_configs'] = $saved_configs;
                $success_message = "Configuration removed.";
            }
            break;
    }
}

include 'includes/admin-header.php';
?>
            <div class="admin-page-header">
                <h1><i class="fas fa-plug"></i> Integrations</h1>
                <p>Configure clearinghouse connections, external services, and API integrations</p>
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

            <!-- Integration Tabs -->
            <div class="integration-tabs">
                <a href="?tab=clearinghouse" class="tab-btn <?php echo $active_tab === 'clearinghouse' ? 'active' : ''; ?>">
                    <i class="fas fa-hospital"></i> Clearinghouse / Eligibility
                </a>
                <a href="?tab=fhir" class="tab-btn <?php echo $active_tab === 'fhir' ? 'active' : ''; ?>">
                    <i class="fas fa-fire"></i> FHIR / HL7
                </a>
                <a href="?tab=lab" class="tab-btn <?php echo $active_tab === 'lab' ? 'active' : ''; ?>">
                    <i class="fas fa-flask"></i> Lab Interfaces
                </a>
                <a href="?tab=pharmacy" class="tab-btn <?php echo $active_tab === 'pharmacy' ? 'active' : ''; ?>">
                    <i class="fas fa-pills"></i> eRx / Pharmacy
                </a>
            </div>

            <?php if ($active_tab === 'clearinghouse'): ?>
            <!-- Clearinghouse Configuration -->
            <div class="integration-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-shield-alt"></i> Insurance Eligibility Verification</h2>
                        <p>Configure clearinghouse connections for real-time eligibility (270/271) verification</p>
                    </div>
                </div>

                <!-- Current Status Card -->
                <div class="status-card">
                    <div class="status-header">
                        <h3>Current Configuration</h3>
                    </div>
                    <div class="status-body">
                        <?php 
                        $primary_config = null;
                        foreach ($saved_configs as $config) {
                            if ($config['is_primary'] ?? false) {
                                $primary_config = $config;
                                break;
                            }
                        }
                        ?>
                        <?php if ($primary_config): ?>
                        <div class="status-item active">
                            <div class="status-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="status-details">
                                <strong>Primary: <?php echo htmlspecialchars($primary_config['name']); ?></strong>
                                <span><?php echo $primary_config['test_mode'] ? 'Test Mode' : 'Production'; ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="status-item warning">
                            <div class="status-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="status-details">
                                <strong>No Primary Clearinghouse Configured</strong>
                                <span>Select a clearinghouse below or use Manual Verification</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="status-stats">
                            <div class="stat">
                                <span class="stat-value"><?php echo count($saved_configs); ?></span>
                                <span class="stat-label">Configured</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo count(array_filter($saved_configs, fn($c) => $c['is_active'] ?? false)); ?></span>
                                <span class="stat-label">Active</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value">0</span>
                                <span class="stat-label">Today's Checks</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Provider Cards -->
                <div class="providers-grid">
                    <?php foreach ($clearinghouse_providers as $key => $provider): ?>
                    <?php $config = $saved_configs[$key] ?? null; ?>
                    <div class="provider-card <?php echo $config ? 'configured' : ''; ?> <?php echo ($config['is_primary'] ?? false) ? 'primary' : ''; ?>">
                        <div class="provider-header">
                            <div class="provider-icon">
                                <i class="fas <?php echo $provider['logo']; ?>"></i>
                            </div>
                            <div class="provider-info">
                                <h4><?php echo htmlspecialchars($provider['name']); ?></h4>
                                <p><?php echo htmlspecialchars($provider['description']); ?></p>
                            </div>
                            <?php if ($provider['is_free']): ?>
                            <span class="free-badge">FREE</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="provider-status">
                            <?php if ($config): ?>
                            <span class="status-badge <?php echo ($config['is_active'] ?? false) ? 'active' : 'inactive'; ?>">
                                <?php echo ($config['is_active'] ?? false) ? 'Active' : 'Inactive'; ?>
                            </span>
                            <?php if ($config['is_primary'] ?? false): ?>
                            <span class="status-badge primary">Primary</span>
                            <?php endif; ?>
                            <?php if ($config['test_mode'] ?? true): ?>
                            <span class="status-badge test">Test Mode</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="status-badge not-configured">Not Configured</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="provider-actions">
                            <button class="btn btn-primary btn-sm" onclick="configureProvider('<?php echo $key; ?>')">
                                <i class="fas fa-cog"></i> <?php echo $config ? 'Edit' : 'Configure'; ?>
                            </button>
                            <?php if ($config): ?>
                            <button class="btn btn-secondary btn-sm" onclick="testConnection('<?php echo $key; ?>')">
                                <i class="fas fa-vial"></i> Test
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php elseif ($active_tab === 'fhir'): ?>
            <!-- FHIR Configuration -->
            <div class="integration-section">
                <div class="section-header">
                    <h2><i class="fas fa-fire"></i> FHIR / HL7 Integration</h2>
                    <p>Configure FHIR R4 and HL7 v2 interfaces</p>
                </div>
                <div class="coming-soon">
                    <i class="fas fa-hard-hat"></i>
                    <h3>Coming Soon</h3>
                    <p>FHIR R4 API and HL7 v2 interface configuration will be available in a future release.</p>
                </div>
            </div>
            
            <?php elseif ($active_tab === 'lab'): ?>
            <!-- Lab Interfaces -->
            <div class="integration-section">
                <div class="section-header">
                    <h2><i class="fas fa-flask"></i> Lab Interfaces</h2>
                    <p>Configure connections to reference laboratories</p>
                </div>
                <div class="coming-soon">
                    <i class="fas fa-hard-hat"></i>
                    <h3>Coming Soon</h3>
                    <p>Laboratory interface configuration will be available in a future release.</p>
                </div>
            </div>
            
            <?php elseif ($active_tab === 'pharmacy'): ?>
            <!-- Pharmacy / eRx -->
            <div class="integration-section">
                <div class="section-header">
                    <h2><i class="fas fa-pills"></i> eRx / Pharmacy Integration</h2>
                    <p>Configure Surescripts and pharmacy connections</p>
                </div>
                <div class="coming-soon">
                    <i class="fas fa-hard-hat"></i>
                    <h3>Coming Soon</h3>
                    <p>e-Prescribing integration will be available in a future release.</p>
                </div>
            </div>
            <?php endif; ?>

<!-- Configuration Modal -->
<div id="configModal" class="modal" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> <span id="modalProviderName">Configure Provider</span></h5>
                <button type="button" class="close" onclick="closeConfigModal()">&times;</button>
            </div>
            <form method="POST" id="configForm">
                <input type="hidden" name="action" value="save_clearinghouse">
                <input type="hidden" name="provider" id="configProvider">
                <div class="modal-body">
                    <div id="configFields">
                        <!-- Dynamic fields inserted here -->
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" id="configActive" checked>
                                <span>Active</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_primary" id="configPrimary">
                                <span>Set as Primary Clearinghouse</span>
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="test_mode" id="configTestMode" checked>
                                <span>Test Mode (Use sandbox endpoints)</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="deleteConfig()" id="deleteBtn" style="margin-right:auto;display:none;">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeConfigModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.integration-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 20px;
    background: #f5f5f5;
    padding: 5px;
    border-radius: 8px;
}

.tab-btn {
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: #666;
    text-decoration: none;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-btn:hover {
    background: rgba(255,255,255,0.5);
    color: #333;
}

.tab-btn.active {
    background: white;
    color: #1a4a5e;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.integration-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-header {
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 18px;
    color: #1a4a5e;
    margin: 0 0 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-header p {
    color: #666;
    margin: 0;
    font-size: 13px;
}

.status-card {
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
    overflow: hidden;
}

.status-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 12px 15px;
}

.status-header h3 {
    margin: 0;
    font-size: 14px;
}

.status-body {
    padding: 15px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: white;
    border-radius: 6px;
    margin-bottom: 15px;
}

.status-item.active .status-icon {
    color: #28a745;
}

.status-item.warning .status-icon {
    color: #ffc107;
}

.status-icon {
    font-size: 24px;
}

.status-details strong {
    display: block;
    font-size: 14px;
    color: #333;
}

.status-details span {
    font-size: 12px;
    color: #666;
}

.status-stats {
    display: flex;
    gap: 20px;
}

.status-stats .stat {
    text-align: center;
}

.status-stats .stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #1a4a5e;
}

.status-stats .stat-label {
    font-size: 11px;
    color: #666;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 15px;
}

.provider-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    transition: all 0.2s;
}

.provider-card:hover {
    border-color: #1a4a5e;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.provider-card.configured {
    border-color: #28a745;
}

.provider-card.primary {
    border-color: #1a4a5e;
    background: linear-gradient(to bottom, rgba(26,74,94,0.05), white);
}

.provider-header {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    position: relative;
}

.provider-icon {
    width: 48px;
    height: 48px;
    background: #e3f2fd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a4a5e;
    font-size: 20px;
    flex-shrink: 0;
}

.provider-info h4 {
    margin: 0 0 4px;
    font-size: 15px;
    color: #333;
}

.provider-info p {
    margin: 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.free-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #28a745;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
}

.provider-status {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.status-badge {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.inactive {
    background: #f5f5f5;
    color: #666;
}

.status-badge.primary {
    background: #e3f2fd;
    color: #1565c0;
}

.status-badge.test {
    background: #fff3e0;
    color: #e65100;
}

.status-badge.not-configured {
    background: #f5f5f5;
    color: #999;
}

.provider-actions {
    display: flex;
    gap: 8px;
}

.provider-actions .btn {
    flex: 1;
}

.coming-soon {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.coming-soon i {
    font-size: 48px;
    color: #ffc107;
    margin-bottom: 15px;
}

.coming-soon h3 {
    margin: 0 0 10px;
    color: #333;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-dialog {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-dialog.modal-lg {
    max-width: 700px;
}

.modal-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-header .close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.8;
}

.modal-header .close:hover {
    opacity: 1;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e0e0e0;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.form-group input[type="text"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus {
    border-color: #1a4a5e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(26,74,94,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.help-text {
    font-size: 11px;
    color: #888;
    margin-top: 4px;
}
</style>

<script>
const providerConfigs = <?php echo json_encode($clearinghouse_providers); ?>;
const savedConfigs = <?php echo json_encode($saved_configs); ?>;

const fieldConfigs = {
    'availity': [
        {name: 'client_id', label: 'Client ID', type: 'text', required: true},
        {name: 'client_secret', label: 'Client Secret', type: 'password', required: true},
        {name: 'customer_id', label: 'Customer ID', type: 'text', required: true},
        {name: 'submitter_id', label: 'Submitter ID', type: 'text', required: true}
    ],
    'change_healthcare': [
        {name: 'client_id', label: 'Client ID', type: 'text', required: true},
        {name: 'client_secret', label: 'Client Secret', type: 'password', required: true},
        {name: 'submitter_id', label: 'Submitter ID', type: 'text', required: true}
    ],
    'waystar': [
        {name: 'username', label: 'Username', type: 'text', required: true},
        {name: 'password', label: 'Password', type: 'password', required: true},
        {name: 'site_id', label: 'Site ID', type: 'text', required: true}
    ],
    'trizetto': [
        {name: 'username', label: 'Username', type: 'text', required: true},
        {name: 'password', label: 'Password', type: 'password', required: true},
        {name: 'facility_id', label: 'Facility ID', type: 'text', required: true}
    ],
    'office_ally': [
        {name: 'username', label: 'Username', type: 'text', required: true},
        {name: 'password', label: 'Password', type: 'password', required: true},
        {name: 'practice_id', label: 'Practice ID', type: 'text', required: true}
    ],
    'claims_md': [
        {name: 'api_key', label: 'API Key', type: 'password', required: true},
        {name: 'practice_id', label: 'Practice ID', type: 'text', required: true}
    ],
    'manual': []
};

function configureProvider(provider) {
    const providerInfo = providerConfigs[provider];
    const fields = fieldConfigs[provider] || [];
    const saved = savedConfigs[provider] || null;
    
    document.getElementById('modalProviderName').textContent = 'Configure ' + providerInfo.name;
    document.getElementById('configProvider').value = provider;
    
    // Build fields HTML
    let fieldsHtml = '';
    
    if (provider === 'manual') {
        fieldsHtml = `
            <div class="alert alert-info" style="background:#e3f2fd;padding:15px;border-radius:6px;margin-bottom:15px;">
                <i class="fas fa-info-circle"></i>
                <strong>Manual Verification Mode</strong><br>
                No API integration required. Staff will verify eligibility by calling payers directly 
                or using payer portals, then record the results in the system.
            </div>
        `;
    } else {
        fields.forEach(field => {
            const savedValue = saved?.credentials?.[field.name] || '';
            fieldsHtml += `
                <div class="form-group">
                    <label>${field.label}${field.required ? ' *' : ''}</label>
                    <input type="${field.type}" name="cred_${field.name}" value="${savedValue}" ${field.required ? 'required' : ''}>
                </div>
            `;
        });
    }
    
    document.getElementById('configFields').innerHTML = fieldsHtml;
    
    // Set checkboxes
    document.getElementById('configActive').checked = saved?.is_active ?? true;
    document.getElementById('configPrimary').checked = saved?.is_primary ?? false;
    document.getElementById('configTestMode').checked = saved?.test_mode ?? true;
    
    // Show/hide delete button
    document.getElementById('deleteBtn').style.display = saved ? 'block' : 'none';
    
    document.getElementById('configModal').style.display = 'flex';
}

function closeConfigModal() {
    document.getElementById('configModal').style.display = 'none';
}

function testConnection(provider) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="test_connection">
        <input type="hidden" name="provider" value="${provider}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteConfig() {
    if (confirm('Are you sure you want to remove this configuration?')) {
        const provider = document.getElementById('configProvider').value;
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_config">
            <input type="hidden" name="provider" value="${provider}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on click outside
document.getElementById('configModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeConfigModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>

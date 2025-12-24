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
    'patient_record_protection' => false,
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
            $system_settings['patient_record_protection'] = isset($_POST['patient_record_protection']);
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

// Include admin header
include 'includes/admin-header.php';
?>
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
                            
                            <div class="setting-row">
                                <div class="setting-label">
                                    <strong>Patient Record Protection</strong>
                                    <span>Require DOB verification to access patient records (HIPAA)</span>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" name="patient_record_protection" <?php echo ($system_settings['patient_record_protection'] ?? false) ? 'checked' : ''; ?>>
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
<?php include 'includes/admin-footer.php'; ?>

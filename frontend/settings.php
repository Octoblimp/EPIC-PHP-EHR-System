<?php
/**
 * Openspace EHR - User Settings Page
 */
$page_title = 'Settings - Openspace EHR';

require_once 'includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$success_message = '';

// Get current settings from session or defaults
$settings = $_SESSION['user_settings'] ?? [
    'theme' => 'light',
    'sidebar_collapsed' => false,
    'default_tab' => 'summary',
    'patient_list_view' => 'list',
    'results_days' => 7,
    'auto_refresh' => true,
    'refresh_interval' => 60,
    'font_size' => 'medium',
    'compact_mode' => false,
    'show_photos' => true,
    'date_format' => 'MM/DD/YYYY',
    'time_format' => '12h'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'save_display':
            $settings['theme'] = $_POST['theme'] ?? 'light';
            $settings['font_size'] = $_POST['font_size'] ?? 'medium';
            $settings['compact_mode'] = isset($_POST['compact_mode']);
            $settings['show_photos'] = isset($_POST['show_photos']);
            $success_message = 'Display settings saved.';
            break;
        case 'save_clinical':
            $settings['default_tab'] = $_POST['default_tab'] ?? 'summary';
            $settings['patient_list_view'] = $_POST['patient_list_view'] ?? 'list';
            $settings['results_days'] = intval($_POST['results_days'] ?? 7);
            $success_message = 'Clinical workflow settings saved.';
            break;
        case 'save_system':
            $settings['auto_refresh'] = isset($_POST['auto_refresh']);
            $settings['refresh_interval'] = intval($_POST['refresh_interval'] ?? 60);
            $settings['date_format'] = $_POST['date_format'] ?? 'MM/DD/YYYY';
            $settings['time_format'] = $_POST['time_format'] ?? '12h';
            $success_message = 'System settings saved.';
            break;
    }
    $_SESSION['user_settings'] = $settings;
}

require_once 'includes/header.php';
?>

<main class="page-content settings-page">
    <div class="content-wrapper">
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p class="subtitle">Customize your Openspace EHR experience</p>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <div class="settings-layout">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <a href="#display" class="active"><i class="fas fa-desktop"></i> Display</a>
                <a href="#clinical"><i class="fas fa-stethoscope"></i> Clinical Workflow</a>
                <a href="#system"><i class="fas fa-sliders-h"></i> System</a>
                <a href="#shortcuts"><i class="fas fa-keyboard"></i> Keyboard Shortcuts</a>
                <a href="#accessibility"><i class="fas fa-universal-access"></i> Accessibility</a>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <!-- Display Settings -->
                <section id="display" class="settings-section">
                    <div class="section-header">
                        <h2><i class="fas fa-desktop"></i> Display Settings</h2>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="save_display">
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Theme</label>
                                <p>Choose your preferred color theme</p>
                            </div>
                            <div class="setting-control">
                                <div class="theme-options">
                                    <label class="theme-option <?php echo $settings['theme'] === 'light' ? 'selected' : ''; ?>">
                                        <input type="radio" name="theme" value="light" <?php echo $settings['theme'] === 'light' ? 'checked' : ''; ?>>
                                        <div class="theme-preview light">
                                            <div class="preview-header"></div>
                                            <div class="preview-body"></div>
                                        </div>
                                        <span>Light</span>
                                    </label>
                                    <label class="theme-option <?php echo $settings['theme'] === 'dark' ? 'selected' : ''; ?>">
                                        <input type="radio" name="theme" value="dark" <?php echo $settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                                        <div class="theme-preview dark">
                                            <div class="preview-header"></div>
                                            <div class="preview-body"></div>
                                        </div>
                                        <span>Dark</span>
                                    </label>
                                    <label class="theme-option <?php echo $settings['theme'] === 'high-contrast' ? 'selected' : ''; ?>">
                                        <input type="radio" name="theme" value="high-contrast" <?php echo $settings['theme'] === 'high-contrast' ? 'checked' : ''; ?>>
                                        <div class="theme-preview high-contrast">
                                            <div class="preview-header"></div>
                                            <div class="preview-body"></div>
                                        </div>
                                        <span>High Contrast</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Font Size</label>
                                <p>Adjust text size throughout the application</p>
                            </div>
                            <div class="setting-control">
                                <select name="font_size">
                                    <option value="small" <?php echo $settings['font_size'] === 'small' ? 'selected' : ''; ?>>Small</option>
                                    <option value="medium" <?php echo $settings['font_size'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="large" <?php echo $settings['font_size'] === 'large' ? 'selected' : ''; ?>>Large</option>
                                    <option value="xlarge" <?php echo $settings['font_size'] === 'xlarge' ? 'selected' : ''; ?>>Extra Large</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Compact Mode</label>
                                <p>Reduce spacing to show more information</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox" name="compact_mode" <?php echo $settings['compact_mode'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Show Patient Photos</label>
                                <p>Display patient photos in lists and charts</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox" name="show_photos" <?php echo $settings['show_photos'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Display Settings
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Clinical Workflow Settings -->
                <section id="clinical" class="settings-section">
                    <div class="section-header">
                        <h2><i class="fas fa-stethoscope"></i> Clinical Workflow</h2>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="save_clinical">
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Default Patient Tab</label>
                                <p>Tab to open when viewing a patient chart</p>
                            </div>
                            <div class="setting-control">
                                <select name="default_tab">
                                    <option value="summary" <?php echo $settings['default_tab'] === 'summary' ? 'selected' : ''; ?>>Summary</option>
                                    <option value="chart-review" <?php echo $settings['default_tab'] === 'chart-review' ? 'selected' : ''; ?>>Chart Review</option>
                                    <option value="results" <?php echo $settings['default_tab'] === 'results' ? 'selected' : ''; ?>>Results</option>
                                    <option value="mar" <?php echo $settings['default_tab'] === 'mar' ? 'selected' : ''; ?>>MAR</option>
                                    <option value="orders" <?php echo $settings['default_tab'] === 'orders' ? 'selected' : ''; ?>>Orders</option>
                                    <option value="notes" <?php echo $settings['default_tab'] === 'notes' ? 'selected' : ''; ?>>Notes</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Patient List View</label>
                                <p>Default view mode for patient lists</p>
                            </div>
                            <div class="setting-control">
                                <select name="patient_list_view">
                                    <option value="list" <?php echo $settings['patient_list_view'] === 'list' ? 'selected' : ''; ?>>List View</option>
                                    <option value="cards" <?php echo $settings['patient_list_view'] === 'cards' ? 'selected' : ''; ?>>Card View</option>
                                    <option value="compact" <?php echo $settings['patient_list_view'] === 'compact' ? 'selected' : ''; ?>>Compact List</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Results Time Frame</label>
                                <p>Default number of days to show in results</p>
                            </div>
                            <div class="setting-control">
                                <select name="results_days">
                                    <option value="1" <?php echo $settings['results_days'] == 1 ? 'selected' : ''; ?>>Last 24 Hours</option>
                                    <option value="3" <?php echo $settings['results_days'] == 3 ? 'selected' : ''; ?>>Last 3 Days</option>
                                    <option value="7" <?php echo $settings['results_days'] == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="14" <?php echo $settings['results_days'] == 14 ? 'selected' : ''; ?>>Last 14 Days</option>
                                    <option value="30" <?php echo $settings['results_days'] == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Workflow Settings
                            </button>
                        </div>
                    </form>
                </section>

                <!-- System Settings -->
                <section id="system" class="settings-section">
                    <div class="section-header">
                        <h2><i class="fas fa-sliders-h"></i> System Settings</h2>
                    </div>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="save_system">
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Auto-Refresh Data</label>
                                <p>Automatically refresh patient data at intervals</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox" name="auto_refresh" <?php echo $settings['auto_refresh'] ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Refresh Interval</label>
                                <p>How often to refresh data (in seconds)</p>
                            </div>
                            <div class="setting-control">
                                <select name="refresh_interval">
                                    <option value="30" <?php echo $settings['refresh_interval'] == 30 ? 'selected' : ''; ?>>30 seconds</option>
                                    <option value="60" <?php echo $settings['refresh_interval'] == 60 ? 'selected' : ''; ?>>1 minute</option>
                                    <option value="120" <?php echo $settings['refresh_interval'] == 120 ? 'selected' : ''; ?>>2 minutes</option>
                                    <option value="300" <?php echo $settings['refresh_interval'] == 300 ? 'selected' : ''; ?>>5 minutes</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Date Format</label>
                                <p>Preferred format for displaying dates</p>
                            </div>
                            <div class="setting-control">
                                <select name="date_format">
                                    <option value="MM/DD/YYYY" <?php echo $settings['date_format'] === 'MM/DD/YYYY' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                    <option value="DD/MM/YYYY" <?php echo $settings['date_format'] === 'DD/MM/YYYY' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    <option value="YYYY-MM-DD" <?php echo $settings['date_format'] === 'YYYY-MM-DD' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Time Format</label>
                                <p>12-hour or 24-hour time display</p>
                            </div>
                            <div class="setting-control">
                                <select name="time_format">
                                    <option value="12h" <?php echo $settings['time_format'] === '12h' ? 'selected' : ''; ?>>12-hour (2:30 PM)</option>
                                    <option value="24h" <?php echo $settings['time_format'] === '24h' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save System Settings
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Keyboard Shortcuts -->
                <section id="shortcuts" class="settings-section">
                    <div class="section-header">
                        <h2><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h2>
                    </div>
                    <div class="shortcuts-list">
                        <div class="shortcut-category">
                            <h3>Navigation</h3>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Alt</kbd> + <kbd>H</kbd></span>
                                <span class="shortcut-desc">Go to Home</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Alt</kbd> + <kbd>P</kbd></span>
                                <span class="shortcut-desc">Patient Lists</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Alt</kbd> + <kbd>F</kbd></span>
                                <span class="shortcut-desc">Find Patient</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Alt</kbd> + <kbd>S</kbd></span>
                                <span class="shortcut-desc">Schedule</span>
                            </div>
                        </div>
                        <div class="shortcut-category">
                            <h3>Patient Chart</h3>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>1</kbd> - <kbd>8</kbd></span>
                                <span class="shortcut-desc">Switch chart tabs</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Ctrl</kbd> + <kbd>N</kbd></span>
                                <span class="shortcut-desc">New Note</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Ctrl</kbd> + <kbd>O</kbd></span>
                                <span class="shortcut-desc">New Order</span>
                            </div>
                        </div>
                        <div class="shortcut-category">
                            <h3>General</h3>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>/</kbd></span>
                                <span class="shortcut-desc">Focus Search</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>Esc</kbd></span>
                                <span class="shortcut-desc">Close Modal/Menu</span>
                            </div>
                            <div class="shortcut-item">
                                <span class="shortcut-keys"><kbd>?</kbd></span>
                                <span class="shortcut-desc">Show Shortcuts Help</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Accessibility -->
                <section id="accessibility" class="settings-section">
                    <div class="section-header">
                        <h2><i class="fas fa-universal-access"></i> Accessibility</h2>
                    </div>
                    <div class="settings-form">
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Screen Reader Optimization</label>
                                <p>Enhanced support for screen readers</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Reduce Motion</label>
                                <p>Minimize animations and transitions</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <label>Focus Indicators</label>
                                <p>Enhanced visual focus indicators</p>
                            </div>
                            <div class="setting-control">
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
.settings-page {
    padding: 20px;
    background: #f0f4f8;
    min-height: calc(100vh - 100px);
}

.content-wrapper {
    max-width: 1100px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 24px;
    color: #1a4a5e;
    margin: 0;
}

.page-header .subtitle {
    color: #666;
    margin-top: 5px;
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

.settings-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 25px;
}

.settings-nav {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.settings-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #555;
    text-decoration: none;
    border-left: 3px solid transparent;
}

.settings-nav a:hover {
    background: #f5f8fa;
}

.settings-nav a.active {
    background: #f0f8ff;
    border-left-color: #1a4a5e;
    color: #1a4a5e;
    font-weight: 500;
}

.settings-nav a i {
    width: 20px;
    text-align: center;
}

.settings-content {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.settings-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
}

.section-header h2 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.settings-form {
    padding: 10px 0;
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 25px;
    border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 4px;
}

.setting-info p {
    margin: 0;
    color: #888;
    font-size: 13px;
}

.setting-control select {
    padding: 8px 12px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
    min-width: 180px;
}

.setting-control select:focus {
    outline: none;
    border-color: #1a4a5e;
}

/* Theme Options */
.theme-options {
    display: flex;
    gap: 15px;
}

.theme-option {
    cursor: pointer;
    text-align: center;
}

.theme-option input {
    display: none;
}

.theme-preview {
    width: 80px;
    height: 55px;
    border-radius: 6px;
    overflow: hidden;
    border: 2px solid #ddd;
    margin-bottom: 5px;
}

.theme-option.selected .theme-preview {
    border-color: #1a4a5e;
    box-shadow: 0 0 0 2px rgba(26, 74, 94, 0.2);
}

.theme-preview .preview-header {
    height: 15px;
}

.theme-preview .preview-body {
    height: 40px;
}

.theme-preview.light .preview-header { background: #1a4a5e; }
.theme-preview.light .preview-body { background: #f0f4f8; }

.theme-preview.dark .preview-header { background: #0a1f2a; }
.theme-preview.dark .preview-body { background: #1a2a35; }

.theme-preview.high-contrast .preview-header { background: #000; }
.theme-preview.high-contrast .preview-body { background: #fff; }

.theme-option span {
    font-size: 12px;
    color: #666;
}

/* Toggle Switch */
.switch {
    position: relative;
    width: 50px;
    height: 26px;
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
    border-radius: 26px;
    transition: 0.3s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
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
    transform: translateX(24px);
}

.form-actions {
    padding: 15px 25px;
    border-top: 1px solid #e0e0e0;
    background: #fafbfc;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
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

/* Shortcuts */
.shortcuts-list {
    padding: 20px;
}

.shortcut-category {
    margin-bottom: 25px;
}

.shortcut-category:last-child {
    margin-bottom: 0;
}

.shortcut-category h3 {
    font-size: 13px;
    color: #888;
    text-transform: uppercase;
    margin: 0 0 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.shortcut-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
}

.shortcut-keys {
    display: flex;
    gap: 5px;
}

kbd {
    display: inline-block;
    padding: 3px 8px;
    font-size: 12px;
    background: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 1px 0 #bbb;
    font-family: monospace;
}

.shortcut-desc {
    color: #555;
    font-size: 13px;
}

@media (max-width: 800px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    
    .settings-nav {
        position: static;
        display: flex;
        overflow-x: auto;
    }
    
    .settings-nav a {
        white-space: nowrap;
        border-left: none;
        border-bottom: 3px solid transparent;
    }
    
    .settings-nav a.active {
        border-bottom-color: #1a4a5e;
    }
}
</style>

<script>
// Theme option selection
document.querySelectorAll('.theme-option input').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
        this.closest('.theme-option').classList.add('selected');
    });
});

// Settings navigation
document.querySelectorAll('.settings-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.settings-nav a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        const target = this.getAttribute('href');
        document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

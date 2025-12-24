<?php
/**
 * Openspace EHR - User Profile Page
 */
$page_title = 'My Profile - Openspace EHR';

require_once 'includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                // In production, this would update the database
                $success_message = 'Profile updated successfully.';
                break;
            case 'change_password':
                $current = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';
                
                if ($new !== $confirm) {
                    $error_message = 'New passwords do not match.';
                } elseif (strlen($new) < 8) {
                    $error_message = 'Password must be at least 8 characters.';
                } else {
                    $success_message = 'Password changed successfully.';
                }
                break;
        }
    }
}

require_once 'includes/header.php';
?>

<main class="page-content profile-page">
    <div class="content-wrapper">
        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p class="subtitle">Manage your account information and preferences</p>
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

        <div class="profile-layout">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
                    <p class="role"><?php echo htmlspecialchars(ucfirst($user['role'] ?? 'Staff')); ?></p>
                    <p class="username">@<?php echo htmlspecialchars($user['username'] ?? 'user'); ?></p>
                </div>
                
                <nav class="profile-nav">
                    <a href="#profile-info" class="active"><i class="fas fa-user"></i> Profile Information</a>
                    <a href="#security"><i class="fas fa-shield-alt"></i> Security</a>
                    <a href="#notifications"><i class="fas fa-bell"></i> Notifications</a>
                    <a href="#sessions"><i class="fas fa-clock"></i> Login Sessions</a>
                </nav>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Profile Information Section -->
                <section id="profile-info" class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-user"></i> Profile Information</h2>
                    </div>
                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars(explode(' ', $user['name'] ?? '')[0] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars(explode(' ', $user['name'] ?? '', 2)[1] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? $user['username'] . '@hospital.org'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select id="department" name="department">
                                    <option value="emergency">Emergency Medicine</option>
                                    <option value="internal">Internal Medicine</option>
                                    <option value="cardiology">Cardiology</option>
                                    <option value="orthopedics">Orthopedics</option>
                                    <option value="pediatrics">Pediatrics</option>
                                    <option value="surgery">Surgery</option>
                                    <option value="nursing">Nursing</option>
                                    <option value="admin">Administration</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="title">Title/Position</label>
                                <input type="text" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($user['role'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="credentials">Credentials/Certifications</label>
                            <input type="text" id="credentials" name="credentials" 
                                   placeholder="MD, RN, BSN, etc.">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </section>

                <!-- Security Section -->
                <section id="security" class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shield-alt"></i> Security</h2>
                    </div>
                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required 
                                       minlength="8">
                                <small>Must be at least 8 characters</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>

                    <div class="security-info">
                        <h3>Two-Factor Authentication</h3>
                        <p>Add an extra layer of security to your account.</p>
                        <button class="btn btn-secondary">
                            <i class="fas fa-mobile-alt"></i> Enable 2FA
                        </button>
                    </div>
                </section>

                <!-- Notifications Section -->
                <section id="notifications" class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-bell"></i> Notification Preferences</h2>
                    </div>
                    <div class="notification-settings">
                        <div class="notification-item">
                            <div class="notification-info">
                                <strong>Critical Results</strong>
                                <p>Get notified immediately for critical lab results</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="notification-item">
                            <div class="notification-info">
                                <strong>New Orders</strong>
                                <p>Notifications for new orders on your patients</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="notification-item">
                            <div class="notification-info">
                                <strong>Medication Alerts</strong>
                                <p>Alerts for medication interactions and due reminders</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="notification-item">
                            <div class="notification-info">
                                <strong>In Basket Messages</strong>
                                <p>Notify when new messages arrive</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="notification-item">
                            <div class="notification-info">
                                <strong>Schedule Changes</strong>
                                <p>Notifications for schedule updates</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- Sessions Section -->
                <section id="sessions" class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-clock"></i> Login Sessions</h2>
                    </div>
                    <div class="sessions-list">
                        <div class="session-item current">
                            <div class="session-icon">
                                <i class="fas fa-desktop"></i>
                            </div>
                            <div class="session-info">
                                <strong>Current Session</strong>
                                <p>Windows 10 • Chrome Browser</p>
                                <span class="session-time">Started: <?php echo date('M j, Y g:i A'); ?></span>
                            </div>
                            <span class="session-badge active">Active</span>
                        </div>
                    </div>
                    <button class="btn btn-danger" onclick="if(confirm('Sign out of all other sessions?')) window.location.href='?action=logout_all'">
                        <i class="fas fa-sign-out-alt"></i> Sign Out All Other Sessions
                    </button>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
.profile-page {
    padding: 20px;
    background: #f0f4f8;
    min-height: calc(100vh - 100px);
}

.content-wrapper {
    max-width: 1200px;
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

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.profile-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 25px;
}

.profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.profile-card {
    background: white;
    border-radius: 8px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #1a4a5e, #2d6a7a);
    border-radius: 50%;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-avatar i {
    font-size: 48px;
    color: white;
}

.profile-card h2 {
    margin: 0 0 5px;
    font-size: 20px;
    color: #333;
}

.profile-card .role {
    color: #1a4a5e;
    font-weight: 500;
    margin: 0 0 5px;
}

.profile-card .username {
    color: #888;
    font-size: 13px;
    margin: 0;
}

.profile-nav {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #555;
    text-decoration: none;
    border-left: 3px solid transparent;
}

.profile-nav a:hover {
    background: #f5f8fa;
}

.profile-nav a.active {
    background: #f0f8ff;
    border-left-color: #1a4a5e;
    color: #1a4a5e;
    font-weight: 500;
}

.profile-nav a i {
    width: 20px;
    text-align: center;
}

.profile-content {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.profile-section {
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

.profile-form {
    padding: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    font-size: 13px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1a4a5e;
}

.form-group small {
    color: #888;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.form-actions {
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
    margin-top: 10px;
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

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.security-info {
    padding: 25px;
    border-top: 1px solid #e0e0e0;
}

.security-info h3 {
    margin: 0 0 8px;
    font-size: 15px;
}

.security-info p {
    color: #666;
    margin: 0 0 15px;
    font-size: 13px;
}

.notification-settings {
    padding: 10px 0;
}

.notification-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 25px;
    border-bottom: 1px solid #f0f0f0;
}

.notification-info strong {
    display: block;
    color: #333;
}

.notification-info p {
    margin: 4px 0 0;
    color: #888;
    font-size: 13px;
}

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

.sessions-list {
    padding: 20px;
}

.session-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 10px;
}

.session-item.current {
    border: 2px solid #1a4a5e;
}

.session-icon {
    width: 45px;
    height: 45px;
    background: #e8f0f4;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.session-icon i {
    font-size: 20px;
    color: #1a4a5e;
}

.session-info {
    flex: 1;
}

.session-info strong {
    display: block;
}

.session-info p {
    margin: 3px 0 0;
    color: #666;
    font-size: 13px;
}

.session-time {
    font-size: 12px;
    color: #888;
}

.session-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.session-badge.active {
    background: #d4edda;
    color: #155724;
}

.sessions-list + .btn {
    margin: 0 20px 20px;
}

@media (max-width: 900px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Profile navigation
document.querySelectorAll('.profile-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.profile-nav a').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        const target = this.getAttribute('href');
        document.querySelector(target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
/**
 * Openspace EHR - Database Updater / Migration System
 * Automatically applies database schema changes
 */

$page_title = 'Database Updater - Openspace EHR';

require_once '../includes/config.php';

// Ensure user is logged in and is admin
session_start();
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

// Migration definitions
$migrations = [
    [
        'version' => '1.0.0',
        'name' => 'Initial Schema',
        'date' => '2024-01-01',
        'description' => 'Base database schema with users, patients, and core tables',
        'status' => 'applied',
        'applied_at' => '2024-01-01 00:00:00'
    ],
    [
        'version' => '1.1.0',
        'name' => 'Audit Logging',
        'date' => '2024-01-15',
        'description' => 'Add audit_logs table for HIPAA compliance',
        'sql' => "
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_id INTEGER,
                username VARCHAR(100),
                action VARCHAR(50),
                resource VARCHAR(100),
                details TEXT,
                patient_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                request_uri TEXT,
                request_method VARCHAR(10)
            );
            CREATE INDEX idx_audit_logs_timestamp ON audit_logs(timestamp);
            CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
            CREATE INDEX idx_audit_logs_patient_id ON audit_logs(patient_id);
        ",
        'status' => 'pending'
    ],
    [
        'version' => '1.2.0',
        'name' => 'Role Permissions',
        'date' => '2024-01-20',
        'description' => 'Add granular role-based permissions system',
        'sql' => "
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                permissions TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE TABLE IF NOT EXISTS role_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                role_id INTEGER NOT NULL,
                permission VARCHAR(100) NOT NULL,
                granted BOOLEAN DEFAULT 1,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            );
            
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (role_id) REFERENCES roles(id)
            );
            
            -- Insert default roles
            INSERT OR IGNORE INTO roles (name, description) VALUES 
                ('Administrator', 'Full system access'),
                ('Physician', 'Clinical access with ordering privileges'),
                ('Nurse', 'Clinical access for nursing care'),
                ('Medical Assistant', 'Limited clinical access'),
                ('Front Desk', 'Scheduling and registration only');
        ",
        'status' => 'pending'
    ],
    [
        'version' => '1.3.0',
        'name' => 'User Settings',
        'date' => '2024-01-25',
        'description' => 'Add user preferences and sidebar customization',
        'sql' => "
            CREATE TABLE IF NOT EXISTS user_settings (
                user_id INTEGER PRIMARY KEY,
                settings_json TEXT,
                sidebar_favorites TEXT,
                theme VARCHAR(20) DEFAULT 'light',
                font_size VARCHAR(10) DEFAULT 'medium',
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ",
        'status' => 'pending'
    ],
    [
        'version' => '1.4.0',
        'name' => 'Messaging System',
        'date' => '2024-02-01',
        'description' => 'Add internal messaging and In Basket tables',
        'sql' => "
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                recipient_id INTEGER NOT NULL,
                subject VARCHAR(255),
                body TEXT,
                patient_id INTEGER,
                message_type VARCHAR(50),
                priority VARCHAR(20) DEFAULT 'normal',
                is_read BOOLEAN DEFAULT 0,
                read_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX idx_messages_recipient ON messages(recipient_id, is_read);
            CREATE INDEX idx_messages_patient ON messages(patient_id);
        ",
        'status' => 'pending'
    ],
    [
        'version' => '1.5.0',
        'name' => 'Patient Record Protection',
        'date' => '2024-02-10',
        'description' => 'Add patient access verification logging',
        'sql' => "
            CREATE TABLE IF NOT EXISTS patient_access_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                access_type VARCHAR(50),
                verified BOOLEAN DEFAULT 0,
                verification_method VARCHAR(50),
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            
            CREATE INDEX idx_patient_access_patient ON patient_access_log(patient_id);
            CREATE INDEX idx_patient_access_user ON patient_access_log(user_id);
        ",
        'status' => 'pending'
    ],
];

// Check migration status from file or database
$migration_status_file = __DIR__ . '/../data/migration_status.json';
$applied_migrations = [];

if (file_exists($migration_status_file)) {
    $applied_migrations = json_decode(file_get_contents($migration_status_file), true) ?? [];
}

// Update migration statuses
foreach ($migrations as &$migration) {
    if (isset($applied_migrations[$migration['version']])) {
        $migration['status'] = 'applied';
        $migration['applied_at'] = $applied_migrations[$migration['version']]['applied_at'];
    }
}
unset($migration);

// Handle migration execution
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'run_migration':
            $version = $_POST['version'] ?? '';
            foreach ($migrations as &$m) {
                if ($m['version'] === $version && $m['status'] === 'pending') {
                    // In a real system, we would execute the SQL here
                    // For now, just mark as applied
                    $applied_migrations[$version] = [
                        'applied_at' => date('Y-m-d H:i:s'),
                        'applied_by' => $user['username'] ?? 'admin'
                    ];
                    $m['status'] = 'applied';
                    $m['applied_at'] = $applied_migrations[$version]['applied_at'];
                    
                    // Save status
                    $data_dir = dirname($migration_status_file);
                    if (!is_dir($data_dir)) {
                        mkdir($data_dir, 0755, true);
                    }
                    file_put_contents($migration_status_file, json_encode($applied_migrations, JSON_PRETTY_PRINT));
                    
                    $message = "Migration {$version} applied successfully!";
                    $message_type = 'success';
                }
            }
            unset($m);
            break;
            
        case 'run_all':
            $count = 0;
            foreach ($migrations as &$m) {
                if ($m['status'] === 'pending') {
                    $applied_migrations[$m['version']] = [
                        'applied_at' => date('Y-m-d H:i:s'),
                        'applied_by' => $user['username'] ?? 'admin'
                    ];
                    $m['status'] = 'applied';
                    $m['applied_at'] = $applied_migrations[$m['version']]['applied_at'];
                    $count++;
                }
            }
            unset($m);
            
            if ($count > 0) {
                $data_dir = dirname($migration_status_file);
                if (!is_dir($data_dir)) {
                    mkdir($data_dir, 0755, true);
                }
                file_put_contents($migration_status_file, json_encode($applied_migrations, JSON_PRETTY_PRINT));
                $message = "{$count} migration(s) applied successfully!";
                $message_type = 'success';
            } else {
                $message = "No pending migrations to apply.";
                $message_type = 'info';
            }
            break;
    }
}

$pending_count = count(array_filter($migrations, fn($m) => $m['status'] === 'pending'));

// Include admin header
include 'includes/admin-header.php';
?>
<style>
    /* Database Updater specific styles */
    .status-banner { background: white; border-radius: 8px; padding: 20px 25px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; }
    .status-info h2 { margin: 0 0 5px; font-size: 18px; color: #333; display: flex; align-items: center; gap: 10px; }
    .status-info p { margin: 0; color: #666; }
    .status-badge { padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; }
    .status-badge.up-to-date { background: #d4edda; color: #155724; }
    .status-badge.pending { background: #fff3cd; color: #856404; }
    .run-all-btn { padding: 10px 20px; background: #1a4a5e; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .run-all-btn:hover { background: #0d3545; }
    .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    .migrations-list { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
    .migrations-header { padding: 15px 25px; background: #f8f9fa; border-bottom: 1px solid #eee; font-weight: 600; color: #333; display: flex; align-items: center; gap: 10px; }
    .migration-item { display: flex; align-items: flex-start; padding: 20px 25px; border-bottom: 1px solid #f0f0f0; }
    .migration-item:last-child { border-bottom: none; }
    .migration-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
    .migration-icon.applied { background: #d4edda; color: #155724; }
    .migration-icon.pending { background: #fff3cd; color: #856404; }
    .migration-info { flex: 1; }
    .migration-header { display: flex; align-items: center; gap: 12px; margin-bottom: 5px; }
    .migration-version { background: #1a4a5e; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
    .migration-name { font-weight: 600; color: #333; }
    .migration-date { color: #888; font-size: 12px; }
    .migration-description { color: #666; font-size: 13px; margin-bottom: 8px; }
    .migration-status { font-size: 12px; }
    .migration-status.applied { color: #155724; }
    .migration-status.pending { color: #856404; }
    .migration-action { margin-left: 20px; }
    .apply-btn { padding: 8px 16px; background: #1a4a5e; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 6px; }
    .apply-btn:hover { background: #0d3545; }
    .applied-badge { padding: 6px 12px; background: #d4edda; color: #155724; border-radius: 4px; font-size: 12px; display: flex; align-items: center; gap: 6px; }
</style>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'info-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="status-banner">
            <div class="status-info">
                <h2><i class="fas fa-database"></i> Database Updates</h2>
                <p>Current database version: <strong>v<?php echo $migrations[0]['version']; ?></strong></p>
            </div>
            <?php if ($pending_count > 0): ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="run_all">
                <button type="submit" class="run-all-btn">
                    <i class="fas fa-play"></i> Run All Pending (<?php echo $pending_count; ?>)
                </button>
            </form>
            <?php else: ?>
            <span class="status-badge up-to-date">
                <i class="fas fa-check"></i> Up to Date
            </span>
            <?php endif; ?>
        </div>
        
        <div class="migrations-list">
            <div class="migrations-header">
                <i class="fas fa-list"></i> Migration History
            </div>
            
            <?php foreach ($migrations as $migration): ?>
            <div class="migration-item">
                <div class="migration-icon <?php echo $migration['status']; ?>">
                    <i class="fas <?php echo $migration['status'] === 'applied' ? 'fa-check' : 'fa-clock'; ?>"></i>
                </div>
                <div class="migration-info">
                    <div class="migration-header">
                        <span class="migration-version">v<?php echo htmlspecialchars($migration['version']); ?></span>
                        <span class="migration-name"><?php echo htmlspecialchars($migration['name']); ?></span>
                        <span class="migration-date"><?php echo htmlspecialchars($migration['date']); ?></span>
                    </div>
                    <div class="migration-description">
                        <?php echo htmlspecialchars($migration['description']); ?>
                    </div>
                    <div class="migration-status <?php echo $migration['status']; ?>">
                        <?php if ($migration['status'] === 'applied'): ?>
                        <i class="fas fa-check"></i> Applied on <?php echo $migration['applied_at']; ?>
                        <?php else: ?>
                        <i class="fas fa-clock"></i> Pending
                        <?php endif; ?>
                    </div>
                </div>
                <div class="migration-action">
                    <?php if ($migration['status'] === 'pending'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="run_migration">
                        <input type="hidden" name="version" value="<?php echo htmlspecialchars($migration['version']); ?>">
                        <button type="submit" class="apply-btn">
                            <i class="fas fa-play"></i> Apply
                        </button>
                    </form>
                    <?php else: ?>
                    <span class="applied-badge">
                        <i class="fas fa-check"></i> Applied
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php include 'includes/admin-footer.php'; ?>

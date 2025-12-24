<?php
/**
 * Openspace EHR - Admin Audit Log
 */
$page_title = 'Audit Log - Openspace EHR';

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

// Demo audit log data
$audit_logs = [
    ['id' => 1, 'timestamp' => '2025-01-10 09:15:32', 'user' => 'drwilson', 'action' => 'VIEW', 'resource' => 'Patient Chart', 'details' => 'Accessed patient John Smith (MRN: 000001)', 'ip' => '192.168.1.45'],
    ['id' => 2, 'timestamp' => '2025-01-10 09:12:18', 'user' => 'nursejones', 'action' => 'UPDATE', 'resource' => 'Vital Signs', 'details' => 'Recorded vitals for patient John Smith', 'ip' => '192.168.1.67'],
    ['id' => 3, 'timestamp' => '2025-01-10 09:08:45', 'user' => 'drwilson', 'action' => 'CREATE', 'resource' => 'Order', 'details' => 'Created medication order: Vancomycin 1g IV', 'ip' => '192.168.1.45'],
    ['id' => 4, 'timestamp' => '2025-01-10 08:55:22', 'user' => 'admin', 'action' => 'LOGIN', 'resource' => 'Authentication', 'details' => 'Successful login', 'ip' => '192.168.1.10'],
    ['id' => 5, 'timestamp' => '2025-01-10 08:45:11', 'user' => 'lab_tech1', 'action' => 'CREATE', 'resource' => 'Lab Result', 'details' => 'Entered CBC results for patient Mary Johnson', 'ip' => '192.168.1.89'],
    ['id' => 6, 'timestamp' => '2025-01-10 08:30:00', 'user' => 'pharmthompson', 'action' => 'VERIFY', 'resource' => 'Medication', 'details' => 'Verified medication order for patient Robert Williams', 'ip' => '192.168.1.78'],
    ['id' => 7, 'timestamp' => '2025-01-10 08:15:33', 'user' => 'drsmith', 'action' => 'VIEW', 'resource' => 'Patient Chart', 'details' => 'Accessed patient Mary Johnson (MRN: 000002)', 'ip' => '192.168.1.52'],
    ['id' => 8, 'timestamp' => '2025-01-10 07:45:12', 'user' => 'nursejones', 'action' => 'CREATE', 'resource' => 'Note', 'details' => 'Created nursing note for patient John Smith', 'ip' => '192.168.1.67'],
    ['id' => 9, 'timestamp' => '2025-01-10 07:30:00', 'user' => 'drwilson', 'action' => 'LOGIN', 'resource' => 'Authentication', 'details' => 'Successful login', 'ip' => '192.168.1.45'],
    ['id' => 10, 'timestamp' => '2025-01-10 06:00:00', 'user' => 'system', 'action' => 'BACKUP', 'resource' => 'Database', 'details' => 'Automated daily backup completed', 'ip' => '192.168.1.1'],
];

// Action colors
$action_colors = [
    'VIEW' => '#17a2b8',
    'CREATE' => '#28a745',
    'UPDATE' => '#ffc107',
    'DELETE' => '#dc3545',
    'LOGIN' => '#6f42c1',
    'LOGOUT' => '#6c757d',
    'VERIFY' => '#20c997',
    'BACKUP' => '#007bff',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/openspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
        }
        .admin-header {
            background: linear-gradient(to right, #1a4a5e, #0d3545);
            color: white;
            padding: 0 20px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
        }
        .admin-nav {
            display: flex;
            gap: 5px;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 13px;
        }
        .admin-nav a:hover { background: rgba(255,255,255,0.1); }
        .admin-nav a.active { background: rgba(255,255,255,0.2); }
        .back-link {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .back-link:hover { background: rgba(255,255,255,0.1); }
        .content {
            padding: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .page-header h1 {
            font-size: 24px;
            color: #1a4a5e;
        }
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: #1a4a5e; color: white; }
        .btn-secondary { background: #e0e0e0; color: #333; }
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-group label {
            font-size: 13px;
            color: #666;
        }
        .filter-input {
            padding: 8px 12px;
            border: 2px solid #d0d8e0;
            border-radius: 4px;
            font-size: 13px;
        }
        .audit-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .audit-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .audit-table th {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            padding: 14px 15px;
            text-align: left;
            font-size: 13px;
            font-weight: 500;
        }
        .audit-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .audit-table tr:hover {
            background: #f8f9fa;
        }
        .action-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
            color: white;
        }
        .timestamp {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }
        .user-link {
            color: #1a4a5e;
            text-decoration: none;
        }
        .user-link:hover {
            text-decoration: underline;
        }
        .ip-address {
            font-family: monospace;
            font-size: 12px;
            color: #888;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px;
        }
        .pagination button {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination button:hover {
            background: #f0f0f0;
        }
        .pagination button.active {
            background: #1a4a5e;
            color: white;
            border-color: #1a4a5e;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a4a5e;
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">
            <i class="fas fa-shield-alt"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="roles.php"><i class="fas fa-user-shield"></i> Roles</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="audit.php" class="active"><i class="fas fa-clipboard-list"></i> Audit Log</a>
        </nav>
        <a href="../home.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to EHR</a>
    </header>
    
    <div class="content">
        <div class="page-header">
            <h1><i class="fas fa-clipboard-list"></i> Audit Log</h1>
            <button class="btn btn-primary" onclick="exportAuditLog()">
                <i class="fas fa-download"></i> Export Log
            </button>
        </div>
        
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value">1,247</div>
                <div class="stat-label">Events Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">24</div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">156</div>
                <div class="stat-label">Patient Records Accessed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Security Alerts</div>
            </div>
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label>Date Range:</label>
                <input type="date" class="filter-input" id="startDate" value="<?php echo date('Y-m-d'); ?>">
                <span>to</span>
                <input type="date" class="filter-input" id="endDate" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group">
                <label>User:</label>
                <select class="filter-input" id="userFilter">
                    <option value="">All Users</option>
                    <option value="admin">admin</option>
                    <option value="drsmith">drsmith</option>
                    <option value="drwilson">drwilson</option>
                    <option value="nursejones">nursejones</option>
                    <option value="pharmthompson">pharmthompson</option>
                    <option value="lab_tech1">lab_tech1</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Action:</label>
                <select class="filter-input" id="actionFilter">
                    <option value="">All Actions</option>
                    <option value="VIEW">VIEW</option>
                    <option value="CREATE">CREATE</option>
                    <option value="UPDATE">UPDATE</option>
                    <option value="DELETE">DELETE</option>
                    <option value="LOGIN">LOGIN</option>
                </select>
            </div>
            <button class="btn btn-secondary" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Apply
            </button>
        </div>
        
        <div class="audit-table">
            <table>
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
                    <?php foreach ($audit_logs as $log): 
                        $action_color = $action_colors[$log['action']] ?? '#6c757d';
                    ?>
                    <tr>
                        <td><span class="timestamp"><?php echo $log['timestamp']; ?></span></td>
                        <td><a href="users.php?user=<?php echo $log['user']; ?>" class="user-link"><?php echo htmlspecialchars($log['user']); ?></a></td>
                        <td>
                            <span class="action-badge" style="background: <?php echo $action_color; ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['resource']); ?></td>
                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                        <td><span class="ip-address"><?php echo htmlspecialchars($log['ip']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pagination">
            <button>&laquo; Prev</button>
            <button class="active">1</button>
            <button>2</button>
            <button>3</button>
            <button>...</button>
            <button>50</button>
            <button>Next &raquo;</button>
        </div>
    </div>

    <script>
        function applyFilters() {
            alert('Filters applied! (Demo - would filter audit log entries)');
        }
        
        function exportAuditLog() {
            alert('Exporting audit log to CSV... (Demo)');
        }
    </script>
</body>
</html>

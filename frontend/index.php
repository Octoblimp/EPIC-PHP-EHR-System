<?php
/**
 * Facility Overview / Home Dashboard
 * Main landing page with census, department stats, alerts, and quick actions
 */
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
    $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /login.php');
    exit;
}

// Session timeout check (HIPAA requirement - 15 minutes)
$session_timeout = 15 * 60; // 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_destroy();
    header('Location: /login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/api.php';

// Initialize API client
$api = new ApiClient();

// Helper function to check permissions
function hasPermission($permission) {
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

// Fetch dashboard data from API
try {
    // Get facility census
    $censusData = $api->get('admin/census') ?? [];
    
    // Get department status
    $departmentsData = $api->get('admin/departments') ?? [];
    
    // Get today's schedule
    $scheduleData = $api->get('scheduling/today') ?? [];
    
    // Get alerts for current user
    $alertsData = $api->get('alerts/user/' . ($_SESSION['user_id'] ?? 0)) ?? [];
    
    // Get tasks for current user
    $tasksData = $api->get('tasks/user/' . ($_SESSION['user_id'] ?? 0)) ?? [];
    
    // Get recent admissions
    $admissionsData = $api->get('patients/recent-admissions') ?? [];
    
    // Get critical results pending acknowledgment
    $criticalResultsData = $api->get('labs/critical/pending') ?? [];
    
    // Get unread message count
    $messagesCount = $api->get('messages/unread/count') ?? ['count' => 0];
    
} catch (Exception $e) {
    // Log error but don't crash - show empty state
    error_log('Dashboard API error: ' . $e->getMessage());
    $censusData = [];
    $departmentsData = [];
    $scheduleData = [];
    $alertsData = [];
    $tasksData = [];
    $admissionsData = [];
    $criticalResultsData = [];
    $messagesCount = ['count' => 0];
}

// Get user info for greeting
$user = $_SESSION['full_name'] ?? 'User';
$current_hour = date('H');
if ($current_hour < 12) {
    $greeting = 'Good morning';
} elseif ($current_hour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Calculate totals from fetched data
$totalCensus = $censusData['total'] ?? 0;
$inpatientCount = $censusData['inpatient'] ?? 0;
$observationCount = $censusData['observation'] ?? 0;
$edCount = $censusData['ed'] ?? 0;

$scheduledCount = $scheduleData['scheduled'] ?? 0;
$arrivedCount = $scheduleData['arrived'] ?? 0;
$completedCount = $scheduleData['completed'] ?? 0;
$noShowCount = $scheduleData['no_show'] ?? 0;
$upcomingAppointments = $scheduleData['upcoming'] ?? [];

$pendingTasksCount = count($tasksData['data'] ?? []);
$criticalAlertsCount = count(array_filter($alertsData['data'] ?? [], fn($a) => ($a['severity'] ?? '') === 'critical'));
$pendingResultsCount = count($criticalResultsData['data'] ?? []);

$pageTitle = 'Facility Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | EPIC EHR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg-color); color: var(--text-primary); }
        .dashboard-container { min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Navigation */
        .dashboard-nav { background: white; padding: 0 20px; height: 60px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 20px; color: var(--primary-color); }
        .nav-brand i { font-size: 28px; }
        .nav-search { flex: 1; max-width: 500px; margin: 0 40px; }
        .search-box { display: flex; align-items: center; background: var(--bg-color); border-radius: 8px; padding: 8px 15px; gap: 10px; }
        .search-box input { flex: 1; border: none; background: transparent; outline: none; font-size: 14px; }
        .search-box kbd { background: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--text-secondary); border: 1px solid var(--border-color); }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-btn { width: 40px; height: 40px; border-radius: 8px; border: none; background: var(--bg-color); cursor: pointer; position: relative; transition: all 0.2s; }
        .nav-btn:hover { background: var(--primary-color); color: white; }
        .nav-btn .badge { position: absolute; top: -5px; right: -5px; background: var(--danger-color); color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; }
        .nav-btn .badge:empty { display: none; }
        .user-menu { position: relative; margin-left: 10px; }
        .user-btn { display: flex; align-items: center; gap: 10px; padding: 5px 10px; border: none; background: transparent; cursor: pointer; border-radius: 8px; }
        .user-btn:hover { background: var(--bg-color); }
        .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .user-dropdown { position: absolute; top: 100%; right: 0; background: white; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); min-width: 200px; display: none; padding: 10px 0; }
        .user-dropdown.show { display: block; }
        .user-dropdown a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: var(--text-primary); text-decoration: none; }
        .user-dropdown a:hover { background: var(--bg-color); }
        .logout-link { color: var(--danger-color) !important; }
        
        /* Content Layout */
        .dashboard-content { display: flex; flex: 1; }
        
        /* Sidebar */
        .dashboard-sidebar { width: 240px; background: white; padding: 20px 0; border-right: 1px solid var(--border-color); overflow-y: auto; }
        .sidebar-section { margin-bottom: 25px; }
        .sidebar-section h4 { font-size: 11px; text-transform: uppercase; color: var(--text-secondary); padding: 0 20px; margin-bottom: 10px; letter-spacing: 0.5px; }
        .sidebar-link { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: var(--text-primary); text-decoration: none; transition: all 0.2s; }
        .sidebar-link:hover { background: var(--bg-color); color: var(--primary-color); }
        .sidebar-link i { width: 20px; text-align: center; }
        .admin-link { color: var(--primary-color); }
        
        /* Main Dashboard */
        .dashboard-main { flex: 1; padding: 25px; overflow-y: auto; }
        
        /* Welcome Banner */
        .welcome-banner { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 16px; padding: 30px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .welcome-text h1 { font-size: 28px; margin-bottom: 5px; }
        .welcome-text p { opacity: 0.9; }
        .quick-stats { display: flex; gap: 30px; }
        .quick-stat { text-align: center; padding: 15px 25px; background: rgba(255,255,255,0.15); border-radius: 12px; }
        .quick-stat.urgent { background: rgba(255,0,0,0.3); }
        .stat-value { font-size: 32px; font-weight: 700; display: block; }
        .stat-label { font-size: 12px; opacity: 0.9; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px; }
        .stat-header i { color: var(--primary-color); font-size: 20px; }
        .stat-header h3 { font-size: 16px; font-weight: 600; }
        .stat-body { padding: 20px; }
        .stat-footer { padding: 15px 20px; border-top: 1px solid var(--border-color); background: var(--bg-color); }
        .stat-footer a { color: var(--primary-color); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px; }
        
        /* Census Card */
        .census-number { text-align: center; margin-bottom: 20px; }
        .big-number { font-size: 48px; font-weight: 700; color: var(--primary-color); display: block; }
        .census-label { color: var(--text-secondary); }
        .census-breakdown { display: flex; justify-content: space-around; }
        .census-item { text-align: center; }
        .census-type { display: block; font-size: 12px; color: var(--text-secondary); }
        .census-count { font-size: 24px; font-weight: 600; }
        
        /* Department Status */
        .department-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .dept-name { width: 80px; font-size: 13px; }
        .dept-bar { flex: 1; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; }
        .dept-fill { height: 100%; border-radius: 4px; }
        .dept-fill.normal { background: var(--accent-color); }
        .dept-fill.high { background: var(--warning-color); }
        .dept-fill.critical { background: var(--danger-color); }
        .dept-count { font-size: 13px; color: var(--text-secondary); width: 50px; text-align: right; }
        
        /* Schedule Card */
        .schedule-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .schedule-stat { text-align: center; padding: 10px; background: var(--bg-color); border-radius: 8px; }
        .schedule-num { font-size: 24px; font-weight: 600; display: block; }
        .schedule-num.scheduled { color: var(--primary-color); }
        .schedule-num.arrived { color: var(--warning-color); }
        .schedule-num.completed { color: var(--accent-color); }
        .schedule-num.noshow { color: var(--danger-color); }
        .schedule-label { font-size: 11px; color: var(--text-secondary); }
        .next-appointments h5 { font-size: 13px; color: var(--text-secondary); margin-bottom: 10px; }
        .appointment-item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
        .apt-time { font-weight: 600; color: var(--primary-color); width: 80px; }
        .apt-patient { flex: 1; }
        .apt-type { font-size: 12px; color: var(--text-secondary); background: var(--bg-color); padding: 3px 8px; border-radius: 4px; }
        
        /* Two Column Layout */
        .two-column { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px; }
        .column-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-header { padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: var(--primary-color); }
        .view-all { font-size: 13px; color: var(--primary-color); text-decoration: none; }
        .card-body { padding: 20px; }
        
        /* Alerts */
        .alert-item { display: flex; align-items: flex-start; gap: 15px; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
        .alert-item.critical { background: #fff5f5; }
        .alert-item.critical > i { color: var(--danger-color); }
        .alert-item.urgent { background: #fff9e6; }
        .alert-item.urgent > i { color: var(--warning-color); }
        .alert-item.info { background: #f0f7ff; }
        .alert-item.info > i { color: var(--primary-color); }
        .alert-item > i { font-size: 20px; margin-top: 2px; }
        .alert-content { flex: 1; }
        .alert-title { display: block; font-weight: 600; margin-bottom: 3px; }
        .alert-detail { display: block; font-size: 13px; color: var(--text-secondary); }
        .alert-time { display: block; font-size: 11px; color: #999; margin-top: 5px; }
        .alert-action { width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--border-color); background: white; cursor: pointer; }
        .alert-action:hover { background: var(--accent-color); border-color: var(--accent-color); color: white; }
        
        /* Tasks */
        .task-item { display: flex; align-items: flex-start; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .task-item input[type="checkbox"] { width: 18px; height: 18px; margin-top: 3px; cursor: pointer; }
        .task-item label { flex: 1; cursor: pointer; }
        .task-title { display: block; }
        .task-due { display: block; font-size: 12px; color: var(--text-secondary); }
        .task-item.high-priority .task-due { color: var(--danger-color); }
        .btn-add-task { width: 100%; padding: 12px; border: 2px dashed var(--border-color); background: transparent; border-radius: 8px; cursor: pointer; color: var(--text-secondary); margin-top: 15px; }
        .btn-add-task:hover { border-color: var(--primary-color); color: var(--primary-color); }
        
        /* Mini Table */
        .mini-table { width: 100%; font-size: 13px; }
        .mini-table th { text-align: left; padding: 10px 0; color: var(--text-secondary); font-weight: 500; border-bottom: 1px solid var(--border-color); }
        .mini-table td { padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .mini-table tr { cursor: pointer; }
        .mini-table tr:hover { background: var(--bg-color); }
        .status-badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; }
        .status-badge.critical { background: #ffe6e6; color: var(--danger-color); }
        .status-badge.stable { background: #e6ffe6; color: #006600; }
        .status-badge.observation { background: #fff3e6; color: #996600; }
        
        /* Critical Results */
        .critical-result { display: flex; align-items: center; gap: 15px; padding: 15px; background: #fff5f5; border-radius: 8px; margin-bottom: 10px; cursor: pointer; }
        .result-icon { width: 40px; height: 40px; background: var(--danger-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .result-info { flex: 1; }
        .result-patient { display: block; font-weight: 600; }
        .result-test { display: block; font-size: 13px; color: var(--danger-color); }
        .result-time { display: block; font-size: 11px; color: #999; }
        .btn-acknowledge { padding: 8px 15px; background: white; border: 1px solid var(--danger-color); color: var(--danger-color); border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-acknowledge:hover { background: var(--danger-color); color: white; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-secondary); }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.3; }
        .empty-state p { font-size: 14px; }
        
        /* Loading State */
        .loading { text-align: center; padding: 20px; color: var(--text-secondary); }
        .loading i { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 992px) { .dashboard-sidebar { display: none; } .two-column { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } .welcome-banner { flex-direction: column; text-align: center; gap: 20px; } .quick-stats { flex-wrap: wrap; justify-content: center; } .nav-search { display: none; } }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Top Navigation Bar -->
    <nav class="dashboard-nav">
        <div class="nav-brand">
            <i class="bi bi-heart-pulse"></i>
            <span>EPIC EHR</span>
        </div>
        <div class="nav-search">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search patients, orders, encounters..." id="globalSearch">
                <kbd>Ctrl+K</kbd>
            </div>
        </div>
        <div class="nav-actions">
            <button class="nav-btn" title="Messages" onclick="location.href='messages.php'">
                <i class="bi bi-envelope"></i>
                <span class="badge" id="unreadMessages"><?= $messagesCount['count'] > 0 ? $messagesCount['count'] : '' ?></span>
            </button>
            <button class="nav-btn" title="Tasks" onclick="location.href='tasks.php'">
                <i class="bi bi-list-check"></i>
                <span class="badge" id="pendingTasks"><?= $pendingTasksCount > 0 ? $pendingTasksCount : '' ?></span>
            </button>
            <button class="nav-btn" title="Alerts">
                <i class="bi bi-bell"></i>
                <span class="badge" id="alertCount"><?= $criticalAlertsCount > 0 ? $criticalAlertsCount : '' ?></span>
            </button>
            <div class="user-menu">
                <button class="user-btn" onclick="toggleUserMenu()">
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
                    <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="profile.php"><i class="bi bi-person"></i> My Profile</a>
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="help.php"><i class="bi bi-question-circle"></i> Help</a>
                    <hr>
                    <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-content">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-section">
                <h4>Quick Actions</h4>
                <a href="patient-search.php" class="sidebar-link"><i class="bi bi-search"></i> Patient Lookup</a>
                <a href="patient-new.php" class="sidebar-link"><i class="bi bi-person-plus"></i> Register Patient</a>
                <a href="scheduling.php" class="sidebar-link"><i class="bi bi-calendar-plus"></i> Schedule Appointment</a>
                <a href="orders.php" class="sidebar-link"><i class="bi bi-clipboard-plus"></i> Place Order</a>
            </div>
            <div class="sidebar-section">
                <h4>Clinical</h4>
                <a href="patients.php" class="sidebar-link"><i class="bi bi-people"></i> Patient List</a>
                <a href="encounters.php" class="sidebar-link"><i class="bi bi-journal-medical"></i> Encounters</a>
                <a href="results.php" class="sidebar-link"><i class="bi bi-file-earmark-medical"></i> Results Review</a>
                <a href="medications.php" class="sidebar-link"><i class="bi bi-capsule"></i> Medications</a>
            </div>
            <div class="sidebar-section">
                <h4>Scheduling</h4>
                <a href="schedule-today.php" class="sidebar-link"><i class="bi bi-calendar-day"></i> Today's Schedule</a>
                <a href="schedule-week.php" class="sidebar-link"><i class="bi bi-calendar-week"></i> Weekly View</a>
                <a href="waitlist.php" class="sidebar-link"><i class="bi bi-hourglass"></i> Waitlist</a>
            </div>
            <div class="sidebar-section">
                <h4>Administrative</h4>
                <a href="billing.php" class="sidebar-link"><i class="bi bi-credit-card"></i> Billing</a>
                <a href="reports.php" class="sidebar-link"><i class="bi bi-graph-up"></i> Reports</a>
                <a href="documents.php" class="sidebar-link"><i class="bi bi-folder"></i> Documents</a>
                <?php if (hasPermission('admin.access')): ?>
                <a href="admin/" class="sidebar-link admin-link"><i class="bi bi-shield-lock"></i> Administration</a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Dashboard Area -->
        <main class="dashboard-main">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1><?= $greeting ?>, <?= htmlspecialchars(explode(' ', $user)[0]) ?>!</h1>
                    <p><?= date('l, F j, Y') ?></p>
                </div>
                <div class="quick-stats">
                    <div class="quick-stat">
                        <span class="stat-value" id="todayAppointments"><?= $scheduledCount ?></span>
                        <span class="stat-label">Today's Appointments</span>
                    </div>
                    <div class="quick-stat">
                        <span class="stat-value" id="pendingResults"><?= $pendingResultsCount ?></span>
                        <span class="stat-label">Pending Results</span>
                    </div>
                    <div class="quick-stat <?= $criticalAlertsCount > 0 ? 'urgent' : '' ?>">
                        <span class="stat-value" id="criticalAlerts"><?= $criticalAlertsCount ?></span>
                        <span class="stat-label">Critical Alerts</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card census">
                    <div class="stat-header"><i class="bi bi-building"></i><h3>Facility Census</h3></div>
                    <div class="stat-body">
                        <div class="census-number">
                            <span class="big-number" id="totalCensus"><?= $totalCensus ?></span>
                            <span class="census-label">Current Patients</span>
                        </div>
                        <div class="census-breakdown">
                            <div class="census-item"><span class="census-type">Inpatient</span><span class="census-count"><?= $inpatientCount ?></span></div>
                            <div class="census-item"><span class="census-type">Observation</span><span class="census-count"><?= $observationCount ?></span></div>
                            <div class="census-item"><span class="census-type">ED</span><span class="census-count"><?= $edCount ?></span></div>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="census.php">View Full Census <i class="bi bi-arrow-right"></i></a></div>
                </div>

                <div class="stat-card departments">
                    <div class="stat-header"><i class="bi bi-diagram-3"></i><h3>Department Status</h3></div>
                    <div class="stat-body">
                        <div class="department-list">
                            <?php 
                            $departments = $departmentsData['data'] ?? [];
                            if (empty($departments)): 
                            ?>
                                <div class="empty-state">
                                    <i class="bi bi-building"></i>
                                    <p>No department data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($departments, 0, 4) as $dept): 
                                    $capacity = $dept['bed_count'] ?? 1;
                                    $occupied = $dept['occupied_beds'] ?? 0;
                                    $percentage = $capacity > 0 ? round(($occupied / $capacity) * 100) : 0;
                                    $fillClass = $percentage >= 90 ? 'critical' : ($percentage >= 75 ? 'high' : 'normal');
                                ?>
                                <div class="department-item">
                                    <span class="dept-name"><?= htmlspecialchars($dept['short_name'] ?? $dept['name'] ?? 'Unknown') ?></span>
                                    <div class="dept-bar">
                                        <div class="dept-fill <?= $fillClass ?>" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                    <span class="dept-count"><?= $occupied ?>/<?= $capacity ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="departments.php">All Departments <i class="bi bi-arrow-right"></i></a></div>
                </div>

                <div class="stat-card schedule">
                    <div class="stat-header"><i class="bi bi-calendar-check"></i><h3>Today's Schedule</h3></div>
                    <div class="stat-body">
                        <div class="schedule-summary">
                            <div class="schedule-stat"><span class="schedule-num scheduled"><?= $scheduledCount ?></span><span class="schedule-label">Scheduled</span></div>
                            <div class="schedule-stat"><span class="schedule-num arrived"><?= $arrivedCount ?></span><span class="schedule-label">Arrived</span></div>
                            <div class="schedule-stat"><span class="schedule-num completed"><?= $completedCount ?></span><span class="schedule-label">Completed</span></div>
                            <div class="schedule-stat"><span class="schedule-num noshow"><?= $noShowCount ?></span><span class="schedule-label">No Show</span></div>
                        </div>
                        <div class="next-appointments">
                            <h5>Upcoming</h5>
                            <?php if (empty($upcomingAppointments)): ?>
                                <div class="empty-state">
                                    <p>No upcoming appointments</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($upcomingAppointments, 0, 3) as $apt): ?>
                                <div class="appointment-item">
                                    <span class="apt-time"><?= htmlspecialchars(date('g:i A', strtotime($apt['scheduled_time'] ?? ''))) ?></span>
                                    <span class="apt-patient"><?= htmlspecialchars($apt['patient_name'] ?? 'Unknown') ?></span>
                                    <span class="apt-type"><?= htmlspecialchars($apt['appointment_type'] ?? 'Visit') ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-footer"><a href="schedule-today.php">Full Schedule <i class="bi bi-arrow-right"></i></a></div>
                </div>
            </div>

            <!-- Two-Column Section -->
            <div class="two-column">
                <div class="column-card alerts-card">
                    <div class="card-header"><h3><i class="bi bi-exclamation-triangle"></i> Alerts & Notifications</h3><a href="alerts.php" class="view-all">View All</a></div>
                    <div class="card-body">
                        <?php 
                        $alerts = $alertsData['data'] ?? [];
                        if (empty($alerts)): 
                        ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No active alerts</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($alerts, 0, 5) as $alert): 
                                $severity = $alert['severity'] ?? 'info';
                                $iconClass = $severity === 'critical' ? 'bi-exclamation-octagon' : 
                                            ($severity === 'urgent' ? 'bi-exclamation-triangle' : 'bi-info-circle');
                            ?>
                            <div class="alert-item <?= htmlspecialchars($severity) ?>">
                                <i class="bi <?= $iconClass ?>"></i>
                                <div class="alert-content">
                                    <span class="alert-title"><?= htmlspecialchars($alert['title'] ?? 'Alert') ?></span>
                                    <span class="alert-detail"><?= htmlspecialchars($alert['message'] ?? '') ?></span>
                                    <span class="alert-time"><?= htmlspecialchars(formatTimeAgo($alert['created_at'] ?? '')) ?></span>
                                </div>
                                <button class="alert-action" onclick="dismissAlert(<?= $alert['id'] ?? 0 ?>)" title="Dismiss">
                                    <i class="bi bi-check"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="column-card tasks-card">
                    <div class="card-header"><h3><i class="bi bi-list-task"></i> My Tasks</h3><a href="tasks.php" class="view-all">View All</a></div>
                    <div class="card-body">
                        <?php 
                        $tasks = $tasksData['data'] ?? [];
                        if (empty($tasks)): 
                        ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No pending tasks</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($tasks, 0, 4) as $index => $task): 
                                $isHighPriority = ($task['priority'] ?? '') === 'high' || ($task['priority'] ?? '') === 'urgent';
                            ?>
                            <div class="task-item <?= $isHighPriority ? 'high-priority' : '' ?>">
                                <input type="checkbox" id="task<?= $index ?>" onchange="completeTask(<?= $task['id'] ?? 0 ?>)">
                                <label for="task<?= $index ?>">
                                    <span class="task-title"><?= htmlspecialchars($task['title'] ?? 'Task') ?></span>
                                    <span class="task-due">Due: <?= htmlspecialchars(formatDate($task['due_date'] ?? '', 'M j, Y')) ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <button class="btn-add-task" onclick="openAddTaskModal()"><i class="bi bi-plus-circle"></i> Add Task</button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="two-column">
                <div class="column-card">
                    <div class="card-header"><h3><i class="bi bi-door-open"></i> Recent Admissions</h3><a href="admissions.php" class="view-all">View All</a></div>
                    <div class="card-body">
                        <?php 
                        $admissions = $admissionsData['data'] ?? [];
                        if (empty($admissions)): 
                        ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>No recent admissions</p>
                            </div>
                        <?php else: ?>
                        <table class="mini-table">
                            <thead><tr><th>Patient</th><th>Time</th><th>Unit</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach (array_slice($admissions, 0, 4) as $admission): 
                                    $statusClass = strtolower($admission['acuity'] ?? 'stable');
                                    $statusClass = $statusClass === 'critical' ? 'critical' : 
                                                  ($statusClass === 'observation' ? 'observation' : 'stable');
                                ?>
                                <tr onclick="location.href='patient.php?id=<?= $admission['patient_id'] ?? 0 ?>'">
                                    <td><?= htmlspecialchars($admission['patient_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars(formatTimeAgo($admission['admission_date'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($admission['unit'] ?? 'N/A') ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($admission['acuity'] ?? 'Stable')) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="column-card">
                    <div class="card-header"><h3><i class="bi bi-exclamation-diamond"></i> Critical Results Pending</h3><a href="results.php?filter=critical" class="view-all">View All</a></div>
                    <div class="card-body">
                        <?php 
                        $criticalResults = $criticalResultsData['data'] ?? [];
                        if (empty($criticalResults)): 
                        ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle"></i>
                                <p>No critical results pending</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($criticalResults, 0, 3) as $result): ?>
                            <div class="critical-result" onclick="viewResult(<?= $result['id'] ?? 0 ?>)">
                                <div class="result-icon"><i class="bi bi-droplet"></i></div>
                                <div class="result-info">
                                    <span class="result-patient"><?= htmlspecialchars($result['patient_name'] ?? 'Unknown') ?></span>
                                    <span class="result-test"><?= htmlspecialchars($result['test_name'] ?? 'Lab') ?>: <?= htmlspecialchars($result['value'] ?? '') ?> <?= htmlspecialchars($result['unit'] ?? '') ?></span>
                                    <span class="result-time">Received: <?= htmlspecialchars(formatTimeAgo($result['resulted_date'] ?? '')) ?></span>
                                </div>
                                <button class="btn-acknowledge" onclick="event.stopPropagation(); acknowledgeResult(<?= $result['id'] ?? 0 ?>)">Acknowledge</button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const API_BASE = '<?= API_BASE_URL ?>';
    
    function toggleUserMenu() { 
        document.getElementById('userDropdown').classList.toggle('show'); 
    }
    
    document.addEventListener('click', function(e) { 
        if (!e.target.closest('.user-menu')) {
            document.getElementById('userDropdown').classList.remove('show'); 
        }
    });
    
    document.getElementById('globalSearch').addEventListener('keydown', function(e) { 
        if (e.key === 'Enter') {
            window.location.href = '/search.php?q=' + encodeURIComponent(this.value); 
        }
    });
    
    document.addEventListener('keydown', function(e) { 
        if (e.ctrlKey && e.key === 'k') { 
            e.preventDefault(); 
            document.getElementById('globalSearch').focus(); 
        } 
    });
    
    // Dismiss alert
    async function dismissAlert(alertId) {
        if (!alertId) return;
        try {
            const response = await fetch(`${API_BASE}/alerts/${alertId}/dismiss`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error dismissing alert:', error);
        }
    }
    
    // Complete task
    async function completeTask(taskId) {
        if (!taskId) return;
        try {
            const response = await fetch(`${API_BASE}/tasks/${taskId}/complete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error completing task:', error);
        }
    }
    
    // Acknowledge critical result
    async function acknowledgeResult(resultId) {
        if (!resultId) return;
        try {
            const response = await fetch(`${API_BASE}/labs/${resultId}/acknowledge`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                location.reload();
            }
        } catch (error) {
            console.error('Error acknowledging result:', error);
        }
    }
    
    // View result details
    function viewResult(resultId) {
        if (resultId) {
            window.location.href = `/results.php?id=${resultId}`;
        }
    }
    
    // Open add task modal
    function openAddTaskModal() {
        window.location.href = '/tasks.php?action=new';
    }
    
    // Session timeout (HIPAA - 15 minutes)
    let lastActivity = Date.now();
    document.addEventListener('mousemove', () => lastActivity = Date.now());
    document.addEventListener('keypress', () => lastActivity = Date.now());
    document.addEventListener('click', () => lastActivity = Date.now());
    
    setInterval(() => { 
        if (Date.now() - lastActivity > 15 * 60 * 1000) {
            window.location.href = '/login.php?timeout=1'; 
        }
    }, 10000);
    
    // Auto-refresh dashboard data every 60 seconds
    setInterval(() => {
        // Only refresh if user is active
        if (Date.now() - lastActivity < 60000) {
            refreshDashboardData();
        }
    }, 60000);
    
    async function refreshDashboardData() {
        try {
            // Refresh critical alerts count
            const alertsResponse = await fetch(`${API_BASE}/alerts/user/<?= $_SESSION['user_id'] ?? 0 ?>/count`);
            if (alertsResponse.ok) {
                const data = await alertsResponse.json();
                const badge = document.getElementById('alertCount');
                badge.textContent = data.critical_count > 0 ? data.critical_count : '';
            }
            
            // Refresh messages count
            const messagesResponse = await fetch(`${API_BASE}/messages/unread/count`);
            if (messagesResponse.ok) {
                const data = await messagesResponse.json();
                const badge = document.getElementById('unreadMessages');
                badge.textContent = data.count > 0 ? data.count : '';
            }
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
        }
    }
</script>
</body>
</html>
<?php

// Helper function for time ago formatting
function formatTimeAgo($datetime) {
    if (empty($datetime)) return 'Unknown';
    
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return 'Unknown';
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>

<?php
/**
 * Epic EHR - Hyperspace Home Dashboard
 * Central navigation hub for the EHR system
 */
session_start();
require_once __DIR__ . '/includes/api.php';

// Load user theme (if set)
$theme = $_SESSION['theme'] ?? 'epic-classic';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic Hyperspace - Home</title>
    <link rel="stylesheet" href="assets/css/epic-styles.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #004d99;
            --accent-color: #3399ff;
            --header-bg: #004080;
            --sidebar-bg: #f5f6f8;
            --text-color: #333;
            --border-color: #ddd;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eef5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main Header */
        .main-header {
            background: linear-gradient(to bottom, #004080, #003366);
            color: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .epic-logo {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
            text-decoration: none;
        }
        
        .epic-logo span {
            color: #66b3ff;
        }
        
        .header-nav {
            display: flex;
            gap: 4px;
        }
        
        .header-nav a {
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px 4px 0 0;
            font-size: 12px;
            transition: background 0.2s;
        }
        
        .header-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .header-nav a.active {
            background: #e8eef5;
            color: #004080;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-right .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .header-right .user-menu:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: #66b3ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-size: 12px;
            font-weight: 600;
        }
        
        .user-role {
            font-size: 10px;
            opacity: 0.8;
        }
        
        /* Quick Actions Bar */
        .quick-actions {
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action-btn:hover {
            background: #e3f2fd;
            border-color: #90caf9;
        }
        
        .quick-action-btn .icon {
            font-size: 16px;
        }
        
        .search-bar {
            flex: 1;
            max-width: 400px;
            margin-left: auto;
            display: flex;
            gap: 4px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .search-bar button {
            padding: 6px 12px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            padding: 16px;
            gap: 16px;
        }
        
        /* Left Sidebar - My Patients */
        .sidebar {
            width: 280px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-header button {
            font-size: 10px;
            padding: 3px 8px;
            cursor: pointer;
        }
        
        .patient-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .patient-item {
            padding: 10px 16px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        
        .patient-item:hover {
            background: #f5f9ff;
        }
        
        .patient-item.selected {
            background: #e3f2fd;
            border-left: 3px solid var(--primary-color);
        }
        
        .patient-name {
            font-weight: 600;
            font-size: 12px;
            color: var(--primary-color);
        }
        
        .patient-meta {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        
        .patient-location {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            font-size: 10px;
        }
        
        .patient-location .bed {
            background: #e3f2fd;
            padding: 1px 6px;
            border-radius: 3px;
            font-weight: 500;
        }
        
        .patient-alerts {
            display: flex;
            gap: 4px;
            margin-top: 4px;
        }
        
        .alert-badge {
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 500;
        }
        
        .alert-badge.allergy { background: #ffcdd2; color: #c62828; }
        .alert-badge.fall { background: #fff3e0; color: #e65100; }
        .alert-badge.isolation { background: #f3e5f5; color: #7b1fa2; }
        
        /* Center - Dashboard Content */
        .dashboard-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        /* Activity Cards */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        .activity-card {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .activity-card .icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .activity-card .title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .activity-card .desc {
            font-size: 10px;
            color: #666;
        }
        
        /* Module Sections */
        .module-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .module-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .module-content {
            padding: 16px;
        }
        
        /* Module Grid */
        .module-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
        }
        
        .module-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
            border: 1px solid #eee;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .module-item:hover {
            background: #e3f2fd;
            border-color: #90caf9;
        }
        
        .module-item .icon {
            font-size: 24px;
            margin-bottom: 6px;
        }
        
        .module-item .name {
            font-size: 11px;
            text-align: center;
            color: #333;
        }
        
        /* Right Sidebar - Tasks/Alerts */
        .right-sidebar {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .task-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .task-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-count {
            background: #ef5350;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }
        
        .task-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .task-item {
            padding: 10px 16px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            font-size: 11px;
        }
        
        .task-item:hover {
            background: #f5f9ff;
        }
        
        .task-item .patient {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .task-item .task-desc {
            color: #666;
            margin-top: 2px;
        }
        
        .task-item .task-time {
            font-size: 10px;
            color: #999;
            margin-top: 4px;
        }
        
        .task-item.urgent {
            border-left: 3px solid #ef5350;
            background: #fff8f8;
        }
        
        /* System Status */
        .system-status {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .status-header {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 12px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 11px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-dot.green { background: #4caf50; }
        .status-dot.yellow { background: #ff9800; }
        .status-dot.red { background: #f44336; }
        
        /* Footer */
        .main-footer {
            background: #f5f6f8;
            padding: 8px 16px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Main Header -->
    <header class="main-header">
        <div class="header-left">
            <a href="home.php" class="epic-logo">Epic<span>Hyperspace</span></a>
            <nav class="header-nav">
                <a href="home.php" class="active">Home</a>
                <a href="patient-lists.php">Patient Lists</a>
                <a href="schedule.php">Schedule</a>
                <a href="in-basket.php">In Basket</a>
                <a href="reports.php">Reports</a>
                <a href="admin.php">Admin</a>
            </nav>
        </div>
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">JS</div>
                <div class="user-info">
                    <div class="user-name">John Smith, MD</div>
                    <div class="user-role">Internal Medicine</div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Quick Actions Bar -->
    <div class="quick-actions">
        <a href="patient-search.php" class="quick-action-btn">
            <span class="icon">🔍</span>
            <span>Patient Search</span>
        </a>
        <a href="new-patient.php" class="quick-action-btn">
            <span class="icon">➕</span>
            <span>New Patient</span>
        </a>
        <a href="orders.php" class="quick-action-btn">
            <span class="icon">📋</span>
            <span>New Order</span>
        </a>
        <a href="notes.php" class="quick-action-btn">
            <span class="icon">📝</span>
            <span>New Note</span>
        </a>
        <div class="search-bar">
            <input type="text" placeholder="Search patients, orders, documents..." id="globalSearch">
            <button onclick="performSearch()">🔍</button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Left Sidebar - My Patients -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <span>My Patients</span>
                <button onclick="refreshPatients()">🔄 Refresh</button>
            </div>
            <div class="patient-list">
                <a href="index.php?patient_id=1" class="patient-item selected">
                    <div class="patient-name">Patient, Test</div>
                    <div class="patient-meta">MRN: 001234 | DOB: 01/15/1965 | 59M</div>
                    <div class="patient-location">
                        <span class="bed">5N-501A</span>
                        <span>Medical ICU</span>
                    </div>
                    <div class="patient-alerts">
                        <span class="alert-badge allergy">Allergies</span>
                        <span class="alert-badge fall">Fall Risk</span>
                    </div>
                </a>
                <a href="index.php?patient_id=2" class="patient-item">
                    <div class="patient-name">Smith, Jane</div>
                    <div class="patient-meta">MRN: 002345 | DOB: 03/22/1978 | 46F</div>
                    <div class="patient-location">
                        <span class="bed">3E-302</span>
                        <span>Surgical</span>
                    </div>
                </a>
                <a href="index.php?patient_id=3" class="patient-item">
                    <div class="patient-name">Johnson, Michael</div>
                    <div class="patient-meta">MRN: 003456 | DOB: 07/10/1952 | 71M</div>
                    <div class="patient-location">
                        <span class="bed">4W-418</span>
                        <span>Telemetry</span>
                    </div>
                    <div class="patient-alerts">
                        <span class="alert-badge isolation">Contact</span>
                    </div>
                </a>
                <a href="index.php?patient_id=4" class="patient-item">
                    <div class="patient-name">Williams, Sarah</div>
                    <div class="patient-meta">MRN: 004567 | DOB: 11/05/1990 | 33F</div>
                    <div class="patient-location">
                        <span class="bed">L&D-205</span>
                        <span>Labor & Delivery</span>
                    </div>
                </a>
                <a href="index.php?patient_id=5" class="patient-item">
                    <div class="patient-name">Brown, Robert</div>
                    <div class="patient-meta">MRN: 005678 | DOB: 09/18/1945 | 78M</div>
                    <div class="patient-location">
                        <span class="bed">5S-512</span>
                        <span>Oncology</span>
                    </div>
                    <div class="patient-alerts">
                        <span class="alert-badge allergy">Allergies</span>
                    </div>
                </a>
            </div>
        </aside>
        
        <!-- Center Dashboard -->
        <main class="dashboard-center">
            <!-- Quick Activities -->
            <div class="activity-grid">
                <a href="activities/summary-index.php" class="activity-card">
                    <div class="icon">📊</div>
                    <div class="title">Chart Summary</div>
                    <div class="desc">View patient overview and summary</div>
                </a>
                <a href="activities/mar.php" class="activity-card">
                    <div class="icon">💊</div>
                    <div class="title">MAR</div>
                    <div class="desc">Medication administration</div>
                </a>
                <a href="activities/flowsheets.php" class="activity-card">
                    <div class="icon">📈</div>
                    <div class="title">Flowsheets</div>
                    <div class="desc">Vitals and assessments</div>
                </a>
                <a href="orders.php" class="activity-card">
                    <div class="icon">📋</div>
                    <div class="title">Orders</div>
                    <div class="desc">View and place orders</div>
                </a>
            </div>
            
            <!-- Clinical Modules -->
            <div class="module-section">
                <div class="module-header">
                    <span>Clinical Activities</span>
                    <button style="font-size: 10px; padding: 3px 8px; cursor: pointer;">Customize</button>
                </div>
                <div class="module-content">
                    <div class="module-grid">
                        <a href="notes.php" class="module-item">
                            <span class="icon">📝</span>
                            <span class="name">Notes</span>
                        </a>
                        <a href="activities/chart-review.php" class="module-item">
                            <span class="icon">📚</span>
                            <span class="name">Chart Review</span>
                        </a>
                        <a href="activities/results.php" class="module-item">
                            <span class="icon">🔬</span>
                            <span class="name">Results</span>
                        </a>
                        <a href="activities/detailed-vitals.php" class="module-item">
                            <span class="icon">💓</span>
                            <span class="name">Vitals</span>
                        </a>
                        <a href="activities/intake-output.php" class="module-item">
                            <span class="icon">💧</span>
                            <span class="name">I/O</span>
                        </a>
                        <a href="activities/care-plan.php" class="module-item">
                            <span class="icon">📋</span>
                            <span class="name">Care Plan</span>
                        </a>
                        <a href="medications.php" class="module-item">
                            <span class="icon">💊</span>
                            <span class="name">Medications</span>
                        </a>
                        <a href="allergies.php" class="module-item">
                            <span class="icon">⚠️</span>
                            <span class="name">Allergies</span>
                        </a>
                        <a href="problems.php" class="module-item">
                            <span class="icon">📌</span>
                            <span class="name">Problem List</span>
                        </a>
                        <a href="imaging.php" class="module-item">
                            <span class="icon">📷</span>
                            <span class="name">Imaging</span>
                        </a>
                        <a href="procedures.php" class="module-item">
                            <span class="icon">🔧</span>
                            <span class="name">Procedures</span>
                        </a>
                        <a href="patient-education.php" class="module-item">
                            <span class="icon">📖</span>
                            <span class="name">Education</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Specialty Modules -->
            <div class="module-section">
                <div class="module-header">
                    <span>Specialty Modules</span>
                </div>
                <div class="module-content">
                    <div class="module-grid">
                        <a href="activities/post-partum-hemorrhage.php" class="module-item">
                            <span class="icon">🤰</span>
                            <span class="name">Perinatal</span>
                        </a>
                        <a href="pediatrics.php" class="module-item">
                            <span class="icon">👶</span>
                            <span class="name">Pediatrics</span>
                        </a>
                        <a href="oncology.php" class="module-item">
                            <span class="icon">🎗️</span>
                            <span class="name">Oncology</span>
                        </a>
                        <a href="cardiology.php" class="module-item">
                            <span class="icon">❤️</span>
                            <span class="name">Cardiology</span>
                        </a>
                        <a href="infection-control.php" class="module-item">
                            <span class="icon">🦠</span>
                            <span class="name">Infection Control</span>
                        </a>
                        <a href="surgery.php" class="module-item">
                            <span class="icon">🏥</span>
                            <span class="name">Surgery</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Right Sidebar - Tasks/Alerts -->
        <aside class="right-sidebar">
            <!-- Tasks -->
            <div class="task-panel">
                <div class="task-header">
                    <span>Tasks Due</span>
                    <span class="task-count">5</span>
                </div>
                <div class="task-list">
                    <div class="task-item urgent">
                        <div class="patient">Patient, Test</div>
                        <div class="task-desc">Medication due: Levofloxacin 750mg</div>
                        <div class="task-time">Due: Now (overdue 15 min)</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Smith, Jane</div>
                        <div class="task-desc">Vital signs due</div>
                        <div class="task-time">Due: 15 minutes</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Johnson, Michael</div>
                        <div class="task-desc">Lab draw: BMP</div>
                        <div class="task-time">Due: 30 minutes</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Patient, Test</div>
                        <div class="task-desc">I/O documentation</div>
                        <div class="task-time">Due: 1 hour</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Brown, Robert</div>
                        <div class="task-desc">Reassessment due</div>
                        <div class="task-time">Due: 2 hours</div>
                    </div>
                </div>
            </div>
            
            <!-- Results to Review -->
            <div class="task-panel">
                <div class="task-header">
                    <span>Results to Review</span>
                    <span class="task-count">3</span>
                </div>
                <div class="task-list">
                    <div class="task-item">
                        <div class="patient">Patient, Test</div>
                        <div class="task-desc">CBC with Differential - Final</div>
                        <div class="task-time">Resulted: 10 min ago</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Johnson, Michael</div>
                        <div class="task-desc">Chest X-Ray - Final</div>
                        <div class="task-time">Resulted: 25 min ago</div>
                    </div>
                    <div class="task-item">
                        <div class="patient">Smith, Jane</div>
                        <div class="task-desc">BMP - Final</div>
                        <div class="task-time">Resulted: 1 hour ago</div>
                    </div>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="system-status">
                <div class="status-header">System Status</div>
                <div class="status-item">
                    <span class="status-dot green"></span>
                    <span>EHR System</span>
                </div>
                <div class="status-item">
                    <span class="status-dot green"></span>
                    <span>Lab Interface</span>
                </div>
                <div class="status-item">
                    <span class="status-dot green"></span>
                    <span>Radiology PACS</span>
                </div>
                <div class="status-item">
                    <span class="status-dot green"></span>
                    <span>Pharmacy System</span>
                </div>
                <div class="status-item">
                    <span class="status-dot yellow"></span>
                    <span>Print Services (degraded)</span>
                </div>
            </div>
        </aside>
    </div>
    
    <!-- Footer -->
    <footer class="main-footer">
        Epic EHR System &copy; <?= date('Y') ?> | Version 1.0.0 | 
        Environment: Development | Server Time: <?= date('Y-m-d H:i:s') ?>
    </footer>
    
    <script>
        function refreshPatients() {
            location.reload();
        }
        
        function performSearch() {
            const query = document.getElementById('globalSearch').value;
            if (query) {
                window.location.href = `patient-search.php?q=${encodeURIComponent(query)}`;
            }
        }
        
        document.getElementById('globalSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    </script>
</body>
</html>

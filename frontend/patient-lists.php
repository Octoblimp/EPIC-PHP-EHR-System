<?php
/**
 * Patient Lists View - Unit Census, My Patients, Department Lists
 * Matching Epic's Patient Lists Hyperspace interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

// Initialize API services
$patientService = new PatientService();
$api = new ApiClient();

// Get list type from query params
$listType = $_GET['list'] ?? 'my_patients';
$departmentId = $_GET['department_id'] ?? null;
$searchQuery = $_GET['q'] ?? '';

// Fetch patients based on list type
try {
    if ($searchQuery) {
        $patients = $patientService->search($searchQuery);
    } else {
        $patients = $patientService->getAll();
    }
} catch (Exception $e) {
    $patients = [];
}

// Get current user info (mock for now)
$currentUser = [
    'name' => 'CINDY W.',
    'department' => 'Surgical Specialty'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Lists - Epic EHR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/epic-styles.css">
    <style>
        /* Patient Lists specific styles */
        .epic-toolbar {
            background: linear-gradient(180deg, #1e5799 0%, #207cca 50%, #2989d8 51%, #207cca 100%);
            padding: 4px 8px;
            display: flex;
            align-items: center;
            gap: 4px;
            border-bottom: 1px solid #1565c0;
        }
        
        .toolbar-btn {
            background: linear-gradient(180deg, #f5f5f5 0%, #e0e0e0 100%);
            border: 1px solid #999;
            border-radius: 3px;
            padding: 4px 10px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .toolbar-btn:hover {
            background: linear-gradient(180deg, #fff 0%, #f0f0f0 100%);
        }
        
        .toolbar-btn img, .toolbar-btn .icon {
            width: 16px;
            height: 16px;
        }
        
        .toolbar-separator {
            width: 1px;
            height: 24px;
            background: rgba(255,255,255,0.3);
            margin: 0 4px;
        }
        
        .page-content {
            display: flex;
            height: calc(100vh - 90px);
        }
        
        /* Left sidebar - My Lists */
        .lists-sidebar {
            width: 220px;
            background: #fff;
            border-right: 1px solid #ccc;
            overflow-y: auto;
        }
        
        .sidebar-section {
            border-bottom: 1px solid #e0e0e0;
        }
        
        .sidebar-section-header {
            padding: 8px 12px;
            font-weight: 600;
            font-size: 13px;
            color: #0066cc;
            background: #f5f5f5;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sidebar-section-header:hover {
            background: #e8f4fc;
        }
        
        .sidebar-section-header .toggle {
            font-size: 10px;
            color: #666;
        }
        
        .sidebar-item {
            padding: 6px 12px 6px 28px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .sidebar-item:hover {
            background: #e8f4fc;
        }
        
        .sidebar-item.active {
            background: #cce5ff;
        }
        
        .sidebar-item .icon {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Main content - Patient Grid */
        .patient-list-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .list-header {
            padding: 8px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .list-title {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .list-title .star {
            color: #ffc107;
        }
        
        .patient-count {
            color: #666;
            font-weight: normal;
            font-size: 13px;
        }
        
        .list-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #666;
        }
        
        .refresh-time {
            color: #666;
        }
        
        .refresh-btn {
            background: none;
            border: none;
            color: #0066cc;
            cursor: pointer;
            font-size: 14px;
        }
        
        .search-box {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
            width: 180px;
        }
        
        /* Patient Data Grid */
        .patient-grid-container {
            flex: 1;
            overflow: auto;
        }
        
        .patient-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .patient-grid th {
            background: linear-gradient(180deg, #e8f4fc 0%, #d4e9f7 100%);
            border: 1px solid #b8d4e8;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .patient-grid th.sortable {
            cursor: pointer;
        }
        
        .patient-grid th.sortable:hover {
            background: linear-gradient(180deg, #d4e9f7 0%, #c0ddef 100%);
        }
        
        .patient-grid th .sort-indicator {
            font-size: 10px;
            margin-left: 4px;
        }
        
        .patient-grid td {
            border: 1px solid #ddd;
            padding: 5px 8px;
            vertical-align: middle;
        }
        
        .patient-grid tr:hover td {
            background: #e8f4fc;
        }
        
        .patient-grid tr.selected td {
            background: #cce5ff;
        }
        
        /* Status indicators */
        .status-icon {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-icon.green {
            background: #28a745;
            color: white;
        }
        
        .status-icon.yellow {
            background: #ffc107;
            color: #333;
        }
        
        .status-icon.red {
            background: #dc3545;
            color: white;
        }
        
        .status-icon.blue {
            background: #17a2b8;
            color: white;
        }
        
        .status-icon.gray {
            background: #6c757d;
            color: white;
        }
        
        .flag-icon {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 2px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .flag-icon.alert {
            background: #dc3545;
            color: white;
        }
        
        .flag-icon.warning {
            background: #ffc107;
            color: #333;
        }
        
        .flag-icon.info {
            background: #17a2b8;
            color: white;
        }
        
        /* Patient name link */
        .patient-name-link {
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
        }
        
        .patient-name-link:hover {
            text-decoration: underline;
        }
        
        /* Problem column */
        .problem-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Column icons */
        .col-icons {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        
        .doc-icon {
            width: 16px;
            height: 16px;
            background: #e0e0e0;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        .doc-icon.has-data {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        /* Team indicator */
        .team-icon {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        /* Available Lists section at bottom */
        .available-lists {
            margin-top: auto;
        }
        
        .available-lists .sidebar-section-header {
            color: #0066cc;
        }
        
        /* Column resizer */
        .col-resizer {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            cursor: col-resize;
        }
        
        .col-resizer:hover {
            background: #0066cc;
        }
    </style>
</head>
<body>
    <!-- Top Epic Toolbar -->
    <header class="app-header">
        <div class="header-left">
            <div class="epic-logo">
                <span class="logo-text">Epic</span>
                <span class="logo-dropdown">▼</span>
            </div>
            <nav class="header-nav">
                <a href="#" class="nav-item">🔍 Patient Lookup</a>
                <a href="#" class="nav-item">📋 OR Cases</a>
                <a href="#" class="nav-item">🏠 House Census</a>
                <a href="#" class="nav-item">📖 UpToDate</a>
                <a href="#" class="nav-item">🔄 Transfer Center</a>
                <a href="#" class="nav-item">🛏️ Staffed Beds</a>
                <a href="#" class="nav-item">👤 Anywhere RN</a>
                <a href="#" class="nav-item">📊 SlicerDicer</a>
                <a href="#" class="nav-item dropdown">Reports ▼</a>
            </nav>
        </div>
        <div class="header-right">
            <span class="user-info"><?= htmlspecialchars($currentUser['name']) ?></span>
            <span class="epic-care-badge">EpicCare</span>
        </div>
    </header>
    
    <!-- Secondary Toolbar -->
    <div class="epic-toolbar">
        <button class="toolbar-btn">
            <span class="icon">🔧</span>
        </button>
        <button class="toolbar-btn">
            <span class="icon">≡</span>
        </button>
        <button class="toolbar-btn">
            <span class="icon">📊</span>
        </button>
        <button class="toolbar-btn">
            <span class="icon">📅</span>
        </button>
        <div class="toolbar-separator"></div>
    </div>
    
    <!-- Page Title Bar -->
    <div style="background: #f5f5f5; padding: 8px 16px; border-bottom: 1px solid #ddd;">
        <h1 style="font-size: 16px; font-weight: 600; margin: 0; color: #333;">Patient Lists</h1>
    </div>
    
    <!-- List Action Bar -->
    <div class="epic-toolbar" style="background: #fff; border-bottom: 1px solid #ddd;">
        <button class="toolbar-btn">
            <span class="icon">✏️</span> Edit List ▼
        </button>
        <div class="toolbar-separator" style="background: #ccc;"></div>
        <button class="toolbar-btn">
            <span class="icon">📋</span> MAR
        </button>
        <button class="toolbar-btn">
            <span class="icon">📊</span> Flowsheets
        </button>
        <button class="toolbar-btn">
            <span class="icon">📝</span> Work List
        </button>
        <div class="toolbar-separator" style="background: #ccc;"></div>
        <button class="toolbar-btn">
            <span class="icon">⬆️</span> Arrived ▼
        </button>
        <button class="toolbar-btn">
            <span class="icon">✅</span> Sign In
        </button>
        <button class="toolbar-btn">
            <span class="icon">🚪</span> Sign Out
        </button>
        <div class="toolbar-separator" style="background: #ccc;"></div>
        <button class="toolbar-btn">
            <span class="icon">💊</span> Pain/Med Reassess
        </button>
        <button class="toolbar-btn">
            <span class="icon">📋</span> Care Handoff
        </button>
        <button class="toolbar-btn">
            <span class="icon">🔔</span> Provider notify/Critical Result
        </button>
        <button class="toolbar-btn">
            <span class="icon">💊</span> DAILY CARES
        </button>
        <button class="toolbar-btn">
            <span class="icon">👶</span> Ped Daily Care
        </button>
        <button class="toolbar-btn">
            More ▼
        </button>
    </div>
    
    <!-- Main Content -->
    <div class="page-content">
        <!-- Left Sidebar - My Lists -->
        <aside class="lists-sidebar">
            <div class="sidebar-section">
                <div class="sidebar-section-header">
                    <span class="toggle">▼</span>
                    My Lists
                </div>
                <div class="sidebar-item <?= $listType === 'my_patients' ? 'active' : '' ?>" onclick="loadList('my_patients')">
                    <span class="icon">👤</span> My Patients
                </div>
                <div class="sidebar-item" onclick="loadList('all_my_patients')">
                    <span class="icon">👥</span> All My Patients
                </div>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-header">
                    <span class="toggle">▼</span>
                    My Unit
                </div>
                <div class="sidebar-item" onclick="loadList('my_login_dept')">
                    <span class="icon">🏥</span> My Login Dept
                </div>
            </div>
            
            <div class="sidebar-section available-lists">
                <div class="sidebar-section-header">
                    <span class="toggle">▼</span>
                    Available Lists
                </div>
                <div class="sidebar-item" onclick="loadList('newlife')">
                    <span class="icon">🏥</span> NewLife Center...
                </div>
                <div class="sidebar-item" onclick="loadList('omh_emergency')">
                    <span class="icon">🚑</span> OMH Emergen...
                </div>
                <div class="sidebar-item" onclick="loadList('omh_preop')">
                    <span class="icon">🏥</span> OMH Pre-Op / ...
                </div>
                <div class="sidebar-item" onclick="loadList('omh_surge')">
                    <span class="icon">🏥</span> OMH Surge
                </div>
                <div class="sidebar-item" onclick="loadList('pediatrics')">
                    <span class="icon">👶</span> Pediatrics
                </div>
                <div class="sidebar-item" onclick="loadList('surgical')">
                    <span class="icon">🔪</span> Surgical Speci...
                </div>
                <div class="sidebar-item" onclick="loadList('admit_obs')">
                    <span class="icon">📋</span> Admit/Obs Orders
                </div>
            </div>
        </aside>
        
        <!-- Main Patient Grid -->
        <main class="patient-list-main">
            <div class="list-header">
                <div class="list-title">
                    <span class="star">★</span>
                    <span id="listName">Surgical Specialty</span>
                    <span class="patient-count" id="patientCount"><?= count($patients) ?> Patients</span>
                </div>
                <div class="list-actions">
                    <span class="refresh-time">Refreshed 3 minutes ago</span>
                    <button class="refresh-btn" onclick="refreshList()">🔄</button>
                    <input type="text" class="search-box" placeholder="Search GHS All Admi" id="searchBox">
                </div>
            </div>
            
            <div class="patient-grid-container">
                <table class="patient-grid">
                    <thead>
                        <tr>
                            <th class="sortable" style="width: 100px;">
                                Patient<br>Location ▲
                            </th>
                            <th class="sortable" style="width: 80px;">Room/Bed</th>
                            <th class="sortable" style="width: 150px;">Patient Name</th>
                            <th class="sortable" style="width: 70px;">Age/Gen</th>
                            <th class="sortable" style="width: 70px;">Class</th>
                            <th style="width: 60px;">Private<br>Encounter<br>Flag</th>
                            <th class="sortable" style="width: 180px;">Problem</th>
                            <th style="width: 40px;">Code<br>Stat</th>
                            <th style="width: 50px;">Shift<br>Req<br>Signed<br>Doc Held</th>
                            <th style="width: 50px;">Med<br>Unack<br>Orde</th>
                            <th style="width: 50px;">Reasse<br>Over<br>Pend</th>
                            <th style="width: 40px;">PRN<br>MED</th>
                            <th style="width: 40px;">New<br>Note</th>
                            <th style="width: 40px;">New<br>Mess</th>
                            <th style="width: 40px;">Rslt<br>Flag</th>
                            <th style="width: 50px;">Adm<br>Req<br>Doc</th>
                            <th style="width: 60px;">Disc<br>Req<br>Doc</th>
                            <th style="width: 70px;">Discharge<br>Med Rec<br>Complete?</th>
                            <th style="width: 50px;">Flow<br>Req<br>My</th>
                            <th style="width: 50px;">Cosi<br>Cosi<br>Note</th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody">
                        <!-- UTI Patient -->
                        <tr onclick="selectPatient(this, 1)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=1" class="patient-name-link">Smith, Mary</a></td>
                            <td>Obse...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Urinary tract infection due to ESBL...</td>
                            <td>D...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td>—</td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- SBO Patient -->
                        <tr onclick="selectPatient(this, 2)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=2" class="patient-name-link">Johnson, Robert</a></td>
                            <td>Obse...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">SBO (small bowel obstruction)...</td>
                            <td>F...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td>—</td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td><span class="team-icon">👤</span></td>
                            <td></td>
                        </tr>
                        
                        <!-- Dementia Patient -->
                        <tr onclick="selectPatient(this, 3)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=3" class="patient-name-link">Williams, Dorothy</a></td>
                            <td>Inpati...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Dementia (HCC)</td>
                            <td>F...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td>—</td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Pyelonephritis Patient -->
                        <tr onclick="selectPatient(this, 4)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=4" class="patient-name-link">Brown, James</a></td>
                            <td>Inpati...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Pyelonephritis</td>
                            <td>F...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="doc-icon">💬</span></td>
                            <td>—</td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Sepsis Patient -->
                        <tr onclick="selectPatient(this, 5)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=5" class="patient-name-link">Davis, Patricia</a></td>
                            <td>Inpati...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Sepsis due to Staphylococcus (HCC)</td>
                            <td>F...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td>—</td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td><span class="team-icon">👤</span></td>
                            <td></td>
                        </tr>
                        
                        <!-- Fever Patient -->
                        <tr onclick="selectPatient(this, 6)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=6" class="patient-name-link">Miller, Linda</a></td>
                            <td>Inpati...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Fever</td>
                            <td>F...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td><span style="font-size: 10px;">Re-...<br>PRN<br>Me...</span></td>
                            <td><span class="doc-icon has-data">📄</span></td>
                            <td>—</td>
                            <td><span class="flag-icon alert">!!</span></td>
                            <td><span class="status-icon green">✓</span></td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Acute on chronic Patient -->
                        <tr onclick="selectPatient(this, 7)">
                            <td></td>
                            <td></td>
                            <td><a href="<?= BASE_URL ?>?patient_id=7" class="patient-name-link">Wilson, Michael</a></td>
                            <td>Inpati...</td>
                            <td>No</td>
                            <td></td>
                            <td class="problem-text">Acute on chronic systolic...</td>
                            <td>R...</td>
                            <td><span class="status-icon green">✓</span></td>
                            <td>—</td>
                            <td><span class="doc-icon">⚡</span></td>
                            <td><span class="doc-icon">📄</span></td>
                            <td>—</td>
                            <td><span class="status-icon blue">○</span></td>
                            <td><span class="flag-icon warning">▲</span></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Bottom Status Bar -->
            <div style="padding: 8px 16px; background: #f5f5f5; border-top: 1px solid #ddd; display: flex; justify-content: space-between; font-size: 11px; color: #666;">
                <div>
                    <span>PAT I</span>
                    <span style="margin-left: 20px;">Results</span>
                    <span style="margin-left: 10px;">Rx Request</span>
                    <span style="margin-left: 10px;">Patient Cells</span>
                    <span style="margin-left: 10px;">My Open Charts</span>
                    <span style="margin-left: 10px;">Transcription</span>
                    <span style="margin-left: 10px;">Cosign - Chart</span>
                    <span style="margin-left: 10px;">Pt Advice Request</span>
                </div>
                <div>
                    <span id="currentTime">1:48 PM</span>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }
        setInterval(updateTime, 1000);
        updateTime();
        
        // Select patient row
        function selectPatient(row, patientId) {
            // Remove previous selection
            document.querySelectorAll('.patient-grid tr.selected').forEach(r => r.classList.remove('selected'));
            row.classList.add('selected');
        }
        
        // Load different lists
        function loadList(listType) {
            window.location.href = `?list=${listType}`;
        }
        
        // Refresh list
        function refreshList() {
            location.reload();
        }
        
        // Toggle sidebar sections
        document.querySelectorAll('.sidebar-section-header').forEach(header => {
            header.addEventListener('click', function() {
                const section = this.parentElement;
                const items = section.querySelectorAll('.sidebar-item');
                const toggle = this.querySelector('.toggle');
                
                items.forEach(item => {
                    item.style.display = item.style.display === 'none' ? 'flex' : 'none';
                });
                
                toggle.textContent = toggle.textContent === '▼' ? '▶' : '▼';
            });
        });
        
        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#patientTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>
</html>

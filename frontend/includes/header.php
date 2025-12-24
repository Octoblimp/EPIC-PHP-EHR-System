<?php
/**
 * Openspace EHR - Reusable Header Template
 * Include this at the top of every page
 */

// Include config if not already included
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user from session
$current_user = $_SESSION['user'] ?? null;
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Initialize open patients in session if not exists
if (!isset($_SESSION['open_patients'])) {
    $_SESSION['open_patients'] = [];
}

// If we're on patient-chart page, add patient to open tabs
if ($current_page === 'patient-chart' && isset($_GET['id'])) {
    $patient_id = $_GET['id'];
    $patient_name = $patient['last_name'] ?? 'Patient';
    if (isset($patient['first_name'])) {
        $patient_name .= ', ' . $patient['first_name'];
    }
    
    // Add to open patients if not already there
    $found = false;
    foreach ($_SESSION['open_patients'] as $p) {
        if ($p['id'] == $patient_id) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['open_patients'][] = [
            'id' => $patient_id,
            'name' => $patient_name
        ];
    }
}

// Use session open patients
$open_patients = $_SESSION['open_patients'] ?? [];
$current_patient = isset($_GET['id']) ? ['id' => $_GET['id']] : null;

// Check if user is admin
$is_admin = in_array(strtolower($current_user['role'] ?? ''), ['admin', 'administrator']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/openspace.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php endforeach; endif; ?>
    <style>
        /* Openspace Logo Dropdown */
        .openspace-logo-wrapper {
            position: relative;
        }
        .openspace-logo {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 2px 8px;
            border-radius: 3px;
        }
        .openspace-logo:hover {
            background: rgba(255,255,255,0.1);
        }
        .openspace-logo .dropdown-arrow {
            font-size: 10px;
            margin-left: 4px;
            opacity: 0.7;
        }
        .openspace-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 4px;
            min-width: 220px;
            display: none;
            z-index: 1001;
        }
        .openspace-menu.show {
            display: block;
        }
        .openspace-menu-header {
            padding: 12px 15px;
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            font-size: 12px;
        }
        .openspace-menu-header strong {
            display: block;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .openspace-menu a, .openspace-menu button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #333;
            font-size: 13px;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }
        .openspace-menu a:hover, .openspace-menu button:hover {
            background: #f0f4f8;
        }
        .openspace-menu a i, .openspace-menu button i {
            width: 18px;
            text-align: center;
            color: #1a4a5e;
        }
        .openspace-menu .menu-divider {
            border-top: 1px solid #e0e0e0;
            margin: 5px 0;
        }
        .openspace-menu .menu-section {
            padding: 5px 15px;
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        /* Patient Search Modal */
        .patient-search-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: flex-start;
            justify-content: center;
            padding-top: 100px;
        }
        .patient-search-modal.show {
            display: flex;
        }
        .patient-search-box {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 500px;
            max-height: 70vh;
            overflow: hidden;
        }
        .patient-search-header {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .patient-search-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .patient-search-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            opacity: 0.8;
        }
        .patient-search-header .close-btn:hover {
            opacity: 1;
        }
        .patient-search-input {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .patient-search-input input {
            width: 100%;
            padding: 10px 15px;
            font-size: 14px;
            border: 2px solid #d0d8e0;
            border-radius: 4px;
        }
        .patient-search-input input:focus {
            outline: none;
            border-color: #1a4a5e;
        }
        .patient-search-results {
            max-height: 400px;
            overflow-y: auto;
        }
        .patient-search-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .patient-search-item:hover {
            background: #f0f8ff;
        }
        .patient-search-item .patient-icon {
            width: 40px;
            height: 40px;
            background: #e8f0f4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a4a5e;
        }
        .patient-search-item .patient-info strong {
            display: block;
            color: #333;
        }
        .patient-search-item .patient-info span {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Main Header Bar -->
    <header class="openspace-header">
        <div class="openspace-logo-wrapper">
            <div class="openspace-logo" onclick="toggleOpenspaceMenu()">
                <span class="logo-icon"><i class="fas fa-hospital"></i></span>
                <span>Openspace</span>
                <i class="fas fa-caret-down dropdown-arrow"></i>
            </div>
            <div class="openspace-menu" id="openspaceMenu">
                <div class="openspace-menu-header">
                    <strong><?php echo APP_NAME; ?></strong>
                    Version <?php echo APP_VERSION; ?>
                </div>
                <a href="home.php"><i class="fas fa-home"></i> Home Dashboard</a>
                <a href="patients.php"><i class="fas fa-users"></i> Patient Lists</a>
                <a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a>
                <div class="menu-divider"></div>
                <div class="menu-section">Tools</div>
                <a href="inbox.php"><i class="fas fa-inbox"></i> In Basket</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="calculator.php"><i class="fas fa-calculator"></i> Clinical Calculator</a>
                <div class="menu-divider"></div>
                <div class="menu-section">Help & Support</div>
                <a href="help.php"><i class="fas fa-question-circle"></i> Help Center</a>
                <a href="about.php"><i class="fas fa-info-circle"></i> About Openspace</a>
                <?php if ($is_admin): ?>
                <div class="menu-divider"></div>
                <div class="menu-section">Administration</div>
                <a href="admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="header-toolbar">
            <button class="toolbar-btn<?php echo $current_page === 'home' ? ' active' : ''; ?>" onclick="window.location.href='home.php'" title="Home">
                <i class="fas fa-home icon"></i>
                <span>Home</span>
            </button>
            <div class="toolbar-divider"></div>
            <button class="toolbar-btn<?php echo $current_page === 'patients' ? ' active' : ''; ?>" onclick="window.location.href='patients.php'" title="Patient Lists">
                <i class="fas fa-users icon"></i>
                <span>Patient Lists</span>
            </button>
            <button class="toolbar-btn" onclick="showPatientSearch()" title="Find Patient">
                <i class="fas fa-search icon"></i>
                <span>Find Patient</span>
            </button>
            <div class="toolbar-divider"></div>
            <button class="toolbar-btn" onclick="window.location.href='schedule.php'" title="Schedule">
                <i class="fas fa-calendar-alt icon"></i>
                <span>Schedule</span>
            </button>
            <button class="toolbar-btn" onclick="window.location.href='inbox.php'" title="In Basket">
                <i class="fas fa-inbox icon"></i>
                <span>In Basket</span>
            </button>
            <div class="toolbar-divider"></div>
            <button class="toolbar-btn" onclick="window.location.href='reports.php'" title="Reports">
                <i class="fas fa-chart-bar icon"></i>
                <span>Reports</span>
            </button>
        </div>
        
        <div class="header-search">
            <i class="fas fa-search search-icon"></i>
            <input type="text" placeholder="Search..." id="globalSearch" onkeypress="handleGlobalSearch(event)">
        </div>
        
        <div class="header-user">
            <?php if ($current_user): ?>
            <span class="user-name"><?php echo htmlspecialchars($current_user['name'] ?? $current_user['username'] ?? 'User'); ?></span>
            <div class="header-dropdown">
                <button class="header-dropdown-btn" onclick="toggleUserMenu()">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="header-dropdown-menu" id="userDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <?php if ($is_admin): ?>
                    <div class="divider"></div>
                    <a href="admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                    <?php endif; ?>
                    <div class="divider"></div>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                </div>
            </div>
            <?php else: ?>
            <a href="login.php" class="toolbar-btn">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </a>
            <?php endif; ?>
        </div>
    </header>
    
    <!-- Patient Tab Bar -->
    <?php if (!empty($open_patients)): ?>
    <div class="patient-tab-bar">
        <?php foreach ($open_patients as $idx => $pt): 
            $is_active = $current_patient && ($pt['id'] == $current_patient['id']);
        ?>
        <div class="patient-tab <?php echo $is_active ? 'active' : ''; ?>" 
             onclick="openPatient(<?php echo $pt['id']; ?>)">
            <span><?php echo htmlspecialchars($pt['name'] ?? 'Unknown'); ?></span>
            <span class="close-tab" onclick="event.stopPropagation(); closePatientTab(<?php echo $pt['id']; ?>)">×</span>
        </div>
        <?php endforeach; ?>
        <button class="add-patient-tab" onclick="showPatientSearch()" title="Open another patient">+</button>
    </div>
    <?php endif; ?>

    <!-- Patient Search Modal -->
    <div class="patient-search-modal" id="patientSearchModal">
        <div class="patient-search-box">
            <div class="patient-search-header">
                <h3><i class="fas fa-search"></i> Find Patient</h3>
                <button class="close-btn" onclick="hidePatientSearch()">×</button>
            </div>
            <div class="patient-search-input">
                <input type="text" id="patientSearchInput" placeholder="Search by name, MRN, or DOB..." 
                       onkeyup="searchPatients(this.value)" autofocus>
            </div>
            <div class="patient-search-results" id="patientSearchResults">
                <!-- Results populated by JS -->
            </div>
        </div>
    </div>

<script>
// Demo patients for search (in production, this would be an API call)
const demoPatients = [
    { id: 1, name: 'Smith, John', mrn: 'MRN000001', dob: '03/15/1955', room: '412-A' },
    { id: 2, name: 'Johnson, Mary', mrn: 'MRN000002', dob: '07/22/1948', room: '415-B' },
    { id: 3, name: 'Williams, Robert', mrn: 'MRN000003', dob: '11/08/1960', room: '420-A' },
    { id: 4, name: 'Davis, Linda', mrn: 'MRN000004', dob: '02/14/1972', room: '418-A' },
    { id: 5, name: 'Wilson, James', mrn: 'MRN000005', dob: '09/30/1945', room: '422-B' },
];

// Openspace menu toggle
function toggleOpenspaceMenu() {
    const menu = document.getElementById('openspaceMenu');
    menu.classList.toggle('show');
}

// User menu toggle
function toggleUserMenu() {
    const menu = document.getElementById('userDropdown');
    menu.classList.toggle('show');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const openspaceWrapper = document.querySelector('.openspace-logo-wrapper');
    if (openspaceWrapper && !openspaceWrapper.contains(event.target)) {
        document.getElementById('openspaceMenu')?.classList.remove('show');
    }
    
    const userDropdown = document.querySelector('.header-dropdown');
    if (userDropdown && !userDropdown.contains(event.target)) {
        document.getElementById('userDropdown')?.classList.remove('show');
    }
});

// Global search
function handleGlobalSearch(event) {
    if (event.key === 'Enter') {
        const query = document.getElementById('globalSearch').value;
        if (query.trim()) {
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        }
    }
}

// Patient Search Modal
function showPatientSearch() {
    document.getElementById('patientSearchModal').classList.add('show');
    document.getElementById('patientSearchInput').focus();
    searchPatients(''); // Show all initially
}

function hidePatientSearch() {
    document.getElementById('patientSearchModal').classList.remove('show');
    document.getElementById('patientSearchInput').value = '';
}

function searchPatients(query) {
    const results = document.getElementById('patientSearchResults');
    const filtered = demoPatients.filter(p => 
        p.name.toLowerCase().includes(query.toLowerCase()) ||
        p.mrn.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length === 0) {
        results.innerHTML = '<div style="padding: 20px; text-align: center; color: #888;">No patients found</div>';
        return;
    }
    
    results.innerHTML = filtered.map(p => `
        <div class="patient-search-item" onclick="addPatientTab(${p.id}, '${p.name}')">
            <div class="patient-icon"><i class="fas fa-user"></i></div>
            <div class="patient-info">
                <strong>${p.name}</strong>
                <span>${p.mrn} | DOB: ${p.dob} | Room: ${p.room}</span>
            </div>
        </div>
    `).join('');
}

// Add patient to tabs and navigate
function addPatientTab(patientId, patientName) {
    // Store in session via AJAX, then navigate
    fetch('api/add-patient-tab.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: patientId, name: patientName })
    }).then(() => {
        window.location.href = 'patient-chart.php?id=' + patientId;
    }).catch(() => {
        // If API fails, just navigate
        window.location.href = 'patient-chart.php?id=' + patientId;
    });
}

// Open patient chart
function openPatient(patientId) {
    window.location.href = 'patient-chart.php?id=' + patientId;
}

// Close patient tab
function closePatientTab(patientId) {
    fetch('api/close-patient-tab.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: patientId })
    }).then(() => {
        // If we're on that patient's chart, go home
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('id') == patientId) {
            window.location.href = 'home.php';
        } else {
            window.location.reload();
        }
    }).catch(() => {
        window.location.reload();
    });
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hidePatientSearch();
    }
});

// Close modal on background click
document.getElementById('patientSearchModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hidePatientSearch();
    }
});
</script>

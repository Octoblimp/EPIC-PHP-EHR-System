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
</head>
<body>
    <!-- Main Header Bar -->
    <header class="openspace-header">
        <div class="openspace-logo" onclick="window.location.href='home.php'">
            <span class="logo-icon"><i class="fas fa-hospital"></i></span>
            <span>Openspace</span>
        </div>
        
        <div class="header-toolbar">
            <button class="toolbar-btn" onclick="window.location.href='home.php'" title="Home">
                <i class="fas fa-home icon"></i>
                <span>Home</span>
            </button>
            <div class="toolbar-divider"></div>
            <button class="toolbar-btn" onclick="window.location.href='patients.php'" title="Patient Lists">
                <i class="fas fa-users icon"></i>
                <span>Patient Lists</span>
            </button>
            <button class="toolbar-btn" onclick="openPatientSearch()" title="Find Patient">
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
                    <?php if (($current_user['role'] ?? '') === 'admin'): ?>
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
    
    <!-- Patient Tab Bar (only shown when patient context exists) -->
    <?php if (isset($open_patients) && !empty($open_patients)): ?>
    <div class="patient-tab-bar">
        <?php foreach ($open_patients as $idx => $patient): 
            $is_active = ($patient['id'] ?? '') == ($current_patient['id'] ?? '');
        ?>
        <div class="patient-tab <?php echo $is_active ? 'active' : ''; ?>" 
             onclick="openPatient(<?php echo $patient['id']; ?>)">
            <span><?php echo htmlspecialchars($patient['name'] ?? 'Unknown'); ?></span>
            <span class="close-tab" onclick="event.stopPropagation(); closePatient(<?php echo $patient['id']; ?>)">×</span>
        </div>
        <?php endforeach; ?>
        <button class="add-patient-tab" onclick="openPatientSearch()" title="Open another patient">+</button>
    </div>
    <?php endif; ?>

<script>
// User menu toggle
function toggleUserMenu() {
    const menu = document.getElementById('userDropdown');
    menu.classList.toggle('show');
}

// Close user menu when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.header-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
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

// Patient search modal
function openPatientSearch() {
    window.location.href = 'patients.php?search=1';
}

// Open patient chart
function openPatient(patientId) {
    window.location.href = 'patient-chart.php?id=' + patientId;
}

// Close patient tab
function closePatient(patientId) {
    // In a real app, this would update session/state
    // For now, just remove from URL or redirect
    window.location.href = 'home.php';
}
</script>

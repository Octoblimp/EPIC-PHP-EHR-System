<?php
/**
 * Openspace EHR - Patient Chart
 * Main patient chart view with Epic Hyperspace-style navigation
 */

// Include configuration
require_once 'includes/config.php';
require_once 'includes/api.php';

// Get patient ID from URL
$patient_id = $_GET['id'] ?? null;
$current_tab = $_GET['tab'] ?? 'summary';

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

// Fetch patient data from API
$patient = apiGet("/patients/{$patient_id}");
if (!$patient || isset($patient['error'])) {
    // Try to get patient anyway for demo
    $patient = [
        'id' => $patient_id,
        'first_name' => 'John',
        'last_name' => 'Smith',
        'date_of_birth' => '1955-03-15',
        'gender' => 'Male',
        'mrn' => 'MRN' . str_pad($patient_id, 6, '0', STR_PAD_LEFT),
        'ssn_last_four' => '1234',
        'blood_type' => 'A+',
        'allergies' => ['Penicillin', 'Sulfa'],
        'room' => '412-A',
        'attending_physician' => 'Dr. Sarah Wilson'
    ];
}

// Calculate age
$dob = new DateTime($patient['date_of_birth'] ?? '1955-01-01');
$now = new DateTime();
$age = $dob->diff($now)->y;

// Patient display info
$patient_name = ($patient['last_name'] ?? 'Unknown') . ', ' . ($patient['first_name'] ?? '');
$patient_age_sex = $age . ' y.o. ' . ($patient['gender'] ?? 'Unknown');
$mrn = $patient['mrn'] ?? 'Unknown';

// For the patient tabs bar
$open_patients = [
    ['id' => $patient_id, 'name' => $patient_name]
];
$current_patient = ['id' => $patient_id];

// Chart tabs configuration
$chart_tabs = [
    'summary' => ['label' => 'Summary', 'icon' => 'fa-clipboard'],
    'chart-review' => ['label' => 'Chart Review', 'icon' => 'fa-file-medical'],
    'results' => ['label' => 'Results', 'icon' => 'fa-flask'],
    'work-list' => ['label' => 'Work List', 'icon' => 'fa-tasks'],
    'mar' => ['label' => 'MAR', 'icon' => 'fa-pills', 'special' => true],
    'flowsheets' => ['label' => 'Flowsheets', 'icon' => 'fa-chart-line'],
    'intake-output' => ['label' => 'Intake/O', 'icon' => 'fa-balance-scale'],
    'notes' => ['label' => 'Notes', 'icon' => 'fa-sticky-note'],
    'education' => ['label' => 'Education', 'icon' => 'fa-graduation-cap'],
    'care-plan' => ['label' => 'Care Plan', 'icon' => 'fa-clipboard-list'],
    'orders' => ['label' => 'Orders', 'icon' => 'fa-prescription'],
];

// Set page title
$page_title = $patient_name . ' - ' . APP_NAME;

// Include header
include 'includes/header.php';
?>

    <!-- Patient Header Banner -->
    <div class="patient-banner">
        <div class="patient-photo">
            <i class="fas fa-user"></i>
        </div>
        <div class="patient-info-main">
            <div class="patient-name-row">
                <span class="patient-name"><?php echo htmlspecialchars($patient_name); ?></span>
                <span class="patient-age-sex"><?php echo htmlspecialchars($patient_age_sex); ?></span>
            </div>
            <div class="patient-ids">
                <span><label>MRN:</label> <?php echo htmlspecialchars($mrn); ?></span>
                <span><label>DOB:</label> <?php echo formatDate($patient['date_of_birth'] ?? ''); ?></span>
                <span><label>Room:</label> <?php echo htmlspecialchars($patient['room'] ?? 'N/A'); ?></span>
                <?php if (!empty($patient['attending_physician'])): ?>
                <span><label>Attending:</label> <?php echo htmlspecialchars($patient['attending_physician']); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="patient-alerts">
            <?php if (!empty($patient['allergies'])): ?>
            <span class="alert-badge allergy">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo is_array($patient['allergies']) ? implode(', ', $patient['allergies']) : $patient['allergies']; ?>
            </span>
            <?php endif; ?>
            <span class="alert-badge code-status">Full Code</span>
            <span class="alert-badge fall-risk">Fall Risk</span>
        </div>
        <div class="patient-info-boxes">
            <div class="patient-info-box">
                <label>Blood Type</label>
                <span class="value"><?php echo htmlspecialchars($patient['blood_type'] ?? 'Unknown'); ?></span>
            </div>
            <div class="patient-info-box">
                <label>Isolation</label>
                <span class="value">None</span>
            </div>
        </div>
    </div>

    <!-- Chart Navigation Tabs -->
    <nav class="chart-nav">
        <div class="chart-nav-tabs">
            <?php foreach ($chart_tabs as $tab_id => $tab): 
                $is_active = ($current_tab === $tab_id);
                $special_class = isset($tab['special']) ? ' mar-tab' : '';
            ?>
            <a href="patient-chart.php?id=<?php echo $patient_id; ?>&tab=<?php echo $tab_id; ?>" 
               class="chart-nav-tab<?php echo $is_active ? ' active' : ''; ?><?php echo $special_class; ?>">
                <i class="fas <?php echo $tab['icon']; ?>"></i>
                <?php echo htmlspecialchars($tab['label']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- Sub Navigation (changes per tab) -->
    <div class="chart-subnav">
        <div class="subnav-tabs">
            <?php if ($current_tab === 'summary'): ?>
            <a href="#" class="subnav-tab active">Overview</a>
            <a href="#" class="subnav-tab">Index</a>
            <a href="#" class="subnav-tab">SBAR Handoff</a>
            <a href="#" class="subnav-tab">Storyboard</a>
            <?php elseif ($current_tab === 'results'): ?>
            <a href="#" class="subnav-tab active">All Results</a>
            <a href="#" class="subnav-tab">Lab</a>
            <a href="#" class="subnav-tab">Imaging</a>
            <a href="#" class="subnav-tab">Micro</a>
            <?php elseif ($current_tab === 'mar'): ?>
            <a href="#" class="subnav-tab active">MAR</a>
            <a href="#" class="subnav-tab">Due</a>
            <a href="#" class="subnav-tab">PRN</a>
            <a href="#" class="subnav-tab">Continuous</a>
            <?php else: ?>
            <a href="#" class="subnav-tab active">All</a>
            <a href="#" class="subnav-tab">Recent</a>
            <?php endif; ?>
        </div>
        <div class="subnav-actions">
            <div class="subnav-search">
                <input type="text" placeholder="Filter...">
            </div>
            <button class="btn btn-sm btn-secondary"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-sm btn-secondary"><i class="fas fa-sync"></i> Refresh</button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="chart-content">
        <!-- Left Sidebar -->
        <aside class="chart-sidebar">
            <!-- Patient Demographics -->
            <div class="sidebar-section">
                <div class="sidebar-header">Patient Info</div>
                <div class="sidebar-content">
                    <div class="sidebar-item">
                        <label>Age:</label>
                        <span><?php echo $age; ?> years</span>
                    </div>
                    <div class="sidebar-item">
                        <label>Gender:</label>
                        <span><?php echo htmlspecialchars($patient['gender'] ?? 'Unknown'); ?></span>
                    </div>
                    <div class="sidebar-item">
                        <label>Height:</label>
                        <span>5'10" (178 cm)</span>
                    </div>
                    <div class="sidebar-item">
                        <label>Weight:</label>
                        <span>180 lbs (82 kg)</span>
                    </div>
                    <div class="sidebar-item">
                        <label>BMI:</label>
                        <span>25.8</span>
                    </div>
                </div>
            </div>

            <!-- Allergies -->
            <div class="sidebar-section allergies-section">
                <div class="sidebar-header"><i class="fas fa-exclamation-triangle"></i> Allergies</div>
                <div class="sidebar-content">
                    <?php if (!empty($patient['allergies'])): 
                        $allergies = is_array($patient['allergies']) ? $patient['allergies'] : [$patient['allergies']];
                        foreach ($allergies as $allergy): ?>
                    <div class="sidebar-item allergy-item">
                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                        <?php echo htmlspecialchars($allergy); ?>
                    </div>
                    <?php endforeach; else: ?>
                    <div class="sidebar-item text-muted">NKA</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Care Team -->
            <div class="sidebar-section">
                <div class="sidebar-header">Care Team</div>
                <div class="sidebar-content">
                    <div class="sidebar-item">
                        <label>Attending:</label>
                        <a href="#" class="sidebar-link"><?php echo htmlspecialchars($patient['attending_physician'] ?? 'N/A'); ?></a>
                    </div>
                    <div class="sidebar-item">
                        <label>PCP:</label>
                        <a href="#" class="sidebar-link">Dr. James Miller</a>
                    </div>
                    <div class="sidebar-item">
                        <label>Nurse:</label>
                        <span>Sarah Jones, RN</span>
                    </div>
                </div>
            </div>

            <!-- Precautions -->
            <div class="sidebar-section">
                <div class="sidebar-header">Precautions</div>
                <div class="sidebar-content">
                    <div class="sidebar-item">
                        <span class="alert-badge fall-risk" style="font-size: 10px;">Fall Risk</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="chart-main">
            <?php
            // Load appropriate content based on current tab
            switch ($current_tab) {
                case 'summary':
                    include 'activities/summary-content.php';
                    break;
                case 'results':
                    include 'activities/results-content.php';
                    break;
                case 'mar':
                    include 'activities/mar-content.php';
                    break;
                case 'flowsheets':
                    include 'activities/flowsheets-content.php';
                    break;
                case 'notes':
                    include 'activities/notes-content.php';
                    break;
                case 'orders':
                    include 'activities/orders-content.php';
                    break;
                case 'care-plan':
                    include 'activities/care-plan-content.php';
                    break;
                case 'chart-review':
                    include 'activities/chart-review-content.php';
                    break;
                default:
                    include 'activities/summary-content.php';
                    break;
            }
            ?>
        </main>
    </div>

<?php include 'includes/footer.php'; ?>

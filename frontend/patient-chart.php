<?php
/**
 * Openspace EHR - Patient Chart
 * Main patient chart view with Epic Hyperspace-style navigation
 * 
 * SECURITY NOTE: All user inputs are validated and sanitized.
 * Patient ID is validated as a positive integer to prevent injection attacks.
 * 
 * SECURITY: If DOB verification is enabled and user hasn't verified,
 * they are REDIRECTED to a separate verification page BEFORE any
 * patient data is loaded. This prevents inspect-element bypasses.
 */

// Include configuration
require_once 'includes/config.php';
require_once 'includes/api.php';
require_once 'includes/patient_protection.php';

// Get and VALIDATE patient ID from URL (SECURITY: Prevent SQL injection)
$raw_patient_id = $_GET['id'] ?? null;
$patient_id = InputValidator::validatePatientId($raw_patient_id);
$current_tab = InputValidator::sanitizeString($_GET['tab'] ?? 'summary');

// Track if tab is valid (will be checked when loading content)
$tab_error = false;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

// SECURITY: Check verification BEFORE loading any patient data
// This prevents inspect-element bypasses since no data is present to reveal
$needs_verification = isPatientProtectionEnabled() && !hasVerifiedPatientAccess($patient_id);

if ($needs_verification) {
    // Redirect to secure verification page - NO patient data loaded
    $verify_url = 'verify-patient.php?id=' . $patient_id . '&tab=' . urlencode($current_tab);
    header('Location: ' . $verify_url);
    exit;
}

// Only load patient data AFTER verification passes
// Fetch patient data from API using patientService
$patientData = $patientService->getById($patient_id);
$patient = ($patientData['success'] ?? false) ? ($patientData['data'] ?? null) : null;

if (!$patient) {
    // Use demo data if API not available
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
        'attending_physician' => 'Dr. Sarah Wilson',
        'insurance' => [
            'primary' => [
                'payer' => 'Blue Cross Blue Shield',
                'plan' => 'PPO Gold',
                'policy_number' => 'BCB123456789',
                'group_number' => 'GRP001',
                'copay' => '$25',
                'subscriber' => 'Self'
            ],
            'secondary' => [
                'payer' => 'Medicare',
                'policy_number' => '1EG4-TE5-MK72'
            ]
        ]
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

// Start session for patient tabs if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize open patients in session if not exists
if (!isset($_SESSION['open_patients'])) {
    $_SESSION['open_patients'] = [];
}

// Add current patient to open tabs if not already there
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

// Use session for open patients (don't override)
$open_patients = $_SESSION['open_patients'];
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
    'demographics' => ['label' => 'Demographics', 'icon' => 'fa-id-card'],
    'insurance' => ['label' => 'Insurance', 'icon' => 'fa-shield-alt'],
];

// Encounter/visit information (from API or demo)
$encounter = [
    'type' => 'Inpatient',
    'status' => 'Active',
    'admit_date' => date('m/d/Y', strtotime('-2 days')),
    'expected_discharge' => date('m/d/Y', strtotime('+3 days')),
    'department' => 'Medical ICU',
    'room' => $patient['room'] ?? '412-A',
    'bed' => 'A',
    'unit' => 'ICU Tower 4',
    'nursing_station' => '4T',
    'attending_provider' => $patient['attending_physician'] ?? 'Dr. Sarah Wilson',
    'primary_nurse' => 'RN Jessica Martinez',
    'code_status' => 'Full Code',
    'fall_risk' => true,
    'isolation' => null,
    'isolation_type' => null,
    'alerts' => []
];

// Sticky notes for this patient
$sticky_notes = [
    [
        'id' => 1,
        'title' => 'NPO after midnight',
        'content' => 'Patient is NPO after midnight for procedure tomorrow',
        'color' => 'yellow',
        'priority' => 'High',
        'created_by' => 'Dr. Wilson',
        'created_at' => date('m/d/Y H:i', strtotime('-4 hours'))
    ],
    [
        'id' => 2,
        'title' => 'Family contact',
        'content' => 'Daughter Jane (555-123-4567) is healthcare proxy',
        'color' => 'blue',
        'priority' => 'Normal',
        'created_by' => 'Care Coordinator',
        'created_at' => date('m/d/Y H:i', strtotime('-1 day'))
    ]
];

// Set page title
$page_title = $patient_name . ' - ' . APP_NAME;

// SECURITY: Verification is now handled via redirect at the top of this file
// No need for overlay - user must complete verification before reaching this point

// Include header
include 'includes/header.php';

    <!-- Enhanced Patient Header Banner - Epic Hyperspace Style -->
    <div class="patient-banner enhanced">
        <!-- Left Section: Photo & Basic Info -->
        <div class="banner-left">
            <div class="patient-photo">
                <i class="fas fa-user"></i>
                <?php if ($encounter['type'] === 'Inpatient'): ?>
                <span class="photo-badge inpatient" title="Inpatient">IP</span>
                <?php elseif ($encounter['type'] === 'Outpatient'): ?>
                <span class="photo-badge outpatient" title="Outpatient">OP</span>
                <?php else: ?>
                <span class="photo-badge" title="<?php echo htmlspecialchars($encounter['type']); ?>"><?php echo substr($encounter['type'], 0, 2); ?></span>
                <?php endif; ?>
            </div>
            <div class="patient-primary-info">
                <div class="patient-name-row">
                    <span class="patient-name"><?php echo htmlspecialchars($patient_name); ?></span>
                    <span class="patient-age-sex"><?php echo htmlspecialchars($patient_age_sex); ?></span>
                    <?php if (!empty($sticky_notes)): ?>
                    <button class="sticky-notes-btn" onclick="toggleStickyNotes()" title="<?php echo count($sticky_notes); ?> Sticky Note(s)">
                        <i class="fas fa-sticky-note"></i>
                        <span class="sticky-count"><?php echo count($sticky_notes); ?></span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="patient-identifiers">
                    <span class="identifier"><label>MRN:</label> <?php echo htmlspecialchars($mrn); ?></span>
                    <span class="identifier"><label>DOB:</label> <?php echo formatDate($patient['date_of_birth'] ?? ''); ?></span>
                    <span class="identifier"><label>SSN:</label> xxx-xx-<?php echo htmlspecialchars($patient['ssn_last_four'] ?? '****'); ?></span>
                </div>
                <!-- Encounter Info Row -->
                <div class="encounter-info-row">
                    <span class="encounter-status <?php echo strtolower($encounter['status']); ?>">
                        <i class="fas fa-circle"></i> <?php echo htmlspecialchars($encounter['type']); ?> - <?php echo htmlspecialchars($encounter['status']); ?>
                    </span>
                    <span class="encounter-detail">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($encounter['department']); ?>
                    </span>
                    <span class="encounter-detail location">
                        <i class="fas fa-bed"></i> 
                        <strong><?php echo htmlspecialchars($encounter['room']); ?></strong>
                        <?php if (!empty($encounter['bed'])): ?>
                        - Bed <?php echo htmlspecialchars($encounter['bed']); ?>
                        <?php endif; ?>
                    </span>
                    <?php if (!empty($encounter['nursing_station'])): ?>
                    <span class="encounter-detail">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($encounter['nursing_station']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Center Section: Alerts & Status -->
        <div class="banner-center">
            <div class="alert-badges">
                <!-- Allergies -->
                <?php if (!empty($patient['allergies'])): ?>
                <span class="alert-badge allergy critical" onclick="showAllergyDetails()">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?php 
                    $allergies = is_array($patient['allergies']) ? $patient['allergies'] : [$patient['allergies']];
                    echo count($allergies) > 2 ? implode(', ', array_slice($allergies, 0, 2)) . ' +' . (count($allergies) - 2) : implode(', ', $allergies);
                    ?>
                </span>
                <?php else: ?>
                <span class="alert-badge nka">
                    <i class="fas fa-check"></i> NKA
                </span>
                <?php endif; ?>
                
                <!-- Code Status -->
                <span class="alert-badge code-status <?php echo $encounter['code_status'] === 'Full Code' ? 'full-code' : 'limited-code'; ?>" onclick="showCodeStatusModal()">
                    <i class="fas <?php echo $encounter['code_status'] === 'Full Code' ? 'fa-heartbeat' : 'fa-heart-broken'; ?>"></i>
                    <?php echo htmlspecialchars($encounter['code_status']); ?>
                </span>
                
                <!-- Fall Risk -->
                <?php if ($encounter['fall_risk']): ?>
                <span class="alert-badge fall-risk">
                    <i class="fas fa-exclamation-circle"></i> Fall Risk
                </span>
                <?php endif; ?>
                
                <!-- Isolation -->
                <?php if (!empty($encounter['isolation'])): ?>
                <span class="alert-badge isolation <?php echo strtolower(str_replace(' ', '-', $encounter['isolation_type'] ?? 'contact')); ?>">
                    <i class="fas fa-shield-virus"></i> <?php echo htmlspecialchars($encounter['isolation_type'] ?? 'Isolation'); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Care Team Quick View -->
            <div class="care-team-row">
                <span class="care-team-member">
                    <label>Attending:</label> <?php echo htmlspecialchars($encounter['attending_provider']); ?>
                </span>
                <?php if (!empty($encounter['primary_nurse'])): ?>
                <span class="care-team-member">
                    <label>RN:</label> <?php echo htmlspecialchars($encounter['primary_nurse']); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Section: Quick Info Boxes -->
        <div class="banner-right">
            <div class="info-boxes-row">
                <div class="patient-info-box">
                    <label>Blood Type</label>
                    <span class="value"><?php echo htmlspecialchars($patient['blood_type'] ?? 'Unknown'); ?></span>
                </div>
                <div class="patient-info-box">
                    <label>Admit</label>
                    <span class="value"><?php echo htmlspecialchars($encounter['admit_date']); ?></span>
                </div>
                <div class="patient-info-box">
                    <label>Exp D/C</label>
                    <span class="value"><?php echo htmlspecialchars($encounter['expected_discharge'] ?? 'TBD'); ?></span>
                </div>
                <?php if (!empty($patient['insurance']['primary'])): ?>
                <div class="patient-info-box insurance-box" title="Click for insurance details" onclick="showInsuranceModal()">
                    <label>Insurance</label>
                    <span class="value"><?php echo htmlspecialchars($patient['insurance']['primary']['payer'] ?? 'Unknown'); ?></span>
                    <span class="insurance-plan"><?php echo htmlspecialchars($patient['insurance']['primary']['plan'] ?? ''); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Banner Actions -->
            <div class="banner-actions">
                <button class="banner-action-btn" onclick="printChart()" title="Print">
                    <i class="fas fa-print"></i>
                </button>
                <button class="banner-action-btn" onclick="refreshChart()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button class="banner-action-btn" onclick="showPatientActions()" title="More Actions">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Sticky Notes Panel (hidden by default) -->
    <div id="stickyNotesPanel" class="sticky-notes-panel" style="display:none;">
        <div class="sticky-notes-header">
            <h4><i class="fas fa-sticky-note"></i> Sticky Notes</h4>
            <div class="sticky-notes-actions">
                <button class="btn btn-sm btn-primary" onclick="addStickyNote()"><i class="fas fa-plus"></i> Add Note</button>
                <button class="close-btn" onclick="toggleStickyNotes()">&times;</button>
            </div>
        </div>
        <div class="sticky-notes-list">
            <?php foreach ($sticky_notes as $note): ?>
            <div class="sticky-note <?php echo htmlspecialchars($note['color']); ?>">
                <div class="sticky-note-header">
                    <strong><?php echo htmlspecialchars($note['title']); ?></strong>
                    <?php if ($note['priority'] === 'High'): ?>
                    <span class="priority-badge high">High</span>
                    <?php endif; ?>
                </div>
                <div class="sticky-note-content">
                    <?php echo htmlspecialchars($note['content']); ?>
                </div>
                <div class="sticky-note-footer">
                    <span class="note-author"><?php echo htmlspecialchars($note['created_by']); ?></span>
                    <span class="note-date"><?php echo htmlspecialchars($note['created_at']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Insurance Details Modal -->
    <div id="insuranceModal" class="modal" style="display:none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-id-card"></i> Insurance Information</h5>
                    <button type="button" class="close" onclick="closeInsuranceModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($patient['insurance']['primary'])): ?>
                    <div class="insurance-section">
                        <h6><i class="fas fa-star"></i> Primary Insurance</h6>
                        <table class="insurance-detail-table">
                            <tr><td>Payer:</td><td><strong><?php echo htmlspecialchars($patient['insurance']['primary']['payer'] ?? 'N/A'); ?></strong></td></tr>
                            <tr><td>Plan:</td><td><?php echo htmlspecialchars($patient['insurance']['primary']['plan'] ?? 'N/A'); ?></td></tr>
                            <tr><td>Policy #:</td><td><?php echo htmlspecialchars($patient['insurance']['primary']['policy_number'] ?? 'N/A'); ?></td></tr>
                            <tr><td>Group #:</td><td><?php echo htmlspecialchars($patient['insurance']['primary']['group_number'] ?? 'N/A'); ?></td></tr>
                            <tr><td>Copay:</td><td><?php echo htmlspecialchars($patient['insurance']['primary']['copay'] ?? 'N/A'); ?></td></tr>
                            <tr><td>Subscriber:</td><td><?php echo htmlspecialchars($patient['insurance']['primary']['subscriber'] ?? 'N/A'); ?></td></tr>
                        </table>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['insurance']['secondary'])): ?>
                    <div class="insurance-section" style="margin-top:15px;">
                        <h6><i class="fas fa-star-half-alt"></i> Secondary Insurance</h6>
                        <table class="insurance-detail-table">
                            <tr><td>Payer:</td><td><strong><?php echo htmlspecialchars($patient['insurance']['secondary']['payer'] ?? 'N/A'); ?></strong></td></tr>
                            <tr><td>Policy #:</td><td><?php echo htmlspecialchars($patient['insurance']['secondary']['policy_number'] ?? 'N/A'); ?></td></tr>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeInsuranceModal()">Close</button>
                    <a href="insurance.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Full Insurance Details
                    </a>
                </div>
            </div>
        </div>
    </div>

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
            <button class="btn btn-sm btn-secondary" onclick="printChart()"><i class="fas fa-print"></i> Print</button>
            <button class="btn btn-sm btn-secondary" onclick="refreshChart()"><i class="fas fa-sync"></i> Refresh</button>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="chart-content">
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
                case 'demographics':
                    include 'activities/demographics-content.php';
                    break;
                case 'insurance':
                    include 'activities/insurance-content.php';
                    break;
                default:
                    // Show error for invalid/unknown tabs instead of redirecting
                    ?>
                    <div class="content-panel" style="margin: 20px;">
                        <div class="panel-header" style="background: linear-gradient(to bottom, #d04040, #a03030);">
                            <span><i class="fas fa-exclamation-triangle"></i> Page Not Available</span>
                        </div>
                        <div class="panel-content" style="padding: 40px; text-align: center;">
                            <div style="font-size: 64px; color: #d04040; margin-bottom: 20px;">
                                <i class="fas fa-ban"></i>
                            </div>
                            <h2 style="color: #333; margin-bottom: 10px;">This page does not exist or you do not have permission to access it.</h2>
                            <p style="color: #666; margin-bottom: 30px;">
                                The requested activity "<strong><?php echo htmlspecialchars($current_tab); ?></strong>" could not be found.<br>
                                Please select a valid activity from the sidebar or contact your system administrator if you believe this is an error.
                            </p>
                            <div style="display: flex; gap: 10px; justify-content: center;">
                                <a href="patient-chart.php?id=<?php echo $patient_id; ?>&tab=summary" class="btn btn-primary">
                                    <i class="fas fa-home"></i> Go to Summary
                                </a>
                                <a href="patients.php" class="btn btn-secondary">
                                    <i class="fas fa-users"></i> Patient List
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </main>
    </div>

<style>
/* Enhanced Patient Banner Styles */
.patient-banner.enhanced {
    display: flex;
    align-items: stretch;
    background: linear-gradient(to bottom, #e8f4fc, #dceef8);
    border-bottom: 2px solid #b8d4e8;
    padding: 0;
    min-height: 90px;
}

.banner-left {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 15px;
    flex: 0 0 auto;
}

.banner-left .patient-photo {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #888;
    position: relative;
    border: 2px solid #ccc;
}

.banner-left .photo-badge {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: #1a4a5e;
    color: white;
    font-size: 9px;
    font-weight: bold;
    padding: 2px 5px;
    border-radius: 3px;
}

.photo-badge.inpatient { background: #1565c0; }
.photo-badge.outpatient { background: #558b2f; }

.patient-primary-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.patient-name-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.patient-name-row .patient-name {
    font-size: 17px;
    font-weight: 600;
    color: #0d47a1;
}

.patient-name-row .patient-age-sex {
    font-size: 13px;
    color: #555;
    background: #f0f0f0;
    padding: 2px 8px;
    border-radius: 3px;
}

.sticky-notes-btn {
    background: #fff8c5;
    border: 1px solid #f0c36d;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #8b6914;
}

.sticky-notes-btn:hover {
    background: #fff3a0;
}

.sticky-count {
    background: #e65100;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.patient-identifiers {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.patient-identifiers .identifier label {
    font-weight: 600;
    color: #666;
}

.encounter-info-row {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 11px;
    margin-top: 2px;
}

.encounter-status {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
}

.encounter-status i {
    font-size: 8px;
}

.encounter-status.active { color: #2e7d32; }
.encounter-status.active i { color: #4caf50; }
.encounter-status.discharged { color: #666; }
.encounter-status.transferred { color: #1565c0; }

.encounter-detail {
    color: #555;
}

.encounter-detail.location {
    font-weight: 600;
    color: #1565c0;
}

.banner-center {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 10px 15px;
    border-left: 1px solid #c8dce8;
    border-right: 1px solid #c8dce8;
}

.alert-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 6px;
}

.alert-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.alert-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-badge.allergy {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.alert-badge.allergy.critical {
    background: #c62828;
    color: white;
    border-color: #b71c1c;
    animation: pulse-alert 2s infinite;
}

@keyframes pulse-alert {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.alert-badge.nka {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.alert-badge.code-status {
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #90caf9;
}

.alert-badge.code-status.limited-code {
    background: #fff3e0;
    color: #e65100;
    border: 1px solid #ffcc80;
}

.alert-badge.fall-risk {
    background: #fff8e1;
    color: #f57f17;
    border: 1px solid #ffe082;
}

.alert-badge.isolation {
    background: #f3e5f5;
    color: #7b1fa2;
    border: 1px solid #ce93d8;
}

.alert-badge.isolation.airborne {
    background: #ffcdd2;
    color: #c62828;
}

.alert-badge.isolation.contact {
    background: #fff9c4;
    color: #f9a825;
}

.alert-badge.isolation.droplet {
    background: #b3e5fc;
    color: #0277bd;
}

.care-team-row {
    display: flex;
    gap: 20px;
    font-size: 11px;
    color: #555;
}

.care-team-member label {
    font-weight: 600;
    color: #777;
}

.banner-right {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-end;
    padding: 10px 15px;
    gap: 8px;
}

.info-boxes-row {
    display: flex;
    gap: 10px;
}

.patient-info-box {
    background: white;
    border: 1px solid #d0d8e0;
    border-radius: 4px;
    padding: 6px 12px;
    text-align: center;
    min-width: 70px;
}

.patient-info-box label {
    display: block;
    font-size: 9px;
    color: #888;
    text-transform: uppercase;
    font-weight: 600;
}

.patient-info-box .value {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.banner-actions {
    display: flex;
    gap: 5px;
}

.banner-action-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #c8d0d8;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    transition: all 0.15s;
}

.banner-action-btn:hover {
    background: #f0f4f8;
    color: #1a4a5e;
    border-color: #1a4a5e;
}

/* Sticky Notes Panel */
.sticky-notes-panel {
    position: absolute;
    top: 90px;
    right: 20px;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1000;
}

.sticky-notes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    border-radius: 8px 8px 0 0;
}

.sticky-notes-header h4 {
    margin: 0;
    font-size: 14px;
}

.sticky-notes-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sticky-notes-header .close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.sticky-notes-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
}

.sticky-note {
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    border-left: 4px solid #f0c36d;
}

.sticky-note.yellow {
    background: #fffde7;
    border-left-color: #f0c36d;
}

.sticky-note.blue {
    background: #e3f2fd;
    border-left-color: #42a5f5;
}

.sticky-note.green {
    background: #e8f5e9;
    border-left-color: #66bb6a;
}

.sticky-note.red {
    background: #ffebee;
    border-left-color: #ef5350;
}

.sticky-note.purple {
    background: #f3e5f5;
    border-left-color: #ab47bc;
}

.sticky-note-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.sticky-note-header strong {
    font-size: 12px;
}

.priority-badge {
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 600;
}

.priority-badge.high {
    background: #c62828;
    color: white;
}

.sticky-note-content {
    font-size: 12px;
    color: #555;
    line-height: 1.4;
}

.sticky-note-footer {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 10px;
    color: #888;
}

/* Insurance box styling */
.insurance-box {
    cursor: pointer;
    transition: all 0.2s;
}
.insurance-box:hover {
    background: #e3f2fd;
    transform: translateY(-1px);
}
.insurance-plan {
    display: block;
    font-size: 10px;
    color: #666;
    margin-top: 2px;
}

/* Insurance Modal */
#insuranceModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}
#insuranceModal .modal-dialog {
    background: white;
    border-radius: 8px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
#insuranceModal .modal-header {
    padding: 15px 20px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
#insuranceModal .modal-header .close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
}
#insuranceModal .modal-body {
    padding: 20px;
}
#insuranceModal .modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.insurance-section h6 {
    color: #1a4a5e;
    margin-bottom: 10px;
    font-size: 14px;
}
.insurance-detail-table {
    width: 100%;
}
.insurance-detail-table td {
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}
.insurance-detail-table td:first-child {
    color: #666;
    width: 100px;
}

/* Print styles */
@media print {
    .sidebar, .header-bar, .chart-subnav, .patient-banner .patient-photo,
    .subnav-actions, #insuranceModal, .btn {
        display: none !important;
    }
    .chart-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .patient-banner {
        border: 1px solid #000;
        padding: 10px;
        margin-bottom: 20px;
    }
    body {
        background: white !important;
    }
    .chart-main {
        box-shadow: none !important;
    }
}
</style>

<script>
// Insurance Modal Functions
function showInsuranceModal() {
    document.getElementById('insuranceModal').style.display = 'flex';
}

function closeInsuranceModal() {
    document.getElementById('insuranceModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('insuranceModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeInsuranceModal();
    }
});

// Sticky Notes Panel
function toggleStickyNotes() {
    const panel = document.getElementById('stickyNotesPanel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

function addStickyNote() {
    // Show add sticky note modal
    const modal = document.createElement('div');
    modal.id = 'addStickyNoteModal';
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-dialog" style="width:400px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sticky-note"></i> Add Sticky Note</h5>
                    <button type="button" class="close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Title</label>
                        <input type="text" id="stickyTitle" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Content</label>
                        <textarea id="stickyContent" class="form-control" rows="3" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Color</label>
                        <select id="stickyColor" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="yellow">Yellow</option>
                            <option value="blue">Blue</option>
                            <option value="green">Green</option>
                            <option value="red">Red</option>
                            <option value="purple">Purple</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select id="stickyPriority" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:15px 20px;border-top:1px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStickyNote()">Save Note</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function saveStickyNote() {
    const title = document.getElementById('stickyTitle').value;
    const content = document.getElementById('stickyContent').value;
    const color = document.getElementById('stickyColor').value;
    const priority = document.getElementById('stickyPriority').value;
    
    if (!title || !content) {
        alert('Please fill in title and content');
        return;
    }
    
    // Save to backend
    const saveBtn = document.querySelector('#addStickyNoteModal .btn-primary');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('api/patient-data.php?action=sticky-note&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            title: title,
            content: content,
            color: color,
            priority: priority
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Add to the DOM
            const noteHtml = `
                <div class="sticky-note ${color}" data-note-id="${data.data?.id || ''}">
                    <div class="sticky-note-header">
                        <strong>${title}</strong>
                        ${priority === 'High' ? '<span class="priority-badge high">High</span>' : ''}
                    </div>
                    <div class="sticky-note-content">${content}</div>
                    <div class="sticky-note-footer">
                        <span class="note-author">${data.data?.created_by || 'Current User'}</span>
                        <span class="note-date">${data.data?.created_at || new Date().toLocaleString()}</span>
                    </div>
                </div>
            `;
            
            document.querySelector('.sticky-notes-list').insertAdjacentHTML('afterbegin', noteHtml);
            document.getElementById('addStickyNoteModal').remove();
            
            // Update count
            const countEl = document.querySelector('.sticky-count');
            if (countEl) {
                countEl.textContent = parseInt(countEl.textContent) + 1;
            }
            
            // Show success toast
            showToast('Sticky note saved successfully', 'success');
        } else {
            showToast(data.error || 'Failed to save sticky note', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Save Note';
        }
    })
    .catch(err => {
        showToast('Error saving sticky note: ' + err.message, 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Save Note';
    });
}

// Patient Actions Menu
function showPatientActions() {
    const existingMenu = document.getElementById('patientActionsMenu');
    if (existingMenu) {
        existingMenu.remove();
        return;
    }
    
    const menu = document.createElement('div');
    menu.id = 'patientActionsMenu';
    menu.style.cssText = `
        position: absolute;
        top: 90px;
        right: 15px;
        background: white;
        border-radius: 6px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        z-index: 1000;
        min-width: 200px;
    `;
    menu.innerHTML = `
        <div style="padding:8px 0;">
            <a href="#" onclick="changePatientStatus()" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-exchange-alt" style="width:20px;"></i> Change Status
            </a>
            <a href="#" onclick="showCodeStatusModal()" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-heartbeat" style="width:20px;"></i> Update Code Status
            </a>
            <a href="#" onclick="toggleStickyNotes()" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-sticky-note" style="width:20px;"></i> Sticky Notes
            </a>
            <div style="border-top:1px solid #e0e0e0;margin:5px 0;"></div>
            <a href="?id=<?php echo $patient_id; ?>&tab=demographics" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-id-card" style="width:20px;"></i> Demographics
            </a>
            <a href="?id=<?php echo $patient_id; ?>&tab=insurance" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-shield-alt" style="width:20px;"></i> Insurance Details
            </a>
            <div style="border-top:1px solid #e0e0e0;margin:5px 0;"></div>
            <a href="#" onclick="printChart()" style="display:block;padding:10px 15px;color:#333;text-decoration:none;font-size:13px;">
                <i class="fas fa-print" style="width:20px;"></i> Print Chart
            </a>
        </div>
    `;
    
    document.body.appendChild(menu);
    
    // Close on click outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 100);
}

// Change Patient Status
function changePatientStatus() {
    document.getElementById('patientActionsMenu')?.remove();
    
    const modal = document.createElement('div');
    modal.id = 'changeStatusModal';
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-dialog" style="width:400px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Change Patient Status</h5>
                    <button type="button" class="close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Current Status</label>
                        <div style="padding:8px;background:#f0f0f0;border-radius:4px;font-weight:500;">
                            <?php echo htmlspecialchars($encounter['type']); ?> - <?php echo htmlspecialchars($encounter['status']); ?>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>New Status</label>
                        <select id="newStatus" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="">Select New Status...</option>
                            <option value="Active">Active</option>
                            <option value="Discharged">Discharged</option>
                            <option value="Transferred">Transferred</option>
                            <option value="Left AMA">Left AMA</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Encounter Type</label>
                        <select id="newEncounterType" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="Inpatient">Inpatient</option>
                            <option value="Outpatient">Outpatient</option>
                            <option value="Observation">Observation</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reason for Change</label>
                        <textarea id="statusChangeReason" class="form-control" rows="2" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:15px 20px;border-top:1px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveStatusChange()">Update Status</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function saveStatusChange() {
    const newStatus = document.getElementById('newStatus').value;
    const newEncounterType = document.getElementById('newEncounterType').value;
    const reason = document.getElementById('statusChangeReason').value;
    
    if (!newStatus) {
        showToast('Please select a new status', 'warning');
        return;
    }
    
    const saveBtn = document.querySelector('#changeStatusModal .btn-primary');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('api/patient-data.php?action=status&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            status: newStatus,
            encounter_type: newEncounterType,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('changeStatusModal').remove();
            showToast('Patient status updated successfully', 'success');
            
            // Update the UI to reflect the new status
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to update status', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Update Status';
        }
    })
    .catch(err => {
        showToast('Error updating status: ' + err.message, 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Update Status';
    });
}

// Code Status Modal
function showCodeStatusModal() {
    document.getElementById('patientActionsMenu')?.remove();
    
    const modal = document.createElement('div');
    modal.id = 'codeStatusModal';
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-dialog" style="width:400px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-heartbeat"></i> Update Code Status</h5>
                    <button type="button" class="close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Current Code Status</label>
                        <div style="padding:8px;background:#f0f0f0;border-radius:4px;font-weight:500;">
                            <?php echo htmlspecialchars($encounter['code_status']); ?>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:15px;">
                        <label>New Code Status</label>
                        <select id="newCodeStatus" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;">
                            <option value="Full Code">Full Code</option>
                            <option value="DNR">DNR (Do Not Resuscitate)</option>
                            <option value="DNI">DNI (Do Not Intubate)</option>
                            <option value="DNR/DNI">DNR/DNI</option>
                            <option value="Comfort Care">Comfort Care Only</option>
                            <option value="Limited Code">Limited Code</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Documentation</label>
                        <textarea id="codeStatusNotes" class="form-control" rows="2" placeholder="Document discussion with patient/family..." style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:15px 20px;border-top:1px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCodeStatus()">Update Code Status</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function saveCodeStatus() {
    const newCodeStatus = document.getElementById('newCodeStatus').value;
    const documentation = document.getElementById('codeStatusNotes').value;
    
    if (!newCodeStatus) {
        showToast('Please select a code status', 'warning');
        return;
    }
    
    const saveBtn = document.querySelector('#codeStatusModal .btn-primary');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('api/patient-data.php?action=code-status&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            code_status: newCodeStatus,
            documentation: documentation
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('codeStatusModal').remove();
            showToast('Code status updated successfully', 'success');
            
            // Update the UI to reflect the new code status
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to update code status', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Update Code Status';
        }
    })
    .catch(err => {
        showToast('Error updating code status: ' + err.message, 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = 'Update Code Status';
    });
}

// Allergy Details
function showAllergyDetails() {
    const allergies = <?php echo json_encode($patient['allergies'] ?? []); ?>;
    let allergiesHtml = '<ul style="margin:0;padding-left:20px;">';
    allergies.forEach(a => {
        allergiesHtml += `<li style="margin-bottom:5px;">${a}</li>`;
    });
    allergiesHtml += '</ul>';
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="modal-dialog" style="width:350px;">
            <div class="modal-content">
                <div class="modal-header" style="background:#c62828;">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Allergies</h5>
                    <button type="button" class="close" onclick="this.closest('.modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    ${allergiesHtml}
                </div>
                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:15px 20px;border-top:1px solid #e0e0e0;">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeInsuranceModal();
        document.getElementById('stickyNotesPanel')?.style.display === 'block' && toggleStickyNotes();
        document.querySelectorAll('.modal').forEach(m => m.remove());
    }
});

// Toast notification function
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    
    const bgColors = {
        'success': '#4CAF50',
        'error': '#f44336',
        'warning': '#ff9800',
        'info': '#2196F3'
    };
    
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: ${bgColors[type] || bgColors.info};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        z-index: 99999;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 14px;
        font-weight: 500;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;
    
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info}" style="font-size: 18px;"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="
            background: none;
            border: none;
            color: white;
            opacity: 0.8;
            cursor: pointer;
            padding: 0;
            margin-left: 10px;
            font-size: 16px;
        ">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Add toast animation styles
if (!document.getElementById('toastStyles')) {
    const style = document.createElement('style');
    style.id = 'toastStyles';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Print Chart Function - Professional Medical PDF Template
function printChart() {
    // Get patient info for print header
    const patientName = <?php echo json_encode($patient_name); ?>;
    const patientAgeSex = <?php echo json_encode($patient_age_sex); ?>;
    const mrn = <?php echo json_encode($mrn); ?>;
    const dob = <?php echo json_encode(date('m/d/Y', strtotime($patient['date_of_birth'] ?? '1955-01-01'))); ?>;
    const currentTab = <?php echo json_encode($current_tab); ?>;
    const allergies = <?php echo json_encode($patient['allergies'] ?? []); ?>;
    const room = <?php echo json_encode($patient['room'] ?? ''); ?>;
    const attendingPhysician = <?php echo json_encode($patient['attending_physician'] ?? ''); ?>;
    const appName = <?php echo json_encode(APP_NAME); ?>;
    
    // Get current content
    const content = document.querySelector('.chart-main').innerHTML;
    
    // Format current date
    const now = new Date();
    const printDate = now.toLocaleString('en-US', { 
        year: 'numeric', month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
    
    // Create print-friendly version with beautiful template
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${patientName} - Chart Print</title>
            <style>
                @page {
                    size: letter;
                    margin: 0.5in;
                }
                
                * {
                    box-sizing: border-box;
                }
                
                body { 
                    font-family: 'Segoe UI', Arial, sans-serif;
                    font-size: 11pt;
                    line-height: 1.4;
                    color: #333;
                    background: white;
                    margin: 0;
                    padding: 0;
                }
                
                /* Professional Header */
                .print-header {
                    background: linear-gradient(135deg, #1a4a5e 0%, #2d7a9c 100%);
                    color: white;
                    padding: 20px 25px;
                    margin-bottom: 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .header-left {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }
                
                .header-logo {
                    width: 50px;
                    height: 50px;
                    background: white;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    color: #1a4a5e;
                }
                
                .header-title h1 {
                    font-size: 20pt;
                    margin: 0;
                    font-weight: 600;
                }
                
                .header-title p {
                    margin: 3px 0 0;
                    font-size: 10pt;
                    opacity: 0.9;
                }
                
                .header-right {
                    text-align: right;
                    font-size: 9pt;
                }
                
                .header-right p {
                    margin: 2px 0;
                }
                
                /* Patient Banner */
                .patient-banner {
                    background: #f8f9fa;
                    border: 2px solid #1a4a5e;
                    border-top: none;
                    padding: 15px 25px;
                    display: grid;
                    grid-template-columns: 1fr 1fr 1fr;
                    gap: 20px;
                }
                
                .banner-section h3 {
                    font-size: 9pt;
                    color: #666;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    margin: 0 0 8px;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }
                
                .banner-field {
                    margin-bottom: 6px;
                }
                
                .banner-field label {
                    display: inline-block;
                    width: 80px;
                    font-size: 9pt;
                    color: #666;
                }
                
                .banner-field span {
                    font-weight: 600;
                    color: #333;
                }
                
                .patient-name-large {
                    font-size: 16pt;
                    font-weight: 700;
                    color: #1a4a5e;
                    margin-bottom: 5px;
                }
                
                /* Allergy Alert Box */
                .allergy-box {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    border-radius: 6px;
                    padding: 10px 15px;
                    margin-top: 10px;
                }
                
                .allergy-box-title {
                    font-size: 9pt;
                    font-weight: 700;
                    color: #856404;
                    text-transform: uppercase;
                    margin-bottom: 5px;
                }
                
                .allergy-list {
                    font-size: 10pt;
                    color: #856404;
                    font-weight: 600;
                }
                
                /* Section Divider */
                .section-divider {
                    background: #1a4a5e;
                    color: white;
                    padding: 8px 25px;
                    font-size: 12pt;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-top: 0;
                }
                
                /* Content Area */
                .chart-content {
                    padding: 20px 25px;
                }
                
                /* Clean up inherited styles from main app */
                .chart-content .card,
                .chart-content .summary-card,
                .chart-content .overview-card,
                .chart-content .insurance-content .overview-card {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    margin-bottom: 15px;
                    page-break-inside: avoid;
                }
                
                .chart-content table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                
                .chart-content th,
                .chart-content td {
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    text-align: left;
                    font-size: 10pt;
                }
                
                .chart-content th {
                    background: #f5f5f5;
                    font-weight: 600;
                }
                
                /* Footer */
                .print-footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: #f8f9fa;
                    border-top: 2px solid #1a4a5e;
                    padding: 10px 25px;
                    font-size: 8pt;
                    color: #666;
                    display: flex;
                    justify-content: space-between;
                }
                
                .confidential-notice {
                    color: #c62828;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                /* Hide interactive elements */
                button, .btn, input, select, textarea,
                .subnav-actions, .card-actions, .btn-icon,
                .verify-option, .add-coverage,
                [onclick], a:not(.static-link) {
                    display: none !important;
                }
                
                /* Print-specific styles */
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .print-header {
                        background: #1a4a5e !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .section-divider {
                        background: #1a4a5e !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .allergy-box {
                        background: #fff3cd !important;
                        border-color: #ffc107 !important;
                    }
                }
            </style>
        </head>
        <body>
            <!-- Professional Header -->
            <div class="print-header">
                <div class="header-left">
                    <div class="header-logo">⚕</div>
                    <div class="header-title">
                        <h1>${appName}</h1>
                        <p>Electronic Health Record</p>
                    </div>
                </div>
                <div class="header-right">
                    <p><strong>Print Date:</strong> ${printDate}</p>
                    <p><strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'System'); ?></p>
                </div>
            </div>
            
            <!-- Patient Banner -->
            <div class="patient-banner">
                <div class="banner-section">
                    <h3>Patient Information</h3>
                    <div class="patient-name-large">${patientName}</div>
                    <div class="banner-field">
                        <label>DOB:</label>
                        <span>${dob}</span>
                    </div>
                    <div class="banner-field">
                        <label>Age/Sex:</label>
                        <span>${patientAgeSex}</span>
                    </div>
                </div>
                <div class="banner-section">
                    <h3>Identifiers</h3>
                    <div class="banner-field">
                        <label>MRN:</label>
                        <span>${mrn}</span>
                    </div>
                    ${room ? `<div class="banner-field"><label>Room:</label><span>${room}</span></div>` : ''}
                    ${attendingPhysician ? `<div class="banner-field"><label>Attending:</label><span>${attendingPhysician}</span></div>` : ''}
                </div>
                <div class="banner-section">
                    <h3>Allergies</h3>
                    ${allergies.length > 0 ? `
                        <div class="allergy-box">
                            <div class="allergy-box-title">⚠ Known Allergies</div>
                            <div class="allergy-list">${allergies.join(', ')}</div>
                        </div>
                    ` : '<p style="color:#666;font-style:italic;">No Known Drug Allergies (NKDA)</p>'}
                </div>
            </div>
            
            <!-- Section Title -->
            <div class="section-divider">
                ${currentTab.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
            </div>
            
            <!-- Chart Content -->
            <div class="chart-content">
                ${content}
            </div>
            
            <!-- Footer -->
            <div class="print-footer">
                <div class="confidential-notice">
                    ⚠ Confidential Medical Information - HIPAA Protected
                </div>
                <div>
                    Patient: ${patientName} | MRN: ${mrn} | Page 1
                </div>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    
    // Wait for styles to load then print
    setTimeout(() => {
        printWindow.focus();
        printWindow.print();
    }, 500);
}

// Refresh Chart Function - Smart refresh that maintains state
function refreshChart() {
    // Show loading indicator
    const main = document.querySelector('.chart-main');
    if (main) {
        const originalContent = main.innerHTML;
        main.innerHTML = `
            <div style="display:flex;align-items:center;justify-content:center;height:200px;flex-direction:column;gap:15px;">
                <div style="width:50px;height:50px;border:4px solid #e0e0e0;border-top-color:#1a4a5e;border-radius:50%;animation:spin 1s linear infinite;"></div>
                <p style="color:#666;font-size:13px;">Refreshing chart data...</p>
            </div>
            <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
        `;
        
        // Simulate refresh delay then reload
        setTimeout(() => {
            location.reload();
        }, 500);
    } else {
        location.reload();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

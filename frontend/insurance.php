<?php
/**
 * Openspace EHR - Insurance Module
 * Insurance verification, eligibility, and reporting
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Insurance - ' . APP_NAME;
$tab = $_GET['tab'] ?? 'verification';

// Demo insurance data
$pending_verifications = [
    ['id' => 1, 'patient' => 'Smith, John', 'mrn' => 'MRN000001', 'appt_date' => '2025-01-11', 'payer' => 'Blue Cross Blue Shield', 'policy' => 'XYZ123456', 'status' => 'pending'],
    ['id' => 2, 'patient' => 'Johnson, Mary', 'mrn' => 'MRN000002', 'appt_date' => '2025-01-11', 'payer' => 'Aetna', 'policy' => 'AET789012', 'status' => 'verified'],
    ['id' => 3, 'patient' => 'Williams, Robert', 'mrn' => 'MRN000003', 'appt_date' => '2025-01-12', 'payer' => 'Medicare', 'policy' => '1EG4-TE5-MK72', 'status' => 'issue'],
];

$payer_mix = [
    ['payer' => 'Medicare', 'patients' => 245, 'percent' => 35, 'color' => '#28a745'],
    ['payer' => 'Blue Cross Blue Shield', 'patients' => 175, 'percent' => 25, 'color' => '#17a2b8'],
    ['payer' => 'Aetna', 'patients' => 105, 'percent' => 15, 'color' => '#6f42c1'],
    ['payer' => 'UnitedHealthcare', 'patients' => 84, 'percent' => 12, 'color' => '#fd7e14'],
    ['payer' => 'Cigna', 'patients' => 56, 'percent' => 8, 'color' => '#20c997'],
    ['payer' => 'Self-Pay', 'patients' => 35, 'percent' => 5, 'color' => '#6c757d'],
];

$coverage_alerts = [
    ['patient' => 'Anderson, Patricia', 'type' => 'Expiring', 'message' => 'Coverage expires in 15 days', 'date' => '2025-01-25'],
    ['patient' => 'Martinez, Carlos', 'type' => 'Terminated', 'message' => 'Policy terminated as of 01/01/2025', 'date' => '2025-01-01'],
    ['patient' => 'Thompson, Sarah', 'type' => 'Changed', 'message' => 'New insurance on file - verify benefits', 'date' => '2025-01-08'],
];

include 'includes/header.php';
?>

<style>
.insurance-page {
    padding: 20px;
    max-width: 1600px;
    margin: 0 auto;
}

.insurance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.insurance-header h1 {
    font-size: 24px;
    color: #1a4a5e;
    margin: 0;
}

.insurance-tabs {
    display: flex;
    gap: 2px;
    background: #e8e8e8;
    border-radius: 6px;
    padding: 3px;
    margin-bottom: 20px;
}

.insurance-tab {
    padding: 10px 20px;
    background: transparent;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
}

.insurance-tab:hover {
    background: rgba(255,255,255,0.5);
}

.insurance-tab.active {
    background: white;
    color: #1a4a5e;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.insurance-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.insurance-stat {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.insurance-stat .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 10px;
}

.insurance-stat .stat-icon.blue { background: #e3f2fd; color: #1976d2; }
.insurance-stat .stat-icon.green { background: #e8f5e9; color: #388e3c; }
.insurance-stat .stat-icon.yellow { background: #fff8e1; color: #f57c00; }
.insurance-stat .stat-icon.red { background: #ffebee; color: #d32f2f; }

.insurance-stat .stat-label {
    font-size: 12px;
    color: #888;
}

.insurance-stat .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a4a5e;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 16px;
}

.card-body {
    padding: 20px;
}

.verification-table {
    width: 100%;
    border-collapse: collapse;
}

.verification-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    border-bottom: 2px solid #e0e0e0;
}

.verification-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.verification-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.verified { background: #d4edda; color: #155724; }
.status-badge.issue { background: #f8d7da; color: #721c24; }

.action-btn {
    padding: 4px 10px;
    border: 1px solid #d0d0d0;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    margin-right: 4px;
}

.action-btn:hover {
    background: #f0f0f0;
}

.action-btn.primary {
    background: #1a4a5e;
    color: white;
    border-color: #1a4a5e;
}

/* Payer Mix Chart */
.payer-mix {
    padding: 15px;
}

.payer-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.payer-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 10px;
}

.payer-info {
    flex: 1;
}

.payer-name {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.payer-count {
    font-size: 11px;
    color: #888;
}

.payer-bar {
    width: 100px;
    height: 8px;
    background: #e8e8e8;
    border-radius: 4px;
    overflow: hidden;
    margin-right: 10px;
}

.payer-bar-fill {
    height: 100%;
    border-radius: 4px;
}

.payer-percent {
    font-size: 13px;
    font-weight: 600;
    color: #1a4a5e;
    min-width: 40px;
    text-align: right;
}

/* Coverage Alerts */
.alert-item {
    padding: 12px 15px;
    border-left: 4px solid;
    background: #f8f9fa;
    margin-bottom: 10px;
    border-radius: 0 4px 4px 0;
}

.alert-item.Expiring { border-left-color: #ffc107; }
.alert-item.Terminated { border-left-color: #dc3545; }
.alert-item.Changed { border-left-color: #17a2b8; }

.alert-patient {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.alert-message {
    font-size: 12px;
    color: #666;
}

.alert-type {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
}

.alert-type.Expiring { background: #fff3cd; color: #856404; }
.alert-type.Terminated { background: #f8d7da; color: #721c24; }
.alert-type.Changed { background: #d1ecf1; color: #0c5460; }

/* Eligibility Details */
.eligibility-details {
    padding: 20px;
}

.eligibility-section {
    margin-bottom: 20px;
}

.eligibility-section h3 {
    font-size: 14px;
    color: #1a4a5e;
    margin: 0 0 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    border-bottom: 1px solid #f0f0f0;
}

.detail-label {
    color: #666;
}

.detail-value {
    font-weight: 600;
    color: #333;
}

.coverage-badge {
    background: #d4edda;
    color: #155724;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
}

/* Reports Section */
.report-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.report-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.report-card:hover {
    border-color: #1a4a5e;
    background: white;
}

.report-card i {
    font-size: 32px;
    color: #1a4a5e;
    margin-bottom: 10px;
}

.report-card h4 {
    margin: 0 0 5px;
    font-size: 14px;
    color: #333;
}

.report-card p {
    margin: 0;
    font-size: 12px;
    color: #888;
}
</style>

<div class="dashboard-content">
    <div class="insurance-page">
        <div class="insurance-header">
            <h1><i class="fas fa-id-card"></i> Insurance Management</h1>
            <button class="btn btn-primary" onclick="verifyEligibility()">
                <i class="fas fa-search"></i> Verify Eligibility
            </button>
        </div>
        
        <div class="insurance-tabs">
            <button class="insurance-tab <?php echo $tab === 'verification' ? 'active' : ''; ?>" onclick="switchTab('verification')">
                <i class="fas fa-clipboard-check"></i> Verification Queue
            </button>
            <button class="insurance-tab <?php echo $tab === 'eligibility' ? 'active' : ''; ?>" onclick="switchTab('eligibility')">
                <i class="fas fa-user-check"></i> Eligibility Check
            </button>
            <button class="insurance-tab <?php echo $tab === 'payers' ? 'active' : ''; ?>" onclick="switchTab('payers')">
                <i class="fas fa-building"></i> Payers
            </button>
            <button class="insurance-tab <?php echo $tab === 'reports' ? 'active' : ''; ?>" onclick="switchTab('reports')">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
        </div>
        
        <div class="insurance-stats">
            <div class="insurance-stat">
                <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-label">Today's Verifications</div>
                <div class="stat-value">24</div>
            </div>
            <div class="insurance-stat">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-label">Verified</div>
                <div class="stat-value">18</div>
            </div>
            <div class="insurance-stat">
                <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
                <div class="stat-label">Pending</div>
                <div class="stat-value">4</div>
            </div>
            <div class="insurance-stat">
                <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-label">Issues</div>
                <div class="stat-value">2</div>
            </div>
        </div>
        
        <!-- Verification Tab -->
        <div id="verificationTab" class="tab-content <?php echo $tab === 'verification' ? '' : 'hidden'; ?>">
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Verification Queue</h2>
                        <button style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                    <table class="verification-table">
                        <thead>
                            <tr>
                                <th>Appt Date</th>
                                <th>Patient</th>
                                <th>Insurance</th>
                                <th>Policy #</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_verifications as $v): ?>
                            <tr>
                                <td><?php echo $v['appt_date']; ?></td>
                                <td>
                                    <strong><?php echo $v['patient']; ?></strong><br>
                                    <span style="font-size: 11px; color: #888;"><?php echo $v['mrn']; ?></span>
                                </td>
                                <td><?php echo $v['payer']; ?></td>
                                <td><code><?php echo $v['policy']; ?></code></td>
                                <td><span class="status-badge <?php echo $v['status']; ?>"><?php echo ucfirst($v['status']); ?></span></td>
                                <td>
                                    <button class="action-btn" onclick="viewEligibility(<?php echo $v['id']; ?>)"><i class="fas fa-eye"></i></button>
                                    <button class="action-btn primary" onclick="checkEligibility(<?php echo $v['id']; ?>)"><i class="fas fa-check"></i> Verify</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div>
                    <div class="card" style="margin-bottom: 20px;">
                        <div class="card-header">
                            <h2><i class="fas fa-chart-pie"></i> Payer Mix</h2>
                        </div>
                        <div class="payer-mix">
                            <?php foreach ($payer_mix as $payer): ?>
                            <div class="payer-item">
                                <div class="payer-color" style="background: <?php echo $payer['color']; ?>"></div>
                                <div class="payer-info">
                                    <div class="payer-name"><?php echo $payer['payer']; ?></div>
                                    <div class="payer-count"><?php echo $payer['patients']; ?> patients</div>
                                </div>
                                <div class="payer-bar">
                                    <div class="payer-bar-fill" style="width: <?php echo $payer['percent']; ?>%; background: <?php echo $payer['color']; ?>"></div>
                                </div>
                                <div class="payer-percent"><?php echo $payer['percent']; ?>%</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-bell"></i> Coverage Alerts</h2>
                        </div>
                        <div class="card-body" style="padding: 15px;">
                            <?php foreach ($coverage_alerts as $alert): ?>
                            <div class="alert-item <?php echo $alert['type']; ?>">
                                <div class="alert-patient">
                                    <?php echo $alert['patient']; ?>
                                    <span class="alert-type <?php echo $alert['type']; ?>"><?php echo $alert['type']; ?></span>
                                </div>
                                <div class="alert-message"><?php echo $alert['message']; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Eligibility Check Tab -->
        <div id="eligibilityTab" class="tab-content <?php echo $tab === 'eligibility' ? '' : 'hidden'; ?>">
            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-search"></i> Real-Time Eligibility Check</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label>Patient</label>
                                <input type="text" placeholder="Search patient by name or MRN..." style="width: 100%; padding: 10px; border: 2px solid #d0d8e0; border-radius: 4px;">
                            </div>
                            <div class="form-group">
                                <label>Date of Service</label>
                                <input type="date" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 2px solid #d0d8e0; border-radius: 4px;">
                            </div>
                        </div>
                        <button class="btn btn-primary" style="width: 100%;"><i class="fas fa-bolt"></i> Check Eligibility</button>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-file-alt"></i> Sample Eligibility Response</h2>
                    </div>
                    <div class="eligibility-details">
                        <div class="eligibility-section">
                            <h3><i class="fas fa-user"></i> Patient Information</h3>
                            <div class="detail-row">
                                <span class="detail-label">Name</span>
                                <span class="detail-value">John Smith</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Member ID</span>
                                <span class="detail-value">XYZ123456</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Group Number</span>
                                <span class="detail-value">GRP98765</span>
                            </div>
                        </div>
                        
                        <div class="eligibility-section">
                            <h3><i class="fas fa-shield-alt"></i> Coverage Status</h3>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><span class="coverage-badge">Active</span></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Effective Date</span>
                                <span class="detail-value">01/01/2025</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Term Date</span>
                                <span class="detail-value">12/31/2025</span>
                            </div>
                        </div>
                        
                        <div class="eligibility-section">
                            <h3><i class="fas fa-dollar-sign"></i> Benefits</h3>
                            <div class="detail-row">
                                <span class="detail-label">Copay - Office Visit</span>
                                <span class="detail-value">$25.00</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Deductible</span>
                                <span class="detail-value">$500 / $1,500</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Coinsurance</span>
                                <span class="detail-value">80% / 20%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reports Tab -->
        <div id="reportsTab" class="tab-content <?php echo $tab === 'reports' ? '' : 'hidden'; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-chart-bar"></i> Insurance Reports</h2>
                </div>
                <div class="card-body">
                    <div class="report-cards">
                        <div class="report-card" onclick="runReport('payer-mix')">
                            <i class="fas fa-chart-pie"></i>
                            <h4>Payer Mix Analysis</h4>
                            <p>Breakdown of patients by insurance carrier</p>
                        </div>
                        <div class="report-card" onclick="runReport('denial')">
                            <i class="fas fa-times-circle"></i>
                            <h4>Denial Report</h4>
                            <p>Claims denied by payer and reason</p>
                        </div>
                        <div class="report-card" onclick="runReport('aging')">
                            <i class="fas fa-calendar-alt"></i>
                            <h4>A/R Aging by Payer</h4>
                            <p>Outstanding balances by insurance</p>
                        </div>
                        <div class="report-card" onclick="runReport('reimbursement')">
                            <i class="fas fa-money-check-alt"></i>
                            <h4>Reimbursement Analysis</h4>
                            <p>Average reimbursement by payer</p>
                        </div>
                        <div class="report-card" onclick="runReport('eligibility')">
                            <i class="fas fa-clipboard-check"></i>
                            <h4>Eligibility Issues</h4>
                            <p>Verification failures and issues</p>
                        </div>
                        <div class="report-card" onclick="runReport('coverage')">
                            <i class="fas fa-file-medical-alt"></i>
                            <h4>Coverage Changes</h4>
                            <p>Insurance changes and terminations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
    document.querySelectorAll('.insurance-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(tab + 'Tab')?.classList.remove('hidden');
    event.target.classList.add('active');
}

function verifyEligibility() {
    alert('Opening eligibility verification... (Demo)');
}

function viewEligibility(id) {
    alert('Viewing eligibility details for ID: ' + id + ' (Demo)');
}

function checkEligibility(id) {
    alert('Checking real-time eligibility for ID: ' + id + ' (Demo)');
}

function runReport(type) {
    alert('Running ' + type + ' report... (Demo)');
}
</script>

<style>
.hidden { display: none !important; }
.tab-content { display: block; }
</style>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * Openspace EHR - Billing Module
 * Charge capture, claims management, and billing workflows
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Billing - ' . APP_NAME;
$tab = $_GET['tab'] ?? 'charges';

// Demo billing data
$pending_charges = [
    ['id' => 1, 'patient' => 'Smith, John', 'mrn' => 'MRN000001', 'date' => '2025-01-10', 'provider' => 'Dr. Wilson', 'cpt' => '99214', 'description' => 'Office Visit - Established', 'icd10' => ['E11.9', 'I10'], 'amount' => 145.00, 'status' => 'pending'],
    ['id' => 2, 'patient' => 'Johnson, Mary', 'mrn' => 'MRN000002', 'date' => '2025-01-10', 'provider' => 'Dr. Smith', 'cpt' => '99213', 'description' => 'Office Visit - Established', 'icd10' => ['J06.9'], 'amount' => 95.00, 'status' => 'pending'],
    ['id' => 3, 'patient' => 'Williams, Robert', 'mrn' => 'MRN000003', 'date' => '2025-01-09', 'provider' => 'Dr. Wilson', 'cpt' => '99215', 'description' => 'Office Visit - Complex', 'icd10' => ['I48.91', 'N18.3'], 'amount' => 210.00, 'status' => 'review'],
];

$claims = [
    ['id' => 'CLM-2025-0001', 'patient' => 'Davis, Linda', 'payer' => 'Blue Cross Blue Shield', 'dos' => '2025-01-05', 'submitted' => '2025-01-06', 'amount' => 325.00, 'status' => 'submitted'],
    ['id' => 'CLM-2025-0002', 'patient' => 'Brown, James', 'payer' => 'Aetna', 'dos' => '2025-01-03', 'submitted' => '2025-01-04', 'amount' => 185.00, 'status' => 'paid', 'paid' => 148.00],
    ['id' => 'CLM-2025-0003', 'patient' => 'Garcia, Maria', 'payer' => 'Medicare', 'dos' => '2025-01-02', 'submitted' => '2025-01-03', 'amount' => 420.00, 'status' => 'denied', 'reason' => 'Missing modifier'],
];

$cpt_codes = [
    ['code' => '99211', 'description' => 'Office Visit - Minimal', 'fee' => 45.00],
    ['code' => '99212', 'description' => 'Office Visit - Low', 'fee' => 75.00],
    ['code' => '99213', 'description' => 'Office Visit - Established Low-Moderate', 'fee' => 95.00],
    ['code' => '99214', 'description' => 'Office Visit - Established Moderate', 'fee' => 145.00],
    ['code' => '99215', 'description' => 'Office Visit - Established High', 'fee' => 210.00],
    ['code' => '99203', 'description' => 'New Patient - Low', 'fee' => 125.00],
    ['code' => '99204', 'description' => 'New Patient - Moderate', 'fee' => 195.00],
    ['code' => '99205', 'description' => 'New Patient - High', 'fee' => 285.00],
    ['code' => '36415', 'description' => 'Venipuncture', 'fee' => 15.00],
    ['code' => '85025', 'description' => 'CBC with Differential', 'fee' => 12.00],
    ['code' => '80053', 'description' => 'Comprehensive Metabolic Panel', 'fee' => 18.00],
];

include 'includes/header.php';
?>

<style>
.billing-page {
    padding: 20px;
    max-width: 1600px;
    margin: 0 auto;
}

.billing-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.billing-header h1 {
    font-size: 24px;
    color: #1a4a5e;
    margin: 0;
}

.billing-tabs {
    display: flex;
    gap: 2px;
    background: #e8e8e8;
    border-radius: 6px;
    padding: 3px;
    margin-bottom: 20px;
}

.billing-tab {
    padding: 10px 20px;
    background: transparent;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
}

.billing-tab:hover {
    background: rgba(255,255,255,0.5);
}

.billing-tab.active {
    background: white;
    color: #1a4a5e;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.billing-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.billing-stat {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.billing-stat .stat-label {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
}

.billing-stat .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a4a5e;
    margin-top: 5px;
}

.billing-stat .stat-change {
    font-size: 12px;
    margin-top: 5px;
}

.billing-stat .stat-change.positive { color: #28a745; }
.billing-stat .stat-change.negative { color: #dc3545; }

.billing-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 20px;
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

.card-header .actions {
    display: flex;
    gap: 8px;
}

.card-header button {
    padding: 6px 12px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.card-header button:hover {
    background: rgba(255,255,255,0.3);
}

.billing-table {
    width: 100%;
    border-collapse: collapse;
}

.billing-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    border-bottom: 2px solid #e0e0e0;
}

.billing-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.billing-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.review { background: #cce5ff; color: #004085; }
.status-badge.submitted { background: #d4edda; color: #155724; }
.status-badge.paid { background: #28a745; color: white; }
.status-badge.denied { background: #dc3545; color: white; }

.icd-codes {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.icd-code {
    background: #e8eef2;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-family: monospace;
}

.amount {
    font-weight: 600;
    font-family: monospace;
}

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

/* Charge capture modal */
.charge-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
}

.form-section h4 {
    margin: 0 0 15px;
    font-size: 14px;
    color: #1a4a5e;
}

.cpt-search {
    position: relative;
}

.cpt-search input {
    width: 100%;
    padding: 10px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
}

.cpt-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    z-index: 100;
}

.cpt-result {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.cpt-result:hover {
    background: #f0f8ff;
}

.cpt-result .code {
    font-weight: 600;
    color: #1a4a5e;
}

.cpt-result .description {
    font-size: 12px;
    color: #666;
}

.cpt-result .fee {
    font-size: 12px;
    color: #28a745;
}

.selected-codes {
    margin-top: 10px;
}

.selected-code {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px;
    background: white;
    border: 1px solid #d0d8e0;
    border-radius: 4px;
    margin-bottom: 5px;
}

.remove-code {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 14px;
}
</style>

<div class="dashboard-content">
    <div class="billing-page">
        <div class="billing-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> Billing & Revenue Cycle</h1>
            <button class="btn btn-primary" onclick="showChargeCapture()">
                <i class="fas fa-plus"></i> New Charge
            </button>
        </div>
        
        <div class="billing-tabs">
            <button class="billing-tab <?php echo $tab === 'charges' ? 'active' : ''; ?>" onclick="switchTab('charges')">
                <i class="fas fa-clipboard-list"></i> Charge Capture
            </button>
            <button class="billing-tab <?php echo $tab === 'claims' ? 'active' : ''; ?>" onclick="switchTab('claims')">
                <i class="fas fa-file-medical"></i> Claims
            </button>
            <button class="billing-tab <?php echo $tab === 'payments' ? 'active' : ''; ?>" onclick="switchTab('payments')">
                <i class="fas fa-money-check-alt"></i> Payments
            </button>
            <button class="billing-tab <?php echo $tab === 'reports' ? 'active' : ''; ?>" onclick="switchTab('reports')">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
            <button class="billing-tab <?php echo $tab === 'fee-schedule' ? 'active' : ''; ?>" onclick="switchTab('fee-schedule')">
                <i class="fas fa-list"></i> Fee Schedule
            </button>
        </div>
        
        <div class="billing-stats">
            <div class="billing-stat">
                <div class="stat-label">Charges Today</div>
                <div class="stat-value">$2,450</div>
                <div class="stat-change positive">+12% vs yesterday</div>
            </div>
            <div class="billing-stat">
                <div class="stat-label">Pending Claims</div>
                <div class="stat-value">47</div>
                <div class="stat-change">$18,250 total</div>
            </div>
            <div class="billing-stat">
                <div class="stat-label">Days in A/R</div>
                <div class="stat-value">32</div>
                <div class="stat-change negative">+2 days this month</div>
            </div>
            <div class="billing-stat">
                <div class="stat-label">Collection Rate</div>
                <div class="stat-value">94.2%</div>
                <div class="stat-change positive">+1.5% YTD</div>
            </div>
        </div>
        
        <!-- Charge Capture Tab -->
        <div id="chargesTab" class="tab-content <?php echo $tab === 'charges' ? '' : 'hidden'; ?>">
            <div class="billing-card">
                <div class="card-header">
                    <h2><i class="fas fa-clipboard-list"></i> Pending Charges</h2>
                    <div class="actions">
                        <button onclick="submitSelected()"><i class="fas fa-paper-plane"></i> Submit Selected</button>
                        <button onclick="exportCharges()"><i class="fas fa-download"></i> Export</button>
                    </div>
                </div>
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="selectAll(this)"></th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Provider</th>
                            <th>CPT</th>
                            <th>Description</th>
                            <th>Dx Codes</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_charges as $charge): ?>
                        <tr>
                            <td><input type="checkbox" class="charge-select" value="<?php echo $charge['id']; ?>"></td>
                            <td><?php echo $charge['date']; ?></td>
                            <td>
                                <strong><?php echo $charge['patient']; ?></strong><br>
                                <span style="font-size:11px;color:#888;"><?php echo $charge['mrn']; ?></span>
                            </td>
                            <td><?php echo $charge['provider']; ?></td>
                            <td><code><?php echo $charge['cpt']; ?></code></td>
                            <td><?php echo $charge['description']; ?></td>
                            <td>
                                <div class="icd-codes">
                                    <?php foreach ($charge['icd10'] as $code): ?>
                                    <span class="icd-code"><?php echo $code; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="amount">$<?php echo number_format($charge['amount'], 2); ?></td>
                            <td><span class="status-badge <?php echo $charge['status']; ?>"><?php echo ucfirst($charge['status']); ?></span></td>
                            <td>
                                <button class="action-btn" onclick="editCharge(<?php echo $charge['id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="action-btn primary" onclick="submitCharge(<?php echo $charge['id']; ?>)"><i class="fas fa-paper-plane"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Claims Tab -->
        <div id="claimsTab" class="tab-content <?php echo $tab === 'claims' ? '' : 'hidden'; ?>">
            <div class="billing-card">
                <div class="card-header">
                    <h2><i class="fas fa-file-medical"></i> Claims Management</h2>
                    <div class="actions">
                        <button><i class="fas fa-filter"></i> Filter</button>
                        <button><i class="fas fa-download"></i> Export</button>
                    </div>
                </div>
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Claim ID</th>
                            <th>Patient</th>
                            <th>Payer</th>
                            <th>DOS</th>
                            <th>Submitted</th>
                            <th>Billed</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td><code><?php echo $claim['id']; ?></code></td>
                            <td><?php echo $claim['patient']; ?></td>
                            <td><?php echo $claim['payer']; ?></td>
                            <td><?php echo $claim['dos']; ?></td>
                            <td><?php echo $claim['submitted']; ?></td>
                            <td class="amount">$<?php echo number_format($claim['amount'], 2); ?></td>
                            <td class="amount"><?php echo isset($claim['paid']) ? '$' . number_format($claim['paid'], 2) : '-'; ?></td>
                            <td>
                                <span class="status-badge <?php echo $claim['status']; ?>"><?php echo ucfirst($claim['status']); ?></span>
                                <?php if (isset($claim['reason'])): ?>
                                <br><small style="color:#dc3545;"><?php echo $claim['reason']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="action-btn"><i class="fas fa-eye"></i></button>
                                <?php if ($claim['status'] === 'denied'): ?>
                                <button class="action-btn primary"><i class="fas fa-redo"></i> Resubmit</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Fee Schedule Tab -->
        <div id="fee-scheduleTab" class="tab-content <?php echo $tab === 'fee-schedule' ? '' : 'hidden'; ?>">
            <div class="billing-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> Fee Schedule</h2>
                    <div class="actions">
                        <button><i class="fas fa-plus"></i> Add Code</button>
                        <button><i class="fas fa-upload"></i> Import</button>
                    </div>
                </div>
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>CPT Code</th>
                            <th>Description</th>
                            <th>Fee</th>
                            <th>Medicare Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cpt_codes as $cpt): ?>
                        <tr>
                            <td><code><?php echo $cpt['code']; ?></code></td>
                            <td><?php echo $cpt['description']; ?></td>
                            <td class="amount">$<?php echo number_format($cpt['fee'], 2); ?></td>
                            <td class="amount">$<?php echo number_format($cpt['fee'] * 0.8, 2); ?></td>
                            <td>
                                <button class="action-btn"><i class="fas fa-edit"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Charge Capture Modal -->
<div class="modal" id="chargeCaptureModal">
    <div class="modal-content" style="width: 800px; max-width: 90%;">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Capture Charge</h3>
            <button class="modal-close" onclick="closeModal('chargeCaptureModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="charge-form">
                <div class="form-section">
                    <h4>Patient & Visit</h4>
                    <div class="form-group">
                        <label>Patient</label>
                        <input type="text" placeholder="Search patient..." id="chargePatient">
                    </div>
                    <div class="form-group">
                        <label>Date of Service</label>
                        <input type="date" id="chargeDOS" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Provider</label>
                        <select id="chargeProvider">
                            <option>Dr. Wilson</option>
                            <option>Dr. Smith</option>
                            <option>Dr. Johnson</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Place of Service</label>
                        <select id="chargePOS">
                            <option value="11">11 - Office</option>
                            <option value="21">21 - Inpatient Hospital</option>
                            <option value="22">22 - Outpatient Hospital</option>
                            <option value="23">23 - Emergency Room</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>Procedure Codes (CPT)</h4>
                    <div class="cpt-search">
                        <input type="text" placeholder="Search CPT code or description..." id="cptSearch" oninput="searchCPT(this.value)">
                        <div class="cpt-results" id="cptResults"></div>
                    </div>
                    <div class="selected-codes" id="selectedCPT">
                        <!-- Selected codes appear here -->
                    </div>
                    
                    <h4 style="margin-top: 20px;">Diagnosis Codes (ICD-10)</h4>
                    <div class="cpt-search">
                        <input type="text" placeholder="Search ICD-10 code or description..." id="icdSearch">
                    </div>
                    <div class="selected-codes" id="selectedICD">
                        <div class="selected-code">
                            <span><strong>E11.9</strong> - Type 2 diabetes mellitus without complications</span>
                            <button class="remove-code">&times;</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <span style="flex:1;font-weight:600;">Total: <span id="chargeTotal">$0.00</span></span>
            <button class="btn-cancel" onclick="closeModal('chargeCaptureModal')">Cancel</button>
            <button class="btn-save" onclick="saveCharge()">Save Charge</button>
        </div>
    </div>
</div>

<script>
const cptCodes = <?php echo json_encode($cpt_codes); ?>;
let selectedCPT = [];
let chargeTotal = 0;

function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
    document.querySelectorAll('.billing-tab').forEach(t => t.classList.remove('active'));
    document.getElementById(tab + 'Tab')?.classList.remove('hidden');
    event.target.classList.add('active');
}

function showChargeCapture() {
    document.getElementById('chargeCaptureModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function searchCPT(query) {
    const results = document.getElementById('cptResults');
    if (query.length < 2) {
        results.style.display = 'none';
        return;
    }
    
    const filtered = cptCodes.filter(c => 
        c.code.includes(query) || c.description.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filtered.length === 0) {
        results.style.display = 'none';
        return;
    }
    
    results.innerHTML = filtered.map(c => `
        <div class="cpt-result" onclick="addCPT('${c.code}', '${c.description}', ${c.fee})">
            <div class="code">${c.code}</div>
            <div class="description">${c.description}</div>
            <div class="fee">$${c.fee.toFixed(2)}</div>
        </div>
    `).join('');
    results.style.display = 'block';
}

function addCPT(code, description, fee) {
    if (selectedCPT.find(c => c.code === code)) return;
    
    selectedCPT.push({ code, description, fee });
    chargeTotal += fee;
    
    document.getElementById('selectedCPT').innerHTML = selectedCPT.map(c => `
        <div class="selected-code">
            <span><strong>${c.code}</strong> - ${c.description}</span>
            <span>$${c.fee.toFixed(2)}</span>
            <button class="remove-code" onclick="removeCPT('${c.code}')">&times;</button>
        </div>
    `).join('');
    
    document.getElementById('chargeTotal').textContent = '$' + chargeTotal.toFixed(2);
    document.getElementById('cptResults').style.display = 'none';
    document.getElementById('cptSearch').value = '';
}

function removeCPT(code) {
    const idx = selectedCPT.findIndex(c => c.code === code);
    if (idx > -1) {
        chargeTotal -= selectedCPT[idx].fee;
        selectedCPT.splice(idx, 1);
    }
    addCPT; // Refresh display
    document.getElementById('selectedCPT').innerHTML = selectedCPT.map(c => `
        <div class="selected-code">
            <span><strong>${c.code}</strong> - ${c.description}</span>
            <span>$${c.fee.toFixed(2)}</span>
            <button class="remove-code" onclick="removeCPT('${c.code}')">&times;</button>
        </div>
    `).join('');
    document.getElementById('chargeTotal').textContent = '$' + chargeTotal.toFixed(2);
}

function saveCharge() {
    alert('Charge saved! (Demo)');
    closeModal('chargeCaptureModal');
}

function selectAll(checkbox) {
    document.querySelectorAll('.charge-select').forEach(c => c.checked = checkbox.checked);
}

function submitSelected() {
    const selected = document.querySelectorAll('.charge-select:checked');
    if (selected.length === 0) {
        alert('Please select charges to submit');
        return;
    }
    alert(`Submitting ${selected.length} charge(s)... (Demo)`);
}

function editCharge(id) {
    alert('Edit charge #' + id + ' (Demo)');
}

function submitCharge(id) {
    alert('Submitting charge #' + id + ' (Demo)');
}

function exportCharges() {
    alert('Exporting charges to CSV... (Demo)');
}

// Hide CPT results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.cpt-search')) {
        document.getElementById('cptResults').style.display = 'none';
    }
});
</script>

<style>
.hidden { display: none !important; }
.tab-content { display: block; }
</style>

<?php include 'includes/footer.php'; ?>

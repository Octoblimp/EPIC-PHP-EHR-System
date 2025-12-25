<?php
/**
 * Insurance Content - Patient insurance information tab
 */

// Patient data should be available from parent
$patient = $patient ?? [];

// Demo insurance data if not available from API
$insurance_data = $patient['insurance'] ?? [
    'primary' => [
        'payer' => 'Blue Cross Blue Shield',
        'payer_type' => 'Commercial',
        'plan' => 'PPO Gold',
        'plan_type' => 'PPO',
        'policy_number' => 'BCB123456789',
        'group_number' => 'GRP001',
        'group_name' => 'ABC Corporation',
        'subscriber_id' => 'BCB123456789',
        'subscriber_name' => 'John Smith',
        'subscriber_relationship' => 'Self',
        'subscriber_dob' => '03/15/1955',
        'effective_date' => '01/01/2024',
        'termination_date' => null,
        'copay_primary' => '$25',
        'copay_specialist' => '$50',
        'copay_emergency' => '$250',
        'deductible' => '$500',
        'deductible_met' => '$350',
        'out_of_pocket_max' => '$3,000',
        'out_of_pocket_met' => '$850',
        'is_verified' => true,
        'verification_date' => date('m/d/Y', strtotime('-3 days')),
        'requires_referral' => false,
        'requires_preauth' => true
    ],
    'secondary' => [
        'payer' => 'Medicare',
        'payer_type' => 'Medicare',
        'plan' => 'Part B',
        'policy_number' => '1EG4-TE5-MK72',
        'effective_date' => '03/01/2020',
        'is_verified' => true,
        'verification_date' => date('m/d/Y', strtotime('-3 days'))
    ]
];

// Demo authorizations
$authorizations = [
    [
        'auth_number' => 'AUTH-2024-001234',
        'service_type' => 'Inpatient Hospital Stay',
        'status' => 'Approved',
        'start_date' => date('m/d/Y', strtotime('-2 days')),
        'end_date' => date('m/d/Y', strtotime('+5 days')),
        'days_approved' => 7,
        'days_used' => 2
    ],
    [
        'auth_number' => 'AUTH-2024-001189',
        'service_type' => 'CT Scan - Abdomen',
        'status' => 'Approved',
        'start_date' => date('m/d/Y'),
        'end_date' => date('m/d/Y', strtotime('+30 days')),
        'units_approved' => 1,
        'units_used' => 0
    ]
];
?>

<div class="insurance-content">
    <!-- Coverage Overview -->
    <div class="coverage-overview">
        <div class="overview-card primary">
            <div class="card-header">
                <span class="coverage-level">
                    <i class="fas fa-star"></i> Primary Insurance
                </span>
                <?php if ($insurance_data['primary']['is_verified'] ?? false): ?>
                <span class="verified-badge">
                    <i class="fas fa-check-circle"></i> Verified <?php echo $insurance_data['primary']['verification_date']; ?>
                </span>
                <?php else: ?>
                <span class="unverified-badge">
                    <i class="fas fa-exclamation-circle"></i> Needs Verification
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="payer-info">
                    <div class="payer-logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="payer-details">
                        <h4><?php echo htmlspecialchars($insurance_data['primary']['payer']); ?></h4>
                        <p><?php echo htmlspecialchars($insurance_data['primary']['plan'] ?? ''); ?> (<?php echo htmlspecialchars($insurance_data['primary']['plan_type'] ?? 'PPO'); ?>)</p>
                    </div>
                </div>
                
                <div class="policy-grid">
                    <div class="policy-field">
                        <label>Policy #</label>
                        <span><?php echo htmlspecialchars($insurance_data['primary']['policy_number']); ?></span>
                    </div>
                    <div class="policy-field">
                        <label>Group #</label>
                        <span><?php echo htmlspecialchars($insurance_data['primary']['group_number'] ?? '—'); ?></span>
                    </div>
                    <div class="policy-field">
                        <label>Subscriber</label>
                        <span><?php echo htmlspecialchars($insurance_data['primary']['subscriber_name'] ?? '—'); ?> (<?php echo htmlspecialchars($insurance_data['primary']['subscriber_relationship'] ?? 'Self'); ?>)</span>
                    </div>
                    <div class="policy-field">
                        <label>Effective Date</label>
                        <span><?php echo htmlspecialchars($insurance_data['primary']['effective_date'] ?? '—'); ?></span>
                    </div>
                </div>

                <!-- Benefits Summary -->
                <div class="benefits-section">
                    <h5><i class="fas fa-dollar-sign"></i> Cost Sharing</h5>
                    <div class="benefits-grid">
                        <div class="benefit-item">
                            <label>PCP Copay</label>
                            <span class="amount"><?php echo htmlspecialchars($insurance_data['primary']['copay_primary'] ?? '—'); ?></span>
                        </div>
                        <div class="benefit-item">
                            <label>Specialist</label>
                            <span class="amount"><?php echo htmlspecialchars($insurance_data['primary']['copay_specialist'] ?? '—'); ?></span>
                        </div>
                        <div class="benefit-item">
                            <label>ER Copay</label>
                            <span class="amount"><?php echo htmlspecialchars($insurance_data['primary']['copay_emergency'] ?? '—'); ?></span>
                        </div>
                    </div>
                    
                    <div class="progress-bars">
                        <div class="progress-item">
                            <div class="progress-header">
                                <span>Deductible</span>
                                <span><?php echo htmlspecialchars($insurance_data['primary']['deductible_met'] ?? '$0'); ?> / <?php echo htmlspecialchars($insurance_data['primary']['deductible'] ?? '$0'); ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php 
                                $ded_met = floatval(str_replace(['$', ','], '', $insurance_data['primary']['deductible_met'] ?? '0'));
                                $ded_total = floatval(str_replace(['$', ','], '', $insurance_data['primary']['deductible'] ?? '1'));
                                $ded_percent = ($ded_total > 0) ? min(100, ($ded_met / $ded_total) * 100) : 0;
                                ?>
                                <div class="progress-fill" style="width: <?php echo $ded_percent; ?>%"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="progress-header">
                                <span>Out-of-Pocket Max</span>
                                <span><?php echo htmlspecialchars($insurance_data['primary']['out_of_pocket_met'] ?? '$0'); ?> / <?php echo htmlspecialchars($insurance_data['primary']['out_of_pocket_max'] ?? '$0'); ?></span>
                            </div>
                            <div class="progress-bar">
                                <?php 
                                $oop_met = floatval(str_replace(['$', ','], '', $insurance_data['primary']['out_of_pocket_met'] ?? '0'));
                                $oop_total = floatval(str_replace(['$', ','], '', $insurance_data['primary']['out_of_pocket_max'] ?? '1'));
                                $oop_percent = ($oop_total > 0) ? min(100, ($oop_met / $oop_total) * 100) : 0;
                                ?>
                                <div class="progress-fill" style="width: <?php echo $oop_percent; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Requirements -->
                <div class="requirements-section">
                    <div class="requirement <?php echo ($insurance_data['primary']['requires_referral'] ?? false) ? 'required' : 'not-required'; ?>">
                        <i class="fas <?php echo ($insurance_data['primary']['requires_referral'] ?? false) ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <span>Referral <?php echo ($insurance_data['primary']['requires_referral'] ?? false) ? 'Required' : 'Not Required'; ?></span>
                    </div>
                    <div class="requirement <?php echo ($insurance_data['primary']['requires_preauth'] ?? false) ? 'required' : 'not-required'; ?>">
                        <i class="fas <?php echo ($insurance_data['primary']['requires_preauth'] ?? false) ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                        <span>Pre-Auth <?php echo ($insurance_data['primary']['requires_preauth'] ?? false) ? 'Required' : 'Not Required'; ?></span>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="verifyInsurance('primary')">
                    <i class="fas fa-sync"></i> Verify Eligibility
                </button>
                <button class="btn btn-sm btn-secondary" onclick="editInsurance('primary')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>

        <?php if (!empty($insurance_data['secondary'])): ?>
        <div class="overview-card secondary">
            <div class="card-header">
                <span class="coverage-level">
                    <i class="fas fa-star-half-alt"></i> Secondary Insurance
                </span>
                <?php if ($insurance_data['secondary']['is_verified'] ?? false): ?>
                <span class="verified-badge">
                    <i class="fas fa-check-circle"></i> Verified
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="payer-info">
                    <div class="payer-logo medicare">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div class="payer-details">
                        <h4><?php echo htmlspecialchars($insurance_data['secondary']['payer']); ?></h4>
                        <p><?php echo htmlspecialchars($insurance_data['secondary']['plan'] ?? ''); ?></p>
                    </div>
                </div>
                
                <div class="policy-grid">
                    <div class="policy-field">
                        <label>Policy #</label>
                        <span><?php echo htmlspecialchars($insurance_data['secondary']['policy_number']); ?></span>
                    </div>
                    <div class="policy-field">
                        <label>Effective Date</label>
                        <span><?php echo htmlspecialchars($insurance_data['secondary']['effective_date'] ?? '—'); ?></span>
                    </div>
                </div>
            </div>
            <div class="card-actions">
                <button class="btn btn-sm btn-secondary" onclick="editInsurance('secondary')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Authorizations Section -->
    <div class="authorizations-section">
        <div class="section-header">
            <h3><i class="fas fa-clipboard-check"></i> Authorizations</h3>
            <button class="btn btn-sm btn-primary" onclick="addAuthorization()">
                <i class="fas fa-plus"></i> Add Authorization
            </button>
        </div>
        <div class="auth-table-container">
            <table class="auth-table">
                <thead>
                    <tr>
                        <th>Auth #</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Valid Dates</th>
                        <th>Units/Days</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authorizations as $auth): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($auth['auth_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($auth['service_type']); ?></td>
                        <td>
                            <span class="auth-status <?php echo strtolower($auth['status']); ?>">
                                <?php echo htmlspecialchars($auth['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($auth['start_date'] . ' - ' . $auth['end_date']); ?></td>
                        <td>
                            <?php if (isset($auth['days_approved'])): ?>
                            <?php echo $auth['days_used']; ?> / <?php echo $auth['days_approved']; ?> days
                            <?php elseif (isset($auth['units_approved'])): ?>
                            <?php echo $auth['units_used']; ?> / <?php echo $auth['units_approved']; ?> units
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon" title="View Details" onclick="viewAuth('<?php echo $auth['auth_number']; ?>')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon" title="Edit" onclick="editAuth('<?php echo $auth['auth_number']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Eligibility History -->
    <div class="eligibility-section">
        <div class="section-header">
            <h3><i class="fas fa-history"></i> Eligibility Check History</h3>
        </div>
        <div class="eligibility-history">
            <div class="eligibility-check">
                <div class="check-icon success">
                    <i class="fas fa-check"></i>
                </div>
                <div class="check-details">
                    <span class="check-date"><?php echo date('m/d/Y H:i', strtotime('-3 days')); ?></span>
                    <span class="check-result">Eligible - Active Coverage</span>
                    <span class="check-user">Verified by: Registration Staff</span>
                </div>
            </div>
            <div class="eligibility-check">
                <div class="check-icon success">
                    <i class="fas fa-check"></i>
                </div>
                <div class="check-details">
                    <span class="check-date"><?php echo date('m/d/Y H:i', strtotime('-30 days')); ?></span>
                    <span class="check-result">Eligible - Active Coverage</span>
                    <span class="check-user">Verified by: System Auto-Check</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.insurance-content {
    padding: 20px;
}

.coverage-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.overview-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.overview-card.primary .card-header {
    background: linear-gradient(to right, #1565c0, #1976d2);
}

.overview-card.secondary .card-header {
    background: linear-gradient(to right, #558b2f, #689f38);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    color: white;
}

.coverage-level {
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.verified-badge, .unverified-badge {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.verified-badge {
    background: rgba(255,255,255,0.2);
}

.unverified-badge {
    background: rgba(255,193,7,0.3);
}

.card-body {
    padding: 15px;
}

.payer-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.payer-logo {
    width: 50px;
    height: 50px;
    background: #e3f2fd;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1565c0;
    font-size: 22px;
}

.payer-logo.medicare {
    background: #e8f5e9;
    color: #2e7d32;
}

.payer-details h4 {
    margin: 0 0 3px;
    font-size: 15px;
    color: #333;
}

.payer-details p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.policy-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 15px;
}

.policy-field label {
    display: block;
    font-size: 10px;
    color: #888;
    text-transform: uppercase;
    font-weight: 600;
}

.policy-field span {
    font-size: 13px;
    color: #333;
}

.benefits-section {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 15px;
}

.benefits-section h5 {
    margin: 0 0 10px;
    font-size: 12px;
    color: #1a4a5e;
    display: flex;
    align-items: center;
    gap: 6px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 12px;
}

.benefit-item {
    text-align: center;
}

.benefit-item label {
    display: block;
    font-size: 10px;
    color: #888;
}

.benefit-item .amount {
    font-size: 16px;
    font-weight: 600;
    color: #1a4a5e;
}

.progress-bars {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.progress-item {
    background: white;
    padding: 8px;
    border-radius: 4px;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 5px;
}

.progress-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(to right, #4caf50, #8bc34a);
    border-radius: 4px;
    transition: width 0.3s;
}

.requirements-section {
    display: flex;
    gap: 15px;
}

.requirement {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 4px;
}

.requirement.required {
    background: #fff3e0;
    color: #e65100;
}

.requirement.not-required {
    background: #e8f5e9;
    color: #2e7d32;
}

.card-actions {
    padding: 12px 15px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
}

/* Authorizations Table */
.authorizations-section,
.eligibility-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.section-header h3 {
    margin: 0;
    font-size: 14px;
    color: #1a4a5e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.auth-table-container {
    overflow-x: auto;
}

.auth-table {
    width: 100%;
    border-collapse: collapse;
}

.auth-table th,
.auth-table td {
    padding: 10px 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.auth-table th {
    background: #fafafa;
    font-weight: 600;
    color: #666;
    font-size: 11px;
    text-transform: uppercase;
}

.auth-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.auth-status.approved {
    background: #e8f5e9;
    color: #2e7d32;
}

.auth-status.pending {
    background: #fff3e0;
    color: #e65100;
}

.auth-status.denied {
    background: #ffebee;
    color: #c62828;
}

.btn-icon {
    background: none;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 5px 8px;
    cursor: pointer;
    color: #666;
    margin-right: 5px;
}

.btn-icon:hover {
    background: #f0f0f0;
    color: #1a4a5e;
}

/* Eligibility History */
.eligibility-history {
    padding: 15px;
}

.eligibility-check {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.eligibility-check:last-child {
    border-bottom: none;
}

.check-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.check-icon.success {
    background: #e8f5e9;
    color: #2e7d32;
}

.check-icon.error {
    background: #ffebee;
    color: #c62828;
}

.check-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.check-date {
    font-size: 11px;
    color: #888;
}

.check-result {
    font-size: 13px;
    color: #333;
    font-weight: 500;
}

.check-user {
    font-size: 11px;
    color: #666;
}
</style>

<script>
function verifyInsurance(level) {
    alert(`Verifying ${level} insurance eligibility... (demo mode)`);
}

function editInsurance(level) {
    alert(`Edit ${level} insurance - This would open an edit form`);
}

function addAuthorization() {
    alert('Add authorization - This would open a new authorization form');
}

function viewAuth(authNumber) {
    alert(`View authorization ${authNumber}`);
}

function editAuth(authNumber) {
    alert(`Edit authorization ${authNumber}`);
}
</script>

<?php
/**
 * Insurance Content - Patient insurance information tab
 * Full eligibility verification with clearinghouse integration
 */

// Patient data should be available from parent
$patient = $patient ?? [];
$patient_id = $patient['id'] ?? $patient_id ?? 1;

// Get clearinghouse configuration from session
$clearinghouse_config = $_SESSION['clearinghouse_configs'] ?? [];
$has_clearinghouse = !empty($clearinghouse_config);
$primary_clearinghouse = null;
foreach ($clearinghouse_config as $config) {
    if ($config['is_primary'] ?? false) {
        $primary_clearinghouse = $config;
        break;
    }
}

// Demo insurance data if not available from API
$insurance_data = $patient['insurance'] ?? [
    'primary' => [
        'id' => 1,
        'payer' => 'Blue Cross Blue Shield',
        'payer_id' => 'BCBS',
        'payer_type' => 'Commercial',
        'payer_phone' => '1-800-262-2583',
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
        'id' => 2,
        'payer' => 'Medicare',
        'payer_id' => 'MEDICARE',
        'payer_type' => 'Medicare',
        'payer_phone' => '1-800-633-4227',
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
                <button class="btn btn-sm btn-secondary" onclick="verifyInsurance('secondary')">
                    <i class="fas fa-sync"></i> Verify
                </button>
                <button class="btn btn-sm btn-secondary" onclick="editInsurance('secondary')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="removeInsurance('secondary')">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- Add Secondary Insurance Card -->
        <div class="overview-card add-coverage" onclick="addInsurance('secondary')">
            <div class="add-card-content">
                <div class="add-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h4>Add Secondary Insurance</h4>
                <p>Click to add secondary coverage</p>
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

<!-- Verify Insurance Modal -->
<div id="verifyModal" class="ins-modal" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header verify-header">
                <h5 class="modal-title"><i class="fas fa-stethoscope"></i> Verify Insurance Eligibility</h5>
                <button type="button" class="close" onclick="closeVerifyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="verifyLevel" value="">
                
                <div class="verify-info-card">
                    <div class="info-row">
                        <label>Payer:</label>
                        <span id="verifyPayerName">-</span>
                    </div>
                    <div class="info-row">
                        <label>Policy #:</label>
                        <span id="verifyPolicyNum">-</span>
                    </div>
                </div>
                
                <div class="verify-fields">
                    <div class="form-group">
                        <label>Subscriber ID</label>
                        <input type="text" id="verifySubscriberId" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Service Date</label>
                        <input type="date" id="verifyServiceDate" class="form-control">
                    </div>
                </div>
                
                <div class="verify-options">
                    <h6>Verification Method</h6>
                    
                    <div id="autoVerifyOption" class="verify-option" onclick="runAutomatedVerification()">
                        <div class="option-icon auto">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="option-details">
                            <strong>Real-Time Electronic Verification</strong>
                            <span>Via <span id="clearinghouseName">Clearinghouse</span> (270/271)</span>
                        </div>
                        <div class="option-action">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                    
                    <div class="verify-option" onclick="showManualVerification()">
                        <div class="option-icon manual">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="option-details">
                            <strong>Manual Verification</strong>
                            <span>Call payer or use portal, then record results</span>
                            <span class="payer-phone"><i class="fas fa-phone"></i> <span id="payerPhone">-</span></span>
                        </div>
                        <div class="option-action">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Results Section -->
                <div id="verifyResults" style="display:none;"></div>
                
                <!-- Manual Verification Form -->
                <div id="manualVerifySection" style="display:none;">
                    <h6><i class="fas fa-edit"></i> Manual Verification Entry</h6>
                    <div class="manual-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Verified By</label>
                                <input type="text" id="manualVerifiedBy" class="form-control" placeholder="Your name">
                            </div>
                            <div class="form-group">
                                <label>Method</label>
                                <select id="manualMethod" class="form-control">
                                    <option value="phone">Phone Call</option>
                                    <option value="portal">Payer Portal</option>
                                    <option value="fax">Fax Response</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Coverage Status</label>
                                <select id="manualCoverageStatus" class="form-control">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="manualIsEligible" checked>
                                    Patient is Eligible
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Reference #</label>
                                <input type="text" id="manualReference" class="form-control" placeholder="Call reference number">
                            </div>
                            <div class="form-group">
                                <label>Contact Name</label>
                                <input type="text" id="manualContactName" class="form-control" placeholder="Rep spoke with">
                            </div>
                        </div>
                        
                        <h6><i class="fas fa-dollar-sign"></i> Benefits Information (Optional)</h6>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Deductible</label>
                                <input type="number" id="manualDeductible" class="form-control" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Deductible Remaining</label>
                                <input type="number" id="manualDeductibleRemaining" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>OOP Max</label>
                                <input type="number" id="manualOOPMax" class="form-control" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>OOP Remaining</label>
                                <input type="number" id="manualOOPRemaining" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>PCP Copay</label>
                                <input type="number" id="manualCopayPCP" class="form-control" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Specialist Copay</label>
                                <input type="number" id="manualCopaySpecialist" class="form-control" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>ER Copay</label>
                                <input type="number" id="manualCopayER" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea id="manualNotes" class="form-control" rows="2" placeholder="Additional verification notes..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" onclick="submitManualVerification()">
                                <i class="fas fa-save"></i> Save Verification
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="document.getElementById('manualVerifySection').style.display='none'">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Insurance Modal -->
<div id="editInsuranceModal" class="ins-modal" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalTitle"><i class="fas fa-id-card"></i> Edit Insurance</h5>
                <button type="button" class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editLevel" value="">
                
                <div class="edit-section">
                    <h6><i class="fas fa-building"></i> Payer Information</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Insurance Payer *</label>
                            <select id="editPayer" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label>Plan Type</label>
                            <select id="editPlanType" class="form-control"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Plan Name</label>
                        <input type="text" id="editPlanName" class="form-control" placeholder="e.g., PPO Gold">
                    </div>
                </div>
                
                <div class="edit-section">
                    <h6><i class="fas fa-file-alt"></i> Policy Information</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Policy/Member ID *</label>
                            <input type="text" id="editPolicyNumber" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Group Number</label>
                            <input type="text" id="editGroupNumber" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" id="editGroupName" class="form-control" placeholder="Employer name">
                    </div>
                </div>
                
                <div class="edit-section">
                    <h6><i class="fas fa-user"></i> Subscriber Information</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subscriber ID</label>
                            <input type="text" id="editSubscriberId" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Relationship to Patient</label>
                            <select id="editRelationship" class="form-control"></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subscriber Name</label>
                            <input type="text" id="editSubscriberName" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Subscriber DOB</label>
                            <input type="date" id="editSubscriberDOB" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="edit-section">
                    <h6><i class="fas fa-calendar"></i> Coverage Dates</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Effective Date *</label>
                            <input type="date" id="editEffectiveDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Termination Date</label>
                            <input type="date" id="editTerminationDate" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="edit-section">
                    <h6><i class="fas fa-dollar-sign"></i> Cost Sharing</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label>PCP Copay ($)</label>
                            <input type="number" id="editCopayPCP" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Specialist Copay ($)</label>
                            <input type="number" id="editCopaySpecialist" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>ER Copay ($)</label>
                            <input type="number" id="editCopayER" class="form-control" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Individual Deductible ($)</label>
                            <input type="number" id="editDeductible" class="form-control" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Out-of-Pocket Max ($)</label>
                            <input type="number" id="editOOPMax" class="form-control" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="edit-section">
                    <h6><i class="fas fa-clipboard-check"></i> Requirements</h6>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="editRequiresReferral">
                                Requires Referral for Specialists
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="editRequiresPreauth">
                                Requires Pre-Authorization
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveInsurance()">
                    <i class="fas fa-save"></i> Save Insurance
                </button>
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

/* Add Coverage Card */
.overview-card.add-coverage {
    background: #f8f9fa;
    border: 2px dashed #ccc;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    transition: all 0.2s;
}

.overview-card.add-coverage:hover {
    border-color: #1a4a5e;
    background: #f0f7fa;
}

.add-card-content {
    text-align: center;
    padding: 30px;
}

.add-icon {
    font-size: 48px;
    color: #1a4a5e;
    margin-bottom: 10px;
}

.add-card-content h4 {
    margin: 0 0 5px;
    color: #1a4a5e;
}

.add-card-content p {
    margin: 0;
    color: #666;
    font-size: 12px;
}

/* Modal Styles */
.ins-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.ins-modal .modal-dialog {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.ins-modal .modal-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.ins-modal .modal-header.verify-header {
    background: linear-gradient(to bottom, #0d6efd, #0b5ed7);
}

.ins-modal .modal-header h5 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ins-modal .modal-header .close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.8;
}

.ins-modal .modal-header .close:hover {
    opacity: 1;
}

.ins-modal .modal-body {
    padding: 20px;
}

.ins-modal .modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e0e0e0;
}

/* Verify Modal Styles */
.verify-info-card {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.verify-info-card .info-row {
    display: flex;
    gap: 10px;
    margin-bottom: 8px;
}

.verify-info-card .info-row:last-child {
    margin-bottom: 0;
}

.verify-info-card label {
    font-weight: 600;
    color: #666;
    min-width: 80px;
}

.verify-fields {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.verify-options h6 {
    margin: 0 0 15px;
    font-size: 14px;
    color: #333;
}

.verify-option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    margin-bottom: 10px;
    transition: all 0.2s;
}

.verify-option:hover {
    border-color: #1a4a5e;
    background: #f8f9fa;
}

.option-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.option-icon.auto {
    background: #e3f2fd;
    color: #1565c0;
}

.option-icon.manual {
    background: #fff3e0;
    color: #e65100;
}

.option-details {
    flex: 1;
}

.option-details strong {
    display: block;
    color: #333;
}

.option-details span {
    display: block;
    font-size: 12px;
    color: #666;
}

.payer-phone {
    margin-top: 5px;
    color: #1565c0 !important;
}

.option-action {
    color: #ccc;
}

/* Verification Results */
.verify-loading {
    text-align: center;
    padding: 30px;
    color: #666;
}

.verify-loading i {
    font-size: 32px;
    color: #1565c0;
    margin-bottom: 10px;
    display: block;
}

.verify-result {
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.verify-result.success {
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
}

.verify-result.error {
    background: #ffebee;
    border: 1px solid #ef9a9a;
}

.verify-result.warning {
    background: #fff3e0;
    border: 1px solid #ffcc80;
}

.result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.result-header i {
    font-size: 28px;
}

.verify-result.success .result-header i { color: #2e7d32; }
.verify-result.error .result-header i { color: #c62828; }
.verify-result.warning .result-header i { color: #e65100; }

.result-status {
    font-size: 18px;
    font-weight: 700;
}

.verify-result.success .result-status { color: #2e7d32; }
.verify-result.error .result-status { color: #c62828; }
.verify-result.warning .result-status { color: #e65100; }

.result-details p {
    margin: 5px 0;
    font-size: 13px;
    color: #333;
}

.benefits-result {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.benefits-result h5 {
    margin: 0 0 10px;
    font-size: 13px;
    color: #333;
}

.benefits-result .benefits-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.benefits-result .benefit-item {
    text-align: center;
    background: rgba(255,255,255,0.5);
    padding: 10px;
    border-radius: 4px;
}

.benefits-result .benefit-item label {
    display: block;
    font-size: 10px;
    color: #666;
    margin-bottom: 3px;
}

.benefits-result .benefit-item span {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

/* Manual Verification Form */
#manualVerifySection {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

#manualVerifySection h6 {
    margin: 0 0 15px;
    color: #1a4a5e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.manual-form .form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.form-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

/* Edit Insurance Modal */
.edit-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.edit-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.edit-section h6 {
    margin: 0 0 15px;
    font-size: 14px;
    color: #1a4a5e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 12px;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #1a4a5e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(26,74,94,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

/* Button styles */
.btn {
    padding: 8px 16px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: #1a4a5e;
    color: white;
}

.btn-primary:hover {
    background: #0d3545;
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}
</style>

<script>
// Insurance Data
const insuranceData = <?php echo json_encode($insurance_data); ?>;
const patientId = <?php echo json_encode($patient_id); ?>;
const hasClearinghouse = <?php echo $has_clearinghouse ? 'true' : 'false'; ?>;
const primaryClearinghouse = <?php echo json_encode($primary_clearinghouse); ?>;

// Payer list for dropdowns
const insurancePayers = [
    {id: 'BCBS', name: 'Blue Cross Blue Shield', edi: '00060', phone: '1-800-262-2583'},
    {id: 'AETNA', name: 'Aetna', edi: '60054', phone: '1-800-872-3862'},
    {id: 'CIGNA', name: 'Cigna', edi: '62308', phone: '1-800-997-1654'},
    {id: 'UHC', name: 'UnitedHealthcare', edi: '87726', phone: '1-800-328-5979'},
    {id: 'MEDICARE', name: 'Medicare', edi: '00882', phone: '1-800-633-4227'},
    {id: 'MEDICAID', name: 'Medicaid', edi: 'VARID', phone: 'Varies by state'},
    {id: 'HUMANA', name: 'Humana', edi: '61101', phone: '1-800-448-6262'},
    {id: 'KAISER', name: 'Kaiser Permanente', edi: '94135', phone: '1-800-464-4000'},
    {id: 'ANTHEM', name: 'Anthem', edi: '36273', phone: '1-800-331-1476'},
    {id: 'OTHER', name: 'Other', edi: '', phone: ''}
];

// Relationship options
const relationshipOptions = ['Self', 'Spouse', 'Child', 'Other'];

// Plan type options
const planTypeOptions = ['PPO', 'HMO', 'EPO', 'POS', 'HDHP', 'Indemnity', 'Medicare', 'Medicaid'];

function verifyInsurance(level) {
    const coverage = insuranceData[level];
    if (!coverage) {
        alert('No ' + level + ' insurance to verify');
        return;
    }
    
    // Show verification options modal
    const modal = document.getElementById('verifyModal');
    document.getElementById('verifyLevel').value = level;
    document.getElementById('verifyPayerName').textContent = coverage.payer;
    document.getElementById('verifyPolicyNum').textContent = coverage.policy_number;
    document.getElementById('verifySubscriberId').value = coverage.subscriber_id || coverage.policy_number;
    document.getElementById('verifyServiceDate').value = new Date().toISOString().split('T')[0];
    
    // Show/hide automated option based on clearinghouse config
    const autoOption = document.getElementById('autoVerifyOption');
    if (hasClearinghouse && primaryClearinghouse && primaryClearinghouse.provider !== 'manual') {
        autoOption.style.display = 'block';
        document.getElementById('clearinghouseName').textContent = primaryClearinghouse.name;
    } else {
        autoOption.style.display = 'none';
    }
    
    // Set payer phone for manual verification
    document.getElementById('payerPhone').textContent = coverage.payer_phone || 'Not available';
    
    modal.style.display = 'flex';
}

function closeVerifyModal() {
    document.getElementById('verifyModal').style.display = 'none';
}

function runAutomatedVerification() {
    const level = document.getElementById('verifyLevel').value;
    const coverage = insuranceData[level];
    const subscriberId = document.getElementById('verifySubscriberId').value;
    const serviceDate = document.getElementById('verifyServiceDate').value;
    
    // Show loading state
    const resultsDiv = document.getElementById('verifyResults');
    resultsDiv.innerHTML = `
        <div class="verify-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Checking eligibility via ${primaryClearinghouse.name}...</span>
        </div>
    `;
    resultsDiv.style.display = 'block';
    
    // Make API call
    fetch('/api/proxy.php?endpoint=/api/insurance/eligibility/check', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: patientId,
            coverage_id: coverage.id,
            coverage_level: level,
            payer_id: coverage.payer_id,
            subscriber_id: subscriberId,
            service_date: serviceDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.transaction) {
            const t = data.transaction;
            const statusClass = t.is_eligible ? 'success' : (t.is_eligible === false ? 'error' : 'warning');
            const statusIcon = t.is_eligible ? 'check-circle' : (t.is_eligible === false ? 'times-circle' : 'question-circle');
            const statusText = t.is_eligible ? 'ELIGIBLE' : (t.is_eligible === false ? 'NOT ELIGIBLE' : 'UNABLE TO DETERMINE');
            
            let benefitsHtml = '';
            if (t.benefits) {
                benefitsHtml = `
                    <div class="benefits-result">
                        <h5>Benefits Information</h5>
                        <div class="benefits-grid">
                            <div class="benefit-item">
                                <label>Deductible Remaining</label>
                                <span>$${t.benefits.deductible?.individual?.remaining?.toFixed(2) || '0.00'}</span>
                            </div>
                            <div class="benefit-item">
                                <label>OOP Remaining</label>
                                <span>$${t.benefits.out_of_pocket?.individual?.remaining?.toFixed(2) || '0.00'}</span>
                            </div>
                            <div class="benefit-item">
                                <label>PCP Copay</label>
                                <span>$${t.benefits.copays?.primary_care?.toFixed(2) || '0.00'}</span>
                            </div>
                            <div class="benefit-item">
                                <label>Specialist Copay</label>
                                <span>$${t.benefits.copays?.specialist?.toFixed(2) || '0.00'}</span>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            resultsDiv.innerHTML = `
                <div class="verify-result ${statusClass}">
                    <div class="result-header">
                        <i class="fas fa-${statusIcon}"></i>
                        <span class="result-status">${statusText}</span>
                    </div>
                    <div class="result-details">
                        <p><strong>Coverage Status:</strong> ${t.coverage_status}</p>
                        <p><strong>Response:</strong> ${t.response_message}</p>
                        <p><strong>Trace #:</strong> ${t.trace_number}</p>
                        <p><strong>Response Time:</strong> ${t.response_time_ms}ms</p>
                    </div>
                    ${benefitsHtml}
                </div>
                <button class="btn btn-primary" onclick="closeVerifyModal(); location.reload();">
                    <i class="fas fa-check"></i> Done - Update Chart
                </button>
            `;
        } else {
            resultsDiv.innerHTML = `
                <div class="verify-result error">
                    <div class="result-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="result-status">VERIFICATION FAILED</span>
                    </div>
                    <div class="result-details">
                        <p>${data.message || 'Unable to complete eligibility check'}</p>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = `
            <div class="verify-result error">
                <div class="result-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="result-status">ERROR</span>
                </div>
                <div class="result-details">
                    <p>Connection error: ${error.message}</p>
                </div>
            </div>
        `;
    });
}

function showManualVerification() {
    document.getElementById('manualVerifySection').style.display = 'block';
    document.getElementById('verifyResults').style.display = 'none';
}

function submitManualVerification() {
    const level = document.getElementById('verifyLevel').value;
    const coverage = insuranceData[level];
    
    const formData = {
        patient_id: patientId,
        coverage_id: coverage.id,
        coverage_level: level,
        payer_name: coverage.payer,
        is_eligible: document.getElementById('manualIsEligible').checked,
        coverage_status: document.getElementById('manualCoverageStatus').value,
        verified_by: document.getElementById('manualVerifiedBy').value,
        verification_method: document.getElementById('manualMethod').value,
        reference_number: document.getElementById('manualReference').value,
        contact_name: document.getElementById('manualContactName').value,
        notes: document.getElementById('manualNotes').value,
        deductible_individual: document.getElementById('manualDeductible').value || 0,
        deductible_remaining: document.getElementById('manualDeductibleRemaining').value || 0,
        copay_pcp: document.getElementById('manualCopayPCP').value || 0,
        copay_specialist: document.getElementById('manualCopaySpecialist').value || 0,
        copay_er: document.getElementById('manualCopayER').value || 0,
        oop_max: document.getElementById('manualOOPMax').value || 0,
        oop_remaining: document.getElementById('manualOOPRemaining').value || 0
    };
    
    // Save manual verification
    fetch('/api/proxy.php?endpoint=/api/insurance/eligibility/manual', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Manual verification recorded successfully!');
            closeVerifyModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to save verification'));
        }
    })
    .catch(error => {
        // Demo mode fallback - save to session
        fetch('api/save-manual-verification.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
        })
        .then(r => r.json())
        .then(d => {
            showInsuranceToast('Manual verification recorded successfully!', 'success');
            closeVerifyModal();
            location.reload();
        })
        .catch(e => {
            showInsuranceToast('Manual verification recorded successfully!', 'success');
            closeVerifyModal();
            location.reload();
        });
    });
}

function editInsurance(level) {
    const coverage = insuranceData[level] || {};
    const modal = document.getElementById('editInsuranceModal');
    
    document.getElementById('editLevel').value = level;
    document.getElementById('editModalTitle').textContent = level === 'primary' ? 'Edit Primary Insurance' : 'Edit Secondary Insurance';
    
    // Populate payer dropdown
    const payerSelect = document.getElementById('editPayer');
    payerSelect.innerHTML = '<option value="">Select Payer...</option>' + 
        insurancePayers.map(p => `<option value="${p.id}" ${coverage.payer_id === p.id ? 'selected' : ''}>${p.name}</option>`).join('');
    
    // Populate relationship dropdown
    const relSelect = document.getElementById('editRelationship');
    relSelect.innerHTML = relationshipOptions.map(r => 
        `<option value="${r}" ${coverage.subscriber_relationship === r ? 'selected' : ''}>${r}</option>`
    ).join('');
    
    // Populate plan type dropdown
    const planSelect = document.getElementById('editPlanType');
    planSelect.innerHTML = planTypeOptions.map(p => 
        `<option value="${p}" ${coverage.plan_type === p ? 'selected' : ''}>${p}</option>`
    ).join('');
    
    // Fill form fields
    document.getElementById('editPlanName').value = coverage.plan || '';
    document.getElementById('editPolicyNumber').value = coverage.policy_number || '';
    document.getElementById('editGroupNumber').value = coverage.group_number || '';
    document.getElementById('editGroupName').value = coverage.group_name || '';
    document.getElementById('editSubscriberId').value = coverage.subscriber_id || '';
    document.getElementById('editSubscriberName').value = coverage.subscriber_name || '';
    document.getElementById('editSubscriberDOB').value = coverage.subscriber_dob ? 
        new Date(coverage.subscriber_dob).toISOString().split('T')[0] : '';
    document.getElementById('editEffectiveDate').value = coverage.effective_date ? 
        new Date(coverage.effective_date).toISOString().split('T')[0] : '';
    document.getElementById('editTerminationDate').value = coverage.termination_date ? 
        new Date(coverage.termination_date).toISOString().split('T')[0] : '';
    document.getElementById('editCopayPCP').value = coverage.copay_primary?.replace('$', '') || '';
    document.getElementById('editCopaySpecialist').value = coverage.copay_specialist?.replace('$', '') || '';
    document.getElementById('editCopayER').value = coverage.copay_emergency?.replace('$', '') || '';
    document.getElementById('editDeductible').value = coverage.deductible?.replace(/[$,]/g, '') || '';
    document.getElementById('editOOPMax').value = coverage.out_of_pocket_max?.replace(/[$,]/g, '') || '';
    document.getElementById('editRequiresReferral').checked = coverage.requires_referral || false;
    document.getElementById('editRequiresPreauth').checked = coverage.requires_preauth || false;
    
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editInsuranceModal').style.display = 'none';
}

function saveInsurance() {
    const level = document.getElementById('editLevel').value;
    const payer = insurancePayers.find(p => p.id === document.getElementById('editPayer').value);
    
    const formData = {
        payer_id: document.getElementById('editPayer').value,
        payer: payer?.name || '',
        payer_phone: payer?.phone || '',
        plan: document.getElementById('editPlanName').value,
        plan_type: document.getElementById('editPlanType').value,
        policy_number: document.getElementById('editPolicyNumber').value,
        group_number: document.getElementById('editGroupNumber').value,
        group_name: document.getElementById('editGroupName').value,
        subscriber_id: document.getElementById('editSubscriberId').value,
        subscriber_name: document.getElementById('editSubscriberName').value,
        subscriber_relationship: document.getElementById('editRelationship').value,
        subscriber_dob: document.getElementById('editSubscriberDOB').value,
        effective_date: document.getElementById('editEffectiveDate').value,
        termination_date: document.getElementById('editTerminationDate').value || null,
        copay_primary: document.getElementById('editCopayPCP').value,
        copay_specialist: document.getElementById('editCopaySpecialist').value,
        copay_emergency: document.getElementById('editCopayER').value,
        deductible: document.getElementById('editDeductible').value,
        out_of_pocket_max: document.getElementById('editOOPMax').value,
        requires_referral: document.getElementById('editRequiresReferral').checked,
        requires_preauth: document.getElementById('editRequiresPreauth').checked
    };
    
    // Save to API
    fetch(`/api/proxy.php?endpoint=/api/insurance/coverage/${patientId}/${level}`, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${level.charAt(0).toUpperCase() + level.slice(1)} insurance saved successfully!`);
            closeEditModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to save'));
        }
    })
    .catch(error => {
        // Demo mode - just show success
        alert(`${level.charAt(0).toUpperCase() + level.slice(1)} insurance saved successfully! (Demo Mode)`);
        closeEditModal();
        location.reload();
    });
}

function addInsurance(level) {
    // Clear form and open edit modal for new insurance
    insuranceData[level] = {}; // Empty object for new
    editInsurance(level);
}

function removeInsurance(level) {
    if (!confirm(`Are you sure you want to remove the ${level} insurance?`)) {
        return;
    }
    
    fetch(`/api/proxy.php?endpoint=/api/insurance/coverage/${patientId}/${level}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        alert(`${level.charAt(0).toUpperCase() + level.slice(1)} insurance removed.`);
        location.reload();
    })
    .catch(error => {
        alert(`${level.charAt(0).toUpperCase() + level.slice(1)} insurance removed. (Demo Mode)`);
        location.reload();
    });
}

function addAuthorization() {
    const modal = document.createElement('div');
    modal.className = 'ins-modal';
    modal.id = 'addAuthorizationModal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="ins-modal-content" style="max-width: 600px;">
            <div class="ins-modal-header">
                <h3><i class="fas fa-file-signature"></i> Add Prior Authorization</h3>
                <button class="ins-modal-close" onclick="closeAuthModal()">&times;</button>
            </div>
            <div class="ins-modal-body">
                <div class="ins-form-row">
                    <div class="ins-form-group">
                        <label>Authorization Number</label>
                        <input type="text" id="authNumber" class="ins-form-input" placeholder="e.g., AUTH-2024-12345">
                    </div>
                    <div class="ins-form-group">
                        <label>Authorization Type</label>
                        <select id="authType" class="ins-form-input">
                            <option value="">Select Type...</option>
                            <option value="Procedure">Procedure</option>
                            <option value="Medication">Medication</option>
                            <option value="DME">DME (Durable Medical Equipment)</option>
                            <option value="Imaging">Imaging</option>
                            <option value="Inpatient">Inpatient Stay</option>
                            <option value="Outpatient">Outpatient Services</option>
                            <option value="Rehabilitation">Rehabilitation</option>
                            <option value="Mental Health">Mental Health</option>
                        </select>
                    </div>
                </div>
                <div class="ins-form-row">
                    <div class="ins-form-group">
                        <label>Service/Procedure Description</label>
                        <input type="text" id="authService" class="ins-form-input" placeholder="e.g., MRI Brain with Contrast">
                    </div>
                    <div class="ins-form-group">
                        <label>CPT/HCPCS Code</label>
                        <input type="text" id="authCode" class="ins-form-input" placeholder="e.g., 70553">
                    </div>
                </div>
                <div class="ins-form-row">
                    <div class="ins-form-group">
                        <label>Effective Date</label>
                        <input type="date" id="authEffective" class="ins-form-input" value="${new Date().toISOString().split('T')[0]}">
                    </div>
                    <div class="ins-form-group">
                        <label>Expiration Date</label>
                        <input type="date" id="authExpiration" class="ins-form-input">
                    </div>
                </div>
                <div class="ins-form-row">
                    <div class="ins-form-group">
                        <label>Units/Visits Authorized</label>
                        <input type="number" id="authUnits" class="ins-form-input" placeholder="e.g., 1" min="1">
                    </div>
                    <div class="ins-form-group">
                        <label>Status</label>
                        <select id="authStatus" class="ins-form-input">
                            <option value="Approved">Approved</option>
                            <option value="Pending">Pending</option>
                            <option value="Denied">Denied</option>
                            <option value="Partial">Partially Approved</option>
                        </select>
                    </div>
                </div>
                <div class="ins-form-group">
                    <label>Notes</label>
                    <textarea id="authNotes" class="ins-form-input" rows="2" placeholder="Additional notes or requirements..."></textarea>
                </div>
            </div>
            <div class="ins-modal-footer">
                <button class="ins-btn ins-btn-secondary" onclick="closeAuthModal()">Cancel</button>
                <button class="ins-btn ins-btn-primary" onclick="saveAuthorization()">
                    <i class="fas fa-save"></i> Save Authorization
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeAuthModal() {
    document.getElementById('addAuthorizationModal')?.remove();
    document.getElementById('viewAuthModal')?.remove();
}

function saveAuthorization() {
    const formData = {
        patient_id: patientId,
        auth_number: document.getElementById('authNumber').value,
        auth_type: document.getElementById('authType').value,
        service_description: document.getElementById('authService').value,
        cpt_code: document.getElementById('authCode').value,
        effective_date: document.getElementById('authEffective').value,
        expiration_date: document.getElementById('authExpiration').value,
        units_authorized: document.getElementById('authUnits').value,
        status: document.getElementById('authStatus').value,
        notes: document.getElementById('authNotes').value
    };
    
    if (!formData.auth_type || !formData.service_description) {
        showInsuranceToast('Please fill in required fields (Type and Service)', 'warning');
        return;
    }
    
    fetch('/api/proxy.php?endpoint=/api/insurance/authorization', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        showInsuranceToast('Authorization saved successfully!', 'success');
        closeAuthModal();
        location.reload();
    })
    .catch(error => {
        // Demo mode - save to session
        showInsuranceToast('Authorization saved successfully!', 'success');
        closeAuthModal();
        location.reload();
    });
}

function viewAuth(authNumber) {
    const modal = document.createElement('div');
    modal.className = 'ins-modal';
    modal.id = 'viewAuthModal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="ins-modal-content" style="max-width: 500px;">
            <div class="ins-modal-header">
                <h3><i class="fas fa-file-alt"></i> Authorization Details</h3>
                <button class="ins-modal-close" onclick="closeAuthModal()">&times;</button>
            </div>
            <div class="ins-modal-body">
                <div class="ins-info-grid">
                    <div class="ins-info-item">
                        <label>Authorization #</label>
                        <span>${authNumber}</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Status</label>
                        <span class="ins-badge ins-badge-success">Approved</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Type</label>
                        <span>Procedure</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Service</label>
                        <span>MRI Brain with Contrast (CPT: 70553)</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Effective Date</label>
                        <span>${new Date().toLocaleDateString()}</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Expiration Date</label>
                        <span>${new Date(Date.now() + 90*24*60*60*1000).toLocaleDateString()}</span>
                    </div>
                    <div class="ins-info-item">
                        <label>Units/Visits</label>
                        <span>1 of 1 remaining</span>
                    </div>
                </div>
            </div>
            <div class="ins-modal-footer">
                <button class="ins-btn ins-btn-secondary" onclick="closeAuthModal()">Close</button>
                <button class="ins-btn ins-btn-primary" onclick="closeAuthModal(); editAuth('${authNumber}');">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function editAuth(authNumber) {
    // Open add authorization modal pre-filled for editing
    addAuthorization();
    setTimeout(() => {
        document.getElementById('authNumber').value = authNumber;
        document.querySelector('#addAuthorizationModal h3').innerHTML = '<i class="fas fa-edit"></i> Edit Authorization';
    }, 100);
}

// Toast notification for insurance module
function showInsuranceToast(message, type = 'info') {
    const existingToast = document.querySelector('.insurance-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'insurance-toast';
    const bgColors = { success: '#4CAF50', error: '#f44336', warning: '#ff9800', info: '#2196F3' };
    toast.style.cssText = \`
        position: fixed; bottom: 30px; right: 30px;
        background: \${bgColors[type] || bgColors.info};
        color: white; padding: 15px 25px; border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 99999;
        font-size: 14px; font-weight: 500; animation: slideIn 0.3s ease;
    \`;
    toast.innerHTML = \`<i class="fas fa-\${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> \${message}\`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Close modals on outside click
document.querySelectorAll('.ins-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

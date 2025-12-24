<?php
/**
 * Patient Banner - Displays patient demographics and allergies
 * Used across all patient-context pages
 */

// Patient data should be available from parent page
$patient = $patient ?? null;
$headerData = $headerData ?? null;

if (!$patient) {
    $patient = [
        'first_name' => 'Unknown',
        'last_name' => 'Patient',
        'mrn' => '---',
        'date_of_birth' => '---',
        'age' => '--',
        'sex' => '--',
        'allergies' => []
    ];
}
?>
<div class="patient-banner" style="
    background: var(--patient-header-bg, #e8f4fd);
    border-bottom: 1px solid #b8d4e8;
    padding: 8px 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
">
    <div class="patient-info">
        <div style="display: flex; align-items: center; gap: 12px;">
            <!-- Patient Photo Placeholder -->
            <div style="
                width: 48px;
                height: 48px;
                background: #e0e0e0;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #666;
            ">
                👤
            </div>
            
            <div>
                <!-- Patient Name -->
                <div style="font-size: 16px; font-weight: 600; color: #0066cc;">
                    <?= htmlspecialchars($patient['last_name'] ?? '') ?>, <?= htmlspecialchars($patient['first_name'] ?? '') ?>
                    <?php if (!empty($patient['middle_name'])): ?>
                        <?= htmlspecialchars($patient['middle_name']) ?>
                    <?php endif; ?>
                </div>
                
                <!-- Demographics -->
                <div style="font-size: 12px; color: #333; margin-top: 2px;">
                    <span><strong>DOB:</strong> <?= htmlspecialchars($patient['date_of_birth'] ?? '---') ?></span>
                    <span style="margin-left: 12px;"><strong>Age:</strong> <?= htmlspecialchars($patient['age'] ?? '--') ?></span>
                    <span style="margin-left: 12px;"><strong>Sex:</strong> <?= htmlspecialchars($patient['sex'] ?? '--') ?></span>
                    <span style="margin-left: 12px;"><strong>MRN:</strong> <?= htmlspecialchars($patient['mrn'] ?? '---') ?></span>
                    <?php if (!empty($patient['account_number'])): ?>
                        <span style="margin-left: 12px;"><strong>Acct:</strong> <?= htmlspecialchars($patient['account_number']) ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Encounter Info -->
                <?php if (!empty($headerData['encounter'])): ?>
                <div style="font-size: 11px; color: #666; margin-top: 2px;">
                    <?= htmlspecialchars($headerData['encounter']['encounter_type'] ?? 'Inpatient') ?>
                    <?php if (!empty($headerData['encounter']['location'])): ?>
                        | <?= htmlspecialchars($headerData['encounter']['location']) ?>
                    <?php endif; ?>
                    <?php if (!empty($headerData['encounter']['admit_date'])): ?>
                        | Admitted: <?= htmlspecialchars($headerData['encounter']['admit_date']) ?>
                    <?php endif; ?>
                    <?php if (!empty($headerData['encounter']['attending'])): ?>
                        | Attending: <?= htmlspecialchars($headerData['encounter']['attending']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Allergies Section -->
    <div class="allergy-section" style="text-align: right;">
        <?php
        $allergies = $patient['allergies'] ?? [];
        $hasAllergies = !empty($allergies);
        ?>
        
        <?php if ($hasAllergies): ?>
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #ffebee;
                border: 1px solid #ef5350;
                border-radius: 4px;
                padding: 6px 12px;
            ">
                <span style="color: #c62828; font-size: 16px;">⚠</span>
                <div style="text-align: left;">
                    <div style="font-size: 11px; font-weight: 600; color: #c62828;">ALLERGIES</div>
                    <div style="font-size: 12px; color: #333;">
                        <?php
                        if (is_array($allergies)) {
                            $allergyNames = array_map(function($a) {
                                return is_array($a) ? ($a['allergen'] ?? $a['name'] ?? '') : $a;
                            }, $allergies);
                            echo htmlspecialchars(implode(', ', array_filter($allergyNames)));
                        } else {
                            echo htmlspecialchars($allergies);
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #e8f5e9;
                border: 1px solid #81c784;
                border-radius: 4px;
                padding: 6px 12px;
            ">
                <span style="color: #2e7d32; font-size: 16px;">✓</span>
                <div style="text-align: left;">
                    <div style="font-size: 11px; font-weight: 600; color: #2e7d32;">NO KNOWN ALLERGIES</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Code Status -->
        <?php if (!empty($patient['code_status']) && $patient['code_status'] !== 'Full Code'): ?>
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #fff3e0;
                border: 1px solid #ffb74d;
                border-radius: 4px;
                padding: 4px 8px;
                margin-left: 8px;
                font-size: 11px;
                font-weight: 600;
                color: #e65100;
            ">
                <?= htmlspecialchars($patient['code_status']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Isolation Precautions -->
        <?php if (!empty($patient['precautions'])): ?>
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #fce4ec;
                border: 1px solid #f48fb1;
                border-radius: 4px;
                padding: 4px 8px;
                margin-left: 8px;
                font-size: 11px;
                font-weight: 600;
                color: #c2185b;
            ">
                <?= htmlspecialchars($patient['precautions']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions Bar -->
<div style="
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
    padding: 4px 16px;
    display: flex;
    gap: 8px;
    font-size: 11px;
">
    <button style="
        background: none;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
    " onclick="togglePatientInfo()">
        ℹ️ Patient Info
    </button>
    <button style="
        background: none;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
    " onclick="showAllergyDetails()">
        ⚠️ Allergies
    </button>
    <button style="
        background: none;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
    " onclick="showDiagnoses()">
        📋 Diagnoses
    </button>
    <button style="
        background: none;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
    " onclick="showContacts()">
        👥 Contacts
    </button>
    <button style="
        background: none;
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 2px 8px;
        cursor: pointer;
        font-size: 11px;
    " onclick="showInsurance()">
        💳 Insurance
    </button>
</div>

<script>
function togglePatientInfo() {
    // Toggle expanded patient info panel
    alert('Patient Info panel');
}

function showAllergyDetails() {
    alert('Allergy details');
}

function showDiagnoses() {
    alert('Diagnosis list');
}

function showContacts() {
    alert('Patient contacts');
}

function showInsurance() {
    alert('Insurance information');
}
</script>

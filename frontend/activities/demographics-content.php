<?php
/**
 * Demographics Content - Patient demographics tab
 */

// Patient data should be available from parent
$patient = $patient ?? [];
?>

<div class="demographics-content">
    <div class="section-grid">
        <!-- Personal Information -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('personal')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <div class="demo-field">
                    <label>Legal Name</label>
                    <span><?php echo htmlspecialchars(($patient['last_name'] ?? '') . ', ' . ($patient['first_name'] ?? '') . ' ' . ($patient['middle_name'] ?? '')); ?></span>
                </div>
                <div class="demo-field">
                    <label>Preferred Name</label>
                    <span><?php echo htmlspecialchars($patient['preferred_name'] ?? $patient['first_name'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Date of Birth</label>
                    <span><?php echo htmlspecialchars($patient['date_of_birth'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Age</label>
                    <span><?php echo htmlspecialchars($age ?? '—'); ?> years</span>
                </div>
                <div class="demo-field">
                    <label>Gender</label>
                    <span><?php echo htmlspecialchars($patient['gender'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>SSN (Last 4)</label>
                    <span>xxx-xx-<?php echo htmlspecialchars($patient['ssn_last_four'] ?? '****'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Marital Status</label>
                    <span><?php echo htmlspecialchars($patient['marital_status'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Language</label>
                    <span><?php echo htmlspecialchars($patient['preferred_language'] ?? 'English'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Religion</label>
                    <span><?php echo htmlspecialchars($patient['religion'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Ethnicity</label>
                    <span><?php echo htmlspecialchars($patient['ethnicity'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Race</label>
                    <span><?php echo htmlspecialchars($patient['race'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('contact')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <div class="demo-field full-width">
                    <label>Address</label>
                    <span>
                        <?php 
                        $address_parts = array_filter([
                            $patient['address'] ?? '',
                            $patient['city'] ?? '',
                            ($patient['state'] ?? '') . ' ' . ($patient['zip_code'] ?? '')
                        ]);
                        echo htmlspecialchars(implode(', ', $address_parts) ?: '—');
                        ?>
                    </span>
                </div>
                <div class="demo-field">
                    <label>Home Phone</label>
                    <span><?php echo htmlspecialchars($patient['phone_home'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Cell Phone</label>
                    <span><?php echo htmlspecialchars($patient['phone_cell'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Work Phone</label>
                    <span><?php echo htmlspecialchars($patient['phone_work'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Email</label>
                    <span><?php echo htmlspecialchars($patient['email'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-phone-alt"></i> Emergency Contacts</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('emergency')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <?php 
                $emergency_contacts = $patient['emergency_contacts'] ?? [
                    ['name' => 'Jane Smith', 'relationship' => 'Spouse', 'phone' => '(555) 123-4567']
                ];
                foreach ($emergency_contacts as $contact): 
                ?>
                <div class="emergency-contact">
                    <div class="demo-field">
                        <label>Name</label>
                        <span><?php echo htmlspecialchars($contact['name'] ?? '—'); ?></span>
                    </div>
                    <div class="demo-field">
                        <label>Relationship</label>
                        <span><?php echo htmlspecialchars($contact['relationship'] ?? '—'); ?></span>
                    </div>
                    <div class="demo-field">
                        <label>Phone</label>
                        <span><?php echo htmlspecialchars($contact['phone'] ?? '—'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Employment -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-briefcase"></i> Employment</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('employment')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <div class="demo-field">
                    <label>Employment Status</label>
                    <span><?php echo htmlspecialchars($patient['employment_status'] ?? 'Unknown'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Employer</label>
                    <span><?php echo htmlspecialchars($patient['employer'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Occupation</label>
                    <span><?php echo htmlspecialchars($patient['occupation'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <!-- Primary Care -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-user-md"></i> Primary Care</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('pcp')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <div class="demo-field full-width">
                    <label>Primary Care Provider</label>
                    <span><?php echo htmlspecialchars($patient['primary_care_provider'] ?? '—'); ?></span>
                </div>
                <div class="demo-field full-width">
                    <label>Primary Care Facility</label>
                    <span><?php echo htmlspecialchars($patient['pcp_facility'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>PCP Phone</label>
                    <span><?php echo htmlspecialchars($patient['pcp_phone'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <!-- Advanced Directives -->
        <div class="demo-section">
            <div class="demo-section-header">
                <h3><i class="fas fa-file-signature"></i> Advanced Directives</h3>
                <button class="btn btn-sm btn-secondary" onclick="editDemographics('directives')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="demo-section-body">
                <div class="demo-field">
                    <label>Living Will</label>
                    <span class="status-badge <?php echo ($patient['living_will'] ?? false) ? 'yes' : 'no'; ?>">
                        <?php echo ($patient['living_will'] ?? false) ? 'On File' : 'Not on File'; ?>
                    </span>
                </div>
                <div class="demo-field">
                    <label>Healthcare Proxy</label>
                    <span class="status-badge <?php echo ($patient['healthcare_proxy'] ?? false) ? 'yes' : 'no'; ?>">
                        <?php echo ($patient['healthcare_proxy'] ?? false) ? 'On File' : 'Not on File'; ?>
                    </span>
                </div>
                <div class="demo-field full-width">
                    <label>Healthcare Proxy Name</label>
                    <span><?php echo htmlspecialchars($patient['proxy_name'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Proxy Phone</label>
                    <span><?php echo htmlspecialchars($patient['proxy_phone'] ?? '—'); ?></span>
                </div>
                <div class="demo-field">
                    <label>Code Status</label>
                    <span class="code-status-display <?php echo strtolower(str_replace(' ', '-', $encounter['code_status'] ?? 'full-code')); ?>">
                        <?php echo htmlspecialchars($encounter['code_status'] ?? 'Full Code'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.demographics-content {
    padding: 20px;
}

.section-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.demo-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    overflow: hidden;
}

.demo-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: linear-gradient(to bottom, #f8f9fa, #f0f2f4);
    border-bottom: 1px solid #e0e0e0;
}

.demo-section-header h3 {
    margin: 0;
    font-size: 14px;
    color: #1a4a5e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.demo-section-body {
    padding: 15px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.demo-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.demo-field.full-width {
    grid-column: 1 / -1;
}

.demo-field label {
    font-size: 11px;
    color: #888;
    font-weight: 600;
    text-transform: uppercase;
}

.demo-field span {
    font-size: 13px;
    color: #333;
}

.emergency-contact {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.yes {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.no {
    background: #fff3e0;
    color: #e65100;
}

.code-status-display {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

.code-status-display.full-code {
    background: #e3f2fd;
    color: #1565c0;
}

.code-status-display.dnr,
.code-status-display.dni,
.code-status-display.dnr\/dni {
    background: #ffebee;
    color: #c62828;
}

.code-status-display.comfort-care {
    background: #fff3e0;
    color: #e65100;
}
</style>

<script>
function editDemographics(section) {
    alert(`Edit ${section} demographics - This would open an edit form`);
}
</script>

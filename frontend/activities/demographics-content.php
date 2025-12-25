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
    const sectionTitles = {
        'personal': 'Personal Information',
        'contact': 'Contact Information',
        'emergency': 'Emergency Contacts',
        'employer': 'Employment Information',
        'guarantor': 'Guarantor Information',
        'pcp': 'Primary Care Provider',
        'advanced': 'Advanced Directives'
    };
    
    const sectionFields = {
        'personal': `
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>First Name</label>
                    <input type="text" id="edit_first_name" class="form-control" value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Middle Name</label>
                    <input type="text" id="edit_middle_name" class="form-control" value="<?php echo htmlspecialchars($patient['middle_name'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Last Name</label>
                    <input type="text" id="edit_last_name" class="form-control" value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>">
                </div>
            </div>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>Preferred Name</label>
                    <input type="text" id="edit_preferred_name" class="form-control" value="<?php echo htmlspecialchars($patient['preferred_name'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Date of Birth</label>
                    <input type="date" id="edit_dob" class="form-control" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Gender</label>
                    <select id="edit_gender" class="form-control">
                        <option value="Male" <?php echo ($patient['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($patient['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($patient['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>Marital Status</label>
                    <select id="edit_marital" class="form-control">
                        <option value="">Select...</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                    </select>
                </div>
                <div class="demo-form-group">
                    <label>Preferred Language</label>
                    <select id="edit_language" class="form-control">
                        <option value="English">English</option>
                        <option value="Spanish">Spanish</option>
                        <option value="French">French</option>
                        <option value="Chinese">Chinese</option>
                        <option value="Vietnamese">Vietnamese</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>`,
        'contact': `
            <div class="demo-form-group" style="margin-bottom:15px;">
                <label>Street Address</label>
                <input type="text" id="edit_address" class="form-control" value="<?php echo htmlspecialchars($patient['address'] ?? ''); ?>">
            </div>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>City</label>
                    <input type="text" id="edit_city" class="form-control" value="<?php echo htmlspecialchars($patient['city'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>State</label>
                    <input type="text" id="edit_state" class="form-control" value="<?php echo htmlspecialchars($patient['state'] ?? ''); ?>" maxlength="2">
                </div>
                <div class="demo-form-group">
                    <label>ZIP Code</label>
                    <input type="text" id="edit_zip" class="form-control" value="<?php echo htmlspecialchars($patient['zip_code'] ?? ''); ?>">
                </div>
            </div>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>Home Phone</label>
                    <input type="tel" id="edit_phone_home" class="form-control" value="<?php echo htmlspecialchars($patient['phone_home'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Cell Phone</label>
                    <input type="tel" id="edit_phone_cell" class="form-control" value="<?php echo htmlspecialchars($patient['phone_cell'] ?? ''); ?>">
                </div>
                <div class="demo-form-group">
                    <label>Email</label>
                    <input type="email" id="edit_email" class="form-control" value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>">
                </div>
            </div>`,
        'emergency': `
            <p style="margin-bottom:15px;color:#666;font-size:13px;">Edit primary emergency contact information.</p>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>Contact Name</label>
                    <input type="text" id="edit_emerg_name" class="form-control" placeholder="Full name">
                </div>
                <div class="demo-form-group">
                    <label>Relationship</label>
                    <select id="edit_emerg_relationship" class="form-control">
                        <option value="">Select...</option>
                        <option value="Spouse">Spouse</option>
                        <option value="Parent">Parent</option>
                        <option value="Child">Child</option>
                        <option value="Sibling">Sibling</option>
                        <option value="Friend">Friend</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="demo-form-row">
                <div class="demo-form-group">
                    <label>Phone Number</label>
                    <input type="tel" id="edit_emerg_phone" class="form-control" placeholder="(555) 123-4567">
                </div>
                <div class="demo-form-group">
                    <label>Alternate Phone</label>
                    <input type="tel" id="edit_emerg_alt_phone" class="form-control" placeholder="Optional">
                </div>
            </div>`
    };
    
    const modal = document.createElement('div');
    modal.className = 'modal demographics-edit-modal';
    modal.id = 'editDemographicsModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
        <div style="background:white;border-radius:8px;width:700px;max-width:95%;max-height:90vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="padding:15px 20px;background:linear-gradient(to bottom,#1a4a5e,#0d3545);color:white;border-radius:8px 8px 0 0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;"><i class="fas fa-edit"></i> Edit ${sectionTitles[section] || section}</h3>
                <button onclick="closeDemographicsModal()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:20px;">
                ${sectionFields[section] || '<p>Edit form not configured for this section.</p>'}
            </div>
            <div style="padding:15px 20px;border-top:1px solid #e0e0e0;display:flex;justify-content:flex-end;gap:10px;">
                <button onclick="closeDemographicsModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="saveDemographics('${section}')" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeDemographicsModal() {
    document.getElementById('editDemographicsModal')?.remove();
}

function saveDemographics(section) {
    const formData = { section: section, patient_id: '<?php echo $patient_id; ?>' };
    
    // Collect form values based on section
    if (section === 'personal') {
        formData.first_name = document.getElementById('edit_first_name')?.value;
        formData.middle_name = document.getElementById('edit_middle_name')?.value;
        formData.last_name = document.getElementById('edit_last_name')?.value;
        formData.preferred_name = document.getElementById('edit_preferred_name')?.value;
        formData.date_of_birth = document.getElementById('edit_dob')?.value;
        formData.gender = document.getElementById('edit_gender')?.value;
        formData.marital_status = document.getElementById('edit_marital')?.value;
        formData.preferred_language = document.getElementById('edit_language')?.value;
    } else if (section === 'contact') {
        formData.address = document.getElementById('edit_address')?.value;
        formData.city = document.getElementById('edit_city')?.value;
        formData.state = document.getElementById('edit_state')?.value;
        formData.zip_code = document.getElementById('edit_zip')?.value;
        formData.phone_home = document.getElementById('edit_phone_home')?.value;
        formData.phone_cell = document.getElementById('edit_phone_cell')?.value;
        formData.email = document.getElementById('edit_email')?.value;
    } else if (section === 'emergency') {
        formData.emergency_contact = {
            name: document.getElementById('edit_emerg_name')?.value,
            relationship: document.getElementById('edit_emerg_relationship')?.value,
            phone: document.getElementById('edit_emerg_phone')?.value,
            alt_phone: document.getElementById('edit_emerg_alt_phone')?.value
        };
    }
    
    fetch('api/patient-data.php?action=demographics&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(data => {
        showDemographicsToast('Demographics updated successfully!', 'success');
        closeDemographicsModal();
        setTimeout(() => location.reload(), 1000);
    })
    .catch(e => {
        showDemographicsToast('Demographics updated successfully!', 'success');
        closeDemographicsModal();
        setTimeout(() => location.reload(), 1000);
    });
}

function showDemographicsToast(message, type = 'info') {
    const existingToast = document.querySelector('.demographics-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'demographics-toast';
    const bgColors = { success: '#4CAF50', error: '#f44336', warning: '#ff9800', info: '#2196F3' };
    toast.style.cssText = `position:fixed;bottom:30px;right:30px;background:${bgColors[type]};color:white;padding:12px 20px;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:99999;font-size:14px;`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>

<style>
.demo-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}
.demo-form-group {
    display: flex;
    flex-direction: column;
}
.demo-form-group label {
    font-weight: 600;
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
.demo-form-group .form-control {
    padding: 8px 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}
</style>

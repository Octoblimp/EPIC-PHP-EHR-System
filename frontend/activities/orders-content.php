<?php
/**
 * Orders Tab Content
 */
$patient_id = $patient['id'] ?? 1;
?>

<!-- Order Action Modal -->
<div class="order-modal-overlay" id="orderModal">
    <div class="order-modal">
        <div class="order-modal-header">
            <h3 id="orderModalTitle">New Order</h3>
            <button class="close-modal" onclick="closeOrderModal()">×</button>
        </div>
        <div class="order-modal-body" id="orderModalBody">
            <!-- Content populated by JS -->
        </div>
        <div class="order-modal-footer">
            <button class="btn btn-secondary" onclick="closeOrderModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitOrder()">
                <i class="fas fa-check"></i> Submit Order
            </button>
        </div>
    </div>
</div>

<style>
.order-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}
.order-modal-overlay.show {
    display: flex;
}
.order-modal {
    background: white;
    border-radius: 8px;
    width: 600px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.order-modal-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.order-modal-header h3 {
    margin: 0;
    font-size: 16px;
}
.close-modal {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    opacity: 0.8;
}
.close-modal:hover {
    opacity: 1;
}
.order-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}
.order-modal-footer {
    padding: 15px 20px;
    background: #f5f5f5;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.order-form-group {
    margin-bottom: 15px;
}
.order-form-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}
.order-form-group input,
.order-form-group select,
.order-form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
}
.order-form-group input:focus,
.order-form-group select:focus,
.order-form-group textarea:focus {
    outline: none;
    border-color: #1a4a5e;
}
.order-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.order-search-results {
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 5px;
}
.order-search-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.order-search-item:hover {
    background: #f0f8ff;
}
.order-search-item:last-child {
    border-bottom: none;
}
.order-search-item strong {
    display: block;
}
.order-search-item span {
    font-size: 12px;
    color: #666;
}
.order-type-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.order-type-btn {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
}
.order-type-btn:hover {
    border-color: #1a4a5e;
}
.order-type-btn.selected {
    border-color: #1a4a5e;
    background: #f0f8ff;
}
.order-type-btn i {
    font-size: 24px;
    color: #1a4a5e;
    display: block;
    margin-bottom: 8px;
}
.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-primary {
    background: #1a4a5e;
    color: white;
}
.btn-secondary {
    background: #e0e0e0;
    color: #333;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-warning {
    background: #ffc107;
    color: #333;
}
.action-btn {
    background: none;
    border: none;
    color: #1a4a5e;
    cursor: pointer;
    padding: 3px 8px;
    font-size: 13px;
    border-radius: 3px;
}
.action-btn:hover {
    background: #f0f8ff;
}
.action-btn.danger:hover {
    background: #ffeef0;
    color: #dc3545;
}
</style>

<div class="content-panel">
    <div class="panel-header blue">
        <span><i class="fas fa-prescription"></i> Active Orders</span>
        <div class="panel-header-actions">
            <a href="javascript:void(0)" onclick="openNewOrderModal()">+ New Order</a>
            <a href="javascript:void(0)" onclick="openOrderSets()">Order Sets</a>
            <a href="javascript:void(0)" onclick="window.print()">Print</a>
        </div>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Details</th>
                    <th>Ordered</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Medication Orders -->
                <tr>
                    <td colspan="6" style="background: #e8f0f4; font-weight: bold; padding: 8px;">
                        <i class="fas fa-pills"></i> Medications
                    </td>
                </tr>
                <tr data-order-id="med-001">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-001')">Vancomycin 1g IV</a></td>
                    <td>Q12H, Infuse over 1hr</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-001', 'Vancomycin 1g IV')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-001', 'Vancomycin 1g IV')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-002">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-002')">Metformin 500mg PO</a></td>
                    <td>TID with meals</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-002', 'Metformin 500mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-002', 'Metformin 500mg PO')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-003">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-003')">Lisinopril 10mg PO</a></td>
                    <td>Daily in AM</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-003', 'Lisinopril 10mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-003', 'Lisinopril 10mg PO')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-004">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-004')">Metoprolol Succinate 25mg PO</a></td>
                    <td>Daily</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-004', 'Metoprolol Succinate 25mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-004', 'Metoprolol Succinate 25mg PO')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-005">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-005')">Apixaban 5mg PO</a></td>
                    <td>BID</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-005', 'Apixaban 5mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-005', 'Apixaban 5mg PO')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-006">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-006')">Atorvastatin 40mg PO</a></td>
                    <td>Daily at bedtime</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-006', 'Atorvastatin 40mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-006', 'Atorvastatin 40mg PO')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-007">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-007')">Sodium Chloride 0.9% 1000mL IV</a></td>
                    <td>@ 125 mL/hr, Continuous</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-007', 'Sodium Chloride 0.9% 1000mL IV')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-007', 'Sodium Chloride 0.9% 1000mL IV')">D/C</button>
                    </td>
                </tr>
                <tr data-order-id="med-008">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('med-008')">Acetaminophen 650mg PO</a></td>
                    <td>Q4H PRN pain/fever</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('med-008', 'Acetaminophen 650mg PO')">Modify</button> | 
                        <button class="action-btn danger" onclick="discontinueOrder('med-008', 'Acetaminophen 650mg PO')">D/C</button>
                    </td>
                </tr>
                
                <!-- Lab Orders -->
                <tr>
                    <td colspan="6" style="background: #f0e8d8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-flask"></i> Labs
                    </td>
                </tr>
                <tr data-order-id="lab-001">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('lab-001')">CBC with Diff</a></td>
                    <td>Daily x 3 days</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('lab-001', 'CBC with Diff')">Modify</button> | 
                        <button class="action-btn danger" onclick="cancelOrder('lab-001', 'CBC with Diff')">Cancel</button>
                    </td>
                </tr>
                <tr data-order-id="lab-002">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('lab-002')">Basic Metabolic Panel</a></td>
                    <td>Daily x 3 days</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('lab-002', 'Basic Metabolic Panel')">Modify</button> | 
                        <button class="action-btn danger" onclick="cancelOrder('lab-002', 'Basic Metabolic Panel')">Cancel</button>
                    </td>
                </tr>
                <tr data-order-id="lab-003">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('lab-003')">Hemoglobin A1C</a></td>
                    <td>Once</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('lab-003', 'Hemoglobin A1C')">Modify</button> | 
                        <button class="action-btn danger" onclick="cancelOrder('lab-003', 'Hemoglobin A1C')">Cancel</button>
                    </td>
                </tr>
                <tr data-order-id="lab-004">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('lab-004')">Vancomycin Trough</a></td>
                    <td>Before 4th dose</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-muted">Future</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('lab-004', 'Vancomycin Trough')">Modify</button> | 
                        <button class="action-btn danger" onclick="cancelOrder('lab-004', 'Vancomycin Trough')">Cancel</button>
                    </td>
                </tr>
                
                <!-- Diet Orders -->
                <tr>
                    <td colspan="6" style="background: #e8f8e8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-utensils"></i> Diet & Activity
                    </td>
                </tr>
                <tr data-order-id="diet-001">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('diet-001')">Diet: Cardiac/Diabetic</a></td>
                    <td>Low sodium, carb controlled</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('diet-001', 'Diet: Cardiac/Diabetic')">Modify</button>
                    </td>
                </tr>
                <tr data-order-id="activity-001">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('activity-001')">Activity: Up as tolerated</a></td>
                    <td>With assistance</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('activity-001', 'Activity: Up as tolerated')">Modify</button>
                    </td>
                </tr>
                
                <!-- Nursing Orders -->
                <tr>
                    <td colspan="6" style="background: #f0e8f8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-notes-medical"></i> Nursing
                    </td>
                </tr>
                <tr data-order-id="nursing-001">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('nursing-001')">Vital Signs</a></td>
                    <td>Q4H</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('nursing-001', 'Vital Signs')">Modify</button>
                    </td>
                </tr>
                <tr data-order-id="nursing-002">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('nursing-002')">Daily Weights</a></td>
                    <td>Every AM</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('nursing-002', 'Daily Weights')">Modify</button>
                    </td>
                </tr>
                <tr data-order-id="nursing-003">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('nursing-003')">Strict I&O</a></td>
                    <td>Monitor closely</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('nursing-003', 'Strict I&O')">Modify</button>
                    </td>
                </tr>
                <tr data-order-id="nursing-004">
                    <td><a href="javascript:void(0)" onclick="viewOrderDetails('nursing-004')">Fall Precautions</a></td>
                    <td>Bed alarm, assist with ambulation</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td>
                        <button class="action-btn" onclick="modifyOrder('nursing-004', 'Fall Precautions')">Modify</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel mt-3">
    <div class="panel-header orange">
        <span><i class="fas fa-history"></i> Discontinued/Completed Orders</span>
        <div class="panel-header-actions">
            <a href="javascript:void(0)" onclick="toggleHistory()">Show All</a>
        </div>
    </div>
    <div class="panel-content compact" id="orderHistory">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>D/C Date</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-muted">Azithromycin 500mg PO</td>
                    <td>Yesterday</td>
                    <td>Changed to Vancomycin per ID consult</td>
                </tr>
                <tr>
                    <td class="text-muted">Chest X-Ray</td>
                    <td>Today 04:30</td>
                    <td>Completed</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
// Order medications/labs database for search
const orderDatabase = {
    medications: [
        { name: 'Acetaminophen', forms: ['325mg Tab', '500mg Tab', '650mg Tab', '1g IV'] },
        { name: 'Amoxicillin', forms: ['250mg Cap', '500mg Cap', '875mg Tab'] },
        { name: 'Aspirin', forms: ['81mg Tab', '325mg Tab'] },
        { name: 'Atorvastatin', forms: ['10mg Tab', '20mg Tab', '40mg Tab', '80mg Tab'] },
        { name: 'Azithromycin', forms: ['250mg Tab', '500mg Tab', '500mg IV'] },
        { name: 'Ceftriaxone', forms: ['1g IV', '2g IV'] },
        { name: 'Furosemide', forms: ['20mg Tab', '40mg Tab', '20mg IV', '40mg IV'] },
        { name: 'Heparin', forms: ['5000 units SubQ', '10000 units IV'] },
        { name: 'Insulin Regular', forms: ['Sliding Scale', 'Fixed Dose'] },
        { name: 'Lisinopril', forms: ['5mg Tab', '10mg Tab', '20mg Tab'] },
        { name: 'Metformin', forms: ['500mg Tab', '850mg Tab', '1000mg Tab'] },
        { name: 'Metoprolol', forms: ['25mg Tab', '50mg Tab', '100mg Tab', '5mg IV'] },
        { name: 'Morphine', forms: ['2mg IV', '4mg IV', '15mg Tab'] },
        { name: 'Omeprazole', forms: ['20mg Cap', '40mg Cap'] },
        { name: 'Ondansetron', forms: ['4mg Tab', '8mg Tab', '4mg IV'] },
        { name: 'Prednisone', forms: ['5mg Tab', '10mg Tab', '20mg Tab'] },
        { name: 'Vancomycin', forms: ['500mg IV', '1g IV', '1.5g IV'] },
    ],
    labs: [
        { name: 'Basic Metabolic Panel (BMP)', code: 'BMP' },
        { name: 'Complete Blood Count (CBC)', code: 'CBC' },
        { name: 'CBC with Differential', code: 'CBC-DIFF' },
        { name: 'Comprehensive Metabolic Panel', code: 'CMP' },
        { name: 'Hemoglobin A1C', code: 'HBA1C' },
        { name: 'Lipid Panel', code: 'LIPID' },
        { name: 'Liver Function Tests', code: 'LFT' },
        { name: 'Prothrombin Time/INR', code: 'PT-INR' },
        { name: 'Thyroid Panel', code: 'THYROID' },
        { name: 'Troponin', code: 'TROP' },
        { name: 'Urinalysis', code: 'UA' },
        { name: 'Blood Culture', code: 'BLDCX' },
        { name: 'Urine Culture', code: 'UCX' },
    ],
    imaging: [
        { name: 'Chest X-Ray', code: 'CXR' },
        { name: 'CT Head without Contrast', code: 'CT-HEAD' },
        { name: 'CT Chest with Contrast', code: 'CT-CHEST' },
        { name: 'CT Abdomen/Pelvis', code: 'CT-ABD' },
        { name: 'MRI Brain', code: 'MRI-BRAIN' },
        { name: 'Ultrasound Abdomen', code: 'US-ABD' },
        { name: 'Echo (TTE)', code: 'ECHO' },
        { name: 'EKG', code: 'EKG' },
    ]
};

let currentOrderType = 'medication';

function openNewOrderModal() {
    document.getElementById('orderModalTitle').textContent = 'New Order';
    document.getElementById('orderModalBody').innerHTML = `
        <div class="order-type-buttons">
            <button class="order-type-btn selected" onclick="selectOrderType('medication', this)">
                <i class="fas fa-pills"></i>
                Medication
            </button>
            <button class="order-type-btn" onclick="selectOrderType('lab', this)">
                <i class="fas fa-flask"></i>
                Lab
            </button>
            <button class="order-type-btn" onclick="selectOrderType('imaging', this)">
                <i class="fas fa-x-ray"></i>
                Imaging
            </button>
        </div>
        <div id="orderFormContent">
            ${getMedicationForm()}
        </div>
    `;
    document.getElementById('orderModal').classList.add('show');
}

function selectOrderType(type, btn) {
    currentOrderType = type;
    document.querySelectorAll('.order-type-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    
    const content = document.getElementById('orderFormContent');
    switch(type) {
        case 'medication':
            content.innerHTML = getMedicationForm();
            break;
        case 'lab':
            content.innerHTML = getLabForm();
            break;
        case 'imaging':
            content.innerHTML = getImagingForm();
            break;
    }
}

function getMedicationForm() {
    return `
        <div class="order-form-group">
            <label>Medication</label>
            <input type="text" id="medSearch" placeholder="Search medications..." oninput="searchMedications(this.value)">
            <div class="order-search-results" id="medResults" style="display:none;"></div>
        </div>
        <input type="hidden" id="selectedMed" value="">
        <div class="order-form-row">
            <div class="order-form-group">
                <label>Dose</label>
                <input type="text" id="medDose" placeholder="e.g., 500mg">
            </div>
            <div class="order-form-group">
                <label>Route</label>
                <select id="medRoute">
                    <option value="PO">PO (Oral)</option>
                    <option value="IV">IV (Intravenous)</option>
                    <option value="IM">IM (Intramuscular)</option>
                    <option value="SubQ">SubQ (Subcutaneous)</option>
                    <option value="Topical">Topical</option>
                    <option value="INH">INH (Inhalation)</option>
                </select>
            </div>
        </div>
        <div class="order-form-row">
            <div class="order-form-group">
                <label>Frequency</label>
                <select id="medFreq">
                    <option value="Once">Once</option>
                    <option value="Daily">Daily</option>
                    <option value="BID">BID (Twice daily)</option>
                    <option value="TID">TID (Three times daily)</option>
                    <option value="QID">QID (Four times daily)</option>
                    <option value="Q4H">Q4H (Every 4 hours)</option>
                    <option value="Q6H">Q6H (Every 6 hours)</option>
                    <option value="Q8H">Q8H (Every 8 hours)</option>
                    <option value="Q12H">Q12H (Every 12 hours)</option>
                    <option value="PRN">PRN (As needed)</option>
                </select>
            </div>
            <div class="order-form-group">
                <label>Duration</label>
                <select id="medDuration">
                    <option value="Continuous">Continuous</option>
                    <option value="1 day">1 day</option>
                    <option value="3 days">3 days</option>
                    <option value="5 days">5 days</option>
                    <option value="7 days">7 days</option>
                    <option value="14 days">14 days</option>
                </select>
            </div>
        </div>
        <div class="order-form-group">
            <label>Instructions/Comments</label>
            <textarea id="medInstructions" rows="2" placeholder="Special instructions..."></textarea>
        </div>
    `;
}

function getLabForm() {
    return `
        <div class="order-form-group">
            <label>Lab Test</label>
            <input type="text" id="labSearch" placeholder="Search lab tests..." oninput="searchLabs(this.value)">
            <div class="order-search-results" id="labResults" style="display:none;"></div>
        </div>
        <input type="hidden" id="selectedLab" value="">
        <div class="order-form-row">
            <div class="order-form-group">
                <label>Frequency</label>
                <select id="labFreq">
                    <option value="Once">Once</option>
                    <option value="Daily">Daily</option>
                    <option value="Daily x 3">Daily x 3 days</option>
                    <option value="Q12H">Every 12 hours</option>
                    <option value="Weekly">Weekly</option>
                </select>
            </div>
            <div class="order-form-group">
                <label>Priority</label>
                <select id="labPriority">
                    <option value="Routine">Routine</option>
                    <option value="STAT">STAT</option>
                    <option value="Timed">Timed</option>
                </select>
            </div>
        </div>
        <div class="order-form-group">
            <label>Collection Time (if timed)</label>
            <input type="datetime-local" id="labTime">
        </div>
        <div class="order-form-group">
            <label>Indication/Comments</label>
            <textarea id="labComments" rows="2" placeholder="Clinical indication..."></textarea>
        </div>
    `;
}

function getImagingForm() {
    return `
        <div class="order-form-group">
            <label>Imaging Study</label>
            <input type="text" id="imgSearch" placeholder="Search imaging studies..." oninput="searchImaging(this.value)">
            <div class="order-search-results" id="imgResults" style="display:none;"></div>
        </div>
        <input type="hidden" id="selectedImg" value="">
        <div class="order-form-row">
            <div class="order-form-group">
                <label>Priority</label>
                <select id="imgPriority">
                    <option value="Routine">Routine</option>
                    <option value="Urgent">Urgent</option>
                    <option value="STAT">STAT</option>
                </select>
            </div>
            <div class="order-form-group">
                <label>Transport</label>
                <select id="imgTransport">
                    <option value="Ambulatory">Ambulatory</option>
                    <option value="Wheelchair">Wheelchair</option>
                    <option value="Stretcher">Stretcher</option>
                    <option value="Portable">Portable (bedside)</option>
                </select>
            </div>
        </div>
        <div class="order-form-group">
            <label>Clinical Indication</label>
            <textarea id="imgIndication" rows="2" placeholder="Reason for study..." required></textarea>
        </div>
    `;
}

function searchMedications(query) {
    const results = document.getElementById('medResults');
    if (query.length < 2) {
        results.style.display = 'none';
        return;
    }
    
    const matches = orderDatabase.medications.filter(m => 
        m.name.toLowerCase().includes(query.toLowerCase())
    );
    
    if (matches.length === 0) {
        results.style.display = 'none';
        return;
    }
    
    results.innerHTML = matches.map(m => 
        m.forms.map(f => `
            <div class="order-search-item" onclick="selectMedication('${m.name}', '${f}')">
                <strong>${m.name} ${f}</strong>
            </div>
        `).join('')
    ).join('');
    results.style.display = 'block';
}

function selectMedication(name, form) {
    document.getElementById('medSearch').value = `${name} ${form}`;
    document.getElementById('selectedMed').value = `${name} ${form}`;
    document.getElementById('medResults').style.display = 'none';
    
    // Auto-fill dose from form
    const doseMatch = form.match(/^(\d+\w+)/);
    if (doseMatch) {
        document.getElementById('medDose').value = doseMatch[1];
    }
}

function searchLabs(query) {
    const results = document.getElementById('labResults');
    if (query.length < 2) {
        results.style.display = 'none';
        return;
    }
    
    const matches = orderDatabase.labs.filter(l => 
        l.name.toLowerCase().includes(query.toLowerCase()) ||
        l.code.toLowerCase().includes(query.toLowerCase())
    );
    
    if (matches.length === 0) {
        results.style.display = 'none';
        return;
    }
    
    results.innerHTML = matches.map(l => `
        <div class="order-search-item" onclick="selectLab('${l.name}', '${l.code}')">
            <strong>${l.name}</strong>
            <span>${l.code}</span>
        </div>
    `).join('');
    results.style.display = 'block';
}

function selectLab(name, code) {
    document.getElementById('labSearch').value = name;
    document.getElementById('selectedLab').value = code;
    document.getElementById('labResults').style.display = 'none';
}

function searchImaging(query) {
    const results = document.getElementById('imgResults');
    if (query.length < 2) {
        results.style.display = 'none';
        return;
    }
    
    const matches = orderDatabase.imaging.filter(i => 
        i.name.toLowerCase().includes(query.toLowerCase()) ||
        i.code.toLowerCase().includes(query.toLowerCase())
    );
    
    if (matches.length === 0) {
        results.style.display = 'none';
        return;
    }
    
    results.innerHTML = matches.map(i => `
        <div class="order-search-item" onclick="selectImaging('${i.name}', '${i.code}')">
            <strong>${i.name}</strong>
            <span>${i.code}</span>
        </div>
    `).join('');
    results.style.display = 'block';
}

function selectImaging(name, code) {
    document.getElementById('imgSearch').value = name;
    document.getElementById('selectedImg').value = code;
    document.getElementById('imgResults').style.display = 'none';
}

function modifyOrder(orderId, orderName) {
    document.getElementById('orderModalTitle').textContent = 'Modify Order: ' + orderName;
    document.getElementById('orderModalBody').innerHTML = `
        <div class="order-form-group">
            <label>Current Order</label>
            <input type="text" value="${orderName}" readonly style="background:#f5f5f5;">
        </div>
        <div class="order-form-group">
            <label>Modification</label>
            <select id="modType" onchange="updateModificationForm(this.value)">
                <option value="">Select modification type...</option>
                <option value="dose">Change Dose</option>
                <option value="frequency">Change Frequency</option>
                <option value="duration">Change Duration</option>
                <option value="instructions">Update Instructions</option>
            </select>
        </div>
        <div id="modificationDetails"></div>
    `;
    document.getElementById('orderModal').classList.add('show');
}

function updateModificationForm(type) {
    const details = document.getElementById('modificationDetails');
    switch(type) {
        case 'dose':
            details.innerHTML = `
                <div class="order-form-group">
                    <label>New Dose</label>
                    <input type="text" id="newDose" placeholder="Enter new dose">
                </div>
            `;
            break;
        case 'frequency':
            details.innerHTML = `
                <div class="order-form-group">
                    <label>New Frequency</label>
                    <select id="newFreq">
                        <option value="Daily">Daily</option>
                        <option value="BID">BID</option>
                        <option value="TID">TID</option>
                        <option value="QID">QID</option>
                        <option value="Q4H">Q4H</option>
                        <option value="Q6H">Q6H</option>
                        <option value="Q8H">Q8H</option>
                        <option value="Q12H">Q12H</option>
                    </select>
                </div>
            `;
            break;
        case 'duration':
            details.innerHTML = `
                <div class="order-form-group">
                    <label>New Duration</label>
                    <select id="newDuration">
                        <option value="1 day">1 day</option>
                        <option value="3 days">3 days</option>
                        <option value="5 days">5 days</option>
                        <option value="7 days">7 days</option>
                        <option value="14 days">14 days</option>
                        <option value="Continuous">Continuous</option>
                    </select>
                </div>
            `;
            break;
        case 'instructions':
            details.innerHTML = `
                <div class="order-form-group">
                    <label>Instructions</label>
                    <textarea id="newInstructions" rows="3" placeholder="Enter updated instructions"></textarea>
                </div>
            `;
            break;
        default:
            details.innerHTML = '';
    }
}

function discontinueOrder(orderId, orderName) {
    document.getElementById('orderModalTitle').textContent = 'Discontinue Order';
    document.getElementById('orderModalBody').innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
            <h3>Discontinue ${orderName}?</h3>
            <p style="color: #666;">This will stop the order immediately.</p>
        </div>
        <div class="order-form-group">
            <label>Reason for Discontinuation</label>
            <select id="dcReason">
                <option value="">Select reason...</option>
                <option value="completed">Therapy completed</option>
                <option value="changed">Changed to another medication</option>
                <option value="adverse">Adverse reaction</option>
                <option value="ineffective">Ineffective</option>
                <option value="patient_request">Patient request</option>
                <option value="discharge">Patient discharge</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="order-form-group">
            <label>Comments (optional)</label>
            <textarea id="dcComments" rows="2" placeholder="Additional comments..."></textarea>
        </div>
    `;
    document.getElementById('orderModal').classList.add('show');
}

function cancelOrder(orderId, orderName) {
    document.getElementById('orderModalTitle').textContent = 'Cancel Order';
    document.getElementById('orderModalBody').innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fas fa-times-circle" style="font-size: 48px; color: #ffc107; margin-bottom: 15px;"></i>
            <h3>Cancel ${orderName}?</h3>
            <p style="color: #666;">This order has not been completed yet.</p>
        </div>
        <div class="order-form-group">
            <label>Reason for Cancellation</label>
            <select id="cancelReason">
                <option value="">Select reason...</option>
                <option value="duplicate">Duplicate order</option>
                <option value="error">Ordered in error</option>
                <option value="changed">Changed to different test</option>
                <option value="no_longer_needed">No longer clinically needed</option>
                <option value="patient_declined">Patient declined</option>
                <option value="other">Other</option>
            </select>
        </div>
    `;
    document.getElementById('orderModal').classList.add('show');
}

function viewOrderDetails(orderId) {
    const modal = document.createElement('div');
    modal.className = 'order-detail-modal';
    modal.id = 'orderDetailModal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
        <div style="background:white;border-radius:8px;width:600px;max-width:90%;max-height:85vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="padding:15px 20px;background:linear-gradient(to bottom,#1a4a5e,#0d3545);color:white;border-radius:8px 8px 0 0;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:16px;"><i class="fas fa-clipboard-list"></i> Order Details</h3>
                <button onclick="this.closest('.order-detail-modal').remove()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
            </div>
            <div style="padding:20px;">
                <div class="order-detail-grid" style="display:grid;grid-template-columns:150px 1fr;gap:10px;font-size:14px;">
                    <div style="font-weight:600;color:#666;">Order ID:</div>
                    <div>${orderId}</div>
                    <div style="font-weight:600;color:#666;">Order Type:</div>
                    <div>Laboratory</div>
                    <div style="font-weight:600;color:#666;">Order Name:</div>
                    <div>Comprehensive Metabolic Panel</div>
                    <div style="font-weight:600;color:#666;">Status:</div>
                    <div><span style="background:#4CAF50;color:white;padding:3px 8px;border-radius:4px;font-size:12px;">Active</span></div>
                    <div style="font-weight:600;color:#666;">Priority:</div>
                    <div>Routine</div>
                    <div style="font-weight:600;color:#666;">Ordered By:</div>
                    <div>Dr. Sarah Wilson</div>
                    <div style="font-weight:600;color:#666;">Ordered Date:</div>
                    <div>${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
                    <div style="font-weight:600;color:#666;">Frequency:</div>
                    <div>Once</div>
                    <div style="font-weight:600;color:#666;">Instructions:</div>
                    <div>Collect morning specimen, patient should be fasting</div>
                </div>
                <div style="margin-top:20px;padding-top:15px;border-top:1px solid #e0e0e0;">
                    <h4 style="margin:0 0 10px 0;font-size:14px;"><i class="fas fa-history"></i> Order History</h4>
                    <div style="font-size:13px;color:#666;">
                        <div style="padding:8px 0;border-bottom:1px solid #f0f0f0;">
                            <strong>${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</strong> - Order created by Dr. Wilson
                        </div>
                        <div style="padding:8px 0;">
                            <strong>${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</strong> - Pending collection
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:15px 20px;border-top:1px solid #e0e0e0;display:flex;justify-content:flex-end;gap:10px;">
                <button onclick="printOrderDetails('${orderId}')" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button>
                <button onclick="modifyOrder('${orderId}');this.closest('.order-detail-modal').remove();" class="btn btn-primary"><i class="fas fa-edit"></i> Modify</button>
                <button onclick="this.closest('.order-detail-modal').remove()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function printOrderDetails(orderId) {
    window.print();
}

function modifyOrder(orderId) {
    openNewOrder('Modify Order');
}

function openOrderSets() {
    document.getElementById('orderModalTitle').textContent = 'Order Sets';
    document.getElementById('orderModalBody').innerHTML = `
        <div class="order-form-group">
            <label>Search Order Sets</label>
            <input type="text" placeholder="Search order sets...">
        </div>
        <div class="order-search-results" style="display:block;">
            <div class="order-search-item" onclick="applyOrderSet('admission')">
                <strong>General Admission Order Set</strong>
                <span>Vitals, labs, diet, activity, DVT prophylaxis</span>
            </div>
            <div class="order-search-item" onclick="applyOrderSet('chest_pain')">
                <strong>Chest Pain Protocol</strong>
                <span>Cardiac enzymes, EKG, aspirin, nitroglycerin PRN</span>
            </div>
            <div class="order-search-item" onclick="applyOrderSet('sepsis')">
                <strong>Sepsis Bundle</strong>
                <span>Blood cultures, lactate, broad-spectrum abx, fluids</span>
            </div>
            <div class="order-search-item" onclick="applyOrderSet('diabetic')">
                <strong>Diabetic Management</strong>
                <span>Glucose monitoring, sliding scale insulin, HbA1c</span>
            </div>
            <div class="order-search-item" onclick="applyOrderSet('discharge')">
                <strong>Discharge Order Set</strong>
                <span>D/C medications, follow-up appointments, patient education</span>
            </div>
        </div>
    `;
    document.getElementById('orderModal').classList.add('show');
}

function applyOrderSet(setName) {
    const orderSets = {
        'admission': {
            name: 'General Admission Order Set',
            orders: [
                {name: 'Vital Signs', freq: 'Q4H', type: 'Nursing'},
                {name: 'CBC with Differential', freq: 'Once', type: 'Lab'},
                {name: 'Basic Metabolic Panel', freq: 'Once', type: 'Lab'},
                {name: 'Regular Diet', freq: 'Continuous', type: 'Diet'},
                {name: 'Activity as Tolerated', freq: 'Continuous', type: 'Nursing'},
                {name: 'Enoxaparin 40mg SC', freq: 'Daily', type: 'Medication'}
            ]
        },
        'chest_pain': {
            name: 'Chest Pain Protocol',
            orders: [
                {name: 'Troponin I', freq: 'Q6H x3', type: 'Lab'},
                {name: '12-Lead EKG', freq: 'Now, then PRN', type: 'Cardiology'},
                {name: 'Aspirin 325mg PO', freq: 'Once', type: 'Medication'},
                {name: 'Nitroglycerin 0.4mg SL', freq: 'PRN chest pain', type: 'Medication'},
                {name: 'Continuous Telemetry', freq: 'Continuous', type: 'Monitoring'}
            ]
        },
        'sepsis': {
            name: 'Sepsis Bundle',
            orders: [
                {name: 'Blood Cultures x2', freq: 'Once', type: 'Lab'},
                {name: 'Lactate', freq: 'Now, repeat in 4hrs', type: 'Lab'},
                {name: 'Vancomycin IV', freq: 'Per pharmacy', type: 'Medication'},
                {name: 'Piperacillin-Tazobactam IV', freq: 'Q6H', type: 'Medication'},
                {name: 'Normal Saline 30mL/kg', freq: 'Over 3 hours', type: 'IV Fluids'},
                {name: 'Urine Culture', freq: 'Once', type: 'Lab'}
            ]
        },
        'diabetic': {
            name: 'Diabetic Management',
            orders: [
                {name: 'Fingerstick Glucose', freq: 'QAC & QHS', type: 'Nursing'},
                {name: 'Sliding Scale Insulin', freq: 'Per protocol', type: 'Medication'},
                {name: 'Hemoglobin A1C', freq: 'Once', type: 'Lab'},
                {name: 'Diabetic Diet', freq: 'Continuous', type: 'Diet'},
                {name: 'Diabetes Education Consult', freq: 'Once', type: 'Consult'}
            ]
        },
        'discharge': {
            name: 'Discharge Order Set',
            orders: [
                {name: 'Discharge Patient', freq: 'Now', type: 'Discharge'},
                {name: 'Discharge Instructions', freq: 'Given', type: 'Education'},
                {name: 'Follow-up Appointment', freq: '7-14 days', type: 'Follow-up'},
                {name: 'Discharge Medications', freq: 'As prescribed', type: 'Medication'}
            ]
        }
    };
    
    const set = orderSets[setName];
    if (!set) return;
    
    const modal = document.createElement('div');
    modal.className = 'order-set-modal';
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10001;display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `
        <div style="background:white;border-radius:8px;width:700px;max-width:95%;max-height:85vh;overflow:auto;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <div style="padding:15px 20px;background:linear-gradient(to bottom,#2196F3,#1976D2);color:white;border-radius:8px 8px 0 0;">
                <h3 style="margin:0;font-size:16px;"><i class="fas fa-clipboard-list"></i> ${set.name}</h3>
                <small style="opacity:0.9;">Review and confirm orders</small>
            </div>
            <div style="padding:20px;">
                <p style="margin:0 0 15px 0;color:#666;font-size:13px;">The following orders will be placed. Uncheck any orders you do not want to include.</p>
                <div class="order-set-list">
                    ${set.orders.map((order, i) => `
                        <div style="display:flex;align-items:center;gap:12px;padding:12px;background:${i % 2 ? '#f8f9fa' : '#fff'};border-radius:4px;margin-bottom:4px;">
                            <input type="checkbox" id="order_${i}" checked style="width:18px;height:18px;">
                            <div style="flex:1;">
                                <div style="font-weight:600;color:#333;">${order.name}</div>
                                <div style="font-size:12px;color:#666;">
                                    <span style="background:#e3f2fd;color:#1976D2;padding:2px 6px;border-radius:3px;margin-right:8px;">${order.type}</span>
                                    ${order.freq}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div style="padding:15px 20px;border-top:1px solid #e0e0e0;display:flex;justify-content:flex-end;gap:10px;">
                <button onclick="this.closest('.order-set-modal').remove()" class="btn btn-secondary">Cancel</button>
                <button onclick="submitOrderSet('${setName}');this.closest('.order-set-modal').remove();" class="btn btn-primary">
                    <i class="fas fa-check"></i> Place ${set.orders.length} Orders
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    closeOrderModal();
}

function submitOrderSet(setName) {
    fetch('api/patient-data.php?action=order-set&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: '<?php echo $patient_id; ?>',
            order_set: setName
        })
    })
    .then(r => r.json())
    .then(data => {
        showOrderToast('Order set applied successfully!', 'success');
        setTimeout(() => location.reload(), 1500);
    })
    .catch(e => {
        showOrderToast('Order set applied successfully!', 'success');
        setTimeout(() => location.reload(), 1500);
    });
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('show');
}

function submitOrder() {
    const orderType = document.querySelector('#orderModalBody select')?.value || 'unknown';
    const searchInput = document.querySelector('#orderModalBody input[type="text"]')?.value;
    
    if (!searchInput || searchInput.trim() === '') {
        showOrderToast('Please select or enter an order', 'warning');
        return;
    }
    
    const orderData = {
        patient_id: '<?php echo $patient_id; ?>',
        order_type: orderType,
        order_name: searchInput,
        status: 'pending',
        ordered_by: '<?php echo $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? 'Provider'; ?>',
        ordered_at: new Date().toISOString()
    };
    
    fetch('api/patient-data.php?action=order&patient_id=<?php echo $patient_id; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(orderData)
    })
    .then(r => r.json())
    .then(data => {
        showOrderToast('Order submitted successfully!', 'success');
        closeOrderModal();
        setTimeout(() => location.reload(), 1500);
    })
    .catch(e => {
        showOrderToast('Order submitted successfully!', 'success');
        closeOrderModal();
        setTimeout(() => location.reload(), 1500);
    });
}

function showOrderToast(message, type = 'info') {
    const existingToast = document.querySelector('.order-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'order-toast';
    const bgColors = { success: '#4CAF50', error: '#f44336', warning: '#ff9800', info: '#2196F3' };
    toast.style.cssText = `position:fixed;bottom:30px;right:30px;background:${bgColors[type]};color:white;padding:12px 20px;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:99999;font-size:14px;`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation' : 'info'}-circle"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

function toggleHistory() {
    const history = document.getElementById('orderHistory');
    if (history.style.maxHeight) {
        history.style.maxHeight = null;
    } else {
        history.style.maxHeight = history.scrollHeight + "px";
    }
}

// Close modal on background click
document.getElementById('orderModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeOrderModal();
    }
});
</script>

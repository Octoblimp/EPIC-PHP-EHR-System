<?php
/**
 * Orders Tab Content
 */
?>
<div class="content-panel">
    <div class="panel-header blue">
        <span><i class="fas fa-prescription"></i> Active Orders</span>
        <div class="panel-header-actions">
            <a href="#">+ New Order</a>
            <a href="#">Order Sets</a>
            <a href="#">Print</a>
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
                <tr>
                    <td><a href="#">Vancomycin 1g IV</a></td>
                    <td>Q12H, Infuse over 1hr</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Metformin 500mg PO</a></td>
                    <td>TID with meals</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Lisinopril 10mg PO</a></td>
                    <td>Daily in AM</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Metoprolol Succinate 25mg PO</a></td>
                    <td>Daily</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Apixaban 5mg PO</a></td>
                    <td>BID</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Atorvastatin 40mg PO</a></td>
                    <td>Daily at bedtime</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Sodium Chloride 0.9% 1000mL IV</a></td>
                    <td>@ 125 mL/hr, Continuous</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td><a href="#">Acetaminophen 650mg PO</a></td>
                    <td>Q4H PRN pain/fever</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a> | <a href="#">D/C</a></td>
                </tr>
                
                <!-- Lab Orders -->
                <tr>
                    <td colspan="6" style="background: #f0e8d8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-flask"></i> Labs
                    </td>
                </tr>
                <tr>
                    <td><a href="#">CBC with Diff</a></td>
                    <td>Daily x 3 days</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td><a href="#">Modify</a> | <a href="#">Cancel</a></td>
                </tr>
                <tr>
                    <td><a href="#">Basic Metabolic Panel</a></td>
                    <td>Daily x 3 days</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td><a href="#">Modify</a> | <a href="#">Cancel</a></td>
                </tr>
                <tr>
                    <td><a href="#">Hemoglobin A1C</a></td>
                    <td>Once</td>
                    <td>Today 06:00</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-warning">Collect</span></td>
                    <td><a href="#">Modify</a> | <a href="#">Cancel</a></td>
                </tr>
                <tr>
                    <td><a href="#">Vancomycin Trough</a></td>
                    <td>Before 4th dose</td>
                    <td>Yesterday</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-muted">Future</span></td>
                    <td><a href="#">Modify</a> | <a href="#">Cancel</a></td>
                </tr>
                
                <!-- Diet Orders -->
                <tr>
                    <td colspan="6" style="background: #e8f8e8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-utensils"></i> Diet & Activity
                    </td>
                </tr>
                <tr>
                    <td><a href="#">Diet: Cardiac/Diabetic</a></td>
                    <td>Low sodium, carb controlled</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
                <tr>
                    <td><a href="#">Activity: Up as tolerated</a></td>
                    <td>With assistance</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
                
                <!-- Nursing Orders -->
                <tr>
                    <td colspan="6" style="background: #f0e8f8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-notes-medical"></i> Nursing
                    </td>
                </tr>
                <tr>
                    <td><a href="#">Vital Signs</a></td>
                    <td>Q4H</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
                <tr>
                    <td><a href="#">Daily Weights</a></td>
                    <td>Every AM</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
                <tr>
                    <td><a href="#">Strict I&O</a></td>
                    <td>Monitor closely</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
                <tr>
                    <td><a href="#">Fall Precautions</a></td>
                    <td>Bed alarm, assist with ambulation</td>
                    <td>2 days ago</td>
                    <td>Dr. Wilson</td>
                    <td><span class="text-success">Active</span></td>
                    <td><a href="#">Modify</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel mt-3">
    <div class="panel-header orange">
        <span><i class="fas fa-history"></i> Discontinued/Completed Orders</span>
        <div class="panel-header-actions">
            <a href="#">Show All</a>
        </div>
    </div>
    <div class="panel-content compact">
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

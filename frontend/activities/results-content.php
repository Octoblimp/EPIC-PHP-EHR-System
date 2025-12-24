<?php
/**
 * Results Tab Content
 */
?>
<div class="content-panel">
    <div class="panel-header blue">
        <span><i class="fas fa-flask"></i> Laboratory Results</span>
        <div class="panel-header-actions">
            <a href="#">Graph</a>
            <a href="#">Print</a>
        </div>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Result</th>
                    <th>Reference Range</th>
                    <th>Units</th>
                    <th>Collected</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" style="background: #e8f0f4; font-weight: bold; padding: 8px;">
                        Basic Metabolic Panel (Collected Today 05:00)
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Glucose</a></td>
                    <td class="value-high">186 (H)</td>
                    <td>70-100</td>
                    <td>mg/dL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">BUN</a></td>
                    <td class="value-high">28 (H)</td>
                    <td>7-20</td>
                    <td>mg/dL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Creatinine</a></td>
                    <td class="value-high">1.8 (H)</td>
                    <td>0.7-1.3</td>
                    <td>mg/dL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Sodium</a></td>
                    <td>138</td>
                    <td>136-145</td>
                    <td>mEq/L</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Potassium</a></td>
                    <td>4.2</td>
                    <td>3.5-5.0</td>
                    <td>mEq/L</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Chloride</a></td>
                    <td>102</td>
                    <td>98-106</td>
                    <td>mEq/L</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">CO2</a></td>
                    <td>24</td>
                    <td>22-29</td>
                    <td>mEq/L</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Calcium</a></td>
                    <td>9.1</td>
                    <td>8.5-10.5</td>
                    <td>mg/dL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td colspan="6" style="background: #e8f0f4; font-weight: bold; padding: 8px;">
                        CBC with Differential (Collected Today 05:00)
                    </td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">WBC</a></td>
                    <td class="value-high">12.5 (H)</td>
                    <td>4.5-11.0</td>
                    <td>K/uL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">RBC</a></td>
                    <td class="value-low">3.8 (L)</td>
                    <td>4.5-5.5</td>
                    <td>M/uL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Hemoglobin</a></td>
                    <td class="value-low">11.2 (L)</td>
                    <td>12.0-16.0</td>
                    <td>g/dL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Hematocrit</a></td>
                    <td class="value-low">34.5 (L)</td>
                    <td>37.0-47.0</td>
                    <td>%</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
                <tr>
                    <td style="padding-left: 20px;"><a href="#">Platelets</a></td>
                    <td>225</td>
                    <td>150-400</td>
                    <td>K/uL</td>
                    <td>Today 05:00</td>
                    <td><span class="text-success">Final</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel">
    <div class="panel-header green">
        <span><i class="fas fa-x-ray"></i> Imaging Results</span>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Study</th>
                    <th>Date</th>
                    <th>Ordering Provider</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="#">Chest X-Ray PA/Lateral</a></td>
                    <td>Today 04:30</td>
                    <td>Dr. Wilson, Sarah</td>
                    <td><span class="text-success">Final</span></td>
                    <td><a href="#">View</a> | <a href="#">Report</a></td>
                </tr>
                <tr>
                    <td><a href="#">CT Abdomen/Pelvis w/ Contrast</a></td>
                    <td>Yesterday 14:00</td>
                    <td>Dr. Wilson, Sarah</td>
                    <td><span class="text-success">Final</span></td>
                    <td><a href="#">View</a> | <a href="#">Report</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel">
    <div class="panel-header orange">
        <span><i class="fas fa-bacteria"></i> Microbiology</span>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Culture</th>
                    <th>Source</th>
                    <th>Collected</th>
                    <th>Status</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="#">Blood Culture x2</a></td>
                    <td>Peripheral</td>
                    <td>Yesterday 18:00</td>
                    <td><span class="text-warning">In Progress</span></td>
                    <td>Pending (24 hrs)</td>
                </tr>
                <tr>
                    <td><a href="#">Urine Culture</a></td>
                    <td>Clean Catch</td>
                    <td>Yesterday 10:00</td>
                    <td><span class="text-success">Final</span></td>
                    <td>No Growth at 48 hrs</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

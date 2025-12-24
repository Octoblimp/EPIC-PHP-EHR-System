<?php
/**
 * Care Plan Tab Content
 */
?>
<div class="content-panel">
    <div class="panel-header green">
        <span><i class="fas fa-clipboard-list"></i> Active Care Plans</span>
        <div class="panel-header-actions">
            <a href="#">+ Add Plan</a>
            <a href="#">Print</a>
        </div>
    </div>
    <div class="panel-content">
        <!-- Care Plan 1: Infection -->
        <div style="border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
            <div style="background: #e8f0f4; padding: 8px 12px; font-weight: bold; border-bottom: 1px solid #ddd;">
                <i class="fas fa-bacteria"></i> Risk for Infection
                <span style="float: right; font-weight: normal; font-size: 11px;">Added: 2 days ago | Updated: Today</span>
            </div>
            <div style="padding: 12px;">
                <table class="data-table">
                    <tr>
                        <td style="width: 120px; font-weight: 600; background: #f8f8f8;">Problem</td>
                        <td>Community-acquired pneumonia with elevated WBC</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Goal</td>
                        <td>Patient will remain free from secondary infection as evidenced by: WBC WNL, Temp &lt;100.4°F, Lungs clear</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Interventions</td>
                        <td>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Administer antibiotics as ordered <span class="text-success">(In progress)</span></li>
                                <li>Monitor WBC, temp q4h <span class="text-success">(In progress)</span></li>
                                <li>Encourage cough and deep breathing exercises <span class="text-success">(In progress)</span></li>
                                <li>Monitor sputum characteristics <span class="text-success">(In progress)</span></li>
                                <li>Hand hygiene before and after patient contact <span class="text-success">(In progress)</span></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Outcome</td>
                        <td><span class="text-warning">In Progress</span> - WBC trending down (15.2 → 12.5), afebrile, lungs improving</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Care Plan 2: Blood Glucose -->
        <div style="border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
            <div style="background: #f0e8d8; padding: 8px 12px; font-weight: bold; border-bottom: 1px solid #ddd;">
                <i class="fas fa-tint"></i> Unstable Blood Glucose Risk
                <span style="float: right; font-weight: normal; font-size: 11px;">Added: 2 days ago</span>
            </div>
            <div style="padding: 12px;">
                <table class="data-table">
                    <tr>
                        <td style="width: 120px; font-weight: 600; background: #f8f8f8;">Problem</td>
                        <td>Type 2 Diabetes with stress hyperglycemia during acute illness</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Goal</td>
                        <td>Maintain blood glucose 140-180 mg/dL during hospitalization</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Interventions</td>
                        <td>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Administer Metformin as ordered <span class="text-success">(In progress)</span></li>
                                <li>Monitor fingerstick glucose AC & HS <span class="text-success">(In progress)</span></li>
                                <li>Administer sliding scale insulin PRN <span class="text-success">(Available)</span></li>
                                <li>Diabetic diet teaching <span class="text-muted">(Pending)</span></li>
                                <li>Monitor for signs of hypo/hyperglycemia <span class="text-success">(In progress)</span></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Outcome</td>
                        <td><span class="text-warning">In Progress</span> - Glucose remains elevated (186), A1C pending</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Care Plan 3: Fall Risk -->
        <div style="border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
            <div style="background: #fff8e8; padding: 8px 12px; font-weight: bold; border-bottom: 1px solid #ddd;">
                <i class="fas fa-exclamation-triangle"></i> Fall Risk
                <span style="float: right; font-weight: normal; font-size: 11px;">Added: 2 days ago</span>
            </div>
            <div style="padding: 12px;">
                <table class="data-table">
                    <tr>
                        <td style="width: 120px; font-weight: 600; background: #f8f8f8;">Problem</td>
                        <td>High fall risk (Morse Score 55) - Age, medications, unsteady gait</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Goal</td>
                        <td>Patient will remain free from falls during hospitalization</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Interventions</td>
                        <td>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Bed in low position with side rails up x2 <span class="text-success">(In progress)</span></li>
                                <li>Call light within reach <span class="text-success">(In progress)</span></li>
                                <li>Bed alarm activated <span class="text-success">(In progress)</span></li>
                                <li>Non-slip footwear when ambulating <span class="text-success">(In progress)</span></li>
                                <li>Assist with ambulation <span class="text-success">(In progress)</span></li>
                                <li>Yellow fall risk band on wrist <span class="text-success">(In progress)</span></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Outcome</td>
                        <td><span class="text-success">Met</span> - No falls since admission</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Care Plan 4: Fluid Volume -->
        <div style="border: 1px solid #ddd; border-radius: 4px;">
            <div style="background: #e8f0f8; padding: 8px 12px; font-weight: bold; border-bottom: 1px solid #ddd;">
                <i class="fas fa-tint"></i> Fluid Volume Management
                <span style="float: right; font-weight: normal; font-size: 11px;">Added: 2 days ago</span>
            </div>
            <div style="padding: 12px;">
                <table class="data-table">
                    <tr>
                        <td style="width: 120px; font-weight: 600; background: #f8f8f8;">Problem</td>
                        <td>Risk for fluid imbalance related to CKD Stage 3 and IV fluid therapy</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Goal</td>
                        <td>Maintain euvolemic state; stable weight; no peripheral edema</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Interventions</td>
                        <td>
                            <ul style="margin: 0; padding-left: 20px;">
                                <li>Strict I&O monitoring <span class="text-success">(In progress)</span></li>
                                <li>Daily weights <span class="text-success">(In progress)</span></li>
                                <li>Assess for edema q shift <span class="text-success">(In progress)</span></li>
                                <li>Monitor BUN/Creatinine daily <span class="text-success">(In progress)</span></li>
                                <li>Adjust IV rate as ordered <span class="text-success">(In progress)</span></li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; background: #f8f8f8;">Outcome</td>
                        <td><span class="text-success">Met</span> - No edema, weight stable, Cr stable at 1.8</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="content-columns mt-3">
    <div class="content-column">
        <div class="content-panel">
            <div class="panel-header blue">
                <span><i class="fas fa-bullseye"></i> Patient Goals</span>
            </div>
            <div class="panel-content">
                <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                    <li>Resolve pneumonia and return home</li>
                    <li>Better understanding of diabetes management</li>
                    <li>Maintain independence with daily activities</li>
                    <li>Family to be involved in discharge planning</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="content-column">
        <div class="content-panel">
            <div class="panel-header purple">
                <span><i class="fas fa-clipboard-check"></i> Discharge Criteria</span>
            </div>
            <div class="panel-content">
                <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                    <li><span class="text-muted">☐</span> Afebrile x 24 hours</li>
                    <li><span class="text-muted">☐</span> WBC normalizing</li>
                    <li><span class="text-muted">☐</span> Tolerating PO antibiotics</li>
                    <li><span class="text-success">☑</span> SpO2 &gt; 94% on room air</li>
                    <li><span class="text-muted">☐</span> Diabetes education completed</li>
                    <li><span class="text-success">☑</span> Home support available</li>
                </ul>
            </div>
        </div>
    </div>
</div>

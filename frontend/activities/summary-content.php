<?php
/**
 * Summary Tab Content - Epic Hyperspace Style
 */
?>
<div class="content-columns">
    <div class="content-column">
        <!-- Orders Needing Unit Collect -->
        <div class="content-panel">
            <div class="panel-header orange">
                <span><i class="fas fa-vial"></i> Orders Needing Unit Collect</span>
                <div class="panel-header-actions">
                    <a href="#">View All</a>
                </div>
            </div>
            <div class="panel-content compact">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Ordered</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="#">CBC with Diff</a></td>
                            <td>Today 06:00</td>
                            <td><span class="text-danger font-bold">STAT</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Basic Metabolic Panel</a></td>
                            <td>Today 06:00</td>
                            <td>Routine</td>
                        </tr>
                        <tr>
                            <td><a href="#">Hemoglobin A1C</a></td>
                            <td>Today 06:00</td>
                            <td>Routine</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- All Unresulted Labs -->
        <div class="content-panel">
            <div class="panel-header yellow">
                <span><i class="fas fa-flask"></i> All Unresulted Labs</span>
                <div class="panel-header-actions">
                    <a href="#" style="color: #333;">View All</a>
                </div>
            </div>
            <div class="panel-content compact">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lab</th>
                            <th>Collected</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="#">Type and Screen</a></td>
                            <td>Today 05:30</td>
                            <td><span class="text-warning">In Process</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Blood Culture x2</a></td>
                            <td>Yesterday 18:00</td>
                            <td><span class="text-warning">In Process</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Currently Infusing -->
        <div class="content-panel">
            <div class="panel-header blue">
                <span><i class="fas fa-prescription-bottle-alt"></i> Currently Infusing</span>
                <div class="panel-header-actions">
                    <a href="#">MAR</a>
                </div>
            </div>
            <div class="panel-content">
                <div class="infusion-item">
                    <div class="infusion-section">
                        <h4>Medication</h4>
                        <div class="infusion-med">Normal Saline 0.9%</div>
                        <div class="infusion-rate">1000 mL @ 125 mL/hr</div>
                    </div>
                    <div class="infusion-section">
                        <h4>Site</h4>
                        <div>Left Hand PIV</div>
                    </div>
                    <div class="infusion-section">
                        <h4>Started</h4>
                        <div>Today 04:00</div>
                    </div>
                    <div class="infusion-section">
                        <h4>ETC</h4>
                        <div>Today 12:00</div>
                    </div>
                </div>
                <div class="infusion-item">
                    <div class="infusion-section">
                        <h4>Medication</h4>
                        <div class="infusion-med">Vancomycin 1g</div>
                        <div class="infusion-rate">250 mL @ 250 mL/hr</div>
                    </div>
                    <div class="infusion-section">
                        <h4>Site</h4>
                        <div>PICC Line</div>
                    </div>
                    <div class="infusion-section">
                        <h4>Started</h4>
                        <div>Today 06:00</div>
                    </div>
                    <div class="infusion-section">
                        <h4>ETC</h4>
                        <div>Today 07:00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Problems -->
        <div class="content-panel">
            <div class="panel-header gray">
                <span><i class="fas fa-notes-medical"></i> Medical Problems</span>
                <div class="panel-header-actions">
                    <a href="#">View All</a>
                </div>
            </div>
            <div class="panel-content">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Problem</th>
                            <th>Status</th>
                            <th>Noted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="#">Type 2 Diabetes Mellitus</a></td>
                            <td>Active</td>
                            <td>2018</td>
                        </tr>
                        <tr>
                            <td><a href="#">Essential Hypertension</a></td>
                            <td>Active</td>
                            <td>2015</td>
                        </tr>
                        <tr>
                            <td><a href="#">Chronic Kidney Disease Stage 3</a></td>
                            <td>Active</td>
                            <td>2020</td>
                        </tr>
                        <tr>
                            <td><a href="#">Atrial Fibrillation</a></td>
                            <td>Active</td>
                            <td>2022</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="content-column">
        <!-- Sticky Notes -->
        <div class="content-panel sticky-note-panel">
            <div class="panel-header yellow">
                <span><i class="fas fa-sticky-note"></i> Sticky Notes</span>
                <div class="panel-header-actions">
                    <a href="#" style="color: #333;">+ Add Note</a>
                </div>
            </div>
            <div class="panel-content">
                <div class="sticky-note-content">
                    <strong>IMPORTANT:</strong> Patient prefers to be called by first name. 
                    Family requests updates via daughter (emergency contact). 
                    Patient is hard of hearing - speak loudly and clearly.
                </div>
                <div class="sticky-note-footer">
                    Added by Jones, Sarah RN - Today 06:15
                </div>
            </div>
        </div>

        <!-- Recent Vitals -->
        <div class="content-panel">
            <div class="panel-header green">
                <span><i class="fas fa-heartbeat"></i> Recent Vitals</span>
                <div class="panel-header-actions">
                    <a href="#">Flowsheets</a>
                </div>
            </div>
            <div class="panel-content compact">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vital</th>
                            <th>Value</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Blood Pressure</td>
                            <td class="value-high">158/92 mmHg</td>
                            <td>06:00</td>
                        </tr>
                        <tr>
                            <td>Heart Rate</td>
                            <td>88 bpm</td>
                            <td>06:00</td>
                        </tr>
                        <tr>
                            <td>Temperature</td>
                            <td>98.6°F (37.0°C)</td>
                            <td>06:00</td>
                        </tr>
                        <tr>
                            <td>Respiratory Rate</td>
                            <td>18/min</td>
                            <td>06:00</td>
                        </tr>
                        <tr>
                            <td>SpO2</td>
                            <td>96% on RA</td>
                            <td>06:00</td>
                        </tr>
                        <tr>
                            <td>Pain Score</td>
                            <td>3/10</td>
                            <td>06:00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Today's Medications -->
        <div class="content-panel">
            <div class="panel-header purple">
                <span><i class="fas fa-pills"></i> Medications Due Today</span>
                <div class="panel-header-actions">
                    <a href="#">MAR</a>
                </div>
            </div>
            <div class="panel-content compact">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="#">Metformin 500mg PO</a></td>
                            <td>08:00</td>
                            <td><span class="text-muted">Scheduled</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Lisinopril 10mg PO</a></td>
                            <td>09:00</td>
                            <td><span class="text-muted">Scheduled</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Metoprolol 25mg PO</a></td>
                            <td>09:00</td>
                            <td><span class="text-muted">Scheduled</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Eliquis 5mg PO</a></td>
                            <td>09:00</td>
                            <td><span class="text-muted">Scheduled</span></td>
                        </tr>
                        <tr>
                            <td><a href="#">Atorvastatin 40mg PO</a></td>
                            <td>21:00</td>
                            <td><span class="text-muted">Scheduled</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Results -->
        <div class="content-panel">
            <div class="panel-header blue">
                <span><i class="fas fa-chart-bar"></i> Recent Results</span>
                <div class="panel-header-actions">
                    <a href="#">All Results</a>
                </div>
            </div>
            <div class="panel-content compact">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Test</th>
                            <th>Result</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><a href="#">Glucose</a></td>
                            <td class="value-high">186 mg/dL (H)</td>
                            <td>Today 05:00</td>
                        </tr>
                        <tr>
                            <td><a href="#">Creatinine</a></td>
                            <td class="value-high">1.8 mg/dL (H)</td>
                            <td>Yesterday</td>
                        </tr>
                        <tr>
                            <td><a href="#">Potassium</a></td>
                            <td>4.2 mEq/L</td>
                            <td>Yesterday</td>
                        </tr>
                        <tr>
                            <td><a href="#">WBC</a></td>
                            <td class="value-high">12.5 K/uL (H)</td>
                            <td>Yesterday</td>
                        </tr>
                        <tr>
                            <td><a href="#">Hemoglobin</a></td>
                            <td class="value-low">11.2 g/dL (L)</td>
                            <td>Yesterday</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

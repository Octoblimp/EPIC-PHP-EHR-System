<?php
/**
 * MAR (Medication Administration Record) Tab Content
 */
$current_time = date('H');
?>
<div class="content-panel">
    <div class="panel-header blue">
        <span><i class="fas fa-pills"></i> Medication Administration Record</span>
        <div class="panel-header-actions">
            <a href="#">Document Given</a>
            <a href="#">Hold All</a>
            <a href="#">Print</a>
        </div>
    </div>
    <div class="panel-content" style="overflow-x: auto;">
        <table class="data-table" style="min-width: 1000px;">
            <thead>
                <tr>
                    <th style="min-width: 250px;">Medication</th>
                    <th>Route</th>
                    <th style="text-align: center;">00:00</th>
                    <th style="text-align: center;">04:00</th>
                    <th style="text-align: center;">08:00</th>
                    <th style="text-align: center;">12:00</th>
                    <th style="text-align: center;">16:00</th>
                    <th style="text-align: center;">20:00</th>
                    <th style="text-align: center;">PRN</th>
                </tr>
            </thead>
            <tbody>
                <!-- Scheduled Medications -->
                <tr>
                    <td colspan="9" style="background: #d8e8f0; font-weight: bold; padding: 8px;">
                        <i class="fas fa-clock"></i> Scheduled Medications
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Metformin 500mg</a><br>
                        <small class="text-muted">Take with meals</small>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Lisinopril 10mg</a><br>
                        <small class="text-muted">Hold if SBP &lt; 100</small>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Metoprolol Succinate 25mg</a><br>
                        <small class="text-muted">Hold if HR &lt; 60</small>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Apixaban (Eliquis) 5mg</a>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Atorvastatin 40mg</a><br>
                        <small class="text-muted">Give at bedtime</small>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Vancomycin 1g in NS 250mL</a><br>
                        <small class="text-muted">Infuse over 1 hour</small>
                    </td>
                    <td>IV</td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #90d090; border: 1px solid #60a060; border-radius: 3px;" title="Given at 06:00 by Jones, S">✓</span>
                    </td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;" title="Scheduled"></span>
                    </td>
                    <td></td>
                </tr>
                
                <!-- Continuous Infusions -->
                <tr>
                    <td colspan="9" style="background: #d0e8d8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-prescription-bottle-alt"></i> Continuous Infusions
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Sodium Chloride 0.9%</a><br>
                        <small class="text-muted">1000mL @ 125 mL/hr</small>
                    </td>
                    <td>IV</td>
                    <td colspan="7" style="background: #e8f8e8;">
                        <div style="background: #60a060; height: 16px; border-radius: 3px; display: flex; align-items: center; padding: 0 8px; color: white; font-size: 10px;">
                            Running since 04:00 - Left Hand PIV
                        </div>
                    </td>
                </tr>
                
                <!-- PRN Medications -->
                <tr>
                    <td colspan="9" style="background: #f0e8d8; font-weight: bold; padding: 8px;">
                        <i class="fas fa-hand-holding-medical"></i> PRN Medications
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Acetaminophen 650mg</a><br>
                        <small class="text-muted">Q4H PRN pain/fever</small>
                    </td>
                    <td>PO</td>
                    <td></td>
                    <td style="text-align: center;">
                        <span style="display: inline-block; width: 20px; height: 20px; background: #90d090; border: 1px solid #60a060; border-radius: 3px;" title="Given at 04:30 for pain">✓</span>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-primary">Give</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Ondansetron 4mg</a><br>
                        <small class="text-muted">Q6H PRN nausea</small>
                    </td>
                    <td>IV</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-primary">Give</button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <a href="#">Morphine Sulfate 2mg</a><br>
                        <small class="text-muted">Q3H PRN severe pain</small>
                    </td>
                    <td>IV</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-primary">Give</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-columns mt-3">
    <div class="content-column">
        <div class="content-panel">
            <div class="panel-header green">
                <span>MAR Legend</span>
            </div>
            <div class="panel-content">
                <div class="d-flex gap-3" style="flex-wrap: wrap;">
                    <span><span style="display: inline-block; width: 16px; height: 16px; background: #e8e8e8; border: 1px solid #999; border-radius: 3px;"></span> Scheduled</span>
                    <span><span style="display: inline-block; width: 16px; height: 16px; background: #90d090; border: 1px solid #60a060; border-radius: 3px;">✓</span> Given</span>
                    <span><span style="display: inline-block; width: 16px; height: 16px; background: #f0d080; border: 1px solid #c0a060; border-radius: 3px;">H</span> Held</span>
                    <span><span style="display: inline-block; width: 16px; height: 16px; background: #f0a0a0; border: 1px solid #c08080; border-radius: 3px;">✗</span> Refused</span>
                    <span><span style="display: inline-block; width: 16px; height: 16px; background: #a0c0f0; border: 1px solid #8080c0; border-radius: 3px;">~</span> Late</span>
                </div>
            </div>
        </div>
    </div>
    <div class="content-column">
        <div class="content-panel">
            <div class="panel-header orange">
                <span>Recent Administration Notes</span>
            </div>
            <div class="panel-content">
                <div class="font-sm">
                    <strong>06:00</strong> - Vancomycin infusion started via PICC<br>
                    <small class="text-muted">Jones, S RN</small>
                </div>
                <div class="font-sm mt-2">
                    <strong>04:30</strong> - Acetaminophen given for pain 4/10<br>
                    <small class="text-muted">Jones, S RN</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Flowsheets Tab Content
 */
?>
<div class="content-panel">
    <div class="panel-header green">
        <span><i class="fas fa-chart-line"></i> Vital Signs Flowsheet</span>
        <div class="panel-header-actions">
            <a href="#">Document</a>
            <a href="#">Graph</a>
            <a href="#">Print</a>
        </div>
    </div>
    <div class="panel-content" style="overflow-x: auto;">
        <table class="data-table" style="min-width: 900px;">
            <thead>
                <tr>
                    <th style="min-width: 150px;">Parameter</th>
                    <th style="text-align: center;">Now</th>
                    <th style="text-align: center;">06:00</th>
                    <th style="text-align: center;">04:00</th>
                    <th style="text-align: center;">02:00</th>
                    <th style="text-align: center;">00:00</th>
                    <th style="text-align: center;">22:00</th>
                    <th style="text-align: center;">20:00</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Blood Pressure</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;" class="value-high">158/92</td>
                    <td style="text-align: center;">142/88</td>
                    <td style="text-align: center;">138/84</td>
                    <td style="text-align: center;">145/86</td>
                    <td style="text-align: center;">140/82</td>
                    <td style="text-align: center;">148/90</td>
                </tr>
                <tr>
                    <td><strong>Heart Rate</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">88</td>
                    <td style="text-align: center;">82</td>
                    <td style="text-align: center;">78</td>
                    <td style="text-align: center;">80</td>
                    <td style="text-align: center;">84</td>
                    <td style="text-align: center;">86</td>
                </tr>
                <tr>
                    <td><strong>Respiratory Rate</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">18</td>
                    <td style="text-align: center;">16</td>
                    <td style="text-align: center;">16</td>
                    <td style="text-align: center;">18</td>
                    <td style="text-align: center;">16</td>
                    <td style="text-align: center;">18</td>
                </tr>
                <tr>
                    <td><strong>Temperature</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">98.6°F</td>
                    <td style="text-align: center;">98.4°F</td>
                    <td style="text-align: center;">98.8°F</td>
                    <td style="text-align: center;">99.0°F</td>
                    <td style="text-align: center;">98.6°F</td>
                    <td style="text-align: center;">98.4°F</td>
                </tr>
                <tr>
                    <td><strong>SpO2</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">96%</td>
                    <td style="text-align: center;">97%</td>
                    <td style="text-align: center;">96%</td>
                    <td style="text-align: center;">95%</td>
                    <td style="text-align: center;">96%</td>
                    <td style="text-align: center;">97%</td>
                </tr>
                <tr>
                    <td><strong>O2 Device</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">RA</td>
                    <td style="text-align: center;">RA</td>
                    <td style="text-align: center;">NC 2L</td>
                    <td style="text-align: center;">NC 2L</td>
                    <td style="text-align: center;">NC 2L</td>
                    <td style="text-align: center;">NC 2L</td>
                </tr>
                <tr>
                    <td><strong>Pain Score</strong></td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-secondary">+</button>
                    </td>
                    <td style="text-align: center;">3</td>
                    <td style="text-align: center;">4</td>
                    <td style="text-align: center;">4</td>
                    <td style="text-align: center;">5</td>
                    <td style="text-align: center;">4</td>
                    <td style="text-align: center;">3</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel mt-3">
    <div class="panel-header blue">
        <span><i class="fas fa-clipboard-check"></i> Nursing Assessments</span>
        <div class="panel-header-actions">
            <a href="#">Document</a>
        </div>
    </div>
    <div class="panel-content" style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="min-width: 150px;">Assessment</th>
                    <th style="text-align: center;">06:00</th>
                    <th style="text-align: center;">04:00</th>
                    <th style="text-align: center;">02:00</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Neuro</strong></td>
                    <td style="text-align: center;">A&O x4</td>
                    <td style="text-align: center;">A&O x4</td>
                    <td style="text-align: center;">A&O x4</td>
                </tr>
                <tr>
                    <td><strong>Cardiac</strong></td>
                    <td style="text-align: center;">Irregular, no murmur</td>
                    <td style="text-align: center;">Irregular</td>
                    <td style="text-align: center;">Irregular</td>
                </tr>
                <tr>
                    <td><strong>Respiratory</strong></td>
                    <td style="text-align: center;">Clear bilateral</td>
                    <td style="text-align: center;">Clear bilateral</td>
                    <td style="text-align: center;">Dim bases</td>
                </tr>
                <tr>
                    <td><strong>GI</strong></td>
                    <td style="text-align: center;">Soft, NT, + BS</td>
                    <td style="text-align: center;">Soft, NT</td>
                    <td style="text-align: center;">Soft, NT</td>
                </tr>
                <tr>
                    <td><strong>GU</strong></td>
                    <td style="text-align: center;">Foley, clear yellow</td>
                    <td style="text-align: center;">Foley</td>
                    <td style="text-align: center;">Foley</td>
                </tr>
                <tr>
                    <td><strong>Skin</strong></td>
                    <td style="text-align: center;">Warm, dry, intact</td>
                    <td style="text-align: center;">Warm, dry</td>
                    <td style="text-align: center;">Warm, dry</td>
                </tr>
                <tr>
                    <td><strong>Fall Risk</strong></td>
                    <td style="text-align: center; background: #fff8e0;">High (Morse 55)</td>
                    <td style="text-align: center; background: #fff8e0;">High (Morse 55)</td>
                    <td style="text-align: center; background: #fff8e0;">High</td>
                </tr>
                <tr>
                    <td><strong>Braden Score</strong></td>
                    <td style="text-align: center;">18 - Mild risk</td>
                    <td style="text-align: center;">18</td>
                    <td style="text-align: center;">18</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-panel mt-3">
    <div class="panel-header purple">
        <span><i class="fas fa-syringe"></i> IV Lines & Access</span>
    </div>
    <div class="panel-content">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Line Type</th>
                    <th>Location</th>
                    <th>Inserted</th>
                    <th>Gauge/Size</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Peripheral IV</td>
                    <td>Left Hand</td>
                    <td>Yesterday 08:00</td>
                    <td>20G</td>
                    <td><span class="text-success">Patent, No S/S infection</span></td>
                    <td><a href="#">Assess</a> | <a href="#">D/C</a></td>
                </tr>
                <tr>
                    <td>PICC Line</td>
                    <td>Right Arm</td>
                    <td>3 days ago</td>
                    <td>4 Fr Double Lumen</td>
                    <td><span class="text-success">Patent, Flushed</span></td>
                    <td><a href="#">Assess</a> | <a href="#">Flush</a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

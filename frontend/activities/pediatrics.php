<?php
/**
 * Epic EHR - Pediatrics/Neonatology Module
 * Growth charts, immunizations, developmental milestones
 */
session_start();
require_once __DIR__ . '/../includes/api.php';
$patient_id = $_GET['patient_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic EHR - Pediatrics</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eef5;
            min-height: 100vh;
        }
        
        .peds-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header */
        .peds-header {
            background: linear-gradient(to bottom, #00897b, #00695c);
            color: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .peds-header h1 {
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .header-btn {
            padding: 6px 12px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 12px;
            cursor: pointer;
        }
        
        .header-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Patient Banner */
        .patient-banner {
            background: #fff;
            padding: 10px 16px;
            border-bottom: 2px solid #00897b;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .patient-photo {
            width: 50px;
            height: 50px;
            background: #b2dfdb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .patient-info h2 {
            font-size: 16px;
            color: #00695c;
        }
        
        .patient-info .meta {
            font-size: 11px;
            color: #666;
        }
        
        .growth-info {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }
        
        .growth-stat {
            text-align: center;
            padding: 5px 15px;
            border-left: 1px solid #ddd;
        }
        
        .growth-stat .label {
            font-size: 10px;
            color: #666;
        }
        
        .growth-stat .value {
            font-size: 14px;
            font-weight: bold;
            color: #00695c;
        }
        
        .growth-stat .percentile {
            font-size: 10px;
            color: #888;
        }
        
        /* Navigation Tabs */
        .peds-tabs {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            padding: 0 16px;
        }
        
        .peds-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: #666;
        }
        
        .peds-tab:hover {
            background: #fff;
        }
        
        .peds-tab.active {
            border-bottom-color: #00897b;
            color: #00897b;
            font-weight: 600;
            background: #fff;
        }
        
        /* Main Content */
        .peds-content {
            flex: 1;
            display: flex;
            padding: 16px;
            gap: 16px;
            overflow: hidden;
        }
        
        /* Left Panel - Growth Chart */
        .growth-panel {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .panel-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-controls {
            display: flex;
            gap: 8px;
        }
        
        .chart-controls select {
            padding: 4px 8px;
            font-size: 11px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .growth-chart {
            flex: 1;
            padding: 16px;
            position: relative;
        }
        
        .growth-chart svg {
            width: 100%;
            height: 100%;
        }
        
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px;
            font-size: 10px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .legend-line {
            width: 20px;
            height: 3px;
        }
        
        /* Right Panel */
        .right-panel {
            width: 380px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }
        
        .info-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .info-card-header {
            padding: 10px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-card-body {
            padding: 12px 16px;
        }
        
        /* Immunization Table */
        .imm-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }
        
        .imm-table th, .imm-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .imm-table th {
            background: #f9f9f9;
            font-weight: 600;
        }
        
        .imm-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .imm-status.complete { background: #c8e6c9; color: #2e7d32; }
        .imm-status.due { background: #fff3e0; color: #e65100; }
        .imm-status.overdue { background: #ffcdd2; color: #c62828; }
        
        /* Milestones */
        .milestone-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .milestone-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .milestone-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }
        
        .milestone-check.met { background: #c8e6c9; color: #2e7d32; }
        .milestone-check.pending { background: #e0e0e0; color: #666; }
        .milestone-check.concern { background: #fff3e0; color: #e65100; }
        
        /* Vitals Summary */
        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        
        .vital-item {
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 6px;
        }
        
        .vital-item .label {
            font-size: 10px;
            color: #666;
        }
        
        .vital-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #00695c;
        }
        
        .vital-item .unit {
            font-size: 10px;
            color: #888;
        }
        
        /* Action Buttons */
        .action-buttons {
            padding: 12px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .action-btn {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            border: 1px solid #00897b;
            background: #fff;
            color: #00897b;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-align: center;
        }
        
        .action-btn:hover {
            background: #e0f2f1;
        }
        
        .action-btn.primary {
            background: #00897b;
            color: #fff;
        }
        
        /* NICU Specific */
        .nicu-panel {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            border-radius: 6px;
            padding: 12px;
            margin-top: 8px;
        }
        
        .nicu-header {
            font-weight: 600;
            font-size: 12px;
            color: #e65100;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .nicu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            font-size: 11px;
        }
        
        .nicu-item {
            display: flex;
            justify-content: space-between;
        }
        
        .nicu-item .label {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="peds-container">
        <!-- Header -->
        <header class="peds-header">
            <h1>👶 Pediatrics / Neonatology</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="window.location.href='../home.php'">← Back</button>
                <button class="header-btn">🖨️ Print</button>
                <button class="header-btn">📋 Growth Report</button>
            </div>
        </header>
        
        <!-- Patient Banner -->
        <div class="patient-banner">
            <div class="patient-photo">👶</div>
            <div class="patient-info">
                <h2>Baby Boy Johnson</h2>
                <div class="meta">MRN: 008901 | DOB: 01/15/2024 | 10 days old | Male | NICU Bed 12</div>
            </div>
            <div class="growth-info">
                <div class="growth-stat">
                    <div class="label">Birth Weight</div>
                    <div class="value">2.8 kg</div>
                    <div class="percentile">25th %ile</div>
                </div>
                <div class="growth-stat">
                    <div class="label">Current Weight</div>
                    <div class="value">2.65 kg</div>
                    <div class="percentile">-5.4%</div>
                </div>
                <div class="growth-stat">
                    <div class="label">Length</div>
                    <div class="value">48 cm</div>
                    <div class="percentile">30th %ile</div>
                </div>
                <div class="growth-stat">
                    <div class="label">Head Circ</div>
                    <div class="value">33 cm</div>
                    <div class="percentile">35th %ile</div>
                </div>
                <div class="growth-stat">
                    <div class="label">Gest Age</div>
                    <div class="value">36+4</div>
                    <div class="percentile">Late Preterm</div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="peds-tabs">
            <button class="peds-tab active">Growth</button>
            <button class="peds-tab">Immunizations</button>
            <button class="peds-tab">Development</button>
            <button class="peds-tab">Feeding</button>
            <button class="peds-tab">NICU Summary</button>
            <button class="peds-tab">Newborn Screen</button>
            <button class="peds-tab">Bilirubin</button>
        </div>
        
        <!-- Main Content -->
        <div class="peds-content">
            <!-- Growth Chart Panel -->
            <div class="growth-panel">
                <div class="panel-header">
                    <span>📈 Growth Chart - Weight for Age</span>
                    <div class="chart-controls">
                        <select id="chartType">
                            <option value="weight">Weight for Age</option>
                            <option value="length">Length for Age</option>
                            <option value="head">Head Circumference</option>
                            <option value="bmi">BMI for Age</option>
                        </select>
                        <select id="chartStandard">
                            <option value="who">WHO Standards</option>
                            <option value="cdc">CDC Standards</option>
                            <option value="fenton">Fenton (Preterm)</option>
                        </select>
                    </div>
                </div>
                <div class="growth-chart">
                    <svg viewBox="0 0 600 400">
                        <!-- Grid -->
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#eee" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="600" height="350" fill="url(#grid)" y="25"/>
                        
                        <!-- Percentile Lines -->
                        <!-- 3rd percentile -->
                        <path d="M 50 320 Q 150 310 250 290 T 450 240 T 580 200" stroke="#ffcdd2" stroke-width="1.5" fill="none"/>
                        <text x="585" y="200" font-size="10" fill="#c62828">3rd</text>
                        
                        <!-- 15th percentile -->
                        <path d="M 50 300 Q 150 285 250 260 T 450 200 T 580 160" stroke="#ffe0b2" stroke-width="1.5" fill="none"/>
                        <text x="585" y="160" font-size="10" fill="#e65100">15th</text>
                        
                        <!-- 50th percentile -->
                        <path d="M 50 270 Q 150 250 250 220 T 450 150 T 580 100" stroke="#a5d6a7" stroke-width="2" fill="none"/>
                        <text x="585" y="100" font-size="10" fill="#2e7d32">50th</text>
                        
                        <!-- 85th percentile -->
                        <path d="M 50 240 Q 150 215 250 180 T 450 100 T 580 60" stroke="#ffe0b2" stroke-width="1.5" fill="none"/>
                        <text x="585" y="60" font-size="10" fill="#e65100">85th</text>
                        
                        <!-- 97th percentile -->
                        <path d="M 50 210 Q 150 180 250 140 T 450 60 T 580 30" stroke="#ffcdd2" stroke-width="1.5" fill="none"/>
                        <text x="585" y="30" font-size="10" fill="#c62828">97th</text>
                        
                        <!-- Patient Data Points -->
                        <circle cx="50" cy="290" r="6" fill="#00897b"/>
                        <text x="50" y="280" font-size="9" fill="#00897b" text-anchor="middle">Birth</text>
                        
                        <circle cx="90" cy="295" r="6" fill="#00897b"/>
                        <text x="90" y="285" font-size="9" fill="#00897b" text-anchor="middle">D3</text>
                        
                        <circle cx="130" cy="300" r="6" fill="#00897b"/>
                        <text x="130" y="290" font-size="9" fill="#00897b" text-anchor="middle">D7</text>
                        
                        <circle cx="170" cy="298" r="6" fill="#00897b"/>
                        <text x="170" y="288" font-size="9" fill="#00897b" text-anchor="middle">D10</text>
                        
                        <!-- Connect points -->
                        <path d="M 50 290 L 90 295 L 130 300 L 170 298" stroke="#00897b" stroke-width="2" fill="none"/>
                        
                        <!-- Axes -->
                        <line x1="50" y1="350" x2="580" y2="350" stroke="#333" stroke-width="1"/>
                        <line x1="50" y1="25" x2="50" y2="350" stroke="#333" stroke-width="1"/>
                        
                        <!-- X-axis labels -->
                        <text x="315" y="390" font-size="11" fill="#333" text-anchor="middle">Age (weeks)</text>
                        <text x="50" y="365" font-size="9" fill="#666" text-anchor="middle">0</text>
                        <text x="130" y="365" font-size="9" fill="#666" text-anchor="middle">2</text>
                        <text x="210" y="365" font-size="9" fill="#666" text-anchor="middle">4</text>
                        <text x="290" y="365" font-size="9" fill="#666" text-anchor="middle">6</text>
                        <text x="370" y="365" font-size="9" fill="#666" text-anchor="middle">8</text>
                        <text x="450" y="365" font-size="9" fill="#666" text-anchor="middle">10</text>
                        <text x="530" y="365" font-size="9" fill="#666" text-anchor="middle">12</text>
                        
                        <!-- Y-axis labels -->
                        <text x="25" y="200" font-size="11" fill="#333" text-anchor="middle" transform="rotate(-90 25 200)">Weight (kg)</text>
                        <text x="40" y="330" font-size="9" fill="#666" text-anchor="end">2.0</text>
                        <text x="40" y="270" font-size="9" fill="#666" text-anchor="end">3.0</text>
                        <text x="40" y="210" font-size="9" fill="#666" text-anchor="end">4.0</text>
                        <text x="40" y="150" font-size="9" fill="#666" text-anchor="end">5.0</text>
                        <text x="40" y="90" font-size="9" fill="#666" text-anchor="end">6.0</text>
                        <text x="40" y="30" font-size="9" fill="#666" text-anchor="end">7.0</text>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-line" style="background: #00897b;"></div>
                        <span>Patient</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-line" style="background: #a5d6a7;"></div>
                        <span>50th Percentile</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-line" style="background: #ffe0b2;"></div>
                        <span>15th/85th Percentile</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-line" style="background: #ffcdd2;"></div>
                        <span>3rd/97th Percentile</span>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel -->
            <div class="right-panel">
                <!-- NICU Status -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>🏥 NICU Status</span>
                        <span style="color: #e65100; font-size: 10px;">Day of Life: 10</span>
                    </div>
                    <div class="info-card-body">
                        <div class="nicu-panel">
                            <div class="nicu-header">⚠️ Active Issues</div>
                            <div class="nicu-grid">
                                <div class="nicu-item">
                                    <span class="label">Respiratory</span>
                                    <span>Room Air</span>
                                </div>
                                <div class="nicu-item">
                                    <span class="label">Jaundice</span>
                                    <span>Phototherapy</span>
                                </div>
                                <div class="nicu-item">
                                    <span class="label">Feeding</span>
                                    <span>PO + Gavage</span>
                                </div>
                                <div class="nicu-item">
                                    <span class="label">Thermoreg</span>
                                    <span>Isolette</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Current Vitals -->
                <div class="info-card">
                    <div class="info-card-header">💓 Current Vitals</div>
                    <div class="info-card-body">
                        <div class="vitals-grid">
                            <div class="vital-item">
                                <div class="label">Heart Rate</div>
                                <div class="value">148</div>
                                <div class="unit">bpm</div>
                            </div>
                            <div class="vital-item">
                                <div class="label">Resp Rate</div>
                                <div class="value">44</div>
                                <div class="unit">breaths/min</div>
                            </div>
                            <div class="vital-item">
                                <div class="label">Temp</div>
                                <div class="value">36.8</div>
                                <div class="unit">°C</div>
                            </div>
                            <div class="vital-item">
                                <div class="label">SpO2</div>
                                <div class="value">98</div>
                                <div class="unit">%</div>
                            </div>
                            <div class="vital-item">
                                <div class="label">BP</div>
                                <div class="value">65/42</div>
                                <div class="unit">mmHg</div>
                            </div>
                            <div class="vital-item">
                                <div class="label">Glucose</div>
                                <div class="value">72</div>
                                <div class="unit">mg/dL</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bilirubin -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>☀️ Bilirubin Trend</span>
                        <span style="color: #ff9800; font-size: 10px;">On Phototherapy</span>
                    </div>
                    <div class="info-card-body">
                        <table class="imm-table">
                            <tr>
                                <th>Time</th>
                                <th>Total</th>
                                <th>Direct</th>
                                <th>Status</th>
                            </tr>
                            <tr>
                                <td>D3 08:00</td>
                                <td>14.2</td>
                                <td>0.4</td>
                                <td><span class="imm-status due">High</span></td>
                            </tr>
                            <tr>
                                <td>D5 08:00</td>
                                <td>16.8</td>
                                <td>0.5</td>
                                <td><span class="imm-status overdue">Photo Start</span></td>
                            </tr>
                            <tr>
                                <td>D7 08:00</td>
                                <td>12.4</td>
                                <td>0.4</td>
                                <td><span class="imm-status due">Improving</span></td>
                            </tr>
                            <tr>
                                <td>D10 08:00</td>
                                <td>9.2</td>
                                <td>0.3</td>
                                <td><span class="imm-status complete">Normal</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Feeding -->
                <div class="info-card">
                    <div class="info-card-header">🍼 Feeding Summary (Last 24h)</div>
                    <div class="info-card-body">
                        <div class="nicu-grid">
                            <div class="nicu-item">
                                <span class="label">Feed Type</span>
                                <span>EBM + Formula</span>
                            </div>
                            <div class="nicu-item">
                                <span class="label">Volume Goal</span>
                                <span>150 mL/kg/day</span>
                            </div>
                            <div class="nicu-item">
                                <span class="label">PO Intake</span>
                                <span>280 mL</span>
                            </div>
                            <div class="nicu-item">
                                <span class="label">Gavage</span>
                                <span>120 mL</span>
                            </div>
                            <div class="nicu-item">
                                <span class="label">Total Intake</span>
                                <span style="font-weight: bold;">400 mL</span>
                            </div>
                            <div class="nicu-item">
                                <span class="label">Cal/kg/day</span>
                                <span>120 kcal</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Immunizations -->
                <div class="info-card">
                    <div class="info-card-header">💉 Immunizations</div>
                    <div class="info-card-body">
                        <table class="imm-table">
                            <tr>
                                <th>Vaccine</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                            <tr>
                                <td>Hepatitis B #1</td>
                                <td>01/15/2024</td>
                                <td><span class="imm-status complete">Given</span></td>
                            </tr>
                            <tr>
                                <td>Hepatitis B #2</td>
                                <td>02/15/2024</td>
                                <td><span class="imm-status due">Due 1mo</span></td>
                            </tr>
                            <tr>
                                <td>DTaP #1</td>
                                <td>03/15/2024</td>
                                <td><span class="imm-status due">Due 2mo</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="action-buttons">
                    <button class="action-btn primary">📋 Document</button>
                    <button class="action-btn">📊 Growth Report</button>
                    <button class="action-btn">💉 Give Vaccine</button>
                    <button class="action-btn">🍼 Log Feed</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.peds-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.peds-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });
    </script>
</body>
</html>

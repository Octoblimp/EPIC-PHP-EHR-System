<?php
/**
 * Epic EHR - Oncology Module
 * Cancer treatment protocols, chemotherapy administration, tumor registry
 */
session_start();
require_once __DIR__ . '/../includes/api.php';
$patient_id = $_GET['patient_id'] ?? 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic EHR - Oncology</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eef5;
            min-height: 100vh;
        }
        
        .onc-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header */
        .onc-header {
            background: linear-gradient(to bottom, #7b1fa2, #6a1b9a);
            color: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .onc-header h1 {
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
            border-bottom: 2px solid #7b1fa2;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .patient-photo {
            width: 50px;
            height: 50px;
            background: #e1bee7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .patient-info h2 {
            font-size: 16px;
            color: #7b1fa2;
        }
        
        .patient-info .meta {
            font-size: 11px;
            color: #666;
        }
        
        .cancer-info {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }
        
        .cancer-stat {
            text-align: center;
            padding: 5px 15px;
            border-left: 1px solid #ddd;
        }
        
        .cancer-stat .label {
            font-size: 10px;
            color: #666;
        }
        
        .cancer-stat .value {
            font-size: 12px;
            font-weight: bold;
            color: #7b1fa2;
        }
        
        /* Navigation Tabs */
        .onc-tabs {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            padding: 0 16px;
        }
        
        .onc-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: #666;
        }
        
        .onc-tab:hover {
            background: #fff;
        }
        
        .onc-tab.active {
            border-bottom-color: #7b1fa2;
            color: #7b1fa2;
            font-weight: 600;
            background: #fff;
        }
        
        /* Main Content */
        .onc-content {
            flex: 1;
            display: flex;
            padding: 16px;
            gap: 16px;
            overflow: hidden;
        }
        
        /* Left Panel - Treatment Timeline */
        .timeline-panel {
            width: 350px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .panel-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
        }
        
        .timeline-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .treatment-cycle {
            margin-bottom: 20px;
        }
        
        .cycle-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f3e5f5;
            border-radius: 6px;
            margin-bottom: 8px;
        }
        
        .cycle-name {
            font-weight: 600;
            font-size: 12px;
            color: #7b1fa2;
        }
        
        .cycle-status {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 10px;
        }
        
        .cycle-status.active { background: #c8e6c9; color: #2e7d32; }
        .cycle-status.completed { background: #e0e0e0; color: #666; }
        .cycle-status.upcoming { background: #fff3e0; color: #e65100; }
        
        .cycle-days {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding-left: 12px;
        }
        
        .day-item {
            width: 36px;
            height: 36px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
        }
        
        .day-item:hover {
            border-color: #7b1fa2;
        }
        
        .day-item.given {
            background: #c8e6c9;
            border-color: #81c784;
        }
        
        .day-item.today {
            background: #fff3e0;
            border-color: #ff9800;
            font-weight: bold;
        }
        
        .day-item.missed {
            background: #ffcdd2;
            border-color: #ef5350;
        }
        
        .day-item .day-num {
            font-weight: 600;
        }
        
        .day-item .day-date {
            font-size: 8px;
            color: #666;
        }
        
        /* Center Panel - Treatment Details */
        .center-panel {
            flex: 1;
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
            padding: 16px;
        }
        
        /* Chemo Orders Table */
        .chemo-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }
        
        .chemo-table th, .chemo-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .chemo-table th {
            background: #fafafa;
            font-weight: 600;
        }
        
        .drug-name {
            font-weight: 600;
            color: #7b1fa2;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .status-badge.due { background: #fff3e0; color: #e65100; }
        .status-badge.infusing { background: #e3f2fd; color: #1565c0; }
        .status-badge.complete { background: #c8e6c9; color: #2e7d32; }
        .status-badge.held { background: #ffcdd2; color: #c62828; }
        
        /* Progress Bar */
        .infusion-progress {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .progress-bar {
            flex: 1;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #7b1fa2, #9c27b0);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 10px;
            color: #666;
            min-width: 40px;
        }
        
        /* Lab Results */
        .lab-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        .lab-item {
            text-align: center;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        
        .lab-item .label {
            font-size: 10px;
            color: #666;
        }
        
        .lab-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .lab-item .value.low { color: #c62828; }
        .lab-item .value.high { color: #e65100; }
        .lab-item .value.normal { color: #2e7d32; }
        
        .lab-item .ref {
            font-size: 9px;
            color: #999;
        }
        
        /* Right Panel */
        .right-panel {
            width: 300px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }
        
        /* Toxicity Grading */
        .toxicity-grid {
            display: grid;
            gap: 8px;
        }
        
        .toxicity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #f9f9f9;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .toxicity-grade {
            display: flex;
            gap: 4px;
        }
        
        .grade-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
            color: #999;
        }
        
        .grade-dot.active.g1 { background: #fff9c4; border-color: #fbc02d; color: #f57f17; }
        .grade-dot.active.g2 { background: #ffe0b2; border-color: #ff9800; color: #e65100; }
        .grade-dot.active.g3 { background: #ffccbc; border-color: #ff5722; color: #d84315; }
        .grade-dot.active.g4 { background: #ffcdd2; border-color: #f44336; color: #c62828; }
        
        /* Action Buttons */
        .action-buttons {
            padding: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .action-btn {
            flex: 1;
            min-width: 100px;
            padding: 10px 12px;
            border: 1px solid #7b1fa2;
            background: #fff;
            color: #7b1fa2;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-align: center;
        }
        
        .action-btn:hover {
            background: #f3e5f5;
        }
        
        .action-btn.primary {
            background: #7b1fa2;
            color: #fff;
        }
        
        .action-btn.danger {
            border-color: #c62828;
            color: #c62828;
        }
        
        .action-btn.danger:hover {
            background: #ffebee;
        }
        
        /* Alerts */
        .alert-box {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            border-left: 4px solid #ff9800;
            padding: 12px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .alert-box.warning {
            background: #ffebee;
            border-color: #ffcdd2;
            border-left-color: #f44336;
        }
        
        .alert-title {
            font-weight: 600;
            color: #e65100;
            margin-bottom: 4px;
        }
        
        .alert-box.warning .alert-title {
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="onc-container">
        <!-- Header -->
        <header class="onc-header">
            <h1>🎗️ Oncology Module</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="window.location.href='../home.php'">← Back</button>
                <button class="header-btn">🖨️ Print</button>
                <button class="header-btn">📋 Treatment Summary</button>
            </div>
        </header>
        
        <!-- Patient Banner -->
        <div class="patient-banner">
            <div class="patient-photo">👤</div>
            <div class="patient-info">
                <h2>Brown, Robert</h2>
                <div class="meta">MRN: 005678 | DOB: 09/18/1945 | 78M | Oncology 5S-512</div>
            </div>
            <div class="cancer-info">
                <div class="cancer-stat">
                    <div class="label">Diagnosis</div>
                    <div class="value">NSCLC Stage IIIA</div>
                </div>
                <div class="cancer-stat">
                    <div class="label">Protocol</div>
                    <div class="value">Carboplatin/Paclitaxel</div>
                </div>
                <div class="cancer-stat">
                    <div class="label">Cycle</div>
                    <div class="value">4 of 6</div>
                </div>
                <div class="cancer-stat">
                    <div class="label">Day</div>
                    <div class="value">Day 1</div>
                </div>
                <div class="cancer-stat">
                    <div class="label">BSA</div>
                    <div class="value">1.92 m²</div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="onc-tabs">
            <button class="onc-tab active">Treatment</button>
            <button class="onc-tab">Protocol</button>
            <button class="onc-tab">Labs/Toxicity</button>
            <button class="onc-tab">Imaging</button>
            <button class="onc-tab">Staging</button>
            <button class="onc-tab">Tumor Board</button>
            <button class="onc-tab">Pathology</button>
        </div>
        
        <!-- Main Content -->
        <div class="onc-content">
            <!-- Treatment Timeline -->
            <div class="timeline-panel">
                <div class="panel-header">📅 Treatment Timeline</div>
                <div class="timeline-content">
                    <div class="treatment-cycle">
                        <div class="cycle-header">
                            <span class="cycle-name">Cycle 4</span>
                            <span class="cycle-status active">In Progress</span>
                        </div>
                        <div class="cycle-days">
                            <div class="day-item today">
                                <span class="day-num">D1</span>
                                <span class="day-date">1/25</span>
                            </div>
                            <div class="day-item">
                                <span class="day-num">D2</span>
                                <span class="day-date">1/26</span>
                            </div>
                            <div class="day-item">
                                <span class="day-num">D8</span>
                                <span class="day-date">2/1</span>
                            </div>
                            <div class="day-item">
                                <span class="day-num">D15</span>
                                <span class="day-date">2/8</span>
                            </div>
                            <div class="day-item">
                                <span class="day-num">D21</span>
                                <span class="day-date">2/14</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="treatment-cycle">
                        <div class="cycle-header">
                            <span class="cycle-name">Cycle 3</span>
                            <span class="cycle-status completed">Completed</span>
                        </div>
                        <div class="cycle-days">
                            <div class="day-item given">
                                <span class="day-num">D1</span>
                                <span class="day-date">1/4</span>
                            </div>
                            <div class="day-item given">
                                <span class="day-num">D2</span>
                                <span class="day-date">1/5</span>
                            </div>
                            <div class="day-item given">
                                <span class="day-num">D8</span>
                                <span class="day-date">1/11</span>
                            </div>
                            <div class="day-item given">
                                <span class="day-num">D15</span>
                                <span class="day-date">1/18</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="treatment-cycle">
                        <div class="cycle-header">
                            <span class="cycle-name">Cycle 5</span>
                            <span class="cycle-status upcoming">Upcoming</span>
                        </div>
                        <div class="cycle-days">
                            <div class="day-item">
                                <span class="day-num">D1</span>
                                <span class="day-date">2/15</span>
                            </div>
                            <div class="day-item">
                                <span class="day-num">D8</span>
                                <span class="day-date">2/22</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Center Panel -->
            <div class="center-panel">
                <!-- Alert Box -->
                <div class="alert-box">
                    <div class="alert-title">⚠️ Pre-Chemo Checklist</div>
                    <div>✓ Labs within 24 hours | ✓ ANC > 1500 | ✓ Platelets > 100K | ⚠️ Creatinine Clearance: 52 (borderline)</div>
                </div>
                
                <!-- Today's Chemotherapy -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>💉 Today's Chemotherapy Orders - Cycle 4 Day 1</span>
                        <button style="font-size: 10px; padding: 3px 8px; cursor: pointer;">View Protocol</button>
                    </div>
                    <div class="info-card-body">
                        <table class="chemo-table">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Dose</th>
                                    <th>Route</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="drug-name">Ondansetron</span><br><small>Pre-med (antiemetic)</small></td>
                                    <td>8 mg</td>
                                    <td>IV Push</td>
                                    <td>--</td>
                                    <td><span class="status-badge complete">Complete</span></td>
                                    <td>--</td>
                                </tr>
                                <tr>
                                    <td><span class="drug-name">Dexamethasone</span><br><small>Pre-med</small></td>
                                    <td>20 mg</td>
                                    <td>IV Push</td>
                                    <td>--</td>
                                    <td><span class="status-badge complete">Complete</span></td>
                                    <td>--</td>
                                </tr>
                                <tr>
                                    <td><span class="drug-name">Diphenhydramine</span><br><small>Pre-med</small></td>
                                    <td>25 mg</td>
                                    <td>IV Push</td>
                                    <td>--</td>
                                    <td><span class="status-badge complete">Complete</span></td>
                                    <td>--</td>
                                </tr>
                                <tr style="background: #f3e5f5;">
                                    <td><span class="drug-name">Paclitaxel</span><br><small>175 mg/m² × 1.92 m²</small></td>
                                    <td>336 mg</td>
                                    <td>IV</td>
                                    <td>3 hr</td>
                                    <td><span class="status-badge infusing">Infusing</span></td>
                                    <td>
                                        <div class="infusion-progress">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: 45%"></div>
                                            </div>
                                            <span class="progress-text">45%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="drug-name">Carboplatin</span><br><small>AUC 6 × (52+25)</small></td>
                                    <td>462 mg</td>
                                    <td>IV</td>
                                    <td>30 min</td>
                                    <td><span class="status-badge due">Due Next</span></td>
                                    <td>--</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Labs -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>🔬 Pre-Treatment Labs (Today 06:00)</span>
                    </div>
                    <div class="info-card-body">
                        <div class="lab-grid">
                            <div class="lab-item">
                                <div class="label">WBC</div>
                                <div class="value normal">6.2</div>
                                <div class="ref">4.5-11.0 K/uL</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">ANC</div>
                                <div class="value normal">3,850</div>
                                <div class="ref">>1500 for chemo</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">Hemoglobin</div>
                                <div class="value low">10.2</div>
                                <div class="ref">12.0-16.0 g/dL</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">Platelets</div>
                                <div class="value normal">185</div>
                                <div class="ref">150-400 K/uL</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">Creatinine</div>
                                <div class="value high">1.4</div>
                                <div class="ref">0.7-1.2 mg/dL</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">CrCl (calc)</div>
                                <div class="value high">52</div>
                                <div class="ref">>60 mL/min</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">ALT</div>
                                <div class="value normal">28</div>
                                <div class="ref">7-56 U/L</div>
                            </div>
                            <div class="lab-item">
                                <div class="label">Bilirubin</div>
                                <div class="value normal">0.8</div>
                                <div class="ref">0.1-1.2 mg/dL</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Actions -->
                <div class="action-buttons" style="padding: 0; gap: 8px;">
                    <button class="action-btn primary">📋 Document Assessment</button>
                    <button class="action-btn">💊 Adjust Dose</button>
                    <button class="action-btn danger">⏸️ Hold Treatment</button>
                </div>
                
                <!-- Toxicity Grading -->
                <div class="info-card">
                    <div class="info-card-header">⚠️ CTCAE Toxicity Grading</div>
                    <div class="info-card-body">
                        <div class="toxicity-grid">
                            <div class="toxicity-item">
                                <span>Nausea</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot active g1">1</span>
                                    <span class="grade-dot">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                            <div class="toxicity-item">
                                <span>Fatigue</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot">1</span>
                                    <span class="grade-dot active g2">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                            <div class="toxicity-item">
                                <span>Neuropathy</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot active g1">1</span>
                                    <span class="grade-dot">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                            <div class="toxicity-item">
                                <span>Alopecia</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot">1</span>
                                    <span class="grade-dot active g2">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                            <div class="toxicity-item">
                                <span>Anemia</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot active g1">1</span>
                                    <span class="grade-dot">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                            <div class="toxicity-item">
                                <span>Renal</span>
                                <div class="toxicity-grade">
                                    <span class="grade-dot active g1">1</span>
                                    <span class="grade-dot">2</span>
                                    <span class="grade-dot">3</span>
                                    <span class="grade-dot">4</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Status -->
                <div class="info-card">
                    <div class="info-card-header">📊 Performance Status</div>
                    <div class="info-card-body">
                        <div style="font-size: 11px;">
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>ECOG</span>
                                <strong>1</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>Karnofsky</span>
                                <strong>80%</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                <span>Weight Change</span>
                                <span style="color: #e65100;">-3.2 kg (-4%)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Response Assessment -->
                <div class="info-card">
                    <div class="info-card-header">🎯 Response Assessment</div>
                    <div class="info-card-body">
                        <div style="font-size: 11px;">
                            <div style="padding: 6px 0; border-bottom: 1px solid #eee;">
                                <div style="color: #666;">Last Imaging: 01/18/2024</div>
                                <div style="font-weight: 600; color: #2e7d32; margin-top: 4px;">Partial Response</div>
                            </div>
                            <div style="padding: 6px 0; border-bottom: 1px solid #eee;">
                                <div style="color: #666;">Primary Tumor</div>
                                <div>4.2 cm → 2.8 cm (-33%)</div>
                            </div>
                            <div style="padding: 6px 0;">
                                <div style="color: #666;">Lymph Nodes</div>
                                <div>Decreased (RECIST: PR)</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming -->
                <div class="info-card">
                    <div class="info-card-header">📅 Upcoming</div>
                    <div class="info-card-body">
                        <div style="font-size: 11px;">
                            <div style="padding: 6px 0; border-bottom: 1px solid #eee;">
                                <div style="color: #7b1fa2; font-weight: 600;">Tomorrow</div>
                                <div>Day 2 - Hydration/Labs</div>
                            </div>
                            <div style="padding: 6px 0; border-bottom: 1px solid #eee;">
                                <div style="color: #7b1fa2; font-weight: 600;">02/08/2024</div>
                                <div>Day 15 - Labs only</div>
                            </div>
                            <div style="padding: 6px 0;">
                                <div style="color: #7b1fa2; font-weight: 600;">02/15/2024</div>
                                <div>Cycle 5 Day 1</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.onc-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.onc-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });
        
        // Simulate infusion progress
        function updateInfusion() {
            const fill = document.querySelector('.progress-fill');
            const text = document.querySelector('.progress-text');
            let current = parseInt(fill.style.width);
            if (current < 100) {
                current += 1;
                fill.style.width = current + '%';
                text.textContent = current + '%';
            }
        }
        setInterval(updateInfusion, 30000); // Update every 30 seconds
    </script>
</body>
</html>

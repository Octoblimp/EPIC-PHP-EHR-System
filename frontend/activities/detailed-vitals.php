<?php
/**
 * Detailed Vitals Entry Form
 * Matching Epic's Detailed Vitals Hyperspace interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

$patientId = $_GET['patient_id'] ?? 1;
$patientService = new PatientService();

try {
    $patient = $patientService->getById($patientId);
} catch (Exception $e) {
    $patient = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Vitals - Epic EHR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/epic-styles.css">
    <style>
        .vitals-container {
            display: flex;
            height: calc(100vh - 100px);
        }
        
        /* Left Navigation */
        .vitals-nav {
            width: 160px;
            background: #f5f5f5;
            border-right: 1px solid #ccc;
            padding: 8px 0;
        }
        
        .nav-section {
            padding: 4px 0;
        }
        
        .nav-section-title {
            padding: 4px 12px;
            font-size: 11px;
            color: #666;
            font-weight: 600;
        }
        
        .nav-item {
            padding: 4px 12px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .nav-item:hover {
            background: #e8f4fc;
        }
        
        .nav-item.active {
            background: #cce5ff;
            font-weight: 600;
        }
        
        .nav-item .checkbox {
            width: 14px;
            height: 14px;
        }
        
        .nav-item.expandable::before {
            content: '▶';
            font-size: 8px;
            margin-right: 4px;
        }
        
        .nav-item.expanded::before {
            content: '▼';
        }
        
        /* Main Vitals Form */
        .vitals-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .vitals-header {
            padding: 8px 16px;
            background: #f0f7ff;
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .vitals-title {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .vitals-instructions {
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        
        .vitals-form-area {
            flex: 1;
            overflow: auto;
            padding: 8px;
        }
        
        /* Vitals Grid Table */
        .vitals-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .vitals-grid th {
            background: linear-gradient(180deg, #f5f5f5 0%, #e8e8e8 100%);
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
        }
        
        .vitals-grid td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            vertical-align: middle;
        }
        
        .vitals-grid td:first-child {
            background: #f9f9f9;
            font-weight: 500;
            width: 130px;
        }
        
        .vitals-grid input[type="text"],
        .vitals-grid select {
            width: 100%;
            padding: 3px 6px;
            border: 1px solid #ccc;
            border-radius: 2px;
            font-size: 12px;
            box-sizing: border-box;
        }
        
        .vitals-grid input:focus,
        .vitals-grid select:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 2px rgba(0,102,204,0.3);
        }
        
        .vitals-grid .input-with-unit {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        
        .vitals-grid .unit {
            font-size: 10px;
            color: #666;
            white-space: nowrap;
        }
        
        /* Arrow indicators for rows with dropdown */
        .row-arrow {
            color: #c00;
            margin-right: 4px;
        }
        
        /* Pain Scale */
        .pain-scale {
            display: flex;
            gap: 2px;
            align-items: center;
        }
        
        .pain-btn {
            width: 32px;
            height: 24px;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
            font-size: 11px;
            border-radius: 2px;
        }
        
        .pain-btn:hover {
            background: #e8f4fc;
        }
        
        .pain-btn.selected {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        .pain-btn.no-pain {
            background: #e8f5e9;
        }
        
        .pain-btn.mild {
            background: #fff9c4;
        }
        
        .pain-btn.moderate {
            background: #ffe0b2;
        }
        
        .pain-btn.severe {
            background: #ffcdd2;
        }
        
        /* Section dividers */
        .section-row td {
            background: #e8f4fc !important;
            font-weight: 600;
            color: #0066cc;
        }
        
        /* Calculated fields */
        .calculated {
            background: #fffde7;
        }
        
        /* BP combined field */
        .bp-combined {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .bp-combined input {
            width: 45px !important;
        }
        
        .bp-combined .slash {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    
    <!-- Patient Header -->
    <div class="patient-header" style="padding: 8px 16px; background: #e8f4fd; border-bottom: 1px solid #ccc;">
        <div class="patient-info">
            <span class="patient-name" style="font-weight: 600; color: #0066cc;">
                <?= htmlspecialchars($patient['last_name'] ?? 'Northstar') ?>, <?= htmlspecialchars($patient['first_name'] ?? 'Jan') ?>
            </span>
            <span style="margin-left: 20px;">MRN: <?= htmlspecialchars($patient['mrn'] ?? 'E1404907') ?></span>
            <span style="margin-left: 20px;">DOB: <?= htmlspecialchars($patient['date_of_birth'] ?? '07/07/2003') ?></span>
            <span style="margin-left: 20px;">Age: <?= htmlspecialchars($patient['age'] ?? '9 y.o.') ?></span>
        </div>
        <div class="encounter-info" style="margin-top: 4px; font-size: 12px; color: #666;">
            <span>Visit: 8/27/2012 visit with Pat Limestone, MD for OFFICE VISIT - ear ache</span>
        </div>
    </div>
    
    <!-- Toolbar -->
    <div style="padding: 6px 12px; background: #f5f5f5; border-bottom: 1px solid #ccc; display: flex; gap: 8px;">
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            📷 Images
        </button>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            📚 References
        </button>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            🎬 Media Manager
        </button>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            👁️ Preview A/S
        </button>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            🖨️ Print A/S
        </button>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            📋 Outside Records
        </button>
        <div style="flex: 1;"></div>
        <button class="toolbar-btn" style="padding: 4px 12px; font-size: 12px; border: 1px solid #999; background: linear-gradient(180deg, #f5f5f5, #e0e0e0); border-radius: 3px; cursor: pointer;">
            ↔️ Resize
        </button>
    </div>
    
    <div class="vitals-container">
        <!-- Left Navigation -->
        <nav class="vitals-nav">
            <div class="nav-section">
                <div class="nav-item">Charting</div>
            </div>
            <div class="nav-section">
                <div class="nav-item expandable">Visit Navigator</div>
                <div class="nav-item" style="padding-left: 24px;">Visit Info</div>
                <div class="nav-item active" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox" checked>
                    Detailed Vitals
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Visit Rx Benefits
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Outpatient Meds
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Immunizations
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Progress Notes
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Problem list
                </div>
                <div class="nav-item" style="padding-left: 24px;">
                    <input type="checkbox" class="checkbox">
                    Goals
                </div>
            </div>
            <div class="nav-section">
                <div class="nav-item">Chart Review</div>
                <div class="nav-item">Prewarrants</div>
                <div class="nav-item">Results Review</div>
                <div class="nav-item">Growth Chart</div>
                <div class="nav-item">Medications</div>
                <div class="nav-item">Order Entry</div>
                <div class="nav-item">Patient Education</div>
                <div class="nav-item">Contraceptives</div>
            </div>
            <div class="nav-section">
                <div class="nav-item expandable">Visit Navigator</div>
                <div class="nav-item" style="padding-left: 24px;">MedPractice</div>
                <div class="nav-item" style="padding-left: 24px;">SmartSets</div>
                <div class="nav-item" style="padding-left: 24px;">Visit Diagnoses</div>
                <div class="nav-item" style="padding-left: 24px;">Meds & Orders</div>
            </div>
            <div class="nav-section">
                <div class="nav-item">LOS</div>
                <div class="nav-item">Discharge</div>
                <div class="nav-item">ld Instructions</div>
                <div class="nav-item">E-Prescribing</div>
                <div class="nav-item">Charge Capture</div>
                <div class="nav-item">Office Encounter</div>
            </div>
            <div class="nav-section">
                <div class="nav-item">More Activities ▼</div>
            </div>
        </nav>
        
        <!-- Main Vitals Form -->
        <main class="vitals-main">
            <div class="vitals-header">
                <div class="vitals-title">
                    <input type="checkbox" checked> Detailed Vitals
                </div>
                <div class="vitals-instructions">
                    *To flag data as significant, right click on the row name*
                </div>
            </div>
            
            <div class="vitals-form-area">
                <table class="vitals-grid">
                    <thead>
                        <tr>
                            <th style="width: 130px;"></th>
                            <th style="width: 100px;">Right arm</th>
                            <th style="width: 100px;">Left arm</th>
                            <th style="width: 100px;">Right leg</th>
                            <th style="width: 100px;">Left leg</th>
                            <th style="width: 150px;">Other (Comment)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- BP Row -->
                        <tr>
                            <td><span class="row-arrow">➜</span> BP</td>
                            <td>
                                <div class="bp-combined">
                                    <input type="text" placeholder="Sys">
                                    <span class="slash">/</span>
                                    <input type="text" placeholder="Dia">
                                </div>
                            </td>
                            <td>
                                <div class="bp-combined">
                                    <input type="text">
                                    <span class="slash">/</span>
                                    <input type="text">
                                </div>
                            </td>
                            <td>
                                <div class="bp-combined">
                                    <input type="text">
                                    <span class="slash">/</span>
                                    <input type="text">
                                </div>
                            </td>
                            <td>
                                <div class="bp-combined">
                                    <input type="text">
                                    <span class="slash">/</span>
                                    <input type="text">
                                </div>
                            </td>
                            <td><input type="text"></td>
                        </tr>
                        
                        <!-- BP Location -->
                        <tr>
                            <td><span class="row-arrow">➜</span> BP Location</td>
                            <td colspan="5">
                                <select style="width: auto;">
                                    <option value="">Select...</option>
                                    <option value="right_arm">Right arm</option>
                                    <option value="left_arm">Left arm</option>
                                    <option value="right_leg">Right leg</option>
                                    <option value="left_leg">Left leg</option>
                                </select>
                            </td>
                        </tr>
                        
                        <!-- BP Method -->
                        <tr>
                            <td><span class="row-arrow">➜</span> BP Method</td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="machine">Machine</option>
                                    <option value="manual">Manual</option>
                                    <option value="doppler">Doppler</option>
                                </select>
                            </td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="machine">Machine</option>
                                    <option value="manual">Manual</option>
                                    <option value="doppler">Doppler</option>
                                </select>
                            </td>
                            <td><input type="text" placeholder="Other (Comment)"></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- BP Cuff Size -->
                        <tr>
                            <td><span class="row-arrow">➜</span> BP Cuff Size</td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="neonatal">Neonate</option>
                                    <option value="infant">Infant</option>
                                    <option value="child">Child</option>
                                    <option value="child_long">Child Long</option>
                                    <option value="small_adult">Small Adult</option>
                                    <option value="adult">Adult</option>
                                    <option value="large_adult">Large Adult</option>
                                    <option value="thigh">Thigh</option>
                                </select>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Patient Position -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Patient Position</td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="lying">Lying</option>
                                    <option value="sitting">Sitting</option>
                                    <option value="standing">Standing</option>
                                </select>
                            </td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="lying">Lying</option>
                                    <option value="sitting">Sitting</option>
                                    <option value="standing">Standing</option>
                                </select>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Heart Rate -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Heart Rate</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">bpm</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Heart Rate Source -->
                        <tr>
                            <td>Heart Rate<br>Source</td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="monitor">Monitor</option>
                                    <option value="apical">Apical</option>
                                    <option value="right">Right</option>
                                    <option value="left">Left</option>
                                    <option value="brachial">Brachial</option>
                                    <option value="dorsalis_pedis">Dorsalis pedis</option>
                                    <option value="femoral">Femoral</option>
                                    <option value="radial">Radial</option>
                                </select>
                            </td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="carotid">Carotid</option>
                                    <option value="popliteal">Popliteal</option>
                                    <option value="pedal_rhythm_tiltd">Pedal rhythm tiltd</option>
                                </select>
                            </td>
                            <td><input type="text" placeholder="Other (Comment)"></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- Resp -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Resp</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">/min</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Temp -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Temp</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">°F</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Temp Source -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Temp Source</td>
                            <td>
                                <select>
                                    <option value="">Select...</option>
                                    <option value="oral">Oral</option>
                                    <option value="tympanic">Tympanic</option>
                                    <option value="rectal">Rectal</option>
                                    <option value="axillary">Axillary</option>
                                    <option value="temporal">Temporal</option>
                                </select>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        
                        <!-- SpO2 -->
                        <tr>
                            <td><span class="row-arrow">➜</span> SpO2</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">%</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Weight -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Weight</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <select style="width: 60px;">
                                        <option value="lb">lb</option>
                                        <option value="kg">kg</option>
                                        <option value="oz">oz</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Height -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Height</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 40px;" placeholder="ft">
                                    <span>'</span>
                                    <input type="text" style="width: 40px;" placeholder="in">
                                    <span>"</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Waist Circumference -->
                        <tr>
                            <td>Waist<br>Circumference</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;" value="1'9&quot;">
                                    <span class="unit">in</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Chest -->
                        <tr>
                            <td>Chest</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">in</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Arm Circumference -->
                        <tr>
                            <td>Arm<br>Circumference</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">in</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Peak Flow -->
                        <tr>
                            <td>Peak Flow</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">L/min</span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- BMI - Calculated -->
                        <tr>
                            <td>BMI (calculated)</td>
                            <td colspan="5" class="calculated">
                                <span id="bmiValue">--</span> kg/m²
                            </td>
                        </tr>
                        
                        <!-- Pain Section -->
                        <tr class="section-row">
                            <td colspan="6">Pain Assessment</td>
                        </tr>
                        
                        <!-- Pain Score -->
                        <tr>
                            <td><span class="row-arrow">➜</span> Pain Score</td>
                            <td colspan="5">
                                <div class="pain-scale">
                                    <button class="pain-btn no-pain" onclick="selectPain(this, 0)">Zero=<br>No pain</button>
                                    <button class="pain-btn" onclick="selectPain(this, 1)">One-</button>
                                    <button class="pain-btn mild" onclick="selectPain(this, 2)">Two-</button>
                                    <button class="pain-btn" onclick="selectPain(this, 3)">Three-</button>
                                    <button class="pain-btn moderate" onclick="selectPain(this, 4)">Four-</button>
                                    <button class="pain-btn" onclick="selectPain(this, 5)">Five-</button>
                                    <button class="pain-btn" onclick="selectPain(this, 6)">Six-</button>
                                    <button class="pain-btn moderate" onclick="selectPain(this, 7)">Seven-</button>
                                    <button class="pain-btn severe" onclick="selectPain(this, 8)">Eight-</button>
                                    <button class="pain-btn severe" onclick="selectPain(this, 9)">Nine-</button>
                                    <button class="pain-btn severe" onclick="selectPain(this, 10)">Ten-</button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Oxygen Section -->
                        <tr class="section-row">
                            <td colspan="6">Oxygen</td>
                        </tr>
                        
                        <tr>
                            <td>O2 Flow Rate</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">L/min</span>
                                </div>
                            </td>
                        </tr>
                        
                        <tr>
                            <td>O2 Delivery</td>
                            <td colspan="5">
                                <select>
                                    <option value="">Select...</option>
                                    <option value="room_air">Room Air</option>
                                    <option value="nasal_cannula">Nasal Cannula</option>
                                    <option value="simple_mask">Simple Mask</option>
                                    <option value="non_rebreather">Non-Rebreather Mask</option>
                                    <option value="venturi">Venturi Mask</option>
                                    <option value="cpap">CPAP</option>
                                    <option value="bipap">BiPAP</option>
                                    <option value="ventilator">Ventilator</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <td>FiO2</td>
                            <td colspan="5">
                                <div class="input-with-unit">
                                    <input type="text" style="width: 80px;">
                                    <span class="unit">%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Action Buttons -->
            <div style="padding: 12px 16px; background: #f5f5f5; border-top: 1px solid #ccc; display: flex; gap: 8px; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="clearForm()">Clear</button>
                <button class="btn btn-primary" onclick="saveVitals()">Accept</button>
            </div>
        </main>
    </div>
    
    <script>
        // Pain scale selection
        function selectPain(btn, value) {
            document.querySelectorAll('.pain-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        }
        
        // Calculate BMI
        function calculateBMI() {
            // Implement BMI calculation based on height/weight inputs
            const weightInput = document.querySelector('input[placeholder="lb"]') || 
                               document.querySelector('tr:has(td:contains("Weight")) input');
            const heightFt = document.querySelector('input[placeholder="ft"]');
            const heightIn = document.querySelector('input[placeholder="in"]');
            
            // BMI calculation would go here
        }
        
        // Save vitals
        function saveVitals() {
            // Collect all vital data and send to API
            const vitalsData = {
                // Collect form values
            };
            
            alert('Vitals saved successfully!');
        }
        
        // Clear form
        function clearForm() {
            document.querySelectorAll('.vitals-grid input').forEach(input => input.value = '');
            document.querySelectorAll('.vitals-grid select').forEach(select => select.selectedIndex = 0);
            document.querySelectorAll('.pain-btn').forEach(btn => btn.classList.remove('selected'));
        }
    </script>
</body>
</html>

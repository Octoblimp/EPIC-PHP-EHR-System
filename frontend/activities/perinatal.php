<?php
/**
 * Epic EHR - Perinatal Module
 * Labor & Delivery, Pregnancy Summary, Fetal Monitoring
 */
session_start();
require_once __DIR__ . '/../includes/api.php';
$patient_id = $_GET['patient_id'] ?? 4;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic EHR - Perinatal</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eef5;
            min-height: 100vh;
        }
        
        .perinatal-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header */
        .peri-header {
            background: linear-gradient(to bottom, #8e24aa, #6a1b9a);
            color: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .peri-header h1 {
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
            border-bottom: 2px solid #8e24aa;
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
            font-size: 20px;
        }
        
        .patient-info h2 {
            font-size: 16px;
            color: #6a1b9a;
        }
        
        .patient-info .meta {
            font-size: 11px;
            color: #666;
        }
        
        .pregnancy-info {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }
        
        .preg-stat {
            text-align: center;
            padding: 5px 15px;
            border-left: 1px solid #ddd;
        }
        
        .preg-stat .label {
            font-size: 10px;
            color: #666;
        }
        
        .preg-stat .value {
            font-size: 14px;
            font-weight: bold;
            color: #6a1b9a;
        }
        
        /* Navigation Tabs */
        .peri-tabs {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            padding: 0 16px;
        }
        
        .peri-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: #666;
        }
        
        .peri-tab:hover {
            background: #fff;
        }
        
        .peri-tab.active {
            border-bottom-color: #8e24aa;
            color: #8e24aa;
            font-weight: 600;
            background: #fff;
        }
        
        /* Main Content */
        .peri-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        /* Left Panel - EFM Strip */
        .efm-panel {
            width: 400px;
            background: #000;
            border-right: 1px solid #333;
            display: flex;
            flex-direction: column;
        }
        
        .efm-header {
            background: #222;
            padding: 8px 12px;
            color: #fff;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
        }
        
        .efm-header .title {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .efm-strip {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        
        .efm-grid {
            height: 50%;
            border-bottom: 1px solid #333;
            position: relative;
        }
        
        .efm-label {
            position: absolute;
            left: 8px;
            top: 8px;
            color: #4caf50;
            font-size: 10px;
            font-family: monospace;
        }
        
        .efm-value {
            position: absolute;
            right: 8px;
            top: 8px;
            color: #4caf50;
            font-size: 24px;
            font-family: monospace;
            font-weight: bold;
        }
        
        .efm-scale {
            position: absolute;
            right: 60px;
            top: 8px;
            color: #666;
            font-size: 8px;
            font-family: monospace;
            text-align: right;
        }
        
        .fhr-line {
            stroke: #4caf50;
            stroke-width: 2;
            fill: none;
        }
        
        .uc-line {
            stroke: #ff9800;
            stroke-width: 2;
            fill: none;
        }
        
        /* Center Panel */
        .center-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Labor Timeline */
        .labor-section {
            background: #fff;
            margin: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            flex: 1;
            overflow: auto;
        }
        
        .section-header {
            padding: 10px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Labor Progress Grid */
        .labor-grid {
            display: grid;
            grid-template-columns: 100px repeat(12, 1fr);
            font-size: 11px;
        }
        
        .grid-header {
            background: #f5f5f5;
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #eee;
            font-weight: 600;
        }
        
        .grid-label {
            background: #fafafa;
            padding: 8px;
            border-bottom: 1px solid #eee;
            border-right: 1px solid #ddd;
            font-weight: 500;
        }
        
        .grid-cell {
            padding: 8px;
            text-align: center;
            border-bottom: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        
        .grid-cell.highlight {
            background: #e8f5e9;
        }
        
        .cervix-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            background: #e1bee7;
            color: #6a1b9a;
            font-weight: bold;
        }
        
        /* Right Panel */
        .right-panel {
            width: 320px;
            background: #fff;
            border-left: 1px solid #ddd;
            overflow-y: auto;
        }
        
        .info-card {
            margin: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .info-card-header {
            padding: 8px 12px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 12px;
        }
        
        .info-card-body {
            padding: 12px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 11px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-row .label {
            color: #666;
        }
        
        .info-row .value {
            font-weight: 500;
        }
        
        /* Alerts */
        .alert-card {
            border-color: #ffcdd2;
        }
        
        .alert-card .info-card-header {
            background: #ffebee;
            color: #c62828;
        }
        
        .alert-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            font-size: 11px;
        }
        
        .alert-icon {
            color: #c62828;
        }
        
        /* Action Buttons */
        .action-buttons {
            padding: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .action-btn {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            border: 1px solid #8e24aa;
            background: #fff;
            color: #8e24aa;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-align: center;
        }
        
        .action-btn:hover {
            background: #f3e5f5;
        }
        
        .action-btn.primary {
            background: #8e24aa;
            color: #fff;
        }
        
        .action-btn.primary:hover {
            background: #6a1b9a;
        }
        
        /* Timeline */
        .timeline {
            padding: 12px;
        }
        
        .timeline-item {
            display: flex;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .timeline-time {
            width: 50px;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
        
        .timeline-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #8e24aa;
            margin-top: 3px;
        }
        
        .timeline-content {
            flex: 1;
            font-size: 11px;
        }
        
        .timeline-content .title {
            font-weight: 600;
        }
        
        .timeline-content .desc {
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="perinatal-container">
        <!-- Header -->
        <header class="peri-header">
            <h1>🤰 Perinatal Module</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="window.location.href='../home.php'">← Back</button>
                <button class="header-btn">🖨️ Print</button>
                <button class="header-btn">📋 Summary</button>
            </div>
        </header>
        
        <!-- Patient Banner -->
        <div class="patient-banner">
            <div class="patient-photo">🤰</div>
            <div class="patient-info">
                <h2>Williams, Sarah</h2>
                <div class="meta">MRN: 004567 | DOB: 11/05/1990 | 33F | L&D Room 205</div>
            </div>
            <div class="pregnancy-info">
                <div class="preg-stat">
                    <div class="label">G/P</div>
                    <div class="value">G2P1</div>
                </div>
                <div class="preg-stat">
                    <div class="label">EGA</div>
                    <div class="value">38w 4d</div>
                </div>
                <div class="preg-stat">
                    <div class="label">EDD</div>
                    <div class="value">02/15/2024</div>
                </div>
                <div class="preg-stat">
                    <div class="label">GBS</div>
                    <div class="value" style="color: #4caf50;">Negative</div>
                </div>
                <div class="preg-stat">
                    <div class="label">Blood Type</div>
                    <div class="value">O+</div>
                </div>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="peri-tabs">
            <button class="peri-tab active">Labor Progress</button>
            <button class="peri-tab">Fetal Monitor</button>
            <button class="peri-tab">Pregnancy Summary</button>
            <button class="peri-tab">Prenatal Labs</button>
            <button class="peri-tab">Delivery</button>
            <button class="peri-tab">Postpartum</button>
            <button class="peri-tab">Newborn</button>
        </div>
        
        <!-- Main Content -->
        <div class="peri-content">
            <!-- EFM Strip Panel -->
            <div class="efm-panel">
                <div class="efm-header">
                    <div class="title">
                        <span>📊</span>
                        <span>Fetal Heart Rate Monitor</span>
                    </div>
                    <span>Live</span>
                </div>
                <div class="efm-strip">
                    <!-- FHR Graph -->
                    <div class="efm-grid">
                        <div class="efm-label">FHR (bpm)</div>
                        <div class="efm-value">145</div>
                        <div class="efm-scale">
                            210<br>180<br>150<br>120<br>90<br>60
                        </div>
                        <svg width="100%" height="100%" viewBox="0 0 400 150" preserveAspectRatio="none">
                            <defs>
                                <pattern id="grid-fhr" width="20" height="15" patternUnits="userSpaceOnUse">
                                    <path d="M 20 0 L 0 0 0 15" fill="none" stroke="#333" stroke-width="0.5"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid-fhr)"/>
                            <!-- FHR Trace -->
                            <path class="fhr-line" d="M 0 75 Q 10 70 20 75 T 40 73 T 60 78 T 80 72 T 100 76 T 120 70 T 140 75 T 160 68 T 180 74 T 200 72 T 220 78 T 240 73 T 260 76 T 280 71 T 300 75 T 320 73 T 340 77 T 360 74 T 380 76 T 400 73"/>
                        </svg>
                    </div>
                    <!-- Contractions Graph -->
                    <div class="efm-grid">
                        <div class="efm-label" style="color: #ff9800;">TOCO (mmHg)</div>
                        <div class="efm-value" style="color: #ff9800;">42</div>
                        <div class="efm-scale">
                            100<br>75<br>50<br>25<br>0
                        </div>
                        <svg width="100%" height="100%" viewBox="0 0 400 150" preserveAspectRatio="none">
                            <rect width="100%" height="100%" fill="url(#grid-fhr)"/>
                            <!-- Contraction Trace -->
                            <path class="uc-line" d="M 0 120 L 30 120 Q 50 120 60 80 Q 70 40 80 80 Q 90 120 100 120 L 180 120 Q 200 120 210 70 Q 220 30 230 70 Q 240 120 250 120 L 330 120 Q 350 120 360 85 Q 370 50 380 85 Q 390 120 400 120"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Center Panel -->
            <div class="center-panel">
                <!-- Labor Progress -->
                <div class="labor-section">
                    <div class="section-header">
                        <span>📈</span>
                        <span>Labor Progress</span>
                    </div>
                    <div class="labor-grid">
                        <div class="grid-header">Time</div>
                        <div class="grid-header">08:00</div>
                        <div class="grid-header">09:00</div>
                        <div class="grid-header">10:00</div>
                        <div class="grid-header">11:00</div>
                        <div class="grid-header">12:00</div>
                        <div class="grid-header">13:00</div>
                        <div class="grid-header">14:00</div>
                        <div class="grid-header">15:00</div>
                        <div class="grid-header">16:00</div>
                        <div class="grid-header">17:00</div>
                        <div class="grid-header">18:00</div>
                        <div class="grid-header">Now</div>
                        
                        <div class="grid-label">Dilation (cm)</div>
                        <div class="grid-cell">4</div>
                        <div class="grid-cell">4</div>
                        <div class="grid-cell">5</div>
                        <div class="grid-cell">5</div>
                        <div class="grid-cell">6</div>
                        <div class="grid-cell">6</div>
                        <div class="grid-cell">7</div>
                        <div class="grid-cell">8</div>
                        <div class="grid-cell">8</div>
                        <div class="grid-cell">9</div>
                        <div class="grid-cell">9</div>
                        <div class="grid-cell highlight"><span class="cervix-badge">10</span></div>
                        
                        <div class="grid-label">Effacement (%)</div>
                        <div class="grid-cell">70</div>
                        <div class="grid-cell">80</div>
                        <div class="grid-cell">80</div>
                        <div class="grid-cell">90</div>
                        <div class="grid-cell">90</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell">100</div>
                        <div class="grid-cell highlight">100</div>
                        
                        <div class="grid-label">Station</div>
                        <div class="grid-cell">-2</div>
                        <div class="grid-cell">-2</div>
                        <div class="grid-cell">-1</div>
                        <div class="grid-cell">-1</div>
                        <div class="grid-cell">0</div>
                        <div class="grid-cell">0</div>
                        <div class="grid-cell">0</div>
                        <div class="grid-cell">+1</div>
                        <div class="grid-cell">+1</div>
                        <div class="grid-cell">+1</div>
                        <div class="grid-cell">+2</div>
                        <div class="grid-cell highlight">+2</div>
                        
                        <div class="grid-label">FHR Baseline</div>
                        <div class="grid-cell">140</div>
                        <div class="grid-cell">142</div>
                        <div class="grid-cell">138</div>
                        <div class="grid-cell">145</div>
                        <div class="grid-cell">144</div>
                        <div class="grid-cell">140</div>
                        <div class="grid-cell">142</div>
                        <div class="grid-cell">148</div>
                        <div class="grid-cell">145</div>
                        <div class="grid-cell">144</div>
                        <div class="grid-cell">146</div>
                        <div class="grid-cell highlight">145</div>
                        
                        <div class="grid-label">Contractions</div>
                        <div class="grid-cell">q5</div>
                        <div class="grid-cell">q5</div>
                        <div class="grid-cell">q4</div>
                        <div class="grid-cell">q4</div>
                        <div class="grid-cell">q4</div>
                        <div class="grid-cell">q3</div>
                        <div class="grid-cell">q3</div>
                        <div class="grid-cell">q3</div>
                        <div class="grid-cell">q2-3</div>
                        <div class="grid-cell">q2-3</div>
                        <div class="grid-cell">q2</div>
                        <div class="grid-cell highlight">q2</div>
                        
                        <div class="grid-label">Epidural</div>
                        <div class="grid-cell">-</div>
                        <div class="grid-cell">-</div>
                        <div class="grid-cell" style="background: #e3f2fd;">Placed</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell">✓</div>
                        <div class="grid-cell highlight">✓</div>
                        
                        <div class="grid-label">Pain (0-10)</div>
                        <div class="grid-cell">7</div>
                        <div class="grid-cell">8</div>
                        <div class="grid-cell">4</div>
                        <div class="grid-cell">2</div>
                        <div class="grid-cell">2</div>
                        <div class="grid-cell">3</div>
                        <div class="grid-cell">2</div>
                        <div class="grid-cell">3</div>
                        <div class="grid-cell">4</div>
                        <div class="grid-cell">5</div>
                        <div class="grid-cell">6</div>
                        <div class="grid-cell highlight">6</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Quick Actions -->
                <div class="action-buttons">
                    <button class="action-btn primary">📝 Cervical Exam</button>
                    <button class="action-btn primary">💊 Give Medication</button>
                    <button class="action-btn">📊 FHR Assessment</button>
                    <button class="action-btn">🏃 Ambulation</button>
                </div>
                
                <!-- Current Status -->
                <div class="info-card">
                    <div class="info-card-header">Current Status</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Labor Stage</span>
                            <span class="value" style="color: #8e24aa;">Active - Second Stage</span>
                        </div>
                        <div class="info-row">
                            <span class="label">ROM Status</span>
                            <span class="value">AROM @ 10:30</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Fluid Color</span>
                            <span class="value" style="color: #4caf50;">Clear</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Fetal Position</span>
                            <span class="value">LOA</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Presentation</span>
                            <span class="value">Vertex</span>
                        </div>
                    </div>
                </div>
                
                <!-- FHR Summary -->
                <div class="info-card">
                    <div class="info-card-header">FHR Interpretation</div>
                    <div class="info-card-body">
                        <div class="info-row">
                            <span class="label">Category</span>
                            <span class="value" style="color: #4caf50;">Category I</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Baseline</span>
                            <span class="value">140-150 bpm</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Variability</span>
                            <span class="value" style="color: #4caf50;">Moderate</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Accelerations</span>
                            <span class="value">Present</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Decelerations</span>
                            <span class="value">None</span>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts -->
                <div class="info-card alert-card">
                    <div class="info-card-header">⚠️ Alerts</div>
                    <div class="info-card-body">
                        <div class="alert-item">
                            <span class="alert-icon">⚠️</span>
                            <span>GBS Prophylaxis due (>4 hrs since dose)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="info-card">
                    <div class="info-card-header">Labor Timeline</div>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-time">06:30</div>
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="title">Admission</div>
                                <div class="desc">4cm dilated, 70% effaced, -2 station</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-time">10:00</div>
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="title">Epidural Placed</div>
                                <div class="desc">Dr. Martinez, Anesthesiology</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-time">10:30</div>
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="title">AROM</div>
                                <div class="desc">Clear fluid, moderate amount</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-time">14:00</div>
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="title">Active Labor</div>
                                <div class="desc">6cm dilated, 100% effaced</div>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-time">18:30</div>
                            <div class="timeline-dot" style="background: #4caf50;"></div>
                            <div class="timeline-content">
                                <div class="title">Complete</div>
                                <div class="desc">10cm, +2 station, ready to push</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.peri-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.peri-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });
        
        // Animate FHR value
        function animateFHR() {
            const fhrValue = document.querySelector('.efm-value');
            if (fhrValue) {
                const base = 145;
                const variation = Math.floor(Math.random() * 10) - 5;
                fhrValue.textContent = base + variation;
            }
        }
        setInterval(animateFHR, 2000);
    </script>
</body>
</html>

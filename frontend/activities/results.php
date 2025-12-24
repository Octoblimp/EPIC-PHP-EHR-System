<?php
/**
 * Results Activity
 * Lab Results, Radiology Results, Microbiology Results viewing
 * With trending/graphing capabilities
 */
session_start();
require_once __DIR__ . '/../includes/api.php';

$patientId = $_GET['patient_id'] ?? null;
$encounterId = $_GET['encounter_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results Review - Epic EHR</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        .results-container {
            display: flex;
            height: calc(100vh - 180px);
            background: #fff;
        }
        
        /* Left Navigation */
        .results-nav {
            width: 220px;
            border-right: 1px solid #ccc;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .nav-header {
            padding: 10px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            font-size: 12px;
        }
        
        .nav-section {
            border-bottom: 1px solid #ddd;
        }
        
        .nav-section-header {
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            background: #e8e8e8;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .nav-section-header:hover {
            background: #ddd;
        }
        
        .nav-item {
            padding: 6px 12px 6px 24px;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-item:hover {
            background: #e3f2fd;
        }
        
        .nav-item.active {
            background: #bbdefb;
            font-weight: 500;
        }
        
        .nav-item .count {
            background: #e0e0e0;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
        }
        
        .nav-item.has-new .count {
            background: #ffcdd2;
            color: #c62828;
        }
        
        /* Main Results Area */
        .results-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .results-toolbar {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-toolbar input[type="search"] {
            width: 200px;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .results-toolbar select {
            padding: 4px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .results-toolbar button {
            padding: 4px 10px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .results-toolbar button:hover {
            background: #f0f0f0;
        }
        
        .results-toolbar .spacer {
            flex: 1;
        }
        
        /* Results Table */
        .results-content {
            flex: 1;
            overflow: auto;
            padding: 0;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .results-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ccc;
            position: sticky;
            top: 0;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .results-table th:hover {
            background: #e0e0e0;
        }
        
        .results-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .results-table tr:hover {
            background: #f5f5f5;
        }
        
        .results-table tr.abnormal {
            background: #fff8e1;
        }
        
        .results-table tr.critical {
            background: #ffebee;
        }
        
        .result-value {
            font-weight: 500;
        }
        
        .result-value.high {
            color: #d32f2f;
        }
        
        .result-value.low {
            color: #1976d2;
        }
        
        .result-value.critical {
            color: #fff;
            background: #d32f2f;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 700;
        }
        
        .result-flag {
            display: inline-block;
            width: 16px;
            font-size: 10px;
            font-weight: 700;
        }
        
        .result-flag.H { color: #d32f2f; }
        .result-flag.L { color: #1976d2; }
        .result-flag.C { color: #fff; background: #d32f2f; padding: 0 3px; border-radius: 2px; }
        
        .result-trend {
            font-size: 12px;
            cursor: pointer;
        }
        
        .result-trend:hover {
            color: #1976d2;
        }
        
        /* Lab Panels */
        .panel-header {
            background: #e3f2fd;
            padding: 8px 12px;
            font-weight: 600;
            border-bottom: 1px solid #90caf9;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .panel-header:hover {
            background: #bbdefb;
        }
        
        .panel-header .expand-icon {
            font-size: 10px;
        }
        
        .panel-header .panel-date {
            margin-left: auto;
            font-weight: normal;
            color: #666;
            font-size: 10px;
        }
        
        .panel-content {
            display: none;
        }
        
        .panel-content.expanded {
            display: table-row-group;
        }
        
        /* Trending/Graph Panel */
        .trend-panel {
            width: 400px;
            border-left: 1px solid #ccc;
            background: #f8f9fa;
            display: none;
            flex-direction: column;
        }
        
        .trend-panel.visible {
            display: flex;
        }
        
        .trend-header {
            padding: 10px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .trend-header h3 {
            font-size: 12px;
            margin: 0;
        }
        
        .trend-header button {
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }
        
        .trend-chart {
            flex: 1;
            padding: 16px;
            overflow: auto;
        }
        
        .chart-container {
            height: 200px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 16px;
            position: relative;
        }
        
        .chart-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        
        /* Simple SVG chart */
        .trend-graph {
            width: 100%;
            height: 100%;
        }
        
        .trend-history {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .trend-history-header {
            padding: 8px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 11px;
        }
        
        .trend-history-item {
            padding: 6px 12px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }
        
        .trend-history-item:last-child {
            border-bottom: none;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .status-badge.final { background: #e8f5e9; color: #2e7d32; }
        .status-badge.preliminary { background: #fff3e0; color: #e65100; }
        .status-badge.pending { background: #fce4ec; color: #c2185b; }
        
        /* Quick filter tabs */
        .filter-tabs {
            display: flex;
            gap: 2px;
            padding: 8px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        
        .filter-tab {
            padding: 4px 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            background: #fff;
        }
        
        .filter-tab:hover {
            background: #e8e8e8;
        }
        
        .filter-tab.active {
            background: #1976d2;
            color: #fff;
            border-color: #1565c0;
        }
    </style>
</head>
<body>
    <!-- Include Epic Header -->
    <div class="epic-header">
        <div class="header-left">
            <span class="epic-logo">Epic</span>
            <span class="header-title">Results Review</span>
        </div>
        <div class="header-right">
            <span class="user-info">User: <?= htmlspecialchars($_SESSION['user_name'] ?? 'System User') ?></span>
        </div>
    </div>
    
    <!-- Patient Banner -->
    <?php 
    $patient = [
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'mrn' => 'MRN001234',
        'date_of_birth' => '01/15/1965',
        'age' => '59 yrs',
        'sex' => 'Male',
        'allergies' => ['Penicillin', 'Sulfa']
    ];
    include __DIR__ . '/../templates/patient-banner.php';
    ?>
    
    <!-- Activity Tabs -->
    <div class="activity-tabs" style="background: #f0f0f0; border-bottom: 1px solid #ccc; padding: 0 10px;">
        <a href="summary-index.php" class="activity-tab">Summary</a>
        <a href="chart-review.php" class="activity-tab">Chart Review</a>
        <a href="mar.php" class="activity-tab">MAR</a>
        <a href="flowsheets.php" class="activity-tab">Flowsheets</a>
        <a href="../notes.php" class="activity-tab">Notes</a>
        <a href="../orders.php" class="activity-tab">Orders</a>
        <a href="results.php" class="activity-tab active" style="background: #fff; border: 1px solid #ccc; border-bottom: none; padding: 6px 16px; margin-bottom: -1px;">Results</a>
    </div>
    
    <div class="results-container">
        <!-- Left Navigation -->
        <div class="results-nav">
            <div class="nav-header">Results Categories</div>
            
            <!-- Labs -->
            <div class="nav-section">
                <div class="nav-section-header" onclick="toggleNavSection(this)">
                    <span>▼</span>
                    <span>🔬 Laboratory</span>
                </div>
                <div class="nav-items">
                    <div class="nav-item active has-new" onclick="showResults('labs-72h')">
                        <span>Last 72 Hours</span>
                        <span class="count">12</span>
                    </div>
                    <div class="nav-item" onclick="showResults('labs-admission')">
                        <span>This Admission</span>
                        <span class="count">45</span>
                    </div>
                    <div class="nav-item" onclick="showResults('labs-all')">
                        <span>All Labs</span>
                        <span class="count">156</span>
                    </div>
                    <div class="nav-item" onclick="showResults('labs-pending')">
                        <span>Pending Results</span>
                        <span class="count">3</span>
                    </div>
                </div>
            </div>
            
            <!-- Microbiology -->
            <div class="nav-section">
                <div class="nav-section-header" onclick="toggleNavSection(this)">
                    <span>▼</span>
                    <span>🦠 Microbiology</span>
                </div>
                <div class="nav-items">
                    <div class="nav-item has-new" onclick="showResults('micro-recent')">
                        <span>Recent</span>
                        <span class="count">4</span>
                    </div>
                    <div class="nav-item" onclick="showResults('micro-cultures')">
                        <span>Cultures</span>
                        <span class="count">6</span>
                    </div>
                    <div class="nav-item" onclick="showResults('micro-sensitivities')">
                        <span>Sensitivities</span>
                        <span class="count">2</span>
                    </div>
                </div>
            </div>
            
            <!-- Radiology -->
            <div class="nav-section">
                <div class="nav-section-header" onclick="toggleNavSection(this)">
                    <span>▼</span>
                    <span>📷 Radiology</span>
                </div>
                <div class="nav-items">
                    <div class="nav-item" onclick="showResults('rad-recent')">
                        <span>Recent</span>
                        <span class="count">3</span>
                    </div>
                    <div class="nav-item" onclick="showResults('rad-xray')">
                        <span>X-Ray</span>
                        <span class="count">5</span>
                    </div>
                    <div class="nav-item" onclick="showResults('rad-ct')">
                        <span>CT</span>
                        <span class="count">2</span>
                    </div>
                    <div class="nav-item" onclick="showResults('rad-mri')">
                        <span>MRI</span>
                        <span class="count">1</span>
                    </div>
                    <div class="nav-item" onclick="showResults('rad-us')">
                        <span>Ultrasound</span>
                        <span class="count">2</span>
                    </div>
                </div>
            </div>
            
            <!-- Cardiology -->
            <div class="nav-section">
                <div class="nav-section-header" onclick="toggleNavSection(this)">
                    <span>▶</span>
                    <span>❤️ Cardiology</span>
                </div>
                <div class="nav-items" style="display: none;">
                    <div class="nav-item" onclick="showResults('card-ecg')">
                        <span>ECG</span>
                        <span class="count">3</span>
                    </div>
                    <div class="nav-item" onclick="showResults('card-echo')">
                        <span>Echo</span>
                        <span class="count">1</span>
                    </div>
                </div>
            </div>
            
            <!-- Pathology -->
            <div class="nav-section">
                <div class="nav-section-header" onclick="toggleNavSection(this)">
                    <span>▶</span>
                    <span>🔬 Pathology</span>
                </div>
                <div class="nav-items" style="display: none;">
                    <div class="nav-item" onclick="showResults('path-surgical')">
                        <span>Surgical Pathology</span>
                        <span class="count">0</span>
                    </div>
                    <div class="nav-item" onclick="showResults('path-cytology')">
                        <span>Cytology</span>
                        <span class="count">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Results Area -->
        <div class="results-main">
            <div class="results-toolbar">
                <input type="search" placeholder="Search results..." id="resultsSearch">
                <select id="dateRange">
                    <option value="72h">Last 72 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="admission">This Admission</option>
                    <option value="all">All Results</option>
                </select>
                <button onclick="refreshResults()">🔄 Refresh</button>
                <div class="spacer"></div>
                <button onclick="printResults()">🖨️ Print</button>
                <button onclick="toggleTrend()" id="trendBtn">📈 Show Trend</button>
            </div>
            
            <!-- Quick Filters -->
            <div class="filter-tabs">
                <span class="filter-tab active" onclick="filterResults('all')">All</span>
                <span class="filter-tab" onclick="filterResults('abnormal')">Abnormal</span>
                <span class="filter-tab" onclick="filterResults('critical')">Critical</span>
                <span class="filter-tab" onclick="filterResults('pending')">Pending</span>
                <span class="filter-tab" onclick="filterResults('new')">New</span>
            </div>
            
            <div class="results-content" id="resultsContent">
                <!-- Results will be populated here -->
            </div>
        </div>
        
        <!-- Trend Panel -->
        <div class="trend-panel" id="trendPanel">
            <div class="trend-header">
                <h3 id="trendTitle">Trend: Hemoglobin</h3>
                <button onclick="closeTrend()">✕</button>
            </div>
            <div class="trend-chart">
                <div class="chart-container">
                    <svg class="trend-graph" id="trendGraph" viewBox="0 0 360 160">
                        <!-- Reference range background -->
                        <rect x="40" y="40" width="300" height="60" fill="#e8f5e9" opacity="0.5"/>
                        
                        <!-- Grid lines -->
                        <line x1="40" y1="20" x2="40" y2="140" stroke="#ccc" stroke-width="1"/>
                        <line x1="40" y1="140" x2="340" y2="140" stroke="#ccc" stroke-width="1"/>
                        
                        <!-- Y axis labels -->
                        <text x="35" y="25" text-anchor="end" font-size="8" fill="#666">16</text>
                        <text x="35" y="55" text-anchor="end" font-size="8" fill="#666">14</text>
                        <text x="35" y="85" text-anchor="end" font-size="8" fill="#666">12</text>
                        <text x="35" y="115" text-anchor="end" font-size="8" fill="#666">10</text>
                        <text x="35" y="145" text-anchor="end" font-size="8" fill="#666">8</text>
                        
                        <!-- Data line -->
                        <polyline 
                            points="60,95 110,90 160,85 210,80 260,75 310,72" 
                            fill="none" 
                            stroke="#1976d2" 
                            stroke-width="2"
                        />
                        
                        <!-- Data points -->
                        <circle cx="60" cy="95" r="4" fill="#1976d2"/>
                        <circle cx="110" cy="90" r="4" fill="#1976d2"/>
                        <circle cx="160" cy="85" r="4" fill="#1976d2"/>
                        <circle cx="210" cy="80" r="4" fill="#1976d2"/>
                        <circle cx="260" cy="75" r="4" fill="#1976d2"/>
                        <circle cx="310" cy="72" r="4" fill="#1976d2"/>
                        
                        <!-- X axis labels -->
                        <text x="60" y="155" text-anchor="middle" font-size="7" fill="#666">1/10</text>
                        <text x="110" y="155" text-anchor="middle" font-size="7" fill="#666">1/11</text>
                        <text x="160" y="155" text-anchor="middle" font-size="7" fill="#666">1/12</text>
                        <text x="210" y="155" text-anchor="middle" font-size="7" fill="#666">1/13</text>
                        <text x="260" y="155" text-anchor="middle" font-size="7" fill="#666">1/14</text>
                        <text x="310" y="155" text-anchor="middle" font-size="7" fill="#666">1/15</text>
                    </svg>
                </div>
                
                <div class="trend-history">
                    <div class="trend-history-header">Historical Values</div>
                    <div id="trendHistory">
                        <!-- History items populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Sample lab data
        const labResults = [
            {
                panel: 'Complete Blood Count',
                date: '01/15/2024 06:00',
                status: 'Final',
                tests: [
                    { name: 'WBC', value: '8.2', unit: 'K/uL', range: '4.5-11.0', flag: '', trend: [12.4, 10.2, 9.1, 8.5, 8.2] },
                    { name: 'RBC', value: '4.1', unit: 'M/uL', range: '4.5-5.5', flag: 'L', trend: [4.0, 4.0, 4.1, 4.1, 4.1] },
                    { name: 'Hemoglobin', value: '11.8', unit: 'g/dL', range: '13.5-17.5', flag: 'L', trend: [11.2, 11.5, 11.6, 11.7, 11.8] },
                    { name: 'Hematocrit', value: '35.4', unit: '%', range: '40-52', flag: 'L', trend: [33.6, 34.5, 34.8, 35.1, 35.4] },
                    { name: 'MCV', value: '86.3', unit: 'fL', range: '80-100', flag: '' },
                    { name: 'MCH', value: '28.8', unit: 'pg', range: '27-33', flag: '' },
                    { name: 'MCHC', value: '33.3', unit: 'g/dL', range: '31.5-35.5', flag: '' },
                    { name: 'RDW', value: '13.5', unit: '%', range: '11.5-14.5', flag: '' },
                    { name: 'Platelet Count', value: '245', unit: 'K/uL', range: '150-400', flag: '' }
                ]
            },
            {
                panel: 'Basic Metabolic Panel',
                date: '01/15/2024 06:00',
                status: 'Final',
                tests: [
                    { name: 'Sodium', value: '138', unit: 'mEq/L', range: '136-145', flag: '', trend: [139, 138, 137, 138, 138] },
                    { name: 'Potassium', value: '4.2', unit: 'mEq/L', range: '3.5-5.0', flag: '' },
                    { name: 'Chloride', value: '102', unit: 'mEq/L', range: '98-106', flag: '' },
                    { name: 'CO2', value: '24', unit: 'mEq/L', range: '23-29', flag: '' },
                    { name: 'BUN', value: '18', unit: 'mg/dL', range: '7-20', flag: '' },
                    { name: 'Creatinine', value: '1.1', unit: 'mg/dL', range: '0.7-1.3', flag: '', trend: [1.3, 1.2, 1.2, 1.1, 1.1] },
                    { name: 'Glucose', value: '142', unit: 'mg/dL', range: '70-100', flag: 'H', trend: [189, 165, 158, 150, 142] },
                    { name: 'Calcium', value: '9.2', unit: 'mg/dL', range: '8.5-10.5', flag: '' }
                ]
            },
            {
                panel: 'Cardiac Markers',
                date: '01/14/2024 18:00',
                status: 'Final',
                tests: [
                    { name: 'Troponin I', value: '<0.01', unit: 'ng/mL', range: '<0.04', flag: '' },
                    { name: 'BNP', value: '285', unit: 'pg/mL', range: '<100', flag: 'H', trend: [340, 320, 305, 295, 285] },
                    { name: 'CK', value: '95', unit: 'U/L', range: '30-200', flag: '' },
                    { name: 'CK-MB', value: '2.1', unit: 'ng/mL', range: '0-5', flag: '' }
                ]
            },
            {
                panel: 'Liver Function Tests',
                date: '01/14/2024 06:00',
                status: 'Final',
                tests: [
                    { name: 'Total Protein', value: '7.2', unit: 'g/dL', range: '6.0-8.3', flag: '' },
                    { name: 'Albumin', value: '3.8', unit: 'g/dL', range: '3.5-5.0', flag: '' },
                    { name: 'Total Bilirubin', value: '0.8', unit: 'mg/dL', range: '0.1-1.2', flag: '' },
                    { name: 'Direct Bilirubin', value: '0.2', unit: 'mg/dL', range: '0.0-0.3', flag: '' },
                    { name: 'AST', value: '28', unit: 'U/L', range: '10-40', flag: '' },
                    { name: 'ALT', value: '32', unit: 'U/L', range: '7-56', flag: '' },
                    { name: 'Alk Phos', value: '78', unit: 'U/L', range: '44-147', flag: '' }
                ]
            },
            {
                panel: 'Coagulation',
                date: '01/13/2024 14:00',
                status: 'Final',
                tests: [
                    { name: 'PT', value: '12.5', unit: 'sec', range: '11.0-13.5', flag: '' },
                    { name: 'INR', value: '1.0', unit: '', range: '0.9-1.1', flag: '' },
                    { name: 'PTT', value: '32', unit: 'sec', range: '25-35', flag: '' }
                ]
            }
        ];
        
        // Render lab results
        function renderLabResults() {
            const content = document.getElementById('resultsContent');
            
            let html = '<table class="results-table">';
            html += `
                <thead>
                    <tr>
                        <th style="width: 200px;">Test Name</th>
                        <th style="width: 100px;">Value</th>
                        <th style="width: 30px;">Flag</th>
                        <th style="width: 120px;">Reference Range</th>
                        <th style="width: 80px;">Units</th>
                        <th style="width: 40px;">Trend</th>
                    </tr>
                </thead>
            `;
            
            labResults.forEach((panel, idx) => {
                html += `
                    <tbody class="panel-content expanded">
                        <tr>
                            <td colspan="6" class="panel-header" onclick="togglePanel(${idx})">
                                <span class="expand-icon">▼</span>
                                <strong>${panel.panel}</strong>
                                <span class="status-badge ${panel.status.toLowerCase()}">${panel.status}</span>
                                <span class="panel-date">${panel.date}</span>
                            </td>
                        </tr>
                `;
                
                panel.tests.forEach(test => {
                    const rowClass = test.flag === 'C' ? 'critical' : (test.flag ? 'abnormal' : '');
                    const valueClass = test.flag === 'H' ? 'high' : (test.flag === 'L' ? 'low' : (test.flag === 'C' ? 'critical' : ''));
                    
                    html += `
                        <tr class="${rowClass}">
                            <td style="padding-left: 24px;">${test.name}</td>
                            <td><span class="result-value ${valueClass}">${test.value}</span></td>
                            <td><span class="result-flag ${test.flag}">${test.flag}</span></td>
                            <td>${test.range}</td>
                            <td>${test.unit}</td>
                            <td>
                                ${test.trend ? `<span class="result-trend" onclick="showTrend('${test.name}', ${JSON.stringify(test.trend).replace(/"/g, '&quot;')})">📈</span>` : ''}
                            </td>
                        </tr>
                    `;
                });
                
                html += '</tbody>';
            });
            
            html += '</table>';
            content.innerHTML = html;
        }
        
        // Toggle panel expansion
        function togglePanel(idx) {
            const panels = document.querySelectorAll('.panel-content');
            const panel = panels[idx];
            const icon = panel.querySelector('.expand-icon');
            
            if (panel.classList.contains('expanded')) {
                panel.querySelectorAll('tr:not(:first-child)').forEach(row => row.style.display = 'none');
                icon.textContent = '▶';
            } else {
                panel.querySelectorAll('tr').forEach(row => row.style.display = '');
                icon.textContent = '▼';
            }
            panel.classList.toggle('expanded');
        }
        
        // Toggle navigation section
        function toggleNavSection(header) {
            const items = header.nextElementSibling;
            const icon = header.querySelector('span:first-child');
            
            if (items.style.display === 'none') {
                items.style.display = '';
                icon.textContent = '▼';
            } else {
                items.style.display = 'none';
                icon.textContent = '▶';
            }
        }
        
        // Show results by category
        function showResults(category) {
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked item
            event.target.closest('.nav-item').classList.add('active');
            
            // In real app, would fetch results for category
            console.log(`Loading results for: ${category}`);
        }
        
        // Filter results
        function filterResults(filter) {
            document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Apply filter logic
            const rows = document.querySelectorAll('.results-table tbody tr:not(:first-child)');
            rows.forEach(row => {
                switch(filter) {
                    case 'abnormal':
                        row.style.display = row.classList.contains('abnormal') || row.classList.contains('critical') ? '' : 'none';
                        break;
                    case 'critical':
                        row.style.display = row.classList.contains('critical') ? '' : 'none';
                        break;
                    case 'all':
                    default:
                        row.style.display = '';
                        break;
                }
            });
        }
        
        // Show trend panel
        function showTrend(testName, values) {
            document.getElementById('trendPanel').classList.add('visible');
            document.getElementById('trendTitle').textContent = `Trend: ${testName}`;
            
            // Update history
            const historyHtml = values.map((val, idx) => {
                const date = new Date();
                date.setDate(date.getDate() - (values.length - 1 - idx));
                return `
                    <div class="trend-history-item">
                        <span>${date.toLocaleDateString()}</span>
                        <span>${val}</span>
                    </div>
                `;
            }).join('');
            
            document.getElementById('trendHistory').innerHTML = historyHtml;
            
            // Update button
            document.getElementById('trendBtn').textContent = '📉 Hide Trend';
        }
        
        // Toggle trend panel
        function toggleTrend() {
            const panel = document.getElementById('trendPanel');
            const btn = document.getElementById('trendBtn');
            
            if (panel.classList.contains('visible')) {
                panel.classList.remove('visible');
                btn.textContent = '📈 Show Trend';
            } else {
                panel.classList.add('visible');
                btn.textContent = '📉 Hide Trend';
            }
        }
        
        // Close trend panel
        function closeTrend() {
            document.getElementById('trendPanel').classList.remove('visible');
            document.getElementById('trendBtn').textContent = '📈 Show Trend';
        }
        
        // Refresh results
        function refreshResults() {
            // In real app, would fetch fresh data
            alert('Refreshing results...');
        }
        
        // Print results
        function printResults() {
            window.print();
        }
        
        // Search results
        document.getElementById('resultsSearch').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.results-table tbody tr:not(:first-child)');
            
            rows.forEach(row => {
                const testName = row.cells[0]?.textContent.toLowerCase() || '';
                row.style.display = testName.includes(query) ? '' : 'none';
            });
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderLabResults();
        });
    </script>
</body>
</html>

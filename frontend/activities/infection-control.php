<?php
/**
 * Epic EHR - Infection Control Module
 * Isolation precautions, surveillance, outbreak management
 */
session_start();
require_once __DIR__ . '/../includes/api.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Epic EHR - Infection Control</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e8eef5;
            min-height: 100vh;
        }
        
        .ic-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        /* Header */
        .ic-header {
            background: linear-gradient(to bottom, #d32f2f, #c62828);
            color: white;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ic-header h1 {
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
        
        /* Navigation Tabs */
        .ic-tabs {
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            padding: 0 16px;
        }
        
        .ic-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: #666;
        }
        
        .ic-tab:hover {
            background: #fff;
        }
        
        .ic-tab.active {
            border-bottom-color: #d32f2f;
            color: #d32f2f;
            font-weight: 600;
            background: #fff;
        }
        
        /* Main Content */
        .ic-content {
            flex: 1;
            display: flex;
            padding: 16px;
            gap: 16px;
            overflow: hidden;
        }
        
        /* Left Panel - Active Isolations */
        .isolation-panel {
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
            background: #ffebee;
            border-bottom: 1px solid #ffcdd2;
            font-weight: 600;
            font-size: 13px;
            color: #c62828;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-content {
            flex: 1;
            overflow-y: auto;
        }
        
        .isolation-item {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .isolation-item:hover {
            background: #fff8f8;
        }
        
        .isolation-item.selected {
            background: #ffebee;
            border-left: 3px solid #d32f2f;
        }
        
        .patient-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .patient-name {
            font-weight: 600;
            font-size: 12px;
            color: #333;
        }
        
        .patient-location {
            font-size: 10px;
            color: #666;
        }
        
        .isolation-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 6px;
        }
        
        .iso-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }
        
        .iso-badge.contact { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
        .iso-badge.droplet { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
        .iso-badge.airborne { background: #fce4ec; color: #c2185b; border: 1px solid #f8bbd9; }
        .iso-badge.enteric { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .iso-badge.neutropenic { background: #f3e5f5; color: #7b1fa2; border: 1px solid #ce93d8; }
        
        .isolation-reason {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        
        /* Center Panel - Dashboard */
        .center-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
        }
        
        .stat-card.contact .value { color: #e65100; }
        .stat-card.droplet .value { color: #1565c0; }
        .stat-card.airborne .value { color: #c2185b; }
        .stat-card.mrsa .value { color: #d32f2f; }
        
        /* Info Card */
        .info-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .info-card-header {
            padding: 12px 16px;
            background: #f5f6f8;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-card-body {
            padding: 16px;
        }
        
        /* Surveillance Table */
        .surveillance-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }
        
        .surveillance-table th, .surveillance-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .surveillance-table th {
            background: #fafafa;
            font-weight: 600;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .status-dot.positive { background: #d32f2f; }
        .status-dot.negative { background: #4caf50; }
        .status-dot.pending { background: #ff9800; }
        
        /* Unit Map */
        .unit-map {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
        }
        
        .room-cell {
            aspect-ratio: 1;
            border: 2px solid #ddd;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .room-cell:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .room-cell .room-num {
            font-weight: 600;
            font-size: 12px;
        }
        
        .room-cell.contact {
            background: #fff3e0;
            border-color: #ff9800;
        }
        
        .room-cell.droplet {
            background: #e3f2fd;
            border-color: #2196f3;
        }
        
        .room-cell.airborne {
            background: #fce4ec;
            border-color: #e91e63;
        }
        
        .room-cell.clean {
            background: #e8f5e9;
            border-color: #4caf50;
        }
        
        .room-cell.empty {
            background: #f5f5f5;
            border-color: #e0e0e0;
            color: #999;
        }
        
        /* Right Panel */
        .right-panel {
            width: 320px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
        }
        
        /* Alert List */
        .alert-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .alert-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        
        .alert-item:last-child {
            border-bottom: none;
        }
        
        .alert-item .time {
            font-size: 9px;
            color: #999;
        }
        
        .alert-item .message {
            margin-top: 4px;
        }
        
        .alert-item.critical {
            background: #ffebee;
            border-left: 3px solid #d32f2f;
        }
        
        .alert-item.warning {
            background: #fff8e1;
            border-left: 3px solid #ff9800;
        }
        
        /* Precautions Guide */
        .precautions-guide {
            font-size: 11px;
        }
        
        .precaution-item {
            display: flex;
            gap: 12px;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .precaution-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .precaution-icon.contact { background: #fff3e0; }
        .precaution-icon.droplet { background: #e3f2fd; }
        .precaution-icon.airborne { background: #fce4ec; }
        
        .precaution-details .name {
            font-weight: 600;
        }
        
        .precaution-details .ppe {
            color: #666;
            font-size: 10px;
            margin-top: 2px;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .action-btn {
            flex: 1;
            min-width: 100px;
            padding: 8px 12px;
            border: 1px solid #d32f2f;
            background: #fff;
            color: #d32f2f;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            text-align: center;
        }
        
        .action-btn:hover {
            background: #ffebee;
        }
        
        .action-btn.primary {
            background: #d32f2f;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="ic-container">
        <!-- Header -->
        <header class="ic-header">
            <h1>🦠 Infection Control</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="window.location.href='../home.php'">← Back</button>
                <button class="header-btn">📊 Reports</button>
                <button class="header-btn">🔔 Alerts</button>
            </div>
        </header>
        
        <!-- Navigation Tabs -->
        <div class="ic-tabs">
            <button class="ic-tab active">Dashboard</button>
            <button class="ic-tab">Active Isolations</button>
            <button class="ic-tab">Surveillance</button>
            <button class="ic-tab">MDRO Tracking</button>
            <button class="ic-tab">HAI Reports</button>
            <button class="ic-tab">Outbreak Mgmt</button>
            <button class="ic-tab">Hand Hygiene</button>
        </div>
        
        <!-- Main Content -->
        <div class="ic-content">
            <!-- Active Isolations Panel -->
            <div class="isolation-panel">
                <div class="panel-header">
                    <span>🔴 Active Isolation Precautions</span>
                    <span style="background: #d32f2f; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px;">24</span>
                </div>
                <div class="panel-content">
                    <div class="isolation-item selected">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Johnson, Michael</div>
                                <div class="patient-location">4W-418 • Telemetry</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge contact">Contact</span>
                        </div>
                        <div class="isolation-reason">MRSA - Wound culture positive</div>
                    </div>
                    
                    <div class="isolation-item">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Davis, Helen</div>
                                <div class="patient-location">5N-508 • Medical ICU</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge droplet">Droplet</span>
                            <span class="iso-badge contact">Contact</span>
                        </div>
                        <div class="isolation-reason">Influenza A - confirmed</div>
                    </div>
                    
                    <div class="isolation-item">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Martinez, Carlos</div>
                                <div class="patient-location">3E-305 • Pulmonary</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge airborne">Airborne</span>
                        </div>
                        <div class="isolation-reason">TB - Rule out (AFB pending)</div>
                    </div>
                    
                    <div class="isolation-item">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Thompson, Mary</div>
                                <div class="patient-location">6S-612 • Surgical</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge contact">Contact</span>
                        </div>
                        <div class="isolation-reason">C. difficile - toxin positive</div>
                    </div>
                    
                    <div class="isolation-item">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Brown, Robert</div>
                                <div class="patient-location">5S-512 • Oncology</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge neutropenic">Neutropenic</span>
                        </div>
                        <div class="isolation-reason">ANC < 500 - Reverse isolation</div>
                    </div>
                    
                    <div class="isolation-item">
                        <div class="patient-row">
                            <div>
                                <div class="patient-name">Wilson, James</div>
                                <div class="patient-location">4E-402 • Medical</div>
                            </div>
                        </div>
                        <div class="isolation-badges">
                            <span class="iso-badge contact">Contact</span>
                        </div>
                        <div class="isolation-reason">VRE - Rectal screen positive</div>
                    </div>
                </div>
            </div>
            
            <!-- Center Panel -->
            <div class="center-panel">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card contact">
                        <div class="icon">🧤</div>
                        <div class="value">14</div>
                        <div class="label">Contact Precautions</div>
                    </div>
                    <div class="stat-card droplet">
                        <div class="icon">😷</div>
                        <div class="value">6</div>
                        <div class="label">Droplet Precautions</div>
                    </div>
                    <div class="stat-card airborne">
                        <div class="icon">🌬️</div>
                        <div class="value">2</div>
                        <div class="label">Airborne Precautions</div>
                    </div>
                    <div class="stat-card mrsa">
                        <div class="icon">🦠</div>
                        <div class="value">8</div>
                        <div class="label">Active MDRO</div>
                    </div>
                </div>
                
                <!-- Unit Map -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>🗺️ Unit Map - 4 West Telemetry</span>
                        <select style="font-size: 11px; padding: 4px 8px;">
                            <option>4 West Telemetry</option>
                            <option>5 North Medical ICU</option>
                            <option>3 East Surgical</option>
                            <option>6 South Oncology</option>
                        </select>
                    </div>
                    <div class="info-card-body">
                        <div class="unit-map">
                            <div class="room-cell clean">
                                <span class="room-num">401</span>
                                <span>Clean</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">402</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell empty">
                                <span class="room-num">403</span>
                                <span>Empty</span>
                            </div>
                            <div class="room-cell contact">
                                <span class="room-num">404</span>
                                <span>Contact</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">405</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">406</span>
                                <span>Occupied</span>
                            </div>
                            
                            <div class="room-cell clean">
                                <span class="room-num">411</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell droplet">
                                <span class="room-num">412</span>
                                <span>Droplet</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">413</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell empty">
                                <span class="room-num">414</span>
                                <span>Empty</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">415</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell contact">
                                <span class="room-num">416</span>
                                <span>Contact</span>
                            </div>
                            
                            <div class="room-cell clean">
                                <span class="room-num">417</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell contact">
                                <span class="room-num">418</span>
                                <span>MRSA</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">419</span>
                                <span>Occupied</span>
                            </div>
                            <div class="room-cell airborne">
                                <span class="room-num">420</span>
                                <span>Neg Press</span>
                            </div>
                            <div class="room-cell empty">
                                <span class="room-num">421</span>
                                <span>Empty</span>
                            </div>
                            <div class="room-cell clean">
                                <span class="room-num">422</span>
                                <span>Occupied</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Surveillance Results -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>🔬 Recent Surveillance Cultures</span>
                    </div>
                    <div class="info-card-body">
                        <table class="surveillance-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Organism</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Johnson, M</td>
                                    <td>4W-418</td>
                                    <td>Wound</td>
                                    <td>MRSA</td>
                                    <td><span class="status-dot positive"></span>Positive</td>
                                    <td>01/23</td>
                                </tr>
                                <tr>
                                    <td>Davis, H</td>
                                    <td>5N-508</td>
                                    <td>Respiratory</td>
                                    <td>Influenza A</td>
                                    <td><span class="status-dot positive"></span>Positive</td>
                                    <td>01/24</td>
                                </tr>
                                <tr>
                                    <td>Martinez, C</td>
                                    <td>3E-305</td>
                                    <td>Sputum AFB</td>
                                    <td>TB</td>
                                    <td><span class="status-dot pending"></span>Pending</td>
                                    <td>01/24</td>
                                </tr>
                                <tr>
                                    <td>Thompson, M</td>
                                    <td>6S-612</td>
                                    <td>Stool C.diff</td>
                                    <td>C. difficile</td>
                                    <td><span class="status-dot positive"></span>Positive</td>
                                    <td>01/22</td>
                                </tr>
                                <tr>
                                    <td>Wilson, J</td>
                                    <td>4E-402</td>
                                    <td>Rectal Screen</td>
                                    <td>VRE</td>
                                    <td><span class="status-dot positive"></span>Positive</td>
                                    <td>01/21</td>
                                </tr>
                                <tr>
                                    <td>Patient, Test</td>
                                    <td>5N-501A</td>
                                    <td>Nares Screen</td>
                                    <td>MRSA</td>
                                    <td><span class="status-dot negative"></span>Negative</td>
                                    <td>01/20</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Quick Actions -->
                <div class="action-buttons">
                    <button class="action-btn primary">🔴 Initiate Isolation</button>
                    <button class="action-btn">📋 Order Surveillance</button>
                    <button class="action-btn">📝 IC Consult</button>
                </div>
                
                <!-- Alerts -->
                <div class="info-card">
                    <div class="info-card-header">
                        <span>🔔 IC Alerts</span>
                        <span style="background: #d32f2f; color: #fff; padding: 2px 6px; border-radius: 8px; font-size: 10px;">5</span>
                    </div>
                    <div class="alert-list">
                        <div class="alert-item critical">
                            <div class="time">10 min ago</div>
                            <div class="message"><strong>Airborne Isolation Required</strong> - Martinez, Carlos 3E-305: TB PCR positive</div>
                        </div>
                        <div class="alert-item warning">
                            <div class="time">45 min ago</div>
                            <div class="message"><strong>New MRSA</strong> - Johnson, Michael: Wound culture MRSA+</div>
                        </div>
                        <div class="alert-item warning">
                            <div class="time">2 hrs ago</div>
                            <div class="message"><strong>Hand Hygiene</strong> - 4 West compliance below 80%</div>
                        </div>
                        <div class="alert-item">
                            <div class="time">3 hrs ago</div>
                            <div class="message"><strong>Isolation Review</strong> - Thompson, Mary: C.diff isolation day 5</div>
                        </div>
                        <div class="alert-item">
                            <div class="time">5 hrs ago</div>
                            <div class="message"><strong>Pending Result</strong> - Martinez, Carlos: AFB culture in progress</div>
                        </div>
                    </div>
                </div>
                
                <!-- Precautions Guide -->
                <div class="info-card">
                    <div class="info-card-header">📋 Isolation Precautions Guide</div>
                    <div class="precautions-guide">
                        <div class="precaution-item">
                            <div class="precaution-icon contact">🧤</div>
                            <div class="precaution-details">
                                <div class="name">Contact Precautions</div>
                                <div class="ppe">Gown + Gloves • Private Room</div>
                            </div>
                        </div>
                        <div class="precaution-item">
                            <div class="precaution-icon droplet">😷</div>
                            <div class="precaution-details">
                                <div class="name">Droplet Precautions</div>
                                <div class="ppe">Surgical Mask • 3ft distance</div>
                            </div>
                        </div>
                        <div class="precaution-item">
                            <div class="precaution-icon airborne">🌬️</div>
                            <div class="precaution-details">
                                <div class="name">Airborne Precautions</div>
                                <div class="ppe">N95 Respirator • Negative pressure room</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- HAI Metrics -->
                <div class="info-card">
                    <div class="info-card-header">📊 HAI Metrics (This Month)</div>
                    <div class="info-card-body">
                        <div style="font-size: 11px;">
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>CLABSI</span>
                                <span style="color: #4caf50;">0 events</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>CAUTI</span>
                                <span style="color: #4caf50;">1 event</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>SSI</span>
                                <span style="color: #4caf50;">0 events</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee;">
                                <span>C. difficile</span>
                                <span style="color: #e65100;">3 events</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                                <span>Hand Hygiene</span>
                                <span style="color: #4caf50;">87%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.ic-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.ic-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });
        
        // Isolation item selection
        document.querySelectorAll('.isolation-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.isolation-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
            });
        });
        
        // Room cell click
        document.querySelectorAll('.room-cell').forEach(cell => {
            cell.addEventListener('click', () => {
                const room = cell.querySelector('.room-num').textContent;
                alert('Room ' + room + ' details');
            });
        });
    </script>
</body>
</html>

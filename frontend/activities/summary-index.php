<?php
/**
 * Summary Index - Quick navigation dashboard
 * Matching Epic's Summary Index Hyperspace interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

$patientId = $_GET['patient_id'] ?? 1;
$patientService = new PatientService();

try {
    $patient = $patientService->getById($patientId);
    $headerData = $patientService->getHeaderData($patientId);
} catch (Exception $e) {
    $patient = null;
    $headerData = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary - Index - Epic EHR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/epic-styles.css">
    <style>
        .summary-container {
            display: flex;
            height: calc(100vh - 150px);
        }
        
        /* Left Sidebar - Precautions/Info */
        .summary-sidebar {
            width: 180px;
            background: #fff;
            border-right: 1px solid #ccc;
            padding: 12px;
            overflow-y: auto;
        }
        
        .sidebar-search {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        
        .sidebar-section {
            margin-bottom: 16px;
        }
        
        .sidebar-section-title {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .sidebar-item {
            font-size: 12px;
            margin-bottom: 4px;
            color: #333;
        }
        
        .sidebar-item.warning {
            color: #c00;
        }
        
        .provider-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
        }
        
        .provider-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .provider-details {
            font-size: 12px;
        }
        
        .provider-name {
            font-weight: 600;
        }
        
        .provider-role {
            color: #666;
        }
        
        /* Main Content - Index Grid */
        .summary-main {
            flex: 1;
            overflow-y: auto;
            background: #f5f5f5;
            padding: 16px;
        }
        
        .index-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        
        .index-section {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .index-section-header {
            background: linear-gradient(180deg, #e8f4fc 0%, #d4e9f7 100%);
            padding: 8px 12px;
            border-bottom: 1px solid #b8d4e8;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .index-section-icon {
            width: 20px;
            height: 20px;
            background: #0066cc;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        .index-section-title {
            font-weight: 600;
            font-size: 13px;
            color: #333;
        }
        
        .index-section-content {
            padding: 8px 12px;
        }
        
        .index-link {
            display: block;
            padding: 4px 8px;
            margin: 2px 0;
            color: #0066cc;
            text-decoration: none;
            font-size: 12px;
            border-radius: 2px;
        }
        
        .index-link:hover {
            background: #e8f4fc;
            text-decoration: underline;
        }
        
        .index-links-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 4px;
        }
        
        .index-links-grid.three-col {
            grid-template-columns: repeat(3, 1fr);
        }
        
        /* Activity Tabs at top */
        .activity-tabs {
            display: flex;
            background: linear-gradient(180deg, #e8f4fc 0%, #d4e9f7 100%);
            border-bottom: 1px solid #b8d4e8;
            padding: 0 8px;
        }
        
        .activity-tab {
            padding: 10px 16px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #333;
            position: relative;
        }
        
        .activity-tab:hover {
            background: rgba(0,0,0,0.05);
        }
        
        .activity-tab.active {
            background: #fff;
            font-weight: 600;
            border: 1px solid #b8d4e8;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
            border-radius: 4px 4px 0 0;
        }
        
        .activity-tab .tab-icon {
            margin-right: 6px;
        }
        
        .mar-tab {
            background: linear-gradient(180deg, #8b0000 0%, #a00 100%) !important;
            color: white !important;
            border-radius: 4px;
            margin: 4px 2px;
        }
        
        /* Sub-tabs */
        .sub-tabs {
            display: flex;
            padding: 0 16px;
            background: #fff;
            border-bottom: 1px solid #ddd;
        }
        
        .sub-tab {
            padding: 8px 16px;
            font-size: 12px;
            color: #0066cc;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .sub-tab:hover {
            background: #f5f5f5;
        }
        
        .sub-tab.active {
            border-bottom-color: #0066cc;
            font-weight: 600;
        }
        
        /* Index search */
        .index-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .index-search {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .index-search input {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
            width: 200px;
        }
        
        .index-search-btn {
            padding: 6px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    
    <!-- Patient Header -->
    <?php include __DIR__ . '/../templates/patient-banner.php'; ?>
    
    <!-- Activity Tabs -->
    <div class="activity-tabs">
        <button class="activity-tab">
            <span class="tab-icon">◀</span>
        </button>
        <button class="activity-tab">
            <span class="tab-icon">▶</span>
        </button>
        <button class="activity-tab active">Summary</button>
        <button class="activity-tab">Chart Review</button>
        <button class="activity-tab">Results</button>
        <button class="activity-tab">Work List</button>
        <button class="activity-tab mar-tab">
            <span class="tab-icon">💊</span> MAR
        </button>
        <button class="activity-tab">
            <span class="tab-icon">📊</span> Flowsheets
        </button>
        <button class="activity-tab">Intake/O...</button>
        <button class="activity-tab">Notes</button>
        <button class="activity-tab">Education</button>
        <button class="activity-tab">Care Plan</button>
        <button class="activity-tab">Orders</button>
        <button class="activity-tab">Charg...</button>
        <button class="activity-tab">Navigators</button>
        <button class="activity-tab">DC Info</button>
        <button class="activity-tab">
            <span class="tab-icon">▼</span>
        </button>
    </div>
    
    <!-- Sub Tabs -->
    <div class="sub-tabs">
        <div class="sub-tab">Overview</div>
        <div class="sub-tab active">Index</div>
        <div class="sub-tab">SBAR Handoff</div>
        <div class="sub-tab">Comp Flowsheet</div>
        <div class="sub-tab">Cosign</div>
        <div class="sub-tab">Active Orders</div>
        <div class="sub-tab">FIM Assessment</div>
        <div class="sub-tab" style="margin-left: auto;">▼</div>
    </div>
    
    <div class="summary-container">
        <!-- Left Sidebar -->
        <aside class="summary-sidebar">
            <input type="text" class="sidebar-search" placeholder="🔍 Search">
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">PRECAUTIONS</div>
                <div class="sidebar-item warning">Fall precautions</div>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Organism: None</div>
            </div>
            
            <div class="provider-info">
                <div class="provider-avatar">👤</div>
                <div class="provider-details">
                    <div class="provider-name">Zeller, Timothy<br>Aaron, MD</div>
                    <div class="provider-role">Attending</div>
                </div>
            </div>
            
            <div class="sidebar-section" style="margin-top: 20px;">
                <div class="sidebar-section-title">ALLERGIES</div>
                <div class="sidebar-item warning">Codeine, Penicillins</div>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Pharmacy: Yes - Hover for Details</div>
            </div>
        </aside>
        
        <!-- Main Content - Index Grid -->
        <main class="summary-main">
            <div class="index-header">
                <h2 style="font-size: 16px; font-weight: 600; margin: 0;">Summary</h2>
                <div class="index-search">
                    <input type="text" placeholder="Index">
                    <button class="index-search-btn">🔍</button>
                    <button class="index-search-btn">🔎</button>
                    <button class="index-search-btn">⬇️</button>
                </div>
            </div>
            
            <div class="index-grid">
                <!-- Quick View Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">📋</span>
                        <span class="index-section-title">Quick View</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid three-col">
                            <a href="flowsheets.php?type=comprehensive" class="index-link">Comprehensive Flowsheet</a>
                            <a href="#" class="index-link">SBAR Handoff</a>
                            <a href="#" class="index-link">Overview</a>
                            <a href="#" class="index-link">Patient Care Snapshot</a>
                            <a href="#" class="index-link">ED Encounter Summary</a>
                            <a href="#" class="index-link">ED Patient Care Timeline</a>
                            <a href="#" class="index-link">Shift Assessment</a>
                            <a href="#" class="index-link">Medical, Surgical, and Family History</a>
                            <a href="#" class="index-link">Care Plan and Patient Education</a>
                            <a href="#" class="index-link">Restraints</a>
                            <a href="#" class="index-link">Discharge</a>
                            <a href="#" class="index-link">Code Summary (for printing)</a>
                            <a href="#" class="index-link">Blood Transfusion</a>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">📝</span>
                        <span class="index-section-title">Orders</span>
                    </div>
                    <div class="index-section-content">
                        <a href="#" class="index-link">Active Orders</a>
                        <a href="mar.php" class="index-link">Medication Administration</a>
                        <a href="#" class="index-link">Cancel Individual Lab Collections</a>
                    </div>
                </div>
                
                <!-- Perinatal Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">👶</span>
                        <span class="index-section-title">Perinatal</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">Current Pregnancy Summary</a>
                            <a href="#" class="index-link">Labor Assessment</a>
                            <a href="#" class="index-link">L&D Timeline</a>
                            <a href="#" class="index-link">Delivery Summary</a>
                            <a href="#" class="index-link">NST Results</a>
                        </div>
                    </div>
                </div>
                
                <!-- Therapy Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🏃</span>
                        <span class="index-section-title">Therapy</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">Therapy Flowsheet</a>
                            <a href="#" class="index-link">PT Overview</a>
                            <a href="#" class="index-link">OT Overview</a>
                            <a href="#" class="index-link">SLP Overview</a>
                            <a href="#" class="index-link">Rehab Nursing Flowsheet</a>
                        </div>
                    </div>
                </div>
                
                <!-- Medications Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">💊</span>
                        <span class="index-section-title">Medications</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">Current Meds</a>
                            <a href="#" class="index-link">Medication History</a>
                            <a href="#" class="index-link">Anti-Coagulation Dosing</a>
                            <a href="#" class="index-link">Fever/Antibiotic Dosing</a>
                            <a href="#" class="index-link">Glucose Monitoring</a>
                            <a href="#" class="index-link">Pain Monitoring</a>
                        </div>
                    </div>
                </div>
                
                <!-- Pediatric/Neonatology Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🍼</span>
                        <span class="index-section-title">Pediatric/Neonatology</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">Pediatric Comprehensive Data Flowsheet</a>
                            <a href="#" class="index-link">Delivery</a>
                            <a href="#" class="index-link">Apnea/Bradycardia</a>
                            <a href="#" class="index-link">TPN History</a>
                            <a href="#" class="index-link">NICU Nutrition</a>
                        </div>
                    </div>
                </div>
                
                <!-- Significant Events Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">⚡</span>
                        <span class="index-section-title">Significant Events</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">Code Data</a>
                            <a href="#" class="index-link">Sedation</a>
                            <a href="#" class="index-link">Sedation Review</a>
                            <a href="#" class="index-link">Blood Transfusion</a>
                        </div>
                    </div>
                </div>
                
                <!-- Results Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🔬</span>
                        <span class="index-section-title">Results</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="results.php?view=labs_72h" class="index-link">Labs - Last 72 Hours</a>
                            <a href="results.php?view=labs_entire" class="index-link">Labs - Entire Admission</a>
                            <a href="results.php?view=labs_unresulted" class="index-link">Labs - Unresulted</a>
                            <a href="results.php?view=micro" class="index-link">Microbiology Results</a>
                            <a href="results.php?view=radiology" class="index-link">Radiology Results</a>
                            <a href="#" class="index-link">PPD Results</a>
                        </div>
                    </div>
                </div>
                
                <!-- Infection Control Reports Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🦠</span>
                        <span class="index-section-title">Infection Control Reports</span>
                    </div>
                    <div class="index-section-content">
                        <div class="index-links-grid">
                            <a href="#" class="index-link">VAP</a>
                            <a href="#" class="index-link">Central Line Infection</a>
                            <a href="#" class="index-link">Device UTI</a>
                            <a href="#" class="index-link">MDRO/C Diff</a>
                            <a href="#" class="index-link">Surgical Site Infection</a>
                        </div>
                    </div>
                </div>
                
                <!-- Oncology Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🎗️</span>
                        <span class="index-section-title">Oncology</span>
                    </div>
                    <div class="index-section-content">
                        <a href="#" class="index-link">Oncology Summary</a>
                        <a href="#" class="index-link">Treatment & Support Plan</a>
                        <a href="#" class="index-link">Infusion Summary</a>
                    </div>
                </div>
                
                <!-- Print Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">🖨️</span>
                        <span class="index-section-title">Print</span>
                    </div>
                    <div class="index-section-content">
                        <a href="#" class="index-link">Print Patient Summary</a>
                        <a href="#" class="index-link">Print MAR</a>
                        <a href="#" class="index-link">Print Flowsheet</a>
                        <a href="#" class="index-link">Print Care Plan</a>
                    </div>
                </div>
                
                <!-- Education Section -->
                <div class="index-section">
                    <div class="index-section-header">
                        <span class="index-section-icon">📚</span>
                        <span class="index-section-title">Education</span>
                    </div>
                    <div class="index-section-content">
                        <a href="#" class="index-link">Patient Education</a>
                        <a href="#" class="index-link">Discharge Instructions</a>
                        <a href="#" class="index-link">Medication Teaching</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Tab switching
        document.querySelectorAll('.activity-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active from all tabs
                document.querySelectorAll('.activity-tab').forEach(t => t.classList.remove('active'));
                // Add active to clicked tab
                this.classList.add('active');
            });
        });
        
        // Sub-tab switching
        document.querySelectorAll('.sub-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>

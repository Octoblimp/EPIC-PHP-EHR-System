<?php
/**
 * Chart Review Activity
 * Historical documentation review with filtering by date range and document type
 * Matches Epic's Chart Review interface
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
    <title>Chart Review - Epic EHR</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        .chart-review-container {
            display: flex;
            height: calc(100vh - 180px);
            background: #fff;
        }
        
        /* Left Panel - Filter Tree */
        .filter-panel {
            width: 280px;
            border-right: 1px solid #ccc;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .filter-header {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .date-filter {
            padding: 10px;
            background: #fff;
            border-bottom: 1px solid #ddd;
        }
        
        .date-filter label {
            display: block;
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .date-filter input {
            width: 100%;
            padding: 4px 6px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            margin-bottom: 8px;
        }
        
        .date-presets {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .date-preset {
            padding: 3px 8px;
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 3px;
            font-size: 10px;
            cursor: pointer;
        }
        
        .date-preset:hover {
            background: #bbdefb;
        }
        
        .date-preset.active {
            background: #1976d2;
            color: white;
            border-color: #1565c0;
        }
        
        .filter-tree {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        
        .tree-section {
            margin-bottom: 4px;
        }
        
        .tree-header {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        
        .tree-header:hover {
            background: #e8e8e8;
        }
        
        .tree-expand {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 4px;
            font-size: 10px;
        }
        
        .tree-checkbox {
            margin-right: 6px;
        }
        
        .tree-count {
            margin-left: auto;
            background: #e0e0e0;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
            color: #666;
        }
        
        .tree-items {
            margin-left: 24px;
            display: none;
        }
        
        .tree-items.expanded {
            display: block;
        }
        
        .tree-item {
            display: flex;
            align-items: center;
            padding: 3px 8px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .tree-item:hover {
            background: #e3f2fd;
        }
        
        .tree-item.selected {
            background: #bbdefb;
        }
        
        /* Center Panel - Document List */
        .document-list-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .document-list-header {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .document-list-header input[type="search"] {
            flex: 1;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .document-grid {
            flex: 1;
            overflow-y: auto;
        }
        
        .document-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .document-table th {
            background: #f0f0f0;
            padding: 6px 8px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ccc;
            position: sticky;
            top: 0;
            cursor: pointer;
        }
        
        .document-table th:hover {
            background: #e0e0e0;
        }
        
        .document-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }
        
        .document-table tr:hover {
            background: #f5f5f5;
        }
        
        .document-table tr.selected {
            background: #e3f2fd;
        }
        
        .document-table tr.unread td:first-child {
            border-left: 3px solid #1976d2;
        }
        
        .doc-type-icon {
            width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 4px;
            font-size: 12px;
        }
        
        .doc-status {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .doc-status.final { background: #e8f5e9; color: #2e7d32; }
        .doc-status.preliminary { background: #fff3e0; color: #e65100; }
        .doc-status.addended { background: #e3f2fd; color: #1565c0; }
        .doc-status.signed { background: #e8f5e9; color: #2e7d32; }
        .doc-status.pended { background: #fce4ec; color: #c2185b; }
        
        /* Right Panel - Document Preview */
        .preview-panel {
            width: 45%;
            border-left: 1px solid #ccc;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .preview-header {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            font-size: 12px;
        }
        
        .preview-toolbar {
            padding: 6px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            gap: 6px;
        }
        
        .preview-toolbar button {
            padding: 4px 10px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .preview-toolbar button:hover {
            background: #f0f0f0;
        }
        
        .preview-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .document-preview {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        .preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        
        .preview-empty-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        /* Document type specific styling */
        .doc-header {
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .doc-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .doc-meta {
            font-size: 11px;
            color: #666;
        }
        
        .doc-meta span {
            margin-right: 20px;
        }
        
        .doc-section {
            margin-bottom: 15px;
        }
        
        .doc-section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        
        .signature-block {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <!-- Include Epic Header -->
    <div class="epic-header">
        <div class="header-left">
            <span class="epic-logo">Epic</span>
            <span class="header-title">Chart Review</span>
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
        <a href="chart-review.php" class="activity-tab active" style="background: #fff; border: 1px solid #ccc; border-bottom: none; padding: 6px 16px; margin-bottom: -1px;">Chart Review</a>
        <a href="mar.php" class="activity-tab">MAR</a>
        <a href="flowsheets.php" class="activity-tab">Flowsheets</a>
        <a href="../notes.php" class="activity-tab">Notes</a>
        <a href="../orders.php" class="activity-tab">Orders</a>
        <a href="results.php" class="activity-tab">Results</a>
    </div>
    
    <div class="chart-review-container">
        <!-- Filter Panel -->
        <div class="filter-panel">
            <div class="filter-header">
                <span>Filter Documents</span>
                <button style="font-size: 10px; padding: 2px 6px; cursor: pointer;">Reset</button>
            </div>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <label>Date Range:</label>
                <input type="date" id="startDate" placeholder="Start Date">
                <input type="date" id="endDate" placeholder="End Date">
                
                <div class="date-presets">
                    <span class="date-preset" onclick="setDatePreset('24h')">24 Hours</span>
                    <span class="date-preset active" onclick="setDatePreset('72h')">72 Hours</span>
                    <span class="date-preset" onclick="setDatePreset('7d')">7 Days</span>
                    <span class="date-preset" onclick="setDatePreset('30d')">30 Days</span>
                    <span class="date-preset" onclick="setDatePreset('admission')">This Admission</span>
                    <span class="date-preset" onclick="setDatePreset('all')">All</span>
                </div>
            </div>
            
            <!-- Document Type Tree -->
            <div class="filter-tree">
                <!-- Notes -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>📝 Notes</span>
                        <span class="tree-count">24</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item" onclick="filterByType('progress_note')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Progress Notes</span>
                            <span class="tree-count">8</span>
                        </div>
                        <div class="tree-item" onclick="filterByType('nursing_note')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Nursing Notes</span>
                            <span class="tree-count">6</span>
                        </div>
                        <div class="tree-item" onclick="filterByType('consultation')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Consultations</span>
                            <span class="tree-count">3</span>
                        </div>
                        <div class="tree-item" onclick="filterByType('procedure_note')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Procedure Notes</span>
                            <span class="tree-count">2</span>
                        </div>
                        <div class="tree-item" onclick="filterByType('h_and_p')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>H&P</span>
                            <span class="tree-count">1</span>
                        </div>
                        <div class="tree-item" onclick="filterByType('discharge_summary')">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Discharge Summary</span>
                            <span class="tree-count">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Results -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>🔬 Results</span>
                        <span class="tree-count">45</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Lab Results</span>
                            <span class="tree-count">32</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Microbiology</span>
                            <span class="tree-count">4</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Pathology</span>
                            <span class="tree-count">1</span>
                        </div>
                    </div>
                </div>
                
                <!-- Imaging -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>📷 Imaging</span>
                        <span class="tree-count">8</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>X-Ray</span>
                            <span class="tree-count">3</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>CT</span>
                            <span class="tree-count">2</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>MRI</span>
                            <span class="tree-count">1</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Ultrasound</span>
                            <span class="tree-count">2</span>
                        </div>
                    </div>
                </div>
                
                <!-- Orders -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>📋 Orders</span>
                        <span class="tree-count">52</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Medication Orders</span>
                            <span class="tree-count">28</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Lab Orders</span>
                            <span class="tree-count">15</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Imaging Orders</span>
                            <span class="tree-count">5</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Nursing Orders</span>
                            <span class="tree-count">4</span>
                        </div>
                    </div>
                </div>
                
                <!-- Flowsheets -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>📊 Flowsheets</span>
                        <span class="tree-count">18</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Vital Signs</span>
                            <span class="tree-count">12</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>I/O</span>
                            <span class="tree-count">4</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Assessments</span>
                            <span class="tree-count">2</span>
                        </div>
                    </div>
                </div>
                
                <!-- External -->
                <div class="tree-section">
                    <div class="tree-header" onclick="toggleSection(this)">
                        <span class="tree-expand">▶</span>
                        <input type="checkbox" class="tree-checkbox" checked>
                        <span>🏥 External Documents</span>
                        <span class="tree-count">3</span>
                    </div>
                    <div class="tree-items">
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>Outside Records</span>
                            <span class="tree-count">2</span>
                        </div>
                        <div class="tree-item">
                            <input type="checkbox" class="tree-checkbox" checked>
                            <span>CareEverywhere</span>
                            <span class="tree-count">1</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Document List -->
        <div class="document-list-panel">
            <div class="document-list-header">
                <input type="search" placeholder="Search documents..." id="docSearch">
                <select style="padding: 4px; font-size: 11px; border: 1px solid #ccc; border-radius: 3px;">
                    <option>Sort by: Date (Newest)</option>
                    <option>Sort by: Date (Oldest)</option>
                    <option>Sort by: Type</option>
                    <option>Sort by: Author</option>
                </select>
                <button style="padding: 4px 8px; font-size: 11px;">🔄 Refresh</button>
            </div>
            
            <div class="document-grid">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Date/Time</th>
                            <th style="width: 180px;">Document Type</th>
                            <th>Title/Description</th>
                            <th style="width: 120px;">Author</th>
                            <th style="width: 80px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="documentList">
                        <!-- Documents will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Preview Panel -->
        <div class="preview-panel">
            <div class="preview-header">
                <span id="previewTitle">Document Preview</span>
            </div>
            <div class="preview-toolbar">
                <button onclick="printDocument()">🖨️ Print</button>
                <button onclick="copyDocument()">📋 Copy</button>
                <button onclick="expandPreview()">⛶ Expand</button>
                <button onclick="addAddendum()">✏️ Addendum</button>
            </div>
            <div class="preview-content" id="previewContent">
                <div class="preview-empty">
                    <div class="preview-empty-icon">📄</div>
                    <div>Select a document to preview</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Sample documents data
        const documents = [
            {
                id: 1,
                date: '01/15/2024 14:32',
                type: 'Progress Note',
                typeIcon: '📝',
                title: 'Physician Progress Note - Hospital Day 3',
                author: 'Dr. Smith, John',
                status: 'Signed',
                unread: true,
                content: `PROGRESS NOTE - HOSPITAL DAY 3

Date: 01/15/2024 14:32
Author: Dr. John Smith, MD
Department: Internal Medicine

SUBJECTIVE:
Patient reports feeling better today. Pain is controlled at 3/10 with current medication regimen. 
Slept well overnight. No shortness of breath, chest pain, or palpitations. 
Appetite improving - ate 75% of breakfast.

OBJECTIVE:
Vital Signs:
- BP: 128/78 mmHg
- HR: 72 bpm, regular
- Temp: 98.6°F (37.0°C)
- RR: 16/min
- SpO2: 97% on room air

General: Alert, oriented x3, comfortable appearance
HEENT: PERRLA, moist mucous membranes
Cardiovascular: Regular rate and rhythm, no murmurs
Respiratory: Clear to auscultation bilaterally
Abdomen: Soft, non-tender, bowel sounds present
Extremities: No edema, pulses 2+ bilaterally

Labs (01/15/2024):
- WBC: 8.2 K/uL (improved from 12.4)
- Hgb: 11.8 g/dL
- Creatinine: 1.1 mg/dL
- BNP: 125 pg/mL (down from 340)

ASSESSMENT/PLAN:
1. Community-acquired pneumonia - improving
   - Continue IV antibiotics, will transition to PO tomorrow
   - Repeat CXR tomorrow morning
   
2. Acute on chronic systolic heart failure - stable
   - Continue home medications
   - Daily weights, strict I/O
   - Low sodium diet
   
3. Type 2 Diabetes - controlled
   - Continue current insulin regimen
   - QID fingersticks

Disposition: Continue current care. Anticipate discharge in 1-2 days if continues to improve.

Electronically signed by: John Smith, MD
01/15/2024 14:45`
            },
            {
                id: 2,
                date: '01/15/2024 08:15',
                type: 'Nursing Note',
                typeIcon: '👩‍⚕️',
                title: 'Day Shift Assessment',
                author: 'RN Johnson, Sarah',
                status: 'Signed',
                unread: false,
                content: `NURSING ASSESSMENT NOTE

Date/Time: 01/15/2024 08:15
Nurse: Sarah Johnson, RN
Shift: Day Shift (0700-1900)

NEUROLOGICAL:
Alert and oriented to person, place, time, and situation.
PERRLA 3mm. Speech clear. Following commands appropriately.
No focal deficits noted.

CARDIOVASCULAR:
Heart sounds regular. Telemetry showing NSR at 72 bpm.
Peripheral pulses palpable. No edema noted.
BP stable - see vital signs flowsheet.

RESPIRATORY:
Lungs clear to auscultation in all lobes.
SpO2 97% on room air. No accessory muscle use.
Patient denies shortness of breath.

GASTROINTESTINAL:
Abdomen soft, non-distended. Bowel sounds present x4 quadrants.
Last BM yesterday evening - formed, brown.
Tolerating regular diet, ate 75% breakfast.

GENITOURINARY:
Voiding without difficulty. Urine clear yellow.
Foley catheter removed yesterday.

SKIN:
Intact. Braden score 20 (no risk).
IV site LFA clean, dry, no erythema.

PAIN:
Reports 3/10 at IV site. Medication effective.

SAFETY:
Fall risk: Low. Bed in lowest position.
Call light within reach. Patient instructed on fall precautions.

PLAN:
- Continue current care
- Encourage ambulation TID
- Monitor for signs of infection
- Anticipate transition to oral antibiotics

Electronically signed by: Sarah Johnson, RN
01/15/2024 08:30`
            },
            {
                id: 3,
                date: '01/14/2024 16:45',
                type: 'Lab Result',
                typeIcon: '🔬',
                title: 'Complete Blood Count (CBC)',
                author: 'Lab Services',
                status: 'Final',
                unread: false,
                content: `LABORATORY REPORT

Test: Complete Blood Count with Differential
Collected: 01/14/2024 06:00
Received: 01/14/2024 06:15
Reported: 01/14/2024 07:30
Status: Final

RESULTS:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Component          Result    Flag    Reference Range
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
WBC                8.2       -       4.5-11.0 K/uL
RBC                4.1       -       4.5-5.5 M/uL
Hemoglobin         11.8      L       13.5-17.5 g/dL
Hematocrit         35.4      L       40-52 %
MCV                86.3      -       80-100 fL
MCH                28.8      -       27-33 pg
MCHC               33.3      -       31.5-35.5 g/dL
RDW                13.5      -       11.5-14.5 %
Platelet Count     245       -       150-400 K/uL
MPV                10.2      -       9.4-12.4 fL

DIFFERENTIAL:
Neutrophils %      68        -       40-70 %
Lymphocytes %      22        -       20-40 %
Monocytes %        7         -       2-8 %
Eosinophils %      2         -       1-4 %
Basophils %        1         -       0-1 %

Neutrophils Abs    5.58      -       1.8-7.7 K/uL
Lymphocytes Abs    1.80      -       1.0-4.8 K/uL
Monocytes Abs      0.57      -       0.1-0.8 K/uL
Eosinophils Abs    0.16      -       0.0-0.4 K/uL
Basophils Abs      0.08      -       0.0-0.1 K/uL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Verified by: Jane Williams, MT(ASCP)
01/14/2024 07:30`
            },
            {
                id: 4,
                date: '01/14/2024 11:30',
                type: 'Radiology',
                typeIcon: '📷',
                title: 'Chest X-Ray PA and Lateral',
                author: 'Dr. Lee, Radiology',
                status: 'Final',
                unread: false,
                content: `RADIOLOGY REPORT

Exam: Chest X-Ray PA and Lateral
Date: 01/14/2024 10:45
Accession: RAD-2024-00458

CLINICAL INDICATION:
Pneumonia follow-up. SOB.

COMPARISON:
Chest X-Ray dated 01/12/2024

TECHNIQUE:
PA and lateral views of the chest obtained.

FINDINGS:

LUNGS: There is improvement in the right lower lobe consolidation 
compared to prior study. Residual opacity remains but is decreased 
in size and density. The left lung is clear. No pleural effusion.

HEART: Heart size is normal. Mediastinal contours are unremarkable.

BONES: No acute osseous abnormality. Mild degenerative changes of 
the thoracic spine.

IMPRESSION:
1. Improving right lower lobe pneumonia compared to 01/12/2024.
2. No pleural effusion.
3. Heart size normal.

RECOMMENDATION:
Clinical correlation. Follow-up imaging as clinically indicated.

Electronically signed by:
Michael Lee, MD
Diagnostic Radiology
01/14/2024 11:30`
            },
            {
                id: 5,
                date: '01/13/2024 09:00',
                type: 'H&P',
                typeIcon: '📋',
                title: 'History and Physical - Admission',
                author: 'Dr. Smith, John',
                status: 'Signed',
                unread: false,
                content: `HISTORY AND PHYSICAL

Date of Admission: 01/13/2024
Attending Physician: Dr. John Smith, MD

CHIEF COMPLAINT:
Shortness of breath and cough x 3 days

HISTORY OF PRESENT ILLNESS:
This is a 59-year-old male with history of CHF (EF 40%), Type 2 DM, and HTN 
who presents with 3 days of worsening shortness of breath and productive cough. 
Patient reports yellow-green sputum production. Denies hemoptysis. Reports 
subjective fevers at home but did not take temperature. Associated with 
fatigue and decreased appetite. No chest pain. No leg swelling. Has been 
compliant with home medications including lasix.

REVIEW OF SYSTEMS:
Constitutional: Fatigue, subjective fever
HEENT: Negative
Cardiovascular: Denies chest pain, palpitations, leg swelling
Respiratory: SOB, productive cough as above
GI: Decreased appetite, no N/V/D
GU: No dysuria, hematuria
Neurological: No headache, weakness, numbness

PAST MEDICAL HISTORY:
1. Systolic heart failure, EF 40% (echo 06/2023)
2. Type 2 Diabetes Mellitus
3. Hypertension
4. Hyperlipidemia

SURGICAL HISTORY:
- Appendectomy (1995)
- Right knee arthroscopy (2018)

MEDICATIONS:
1. Lisinopril 20mg daily
2. Metoprolol succinate 50mg daily
3. Furosemide 40mg daily
4. Metformin 1000mg BID
5. Atorvastatin 40mg daily
6. Aspirin 81mg daily

ALLERGIES:
Penicillin - rash
Sulfa - hives

SOCIAL HISTORY:
- Tobacco: Former smoker, quit 10 years ago, 20 pack-year history
- Alcohol: Occasional social use
- Drugs: Denies
- Lives with wife, retired engineer

FAMILY HISTORY:
- Father: CAD, died at age 72 from MI
- Mother: Type 2 DM, HTN, alive at 82
- Siblings: Brother with HTN

PHYSICAL EXAMINATION:

Vital Signs:
- Temp: 101.2°F (38.4°C)
- BP: 138/82 mmHg
- HR: 92 bpm
- RR: 22/min
- SpO2: 94% on room air

General: Alert, mildly ill-appearing, speaking in full sentences
HEENT: NC/AT, PERRLA, MMM, oropharynx clear
Neck: Supple, no JVD, no lymphadenopathy
Cardiovascular: Regular rate, no murmurs, rubs, or gallops
Respiratory: Crackles right lower lobe, decreased breath sounds at base
Abdomen: Soft, non-tender, non-distended, normoactive bowel sounds
Extremities: No edema, pulses 2+ throughout
Neurological: Alert and oriented x3, moves all extremities

LABORATORY DATA:
- WBC: 12.4 K/uL (H)
- Hgb: 12.1 g/dL (L)
- Platelets: 234 K/uL
- Na: 138 mEq/L
- K: 4.2 mEq/L
- Creatinine: 1.2 mg/dL
- Glucose: 156 mg/dL (H)
- BNP: 340 pg/mL (H)
- Procalcitonin: 0.8 ng/mL (H)

IMAGING:
Chest X-Ray: Right lower lobe consolidation consistent with pneumonia

ASSESSMENT/PLAN:

1. Community-acquired pneumonia
   - Admit to general medicine
   - IV Levofloxacin 750mg daily (avoiding beta-lactams due to allergy)
   - Respiratory isolation pending
   - Blood cultures x2, sputum culture
   - Supplemental O2 as needed

2. Systolic heart failure - compensated
   - Continue home medications
   - Daily weights
   - Fluid restriction 1.5L
   - Low sodium diet

3. Type 2 Diabetes
   - Hold metformin
   - Sliding scale insulin
   - Monitor blood glucose QID

4. DVT prophylaxis
   - Heparin SQ

Electronically signed by: John Smith, MD
01/13/2024 10:15`
            },
            {
                id: 6,
                date: '01/14/2024 19:00',
                type: 'Consultation',
                typeIcon: '👨‍⚕️',
                title: 'Cardiology Consultation',
                author: 'Dr. Patel, Cardiology',
                status: 'Signed',
                unread: false,
                content: `CARDIOLOGY CONSULTATION

Date: 01/14/2024
Requesting Physician: Dr. John Smith
Consultant: Dr. Raj Patel, MD, FACC

REASON FOR CONSULTATION:
Evaluate heart failure management in setting of acute illness.

HISTORY OF PRESENT ILLNESS:
59-year-old male with known systolic heart failure (EF 40% on echo 06/2023) 
admitted with community-acquired pneumonia. Patient has been on 
guideline-directed medical therapy. Requesting cardiology input regarding 
HF management during acute illness.

[See primary H&P for complete history]

CARDIOVASCULAR REVIEW:
- No chest pain or pressure
- Baseline dyspnea on exertion at 1-2 blocks
- No PND or orthopnea at baseline
- No palpitations
- Weight has been stable at home
- Compliant with low-sodium diet and fluid restriction

CARDIOVASCULAR EXAMINATION:
- JVP 7cm
- Heart: Regular rate and rhythm, S1/S2 normal, no S3/S4
- No murmurs
- Lungs: Crackles RLL (related to pneumonia)
- Extremities: No peripheral edema
- Pulses: 2+ throughout

DIAGNOSTIC DATA:
- BNP: 340 pg/mL (mildly elevated)
- ECG: NSR, no acute changes
- Prior Echo (06/2023): EF 40%, mild MR, no LVH

ASSESSMENT:
Chronic systolic heart failure, currently compensated despite acute pneumonia. 
BNP only mildly elevated suggesting euvolemic status. No evidence of 
decompensation.

RECOMMENDATIONS:
1. Continue current HF medications:
   - Lisinopril 20mg daily
   - Metoprolol succinate 50mg daily
   - Furosemide 40mg daily
   
2. Consider adding spironolactone 25mg once recovered from acute illness

3. Daily weights - call if >2lb gain

4. Repeat BNP prior to discharge to establish new baseline

5. Follow-up in cardiology clinic in 2-3 weeks

6. Repeat echocardiogram in 3 months or sooner if clinical decline

Thank you for this consultation. We will follow along during hospitalization.

Electronically signed by:
Raj Patel, MD, FACC
01/14/2024 19:45`
            }
        ];
        
        // Render documents
        function renderDocuments(docs) {
            const tbody = document.getElementById('documentList');
            tbody.innerHTML = docs.map(doc => `
                <tr class="${doc.unread ? 'unread' : ''}" onclick="selectDocument(${doc.id})" data-id="${doc.id}">
                    <td>${doc.date}</td>
                    <td>
                        <span class="doc-type-icon">${doc.typeIcon}</span>
                        ${doc.type}
                    </td>
                    <td>${doc.title}</td>
                    <td>${doc.author}</td>
                    <td><span class="doc-status ${doc.status.toLowerCase()}">${doc.status}</span></td>
                </tr>
            `).join('');
        }
        
        // Select document
        function selectDocument(id) {
            // Remove previous selection
            document.querySelectorAll('.document-table tr').forEach(tr => tr.classList.remove('selected'));
            
            // Add selection to clicked row
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.classList.remove('unread');
                row.classList.add('selected');
            }
            
            // Show preview
            const doc = documents.find(d => d.id === id);
            if (doc) {
                document.getElementById('previewTitle').textContent = doc.title;
                document.getElementById('previewContent').innerHTML = `
                    <div class="document-preview">${escapeHtml(doc.content)}</div>
                `;
            }
        }
        
        // Helper to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Toggle tree section
        function toggleSection(header) {
            const items = header.nextElementSibling;
            const expand = header.querySelector('.tree-expand');
            
            if (items.classList.contains('expanded')) {
                items.classList.remove('expanded');
                expand.textContent = '▶';
            } else {
                items.classList.add('expanded');
                expand.textContent = '▼';
            }
        }
        
        // Date preset selection
        function setDatePreset(preset) {
            document.querySelectorAll('.date-preset').forEach(el => el.classList.remove('active'));
            event.target.classList.add('active');
            
            const now = new Date();
            let start = new Date();
            
            switch(preset) {
                case '24h':
                    start.setHours(start.getHours() - 24);
                    break;
                case '72h':
                    start.setHours(start.getHours() - 72);
                    break;
                case '7d':
                    start.setDate(start.getDate() - 7);
                    break;
                case '30d':
                    start.setDate(start.getDate() - 30);
                    break;
                case 'admission':
                    start = new Date('2024-01-13'); // Admission date
                    break;
                case 'all':
                    start = null;
                    break;
            }
            
            // Apply filter (in real app would filter documents)
            console.log(`Filter: ${preset}`, start);
        }
        
        // Document search
        document.getElementById('docSearch').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const filtered = documents.filter(doc => 
                doc.title.toLowerCase().includes(query) ||
                doc.type.toLowerCase().includes(query) ||
                doc.author.toLowerCase().includes(query)
            );
            renderDocuments(filtered);
        });
        
        // Filter by type
        function filterByType(type) {
            console.log(`Filter by type: ${type}`);
        }
        
        // Preview actions
        function printDocument() {
            window.print();
        }
        
        function copyDocument() {
            const content = document.querySelector('.document-preview');
            if (content) {
                navigator.clipboard.writeText(content.textContent);
                alert('Document copied to clipboard');
            }
        }
        
        function expandPreview() {
            // Would open in new window/full screen
            alert('Expand to full screen');
        }
        
        function addAddendum() {
            alert('Add addendum functionality');
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderDocuments(documents);
            
            // Expand first tree section
            document.querySelector('.tree-header').click();
        });
    </script>
</body>
</html>

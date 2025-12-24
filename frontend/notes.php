<?php
/**
 * Clinical Notes Module
 * Progress notes, H&P, Discharge Summary with SmartText templates and cosigning
 */
session_start();
require_once __DIR__ . '/includes/api.php';

$patientId = $_GET['patient_id'] ?? null;
$encounterId = $_GET['encounter_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Notes - Epic EHR</title>
    <link rel="stylesheet" href="assets/css/epic-styles.css">
    <style>
        .notes-container {
            display: flex;
            height: calc(100vh - 180px);
            background: #fff;
        }
        
        /* Left Panel - Note Types */
        .notes-sidebar {
            width: 220px;
            border-right: 1px solid #ccc;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 10px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            font-size: 12px;
        }
        
        .note-type-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .note-type-item {
            padding: 8px 12px;
            font-size: 11px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .note-type-item:hover {
            background: #e3f2fd;
        }
        
        .note-type-item.active {
            background: #bbdefb;
            font-weight: 500;
        }
        
        .note-type-item .icon {
            font-size: 14px;
        }
        
        .note-type-item .count {
            margin-left: auto;
            background: #e0e0e0;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
        }
        
        .note-type-item.pended .count {
            background: #ffcdd2;
            color: #c62828;
        }
        
        /* Main Content */
        .notes-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .notes-toolbar {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .notes-toolbar button {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .notes-toolbar button:hover {
            background: #f0f0f0;
        }
        
        .notes-toolbar button.primary {
            background: #1976d2;
            color: #fff;
            border-color: #1565c0;
        }
        
        .notes-toolbar button.primary:hover {
            background: #1565c0;
        }
        
        .notes-toolbar .spacer {
            flex: 1;
        }
        
        /* Notes List */
        .notes-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .note-card {
            border: 1px solid #ddd;
            margin: 8px;
            border-radius: 4px;
            background: #fff;
        }
        
        .note-card.pended {
            border-color: #ffb74d;
            background: #fff8e1;
        }
        
        .note-card.needs-cosign {
            border-color: #ff7043;
            background: #fbe9e7;
        }
        
        .note-card-header {
            padding: 10px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .note-card.pended .note-card-header {
            background: #ffecb3;
        }
        
        .note-card.needs-cosign .note-card-header {
            background: #ffccbc;
        }
        
        .note-card-header:hover {
            background: #e8e8e8;
        }
        
        .note-expand {
            font-size: 10px;
        }
        
        .note-title {
            font-weight: 600;
            font-size: 12px;
        }
        
        .note-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .note-status.signed { background: #e8f5e9; color: #2e7d32; }
        .note-status.pended { background: #fff3e0; color: #e65100; }
        .note-status.cosign { background: #fce4ec; color: #c2185b; }
        .note-status.addended { background: #e3f2fd; color: #1565c0; }
        
        .note-meta {
            margin-left: auto;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
        
        .note-card-body {
            padding: 12px;
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .note-card.expanded .note-card-body {
            display: block;
        }
        
        .note-content {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        
        .note-card-footer {
            padding: 8px 12px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            display: none;
            gap: 8px;
        }
        
        .note-card.expanded .note-card-footer {
            display: flex;
        }
        
        .note-card-footer button {
            padding: 4px 10px;
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
            background: #fff;
        }
        
        .note-card-footer button:hover {
            background: #f0f0f0;
        }
        
        /* Note Editor Modal */
        .note-editor-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .note-editor-modal.visible {
            display: flex;
        }
        
        .note-editor {
            width: 90%;
            max-width: 1000px;
            height: 85%;
            background: #fff;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .editor-header {
            padding: 12px 16px;
            background: #1976d2;
            color: #fff;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .editor-header h2 {
            margin: 0;
            font-size: 14px;
        }
        
        .editor-header button {
            background: none;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }
        
        .editor-toolbar {
            padding: 8px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .editor-toolbar select {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .editor-toolbar button {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            background: #fff;
            cursor: pointer;
        }
        
        .editor-toolbar button:hover {
            background: #e0e0e0;
        }
        
        .editor-toolbar .divider {
            width: 1px;
            height: 20px;
            background: #ccc;
            margin: 0 4px;
        }
        
        .editor-content {
            flex: 1;
            display: flex;
            min-height: 0;
        }
        
        .editor-textarea {
            flex: 1;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
            border: none;
            resize: none;
            outline: none;
        }
        
        .smarttext-panel {
            width: 250px;
            border-left: 1px solid #ddd;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .smarttext-header {
            padding: 8px 12px;
            background: #e8e8e8;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
            font-weight: 600;
        }
        
        .smarttext-search {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .smarttext-search input {
            width: 100%;
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .smarttext-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .smarttext-item {
            padding: 6px 12px;
            font-size: 11px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .smarttext-item:hover {
            background: #e3f2fd;
        }
        
        .smarttext-item .code {
            font-weight: 600;
            color: #1976d2;
        }
        
        .smarttext-item .desc {
            color: #666;
            font-size: 10px;
        }
        
        .editor-footer {
            padding: 12px 16px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .editor-footer .spacer {
            flex: 1;
        }
        
        .editor-footer button {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            background: #fff;
        }
        
        .editor-footer button:hover {
            background: #f0f0f0;
        }
        
        .editor-footer button.primary {
            background: #1976d2;
            color: #fff;
            border-color: #1565c0;
        }
        
        .editor-footer button.primary:hover {
            background: #1565c0;
        }
        
        .editor-footer button.success {
            background: #388e3c;
            color: #fff;
            border-color: #2e7d32;
        }
        
        .editor-footer button.success:hover {
            background: #2e7d32;
        }
        
        /* Cosign Modal */
        .cosign-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }
        
        .cosign-modal.visible {
            display: flex;
        }
        
        .cosign-dialog {
            width: 400px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .cosign-dialog-header {
            padding: 12px 16px;
            background: #ff7043;
            color: #fff;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
        }
        
        .cosign-dialog-body {
            padding: 16px;
        }
        
        .cosign-dialog-body label {
            display: block;
            font-size: 12px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .cosign-dialog-body select,
        .cosign-dialog-body input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        
        .cosign-dialog-footer {
            padding: 12px 16px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            border-radius: 0 0 8px 8px;
        }
        
        .cosign-dialog-footer button {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Include Epic Header -->
    <div class="epic-header">
        <div class="header-left">
            <span class="epic-logo">Epic</span>
            <span class="header-title">Clinical Notes</span>
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
    include __DIR__ . '/templates/patient-banner.php';
    ?>
    
    <!-- Activity Tabs -->
    <div class="activity-tabs" style="background: #f0f0f0; border-bottom: 1px solid #ccc; padding: 0 10px;">
        <a href="activities/summary-index.php" class="activity-tab">Summary</a>
        <a href="activities/chart-review.php" class="activity-tab">Chart Review</a>
        <a href="activities/mar.php" class="activity-tab">MAR</a>
        <a href="activities/flowsheets.php" class="activity-tab">Flowsheets</a>
        <a href="notes.php" class="activity-tab active" style="background: #fff; border: 1px solid #ccc; border-bottom: none; padding: 6px 16px; margin-bottom: -1px;">Notes</a>
        <a href="orders.php" class="activity-tab">Orders</a>
        <a href="activities/results.php" class="activity-tab">Results</a>
    </div>
    
    <div class="notes-container">
        <!-- Left Sidebar - Note Types -->
        <div class="notes-sidebar">
            <div class="sidebar-header">Note Types</div>
            <div class="note-type-list">
                <div class="note-type-item active" onclick="filterNotes('all')">
                    <span class="icon">📄</span>
                    <span>All Notes</span>
                    <span class="count">15</span>
                </div>
                <div class="note-type-item pended" onclick="filterNotes('pended')">
                    <span class="icon">⏳</span>
                    <span>Pended Notes</span>
                    <span class="count">2</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('cosign')">
                    <span class="icon">✍️</span>
                    <span>Needs Cosign</span>
                    <span class="count">1</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('progress')">
                    <span class="icon">📝</span>
                    <span>Progress Notes</span>
                    <span class="count">8</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('nursing')">
                    <span class="icon">👩‍⚕️</span>
                    <span>Nursing Notes</span>
                    <span class="count">4</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('consult')">
                    <span class="icon">👨‍⚕️</span>
                    <span>Consultations</span>
                    <span class="count">2</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('hp')">
                    <span class="icon">📋</span>
                    <span>H&P</span>
                    <span class="count">1</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('procedure')">
                    <span class="icon">🔧</span>
                    <span>Procedure Notes</span>
                    <span class="count">0</span>
                </div>
                <div class="note-type-item" onclick="filterNotes('discharge')">
                    <span class="icon">🏠</span>
                    <span>Discharge Summary</span>
                    <span class="count">0</span>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="notes-main">
            <div class="notes-toolbar">
                <button class="primary" onclick="openNewNote()">
                    <span>✚</span> New Note
                </button>
                <select id="noteTypeSelect">
                    <option value="">Select Note Type...</option>
                    <option value="progress">Progress Note</option>
                    <option value="nursing">Nursing Note</option>
                    <option value="hp">History & Physical</option>
                    <option value="consult">Consultation</option>
                    <option value="procedure">Procedure Note</option>
                    <option value="discharge">Discharge Summary</option>
                    <option value="telephone">Telephone Encounter</option>
                    <option value="education">Patient Education</option>
                </select>
                <div class="spacer"></div>
                <button onclick="expandAll()">⬇️ Expand All</button>
                <button onclick="collapseAll()">⬆️ Collapse All</button>
                <button onclick="printNotes()">🖨️ Print</button>
            </div>
            
            <div class="notes-list" id="notesList">
                <!-- Notes will be populated here -->
            </div>
        </div>
    </div>
    
    <!-- Note Editor Modal -->
    <div class="note-editor-modal" id="noteEditorModal">
        <div class="note-editor">
            <div class="editor-header">
                <h2 id="editorTitle">New Progress Note</h2>
                <button onclick="closeEditor()">✕</button>
            </div>
            
            <div class="editor-toolbar">
                <select id="templateSelect" onchange="insertTemplate()">
                    <option value="">Insert Template...</option>
                    <option value="soap">SOAP Note</option>
                    <option value="progress">Daily Progress Note</option>
                    <option value="admission">Admission Note</option>
                    <option value="hp">H&P Template</option>
                    <option value="consult">Consultation Template</option>
                    <option value="discharge">Discharge Summary</option>
                    <option value="procedure">Procedure Note</option>
                </select>
                
                <div class="divider"></div>
                
                <button onclick="insertSmartPhrase('.VITALS')">📊 Vitals</button>
                <button onclick="insertSmartPhrase('.LABS')">🔬 Labs</button>
                <button onclick="insertSmartPhrase('.MEDS')">💊 Medications</button>
                <button onclick="insertSmartPhrase('.ALLERGIES')">⚠️ Allergies</button>
                <button onclick="insertSmartPhrase('.DX')">📋 Diagnoses</button>
                
                <div class="divider"></div>
                
                <button onclick="formatBold()"><b>B</b></button>
                <button onclick="formatUnderline()"><u>U</u></button>
                <button onclick="formatList()">≡</button>
            </div>
            
            <div class="editor-content">
                <textarea class="editor-textarea" id="noteTextarea" placeholder="Start typing or select a template..."></textarea>
                
                <div class="smarttext-panel">
                    <div class="smarttext-header">SmartPhrases</div>
                    <div class="smarttext-search">
                        <input type="text" placeholder="Search SmartPhrases..." id="smartSearch">
                    </div>
                    <div class="smarttext-list" id="smartList">
                        <!-- SmartPhrases populated here -->
                    </div>
                </div>
            </div>
            
            <div class="editor-footer">
                <label>
                    <input type="checkbox" id="requiresCosign"> Requires Cosign
                </label>
                <div class="spacer"></div>
                <button onclick="closeEditor()">Cancel</button>
                <button class="primary" onclick="saveNote('pend')">Save as Pended</button>
                <button class="success" onclick="saveNote('sign')">Sign Note</button>
            </div>
        </div>
    </div>
    
    <!-- Cosign Modal -->
    <div class="cosign-modal" id="cosignModal">
        <div class="cosign-dialog">
            <div class="cosign-dialog-header">Request Cosignature</div>
            <div class="cosign-dialog-body">
                <label>Select Attending Physician:</label>
                <select id="cosignProvider">
                    <option value="">Select Provider...</option>
                    <option value="smith">Dr. John Smith, MD</option>
                    <option value="johnson">Dr. Sarah Johnson, MD</option>
                    <option value="williams">Dr. Michael Williams, MD</option>
                    <option value="patel">Dr. Raj Patel, MD</option>
                </select>
                
                <label>Comments (optional):</label>
                <input type="text" placeholder="Add comments for cosigner...">
            </div>
            <div class="cosign-dialog-footer">
                <button onclick="closeCosignModal()" style="background: #fff; border: 1px solid #ccc;">Cancel</button>
                <button onclick="submitCosign()" style="background: #ff7043; color: #fff; border: none;">Request Cosign</button>
            </div>
        </div>
    </div>
    
    <script>
        // Sample notes data
        const notes = [
            {
                id: 1,
                type: 'Progress Note',
                title: 'Physician Progress Note - Hospital Day 3',
                author: 'Dr. John Smith, MD',
                date: '01/15/2024 14:32',
                status: 'signed',
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

ASSESSMENT/PLAN:
1. Community-acquired pneumonia - improving
   - Continue IV antibiotics, transition to PO tomorrow
   
2. Acute on chronic systolic heart failure - stable
   - Continue home medications
   
Disposition: Continue current care. Anticipate discharge in 1-2 days.`
            },
            {
                id: 2,
                type: 'Progress Note',
                title: 'Resident Progress Note - Hospital Day 3',
                author: 'Dr. Jane Doe, MD (PGY-2)',
                date: '01/15/2024 08:00',
                status: 'cosign',
                cosigner: 'Dr. John Smith, MD',
                content: `RESIDENT PROGRESS NOTE

Date: 01/15/2024 08:00
Author: Dr. Jane Doe, MD (PGY-2)
Attending: Dr. John Smith, MD

S: Patient slept well, reports improvement in symptoms.

O: See vitals flowsheet. Lung exam with improved crackles.

A/P:
1. CAP - improving on antibiotics
2. CHF - compensated

[Note awaiting cosignature]`
            },
            {
                id: 3,
                type: 'Nursing Note',
                title: 'Day Shift Assessment',
                author: 'Sarah Johnson, RN',
                date: '01/15/2024 08:15',
                status: 'signed',
                content: `NURSING ASSESSMENT NOTE

Shift: Day Shift (0700-1900)
Nurse: Sarah Johnson, RN

NEUROLOGICAL: Alert and oriented x4. PERRLA 3mm.

CARDIOVASCULAR: NSR on telemetry. Pulses palpable.

RESPIRATORY: Lungs clear. SpO2 97% on RA.

GI: Tolerating diet. +BS x4.

GU: Voiding without difficulty.

SKIN: Intact. Braden 20.

PAIN: 3/10 at IV site. Medicated PRN.

SAFETY: Fall risk low. Call light within reach.`
            },
            {
                id: 4,
                type: 'Progress Note',
                title: 'Evening Note (Pended)',
                author: 'Dr. Mike Wilson, MD',
                date: '01/15/2024 18:45',
                status: 'pended',
                content: `EVENING PROGRESS NOTE (DRAFT)

Patient remains stable this evening.

[Note in progress - will complete on next visit]`
            }
        ];
        
        // SmartPhrases
        const smartPhrases = [
            { code: '.VITALS', desc: 'Insert current vitals' },
            { code: '.LABS', desc: 'Insert recent labs' },
            { code: '.MEDS', desc: 'Insert medication list' },
            { code: '.ALLERGIES', desc: 'Insert allergy list' },
            { code: '.DX', desc: 'Insert diagnoses' },
            { code: '.EXAM', desc: 'Physical exam template' },
            { code: '.ROS', desc: 'Review of systems' },
            { code: '.HPI', desc: 'HPI template' },
            { code: '.PMH', desc: 'Past medical history' },
            { code: '.SOCIAL', desc: 'Social history' },
            { code: '.FAMILY', desc: 'Family history' },
            { code: '.PLAN', desc: 'Assessment/Plan template' },
            { code: '.NEURO', desc: 'Neuro exam' },
            { code: '.CARDIAC', desc: 'Cardiac exam' },
            { code: '.RESP', desc: 'Respiratory exam' },
            { code: '.GI', desc: 'GI exam' },
            { code: '.MSK', desc: 'Musculoskeletal exam' }
        ];
        
        // Render notes
        function renderNotes(notesToRender = notes) {
            const container = document.getElementById('notesList');
            
            container.innerHTML = notesToRender.map(note => {
                const statusClass = note.status === 'pended' ? 'pended' : 
                                   note.status === 'cosign' ? 'needs-cosign' : '';
                const statusLabel = note.status === 'signed' ? 'Signed' :
                                   note.status === 'pended' ? 'Pended' :
                                   note.status === 'cosign' ? 'Needs Cosign' : '';
                
                return `
                    <div class="note-card ${statusClass}" data-id="${note.id}" data-type="${note.type.toLowerCase()}">
                        <div class="note-card-header" onclick="toggleNote(${note.id})">
                            <span class="note-expand">▶</span>
                            <span class="note-title">${note.title}</span>
                            <span class="note-status ${note.status}">${statusLabel}</span>
                            <div class="note-meta">
                                <div>${note.author}</div>
                                <div>${note.date}</div>
                            </div>
                        </div>
                        <div class="note-card-body">
                            <pre class="note-content">${escapeHtml(note.content)}</pre>
                        </div>
                        <div class="note-card-footer">
                            ${note.status === 'pended' ? '<button onclick="editNote(' + note.id + ')">✏️ Edit</button>' : ''}
                            ${note.status === 'cosign' ? '<button onclick="cosignNote(' + note.id + ')">✍️ Cosign</button>' : ''}
                            <button onclick="addAddendum(${note.id})">📝 Addendum</button>
                            <button onclick="printNote(${note.id})">🖨️ Print</button>
                            <button onclick="copyNote(${note.id})">📋 Copy</button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Toggle note expansion
        function toggleNote(id) {
            const card = document.querySelector(`.note-card[data-id="${id}"]`);
            const expand = card.querySelector('.note-expand');
            
            if (card.classList.contains('expanded')) {
                card.classList.remove('expanded');
                expand.textContent = '▶';
            } else {
                card.classList.add('expanded');
                expand.textContent = '▼';
            }
        }
        
        // Filter notes by type
        function filterNotes(type) {
            document.querySelectorAll('.note-type-item').forEach(item => item.classList.remove('active'));
            event.target.closest('.note-type-item').classList.add('active');
            
            let filtered = notes;
            
            switch(type) {
                case 'pended':
                    filtered = notes.filter(n => n.status === 'pended');
                    break;
                case 'cosign':
                    filtered = notes.filter(n => n.status === 'cosign');
                    break;
                case 'progress':
                    filtered = notes.filter(n => n.type === 'Progress Note');
                    break;
                case 'nursing':
                    filtered = notes.filter(n => n.type === 'Nursing Note');
                    break;
                case 'consult':
                    filtered = notes.filter(n => n.type === 'Consultation');
                    break;
            }
            
            renderNotes(filtered);
        }
        
        // Open new note editor
        function openNewNote() {
            const noteType = document.getElementById('noteTypeSelect').value;
            if (!noteType) {
                alert('Please select a note type first');
                return;
            }
            
            document.getElementById('editorTitle').textContent = `New ${getNoteName(noteType)}`;
            document.getElementById('noteTextarea').value = '';
            document.getElementById('noteEditorModal').classList.add('visible');
        }
        
        function getNoteName(type) {
            const names = {
                progress: 'Progress Note',
                nursing: 'Nursing Note',
                hp: 'History & Physical',
                consult: 'Consultation',
                procedure: 'Procedure Note',
                discharge: 'Discharge Summary',
                telephone: 'Telephone Encounter',
                education: 'Patient Education'
            };
            return names[type] || 'Note';
        }
        
        // Close editor
        function closeEditor() {
            document.getElementById('noteEditorModal').classList.remove('visible');
        }
        
        // Insert template
        function insertTemplate() {
            const template = document.getElementById('templateSelect').value;
            const textarea = document.getElementById('noteTextarea');
            
            const templates = {
                soap: `SUBJECTIVE:


OBJECTIVE:
Vital Signs: .VITALS

Physical Exam:
General: 
HEENT: 
Cardiovascular: 
Respiratory: 
Abdomen: 
Extremities: 
Neurological: 

ASSESSMENT:
1. 

PLAN:
1. `,
                progress: `DAILY PROGRESS NOTE

Date: ${new Date().toLocaleDateString()}
Hospital Day: #

SUBJECTIVE:
Patient reports...

OBJECTIVE:
.VITALS

Labs: .LABS

Physical Exam:
General: Alert, NAD
CV: RRR, no m/r/g
Resp: CTAB
Abd: Soft, NT/ND
Ext: No edema

ASSESSMENT/PLAN:
1. Primary diagnosis
   - Plan

2. Secondary diagnosis
   - Plan

Disposition: `,
                hp: `HISTORY AND PHYSICAL

Date of Admission: ${new Date().toLocaleDateString()}
Attending Physician: 

CHIEF COMPLAINT:


HISTORY OF PRESENT ILLNESS:


REVIEW OF SYSTEMS:
Constitutional: 
HEENT: 
Cardiovascular: 
Respiratory: 
GI: 
GU: 
MSK: 
Neurological: 
Psychiatric: 

PAST MEDICAL HISTORY:


PAST SURGICAL HISTORY:


MEDICATIONS:
.MEDS

ALLERGIES:
.ALLERGIES

SOCIAL HISTORY:
Tobacco: 
Alcohol: 
Drugs: 

FAMILY HISTORY:


PHYSICAL EXAMINATION:
Vital Signs: .VITALS
General: 
HEENT: 
Neck: 
Cardiovascular: 
Respiratory: 
Abdomen: 
Extremities: 
Neurological: 
Skin: 

DIAGNOSTIC DATA:


ASSESSMENT:


PLAN:

`
            };
            
            if (templates[template]) {
                textarea.value = templates[template];
            }
            
            document.getElementById('templateSelect').value = '';
        }
        
        // Insert SmartPhrase
        function insertSmartPhrase(code) {
            const textarea = document.getElementById('noteTextarea');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            // In real app, would expand to actual data
            const expansions = {
                '.VITALS': 'BP: 128/78, HR: 72, Temp: 98.6°F, RR: 16, SpO2: 97% RA',
                '.LABS': 'WBC 8.2, Hgb 11.8, Plt 245, Na 138, K 4.2, Cr 1.1, Glu 142',
                '.MEDS': '1. Lisinopril 20mg daily\n2. Metoprolol 50mg daily\n3. Furosemide 40mg daily',
                '.ALLERGIES': 'Penicillin (rash), Sulfa (hives)',
                '.DX': '1. Community-acquired pneumonia\n2. Systolic heart failure\n3. Type 2 Diabetes Mellitus'
            };
            
            const expansion = expansions[code] || code;
            textarea.value = text.substring(0, start) + expansion + text.substring(end);
            textarea.focus();
        }
        
        // Render SmartPhrases list
        function renderSmartPhrases() {
            const list = document.getElementById('smartList');
            list.innerHTML = smartPhrases.map(sp => `
                <div class="smarttext-item" onclick="insertSmartPhrase('${sp.code}')">
                    <div class="code">${sp.code}</div>
                    <div class="desc">${sp.desc}</div>
                </div>
            `).join('');
        }
        
        // Search SmartPhrases
        document.getElementById('smartSearch').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const filtered = smartPhrases.filter(sp => 
                sp.code.toLowerCase().includes(query) || 
                sp.desc.toLowerCase().includes(query)
            );
            
            const list = document.getElementById('smartList');
            list.innerHTML = filtered.map(sp => `
                <div class="smarttext-item" onclick="insertSmartPhrase('${sp.code}')">
                    <div class="code">${sp.code}</div>
                    <div class="desc">${sp.desc}</div>
                </div>
            `).join('');
        });
        
        // Save note
        function saveNote(action) {
            const content = document.getElementById('noteTextarea').value;
            const requiresCosign = document.getElementById('requiresCosign').checked;
            
            if (!content.trim()) {
                alert('Please enter note content');
                return;
            }
            
            if (action === 'sign') {
                if (requiresCosign) {
                    document.getElementById('cosignModal').classList.add('visible');
                } else {
                    alert('Note signed successfully!');
                    closeEditor();
                }
            } else {
                alert('Note saved as pended');
                closeEditor();
            }
        }
        
        // Cosign modal
        function cosignNote(id) {
            alert('Opening cosign workflow for note ' + id);
        }
        
        function closeCosignModal() {
            document.getElementById('cosignModal').classList.remove('visible');
        }
        
        function submitCosign() {
            const provider = document.getElementById('cosignProvider').value;
            if (!provider) {
                alert('Please select a provider');
                return;
            }
            alert('Cosign request sent!');
            closeCosignModal();
            closeEditor();
        }
        
        // Note actions
        function editNote(id) {
            const note = notes.find(n => n.id === id);
            if (note) {
                document.getElementById('editorTitle').textContent = `Edit: ${note.title}`;
                document.getElementById('noteTextarea').value = note.content;
                document.getElementById('noteEditorModal').classList.add('visible');
            }
        }
        
        function addAddendum(id) {
            document.getElementById('editorTitle').textContent = 'Add Addendum';
            document.getElementById('noteTextarea').value = `ADDENDUM to note dated ${new Date().toLocaleDateString()}:\n\n`;
            document.getElementById('noteEditorModal').classList.add('visible');
        }
        
        function printNote(id) {
            window.print();
        }
        
        function copyNote(id) {
            const note = notes.find(n => n.id === id);
            if (note) {
                navigator.clipboard.writeText(note.content);
                alert('Note copied to clipboard');
            }
        }
        
        // Expand/collapse all
        function expandAll() {
            document.querySelectorAll('.note-card').forEach(card => {
                card.classList.add('expanded');
                card.querySelector('.note-expand').textContent = '▼';
            });
        }
        
        function collapseAll() {
            document.querySelectorAll('.note-card').forEach(card => {
                card.classList.remove('expanded');
                card.querySelector('.note-expand').textContent = '▶';
            });
        }
        
        function printNotes() {
            window.print();
        }
        
        // Helper
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderNotes();
            renderSmartPhrases();
        });
    </script>
</body>
</html>

<?php
/**
 * Care Plan Activity
 * Problem-based care planning with goals, interventions, and patient education
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
    <title>Care Plan - Epic EHR</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        .careplan-container {
            display: flex;
            height: calc(100vh - 180px);
            background: #fff;
        }
        
        /* Left Panel - Problem List */
        .problem-panel {
            width: 280px;
            border-right: 1px solid #ccc;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .panel-header {
            padding: 10px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-header button {
            padding: 2px 8px;
            font-size: 10px;
            cursor: pointer;
        }
        
        .problem-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .problem-item {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            font-size: 11px;
        }
        
        .problem-item:hover {
            background: #e3f2fd;
        }
        
        .problem-item.active {
            background: #bbdefb;
            border-left: 3px solid #1976d2;
        }
        
        .problem-item.resolved {
            opacity: 0.6;
            text-decoration: line-through;
        }
        
        .problem-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .problem-meta {
            font-size: 10px;
            color: #666;
        }
        
        .problem-status {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 500;
            margin-left: 4px;
        }
        
        .problem-status.active { background: #e8f5e9; color: #2e7d32; }
        .problem-status.resolved { background: #e0e0e0; color: #666; }
        .problem-status.chronic { background: #fff3e0; color: #e65100; }
        
        /* Main Content */
        .careplan-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .careplan-toolbar {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .careplan-toolbar button {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .careplan-toolbar button:hover {
            background: #f0f0f0;
        }
        
        .careplan-toolbar button.primary {
            background: #1976d2;
            color: #fff;
            border-color: #1565c0;
        }
        
        .careplan-toolbar .spacer {
            flex: 1;
        }
        
        /* Tabs */
        .careplan-tabs {
            display: flex;
            gap: 2px;
            padding: 8px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
        }
        
        .careplan-tab {
            padding: 6px 16px;
            border: 1px solid #ccc;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            font-size: 11px;
            cursor: pointer;
            background: #e8e8e8;
        }
        
        .careplan-tab:hover {
            background: #ddd;
        }
        
        .careplan-tab.active {
            background: #fff;
            font-weight: 600;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }
        
        /* Content Area */
        .careplan-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        /* Care Plan Sections */
        .careplan-section {
            margin-bottom: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .section-header {
            padding: 10px 12px;
            background: #f5f5f5;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        
        .section-header:hover {
            background: #e8e8e8;
        }
        
        .section-header .expand-icon {
            font-size: 10px;
        }
        
        .section-body {
            padding: 12px;
        }
        
        .section-body.collapsed {
            display: none;
        }
        
        /* Goals */
        .goal-item {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
            background: #fafafa;
        }
        
        .goal-item.met {
            border-color: #81c784;
            background: #e8f5e9;
        }
        
        .goal-item.not-met {
            border-color: #e57373;
            background: #ffebee;
        }
        
        .goal-item.in-progress {
            border-color: #ffb74d;
            background: #fff8e1;
        }
        
        .goal-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }
        
        .goal-status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            background: #e0e0e0;
        }
        
        .goal-item.met .goal-status-icon {
            background: #4caf50;
            color: #fff;
        }
        
        .goal-item.in-progress .goal-status-icon {
            background: #ff9800;
            color: #fff;
        }
        
        .goal-description {
            font-size: 12px;
            font-weight: 500;
        }
        
        .goal-target {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        
        .goal-actions {
            margin-top: 8px;
            display: flex;
            gap: 6px;
        }
        
        .goal-actions button {
            padding: 3px 8px;
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
            background: #fff;
        }
        
        /* Interventions */
        .intervention-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .intervention-table th {
            background: #f0f0f0;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #ccc;
        }
        
        .intervention-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .intervention-table tr:hover {
            background: #f5f5f5;
        }
        
        .intervention-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
        }
        
        .intervention-status.active { background: #e8f5e9; color: #2e7d32; }
        .intervention-status.completed { background: #e3f2fd; color: #1565c0; }
        .intervention-status.discontinued { background: #ffebee; color: #c62828; }
        
        /* Patient Education */
        .education-item {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .education-item .icon {
            font-size: 24px;
        }
        
        .education-item .content {
            flex: 1;
        }
        
        .education-title {
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 4px;
        }
        
        .education-meta {
            font-size: 10px;
            color: #666;
        }
        
        .education-actions {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .education-actions button {
            padding: 4px 10px;
            font-size: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            cursor: pointer;
            background: #fff;
        }
        
        .education-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            margin-top: 8px;
        }
        
        /* Add Modal */
        .add-modal {
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
        
        .add-modal.visible {
            display: flex;
        }
        
        .modal-content {
            width: 500px;
            max-height: 80%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 12px 16px;
            background: #1976d2;
            color: #fff;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 14px;
        }
        
        .modal-header button {
            background: none;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 16px;
            overflow-y: auto;
        }
        
        .modal-body label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
            margin-top: 12px;
        }
        
        .modal-body label:first-child {
            margin-top: 0;
        }
        
        .modal-body input,
        .modal-body select,
        .modal-body textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .modal-body textarea {
            height: 80px;
            resize: vertical;
        }
        
        .modal-footer {
            padding: 12px 16px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            border-radius: 0 0 8px 8px;
        }
        
        .modal-footer button {
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
            <span class="header-title">Care Plan</span>
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
        <a href="care-plan.php" class="activity-tab active" style="background: #fff; border: 1px solid #ccc; border-bottom: none; padding: 6px 16px; margin-bottom: -1px;">Care Plan</a>
    </div>
    
    <div class="careplan-container">
        <!-- Left Panel - Problem List -->
        <div class="problem-panel">
            <div class="panel-header">
                <span>Problem List</span>
                <button onclick="addProblem()">+ Add</button>
            </div>
            <div class="problem-list" id="problemList">
                <!-- Problems populated here -->
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="careplan-main">
            <div class="careplan-toolbar">
                <button class="primary" onclick="addGoal()">+ Add Goal</button>
                <button onclick="addIntervention()">+ Add Intervention</button>
                <button onclick="addEducation()">+ Patient Education</button>
                <div class="spacer"></div>
                <button onclick="printCarePlan()">🖨️ Print Care Plan</button>
            </div>
            
            <!-- Tabs -->
            <div class="careplan-tabs">
                <div class="careplan-tab active" onclick="switchTab('all')">All</div>
                <div class="careplan-tab" onclick="switchTab('goals')">Goals</div>
                <div class="careplan-tab" onclick="switchTab('interventions')">Interventions</div>
                <div class="careplan-tab" onclick="switchTab('education')">Patient Education</div>
                <div class="careplan-tab" onclick="switchTab('summary')">Summary View</div>
            </div>
            
            <div class="careplan-content" id="careplanContent">
                <!-- Content populated based on selected problem -->
            </div>
        </div>
    </div>
    
    <!-- Add Goal Modal -->
    <div class="add-modal" id="addGoalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Goal</h3>
                <button onclick="closeModal('addGoalModal')">✕</button>
            </div>
            <div class="modal-body">
                <label>Goal Type:</label>
                <select id="goalType">
                    <option value="short">Short-term Goal</option>
                    <option value="long">Long-term Goal</option>
                </select>
                
                <label>Goal Description:</label>
                <textarea id="goalDescription" placeholder="Patient will..."></textarea>
                
                <label>Target Date:</label>
                <input type="date" id="goalTargetDate">
                
                <label>Measurement Criteria:</label>
                <input type="text" id="goalCriteria" placeholder="How will goal achievement be measured?">
                
                <label>Priority:</label>
                <select id="goalPriority">
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal('addGoalModal')" style="background: #fff; border: 1px solid #ccc;">Cancel</button>
                <button onclick="saveGoal()" style="background: #1976d2; color: #fff; border: none;">Save Goal</button>
            </div>
        </div>
    </div>
    
    <!-- Add Intervention Modal -->
    <div class="add-modal" id="addInterventionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Intervention</h3>
                <button onclick="closeModal('addInterventionModal')">✕</button>
            </div>
            <div class="modal-body">
                <label>Intervention Category:</label>
                <select id="interventionCategory">
                    <option value="nursing">Nursing</option>
                    <option value="medication">Medication</option>
                    <option value="therapy">Therapy</option>
                    <option value="nutrition">Nutrition</option>
                    <option value="education">Education</option>
                    <option value="monitoring">Monitoring</option>
                    <option value="safety">Safety</option>
                </select>
                
                <label>Intervention:</label>
                <textarea id="interventionDescription" placeholder="Describe the intervention..."></textarea>
                
                <label>Frequency:</label>
                <select id="interventionFrequency">
                    <option value="once">Once</option>
                    <option value="prn">PRN</option>
                    <option value="daily">Daily</option>
                    <option value="bid">BID</option>
                    <option value="tid">TID</option>
                    <option value="qid">QID</option>
                    <option value="q4h">Q4H</option>
                    <option value="q6h">Q6H</option>
                    <option value="q8h">Q8H</option>
                    <option value="shift">Each Shift</option>
                </select>
                
                <label>Assigned To:</label>
                <select id="interventionAssigned">
                    <option value="nursing">Nursing</option>
                    <option value="pt">Physical Therapy</option>
                    <option value="ot">Occupational Therapy</option>
                    <option value="slp">Speech Therapy</option>
                    <option value="rt">Respiratory Therapy</option>
                    <option value="dietary">Dietary</option>
                    <option value="pharmacy">Pharmacy</option>
                    <option value="social">Social Work</option>
                </select>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal('addInterventionModal')" style="background: #fff; border: 1px solid #ccc;">Cancel</button>
                <button onclick="saveIntervention()" style="background: #1976d2; color: #fff; border: none;">Save Intervention</button>
            </div>
        </div>
    </div>
    
    <script>
        // Sample data
        const problems = [
            {
                id: 1,
                name: 'Community-acquired Pneumonia',
                icd10: 'J18.9',
                status: 'active',
                onset: '01/13/2024',
                priority: 'high'
            },
            {
                id: 2,
                name: 'Systolic Heart Failure',
                icd10: 'I50.20',
                status: 'chronic',
                onset: '06/15/2023',
                priority: 'medium'
            },
            {
                id: 3,
                name: 'Type 2 Diabetes Mellitus',
                icd10: 'E11.9',
                status: 'chronic',
                onset: '03/22/2020',
                priority: 'medium'
            },
            {
                id: 4,
                name: 'Hypertension',
                icd10: 'I10',
                status: 'chronic',
                onset: '01/10/2019',
                priority: 'low'
            }
        ];
        
        const goals = {
            1: [ // Pneumonia goals
                {
                    id: 1,
                    type: 'short',
                    description: 'Patient will demonstrate improved oxygenation with SpO2 ≥95% on room air',
                    status: 'in-progress',
                    targetDate: '01/17/2024',
                    criteria: 'SpO2 ≥95% on RA for 24 hours'
                },
                {
                    id: 2,
                    type: 'short',
                    description: 'Patient will be afebrile (temp <100.4°F) for 48 hours',
                    status: 'met',
                    targetDate: '01/16/2024',
                    criteria: 'No fever for 48 consecutive hours'
                },
                {
                    id: 3,
                    type: 'long',
                    description: 'Patient will complete antibiotic course and chest X-ray will show resolution',
                    status: 'in-progress',
                    targetDate: '01/20/2024',
                    criteria: 'CXR improvement, completion of antibiotics'
                }
            ],
            2: [ // CHF goals
                {
                    id: 4,
                    type: 'short',
                    description: 'Patient will maintain euvolemic status with no weight gain >2 lbs',
                    status: 'met',
                    targetDate: '01/16/2024',
                    criteria: 'Daily weight stable, no edema'
                },
                {
                    id: 5,
                    type: 'long',
                    description: 'Patient will verbalize understanding of fluid restriction and low-sodium diet',
                    status: 'in-progress',
                    targetDate: '01/18/2024',
                    criteria: 'Teach-back demonstration'
                }
            ]
        };
        
        const interventions = {
            1: [ // Pneumonia interventions
                { id: 1, intervention: 'Administer IV Levofloxacin 750mg daily', category: 'medication', frequency: 'daily', assigned: 'nursing', status: 'active' },
                { id: 2, intervention: 'Monitor SpO2 continuously', category: 'monitoring', frequency: 'continuous', assigned: 'nursing', status: 'active' },
                { id: 3, intervention: 'Incentive spirometry every 2 hours while awake', category: 'therapy', frequency: 'q2h', assigned: 'nursing', status: 'active' },
                { id: 4, intervention: 'Chest physiotherapy BID', category: 'therapy', frequency: 'bid', assigned: 'rt', status: 'active' },
                { id: 5, intervention: 'Assess breath sounds every shift', category: 'nursing', frequency: 'shift', assigned: 'nursing', status: 'active' }
            ],
            2: [ // CHF interventions
                { id: 6, intervention: 'Daily weights before breakfast', category: 'monitoring', frequency: 'daily', assigned: 'nursing', status: 'active' },
                { id: 7, intervention: 'Strict I/O monitoring', category: 'monitoring', frequency: 'shift', assigned: 'nursing', status: 'active' },
                { id: 8, intervention: 'Fluid restriction 1.5L/day', category: 'nutrition', frequency: 'daily', assigned: 'nursing', status: 'active' },
                { id: 9, intervention: 'Low sodium diet 2g/day', category: 'nutrition', frequency: 'daily', assigned: 'dietary', status: 'active' },
                { id: 10, intervention: 'Administer home heart failure medications', category: 'medication', frequency: 'scheduled', assigned: 'nursing', status: 'active' }
            ]
        };
        
        const education = [
            {
                id: 1,
                title: 'Heart Failure: Managing Your Condition',
                type: 'handout',
                given: true,
                givenDate: '01/14/2024',
                understands: true,
                provider: 'RN Johnson'
            },
            {
                id: 2,
                title: 'Low Sodium Diet Guidelines',
                type: 'handout',
                given: true,
                givenDate: '01/14/2024',
                understands: true,
                provider: 'Dietary'
            },
            {
                id: 3,
                title: 'Pneumonia: What You Need to Know',
                type: 'handout',
                given: true,
                givenDate: '01/13/2024',
                understands: true,
                provider: 'RN Smith'
            },
            {
                id: 4,
                title: 'Deep Breathing and Incentive Spirometry',
                type: 'demonstration',
                given: true,
                givenDate: '01/13/2024',
                understands: true,
                provider: 'RT Williams'
            },
            {
                id: 5,
                title: 'Discharge Medications Review',
                type: 'discussion',
                given: false,
                givenDate: null,
                understands: false,
                provider: null
            }
        ];
        
        let selectedProblem = 1;
        
        // Render problem list
        function renderProblems() {
            const container = document.getElementById('problemList');
            container.innerHTML = problems.map(p => `
                <div class="problem-item ${p.id === selectedProblem ? 'active' : ''} ${p.status === 'resolved' ? 'resolved' : ''}" 
                     onclick="selectProblem(${p.id})">
                    <div class="problem-name">
                        ${p.name}
                        <span class="problem-status ${p.status}">${p.status}</span>
                    </div>
                    <div class="problem-meta">
                        ${p.icd10} | Onset: ${p.onset}
                    </div>
                </div>
            `).join('');
        }
        
        // Select problem
        function selectProblem(id) {
            selectedProblem = id;
            renderProblems();
            renderCarePlanContent();
        }
        
        // Render care plan content
        function renderCarePlanContent() {
            const content = document.getElementById('careplanContent');
            const problem = problems.find(p => p.id === selectedProblem);
            const problemGoals = goals[selectedProblem] || [];
            const problemInterventions = interventions[selectedProblem] || [];
            
            content.innerHTML = `
                <!-- Problem Header -->
                <div style="margin-bottom: 16px; padding: 12px; background: #e3f2fd; border-radius: 4px;">
                    <h3 style="margin: 0 0 8px 0; font-size: 14px;">${problem.name}</h3>
                    <div style="font-size: 11px; color: #666;">
                        ICD-10: ${problem.icd10} | Status: ${problem.status} | Onset: ${problem.onset}
                    </div>
                </div>
                
                <!-- Goals Section -->
                <div class="careplan-section">
                    <div class="section-header" onclick="toggleSection(this)">
                        <span><span class="expand-icon">▼</span> Goals (${problemGoals.length})</span>
                        <button onclick="event.stopPropagation(); addGoal()" style="padding: 2px 8px; font-size: 10px;">+ Add</button>
                    </div>
                    <div class="section-body">
                        ${problemGoals.map(g => `
                            <div class="goal-item ${g.status}">
                                <div class="goal-header">
                                    <div class="goal-status-icon">
                                        ${g.status === 'met' ? '✓' : g.status === 'in-progress' ? '⟳' : '○'}
                                    </div>
                                    <div>
                                        <div class="goal-description">${g.description}</div>
                                        <div class="goal-target">
                                            Target: ${g.targetDate} | Criteria: ${g.criteria}
                                        </div>
                                    </div>
                                </div>
                                <div class="goal-actions">
                                    <button onclick="updateGoalStatus(${g.id}, 'met')">Mark Met</button>
                                    <button onclick="updateGoalStatus(${g.id}, 'not-met')">Mark Not Met</button>
                                    <button onclick="editGoal(${g.id})">Edit</button>
                                </div>
                            </div>
                        `).join('')}
                        ${problemGoals.length === 0 ? '<p style="color: #666; font-size: 11px;">No goals defined for this problem.</p>' : ''}
                    </div>
                </div>
                
                <!-- Interventions Section -->
                <div class="careplan-section">
                    <div class="section-header" onclick="toggleSection(this)">
                        <span><span class="expand-icon">▼</span> Interventions (${problemInterventions.length})</span>
                        <button onclick="event.stopPropagation(); addIntervention()" style="padding: 2px 8px; font-size: 10px;">+ Add</button>
                    </div>
                    <div class="section-body">
                        <table class="intervention-table">
                            <thead>
                                <tr>
                                    <th>Intervention</th>
                                    <th>Category</th>
                                    <th>Frequency</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${problemInterventions.map(i => `
                                    <tr>
                                        <td>${i.intervention}</td>
                                        <td>${i.category}</td>
                                        <td>${i.frequency}</td>
                                        <td>${i.assigned}</td>
                                        <td><span class="intervention-status ${i.status}">${i.status}</span></td>
                                        <td>
                                            <button onclick="editIntervention(${i.id})" style="padding: 2px 6px; font-size: 10px;">Edit</button>
                                            <button onclick="discontinueIntervention(${i.id})" style="padding: 2px 6px; font-size: 10px;">D/C</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        ${problemInterventions.length === 0 ? '<p style="color: #666; font-size: 11px;">No interventions defined for this problem.</p>' : ''}
                    </div>
                </div>
                
                <!-- Patient Education Section -->
                <div class="careplan-section">
                    <div class="section-header" onclick="toggleSection(this)">
                        <span><span class="expand-icon">▼</span> Patient Education (${education.length})</span>
                        <button onclick="event.stopPropagation(); addEducation()" style="padding: 2px 8px; font-size: 10px;">+ Add</button>
                    </div>
                    <div class="section-body">
                        ${education.map(e => `
                            <div class="education-item">
                                <div class="icon">${e.type === 'handout' ? '📄' : e.type === 'demonstration' ? '👁️' : '💬'}</div>
                                <div class="content">
                                    <div class="education-title">${e.title}</div>
                                    <div class="education-meta">
                                        Type: ${e.type} | 
                                        ${e.given ? `Given: ${e.givenDate} by ${e.provider}` : 'Not yet given'}
                                    </div>
                                    ${e.given ? `
                                        <div class="education-checkbox">
                                            <input type="checkbox" ${e.understands ? 'checked' : ''} onchange="updateUnderstanding(${e.id}, this.checked)">
                                            Patient verbalizes understanding
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="education-actions">
                                    ${!e.given ? `<button onclick="markEducationGiven(${e.id})">Mark Given</button>` : ''}
                                    <button onclick="printEducation(${e.id})">🖨️ Print</button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        // Toggle section
        function toggleSection(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('.expand-icon');
            
            if (body.classList.contains('collapsed')) {
                body.classList.remove('collapsed');
                icon.textContent = '▼';
            } else {
                body.classList.add('collapsed');
                icon.textContent = '▶';
            }
        }
        
        // Switch tab
        function switchTab(tab) {
            document.querySelectorAll('.careplan-tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter content based on tab
            // In real app, would show/hide sections accordingly
        }
        
        // Modal functions
        function addGoal() {
            document.getElementById('addGoalModal').classList.add('visible');
        }
        
        function addIntervention() {
            document.getElementById('addInterventionModal').classList.add('visible');
        }
        
        function addEducation() {
            alert('Add patient education');
        }
        
        function addProblem() {
            alert('Add problem from problem list');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('visible');
        }
        
        function saveGoal() {
            const description = document.getElementById('goalDescription').value;
            if (!description) {
                alert('Please enter a goal description');
                return;
            }
            alert('Goal saved!');
            closeModal('addGoalModal');
            renderCarePlanContent();
        }
        
        function saveIntervention() {
            const description = document.getElementById('interventionDescription').value;
            if (!description) {
                alert('Please enter an intervention description');
                return;
            }
            alert('Intervention saved!');
            closeModal('addInterventionModal');
            renderCarePlanContent();
        }
        
        // Action functions
        function updateGoalStatus(id, status) {
            alert(`Goal ${id} marked as ${status}`);
            renderCarePlanContent();
        }
        
        function editGoal(id) {
            alert(`Edit goal ${id}`);
        }
        
        function editIntervention(id) {
            alert(`Edit intervention ${id}`);
        }
        
        function discontinueIntervention(id) {
            if (confirm('Discontinue this intervention?')) {
                alert('Intervention discontinued');
                renderCarePlanContent();
            }
        }
        
        function markEducationGiven(id) {
            alert(`Education ${id} marked as given`);
            renderCarePlanContent();
        }
        
        function updateUnderstanding(id, understands) {
            console.log(`Education ${id} understanding: ${understands}`);
        }
        
        function printEducation(id) {
            window.print();
        }
        
        function printCarePlan() {
            window.print();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderProblems();
            renderCarePlanContent();
        });
    </script>
</body>
</html>

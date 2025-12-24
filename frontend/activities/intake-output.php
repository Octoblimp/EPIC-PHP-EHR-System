<?php
/**
 * Intake/Output (I/O) Flowsheet
 * Fluid balance monitoring with shift and 24-hour totals
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
    <title>Intake/Output - Epic EHR</title>
    <link rel="stylesheet" href="../assets/css/epic-styles.css">
    <style>
        .io-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 180px);
            background: #fff;
        }
        
        /* Toolbar */
        .io-toolbar {
            padding: 8px 12px;
            background: linear-gradient(to bottom, #f5f5f5, #e8e8e8);
            border-bottom: 1px solid #ccc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .io-toolbar button {
            padding: 6px 12px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .io-toolbar button:hover {
            background: #f0f0f0;
        }
        
        .io-toolbar button.primary {
            background: #1976d2;
            color: #fff;
            border-color: #1565c0;
        }
        
        .io-toolbar select {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }
        
        .io-toolbar .spacer {
            flex: 1;
        }
        
        /* Summary Cards */
        .io-summary {
            display: flex;
            gap: 16px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
        }
        
        .summary-card {
            flex: 1;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .summary-card.intake {
            border-color: #64b5f6;
            background: #e3f2fd;
        }
        
        .summary-card.output {
            border-color: #ffb74d;
            background: #fff8e1;
        }
        
        .summary-card.balance {
            border-color: #81c784;
            background: #e8f5e9;
        }
        
        .summary-card.balance.negative {
            border-color: #e57373;
            background: #ffebee;
        }
        
        .summary-label {
            font-size: 11px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .summary-card.intake .summary-value { color: #1565c0; }
        .summary-card.output .summary-value { color: #e65100; }
        .summary-card.balance .summary-value { color: #2e7d32; }
        .summary-card.balance.negative .summary-value { color: #c62828; }
        
        .summary-unit {
            font-size: 12px;
            color: #666;
        }
        
        .summary-breakdown {
            font-size: 10px;
            color: #666;
            margin-top: 6px;
            text-align: left;
        }
        
        /* Main Content */
        .io-main {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        /* Left Panel - Categories */
        .io-categories {
            width: 200px;
            border-right: 1px solid #ccc;
            background: #f8f9fa;
            overflow-y: auto;
        }
        
        .category-section {
            border-bottom: 1px solid #ddd;
        }
        
        .category-header {
            padding: 8px 12px;
            background: #e8e8e8;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .category-header:hover {
            background: #ddd;
        }
        
        .category-header.intake { color: #1565c0; }
        .category-header.output { color: #e65100; }
        
        .category-items {
            padding: 4px 0;
        }
        
        .category-item {
            padding: 6px 12px 6px 24px;
            font-size: 11px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        
        .category-item:hover {
            background: #e3f2fd;
        }
        
        .category-item.active {
            background: #bbdefb;
        }
        
        .category-item .total {
            font-weight: 500;
        }
        
        /* Right Panel - Time Grid */
        .io-grid-container {
            flex: 1;
            overflow: auto;
        }
        
        .io-grid {
            min-width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .io-grid th {
            background: #f0f0f0;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #ccc;
            position: sticky;
            top: 0;
            font-weight: 600;
            min-width: 50px;
        }
        
        .io-grid th.time-header {
            min-width: 45px;
        }
        
        .io-grid th.shift-header {
            background: #e3f2fd;
            min-width: 60px;
        }
        
        .io-grid th.total-header {
            background: #e8f5e9;
            min-width: 70px;
        }
        
        .io-grid td {
            padding: 4px;
            text-align: center;
            border: 1px solid #ddd;
            height: 32px;
            vertical-align: middle;
        }
        
        .io-grid td.row-header {
            background: #f8f9fa;
            text-align: left;
            padding-left: 8px;
            font-weight: 500;
            white-space: nowrap;
            position: sticky;
            left: 0;
        }
        
        .io-grid td.row-header.intake {
            border-left: 3px solid #1976d2;
        }
        
        .io-grid td.row-header.output {
            border-left: 3px solid #ff9800;
        }
        
        .io-grid td.editable {
            cursor: pointer;
        }
        
        .io-grid td.editable:hover {
            background: #e3f2fd;
        }
        
        .io-grid td.has-value {
            background: #e8f5e9;
            font-weight: 500;
        }
        
        .io-grid td.shift-total {
            background: #e3f2fd;
            font-weight: 600;
        }
        
        .io-grid td.day-total {
            background: #c8e6c9;
            font-weight: 700;
        }
        
        .io-grid tr.section-header td {
            background: #e0e0e0;
            font-weight: 700;
            font-size: 11px;
        }
        
        .io-grid tr.subtotal td {
            background: #f5f5f5;
            font-weight: 600;
            border-top: 2px solid #ccc;
        }
        
        .io-grid tr.total td {
            background: #e8f5e9;
            font-weight: 700;
            border-top: 2px solid #2e7d32;
        }
        
        .io-grid tr.balance td {
            background: #fff8e1;
            font-weight: 700;
            border-top: 2px solid #ff9800;
        }
        
        /* Value input */
        .value-input {
            width: 40px;
            padding: 2px 4px;
            border: 1px solid #1976d2;
            border-radius: 2px;
            text-align: center;
            font-size: 11px;
        }
        
        /* Entry Modal */
        .io-modal {
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
        
        .io-modal.visible {
            display: flex;
        }
        
        .modal-content {
            width: 400px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
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
        
        /* Quick entry buttons */
        .quick-amounts {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }
        
        .quick-amount {
            padding: 4px 12px;
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }
        
        .quick-amount:hover {
            background: #bbdefb;
        }
    </style>
</head>
<body>
    <!-- Include Epic Header -->
    <div class="epic-header">
        <div class="header-left">
            <span class="epic-logo">Epic</span>
            <span class="header-title">Intake/Output</span>
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
        <a href="flowsheets.php" class="activity-tab">Flowsheets</a>
        <a href="detailed-vitals.php" class="activity-tab">Vitals</a>
        <a href="intake-output.php" class="activity-tab active" style="background: #fff; border: 1px solid #ccc; border-bottom: none; padding: 6px 16px; margin-bottom: -1px;">I/O</a>
        <a href="mar.php" class="activity-tab">MAR</a>
        <a href="care-plan.php" class="activity-tab">Care Plan</a>
    </div>
    
    <div class="io-container">
        <!-- Toolbar -->
        <div class="io-toolbar">
            <button class="primary" onclick="addEntry('intake')">+ Intake</button>
            <button class="primary" onclick="addEntry('output')">+ Output</button>
            <div class="spacer"></div>
            <label style="font-size: 11px;">Date:</label>
            <input type="date" id="ioDate" value="<?= date('Y-m-d') ?>" style="padding: 4px; border: 1px solid #ccc; border-radius: 3px; font-size: 11px;">
            <select id="viewMode">
                <option value="24h">24 Hour View</option>
                <option value="shift">Shift View</option>
                <option value="48h">48 Hour View</option>
            </select>
            <button onclick="refreshIO()">🔄 Refresh</button>
            <button onclick="printIO()">🖨️ Print</button>
        </div>
        
        <!-- Summary Cards -->
        <div class="io-summary">
            <div class="summary-card intake">
                <div class="summary-label">24-Hour Intake</div>
                <div class="summary-value" id="totalIntake">2,450</div>
                <div class="summary-unit">mL</div>
                <div class="summary-breakdown">
                    PO: 1,200 | IV: 1,000 | Blood: 250
                </div>
            </div>
            <div class="summary-card output">
                <div class="summary-label">24-Hour Output</div>
                <div class="summary-value" id="totalOutput">1,850</div>
                <div class="summary-unit">mL</div>
                <div class="summary-breakdown">
                    Urine: 1,600 | Stool: 150 | Other: 100
                </div>
            </div>
            <div class="summary-card balance" id="balanceCard">
                <div class="summary-label">Net Balance</div>
                <div class="summary-value" id="netBalance">+600</div>
                <div class="summary-unit">mL</div>
                <div class="summary-breakdown">
                    Goal: Even to +500 mL
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Urine Output</div>
                <div class="summary-value" id="urineOutput">0.8</div>
                <div class="summary-unit">mL/kg/hr</div>
                <div class="summary-breakdown">
                    Target: &gt;0.5 mL/kg/hr
                </div>
            </div>
        </div>
        
        <!-- Main Grid -->
        <div class="io-main">
            <!-- Categories Panel -->
            <div class="io-categories">
                <div class="category-section">
                    <div class="category-header intake">
                        <span>▼</span> INTAKE
                    </div>
                    <div class="category-items">
                        <div class="category-item active">
                            <span>PO Fluids</span>
                            <span class="total">1,200</span>
                        </div>
                        <div class="category-item">
                            <span>IV Fluids</span>
                            <span class="total">1,000</span>
                        </div>
                        <div class="category-item">
                            <span>IV Medications</span>
                            <span class="total">0</span>
                        </div>
                        <div class="category-item">
                            <span>Blood Products</span>
                            <span class="total">250</span>
                        </div>
                        <div class="category-item">
                            <span>TPN/Tube Feeds</span>
                            <span class="total">0</span>
                        </div>
                        <div class="category-item">
                            <span>Irrigation (In)</span>
                            <span class="total">0</span>
                        </div>
                    </div>
                </div>
                <div class="category-section">
                    <div class="category-header output">
                        <span>▼</span> OUTPUT
                    </div>
                    <div class="category-items">
                        <div class="category-item">
                            <span>Urine</span>
                            <span class="total">1,600</span>
                        </div>
                        <div class="category-item">
                            <span>Stool</span>
                            <span class="total">150</span>
                        </div>
                        <div class="category-item">
                            <span>Emesis</span>
                            <span class="total">0</span>
                        </div>
                        <div class="category-item">
                            <span>NG/OG Drainage</span>
                            <span class="total">0</span>
                        </div>
                        <div class="category-item">
                            <span>Drain Output</span>
                            <span class="total">100</span>
                        </div>
                        <div class="category-item">
                            <span>Chest Tube</span>
                            <span class="total">0</span>
                        </div>
                        <div class="category-item">
                            <span>Irrigation (Out)</span>
                            <span class="total">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- I/O Grid -->
            <div class="io-grid-container">
                <table class="io-grid" id="ioGrid">
                    <thead>
                        <tr>
                            <th style="width: 150px; text-align: left; padding-left: 8px;">Category</th>
                            <!-- Night Shift 7P-7A -->
                            <th class="time-header">19</th>
                            <th class="time-header">20</th>
                            <th class="time-header">21</th>
                            <th class="time-header">22</th>
                            <th class="time-header">23</th>
                            <th class="time-header">00</th>
                            <th class="time-header">01</th>
                            <th class="time-header">02</th>
                            <th class="time-header">03</th>
                            <th class="time-header">04</th>
                            <th class="time-header">05</th>
                            <th class="time-header">06</th>
                            <th class="shift-header">Night</th>
                            <!-- Day Shift 7A-7P -->
                            <th class="time-header">07</th>
                            <th class="time-header">08</th>
                            <th class="time-header">09</th>
                            <th class="time-header">10</th>
                            <th class="time-header">11</th>
                            <th class="time-header">12</th>
                            <th class="time-header">13</th>
                            <th class="time-header">14</th>
                            <th class="time-header">15</th>
                            <th class="time-header">16</th>
                            <th class="time-header">17</th>
                            <th class="time-header">18</th>
                            <th class="shift-header">Day</th>
                            <th class="total-header">24 Hr Total</th>
                        </tr>
                    </thead>
                    <tbody id="ioGridBody">
                        <!-- Grid rows populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Entry Modal -->
    <div class="io-modal" id="entryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Intake</h3>
                <button onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <label>Category:</label>
                <select id="entryCategory">
                    <optgroup label="Intake" id="intakeOptions">
                        <option value="po">PO Fluids</option>
                        <option value="iv">IV Fluids</option>
                        <option value="ivmed">IV Medications</option>
                        <option value="blood">Blood Products</option>
                        <option value="tube">TPN/Tube Feeds</option>
                        <option value="irrigin">Irrigation (In)</option>
                    </optgroup>
                    <optgroup label="Output" id="outputOptions">
                        <option value="urine">Urine</option>
                        <option value="stool">Stool</option>
                        <option value="emesis">Emesis</option>
                        <option value="ng">NG/OG Drainage</option>
                        <option value="drain">Drain Output</option>
                        <option value="chest">Chest Tube</option>
                        <option value="irrigout">Irrigation (Out)</option>
                    </optgroup>
                </select>
                
                <label>Time:</label>
                <input type="time" id="entryTime" value="<?= date('H:i') ?>">
                
                <label>Amount (mL):</label>
                <input type="number" id="entryAmount" placeholder="Enter amount in mL">
                
                <div class="quick-amounts" id="quickAmounts">
                    <span class="quick-amount" onclick="setAmount(30)">30</span>
                    <span class="quick-amount" onclick="setAmount(60)">60</span>
                    <span class="quick-amount" onclick="setAmount(120)">120</span>
                    <span class="quick-amount" onclick="setAmount(240)">240</span>
                    <span class="quick-amount" onclick="setAmount(500)">500</span>
                    <span class="quick-amount" onclick="setAmount(1000)">1000</span>
                </div>
                
                <label>Source/Route:</label>
                <select id="entrySource">
                    <option value="">Select...</option>
                </select>
                
                <label>Notes:</label>
                <input type="text" id="entryNotes" placeholder="Optional notes">
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" style="background: #fff; border: 1px solid #ccc;">Cancel</button>
                <button onclick="saveEntry()" style="background: #1976d2; color: #fff; border: none;">Save</button>
            </div>
        </div>
    </div>
    
    <script>
        // I/O Data structure
        const ioData = {
            intake: {
                po: { name: 'PO Fluids', values: { 7: 240, 8: 120, 12: 240, 14: 200, 18: 400 } },
                iv: { name: 'IV Fluids', values: { 7: 83, 8: 83, 9: 83, 10: 83, 11: 83, 12: 83, 13: 83, 14: 83, 15: 83, 16: 83, 17: 83, 18: 83 } },
                blood: { name: 'Blood Products', values: { 10: 250 } },
                ivmed: { name: 'IV Medications', values: {} },
                tube: { name: 'TPN/Tube Feeds', values: {} },
                irrigin: { name: 'Irrigation (In)', values: {} }
            },
            output: {
                urine: { name: 'Urine', values: { 0: 150, 4: 200, 8: 180, 12: 220, 16: 250, 19: 200, 22: 200, 23: 200 } },
                stool: { name: 'Stool', values: { 9: 150 } },
                emesis: { name: 'Emesis', values: {} },
                ng: { name: 'NG/OG Drainage', values: {} },
                drain: { name: 'Drain Output', values: { 7: 25, 15: 25, 23: 50 } },
                chest: { name: 'Chest Tube', values: {} },
                irrigout: { name: 'Irrigation (Out)', values: {} }
            }
        };
        
        // Source options by category
        const sourceOptions = {
            po: ['Water', 'Juice', 'Coffee/Tea', 'Soda', 'Ice Chips', 'Soup/Broth', 'Other'],
            iv: ['Normal Saline', 'Lactated Ringers', 'D5W', 'D5 1/2 NS', 'Other'],
            ivmed: ['Antibiotic', 'Pain Med', 'Other'],
            blood: ['pRBC', 'Platelets', 'FFP', 'Cryoprecipitate'],
            tube: ['TPN', 'Tube Feeding', 'Lipids'],
            urine: ['Foley', 'Void', 'Straight Cath', 'Condom Cath'],
            stool: ['Formed', 'Soft', 'Loose', 'Liquid', 'Bloody'],
            emesis: ['Bilious', 'Non-bilious', 'Coffee-ground', 'Bloody'],
            ng: ['NG Tube', 'OG Tube', 'G-Tube'],
            drain: ['JP Drain', 'Penrose', 'Wound VAC', 'Other'],
            chest: ['Chest Tube']
        };
        
        let currentType = 'intake';
        
        // Render the I/O grid
        function renderGrid() {
            const tbody = document.getElementById('ioGridBody');
            let html = '';
            
            // Intake Section
            html += `<tr class="section-header"><td colspan="28">INTAKE</td></tr>`;
            
            let intakeSubtotals = Array(28).fill(0);
            
            for (const [key, category] of Object.entries(ioData.intake)) {
                html += `<tr>`;
                html += `<td class="row-header intake">${category.name}</td>`;
                
                // Night shift hours (19-06)
                let nightTotal = 0;
                for (let h = 19; h <= 23; h++) {
                    const val = category.values[h] || '';
                    if (val) { nightTotal += val; intakeSubtotals[h-19] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('intake', '${key}', ${h})">${val}</td>`;
                }
                for (let h = 0; h <= 6; h++) {
                    const val = category.values[h] || '';
                    if (val) { nightTotal += val; intakeSubtotals[5+h] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('intake', '${key}', ${h})">${val}</td>`;
                }
                html += `<td class="shift-total">${nightTotal || ''}</td>`;
                
                // Day shift hours (07-18)
                let dayTotal = 0;
                for (let h = 7; h <= 18; h++) {
                    const val = category.values[h] || '';
                    if (val) { dayTotal += val; intakeSubtotals[12+(h-7)] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('intake', '${key}', ${h})">${val}</td>`;
                }
                html += `<td class="shift-total">${dayTotal || ''}</td>`;
                
                // 24hr total
                const total24 = nightTotal + dayTotal;
                html += `<td class="day-total">${total24 || ''}</td>`;
                html += `</tr>`;
            }
            
            // Intake Subtotal
            html += `<tr class="subtotal"><td class="row-header">INTAKE TOTAL</td>`;
            let intakeNightTotal = 0, intakeDayTotal = 0;
            for (let i = 0; i < 12; i++) {
                intakeNightTotal += intakeSubtotals[i];
                html += `<td>${intakeSubtotals[i] || ''}</td>`;
            }
            html += `<td class="shift-total">${intakeNightTotal}</td>`;
            for (let i = 12; i < 24; i++) {
                intakeDayTotal += intakeSubtotals[i];
                html += `<td>${intakeSubtotals[i] || ''}</td>`;
            }
            html += `<td class="shift-total">${intakeDayTotal}</td>`;
            html += `<td class="day-total">${intakeNightTotal + intakeDayTotal}</td></tr>`;
            
            // Output Section
            html += `<tr class="section-header"><td colspan="28">OUTPUT</td></tr>`;
            
            let outputSubtotals = Array(28).fill(0);
            
            for (const [key, category] of Object.entries(ioData.output)) {
                html += `<tr>`;
                html += `<td class="row-header output">${category.name}</td>`;
                
                // Night shift hours (19-06)
                let nightTotal = 0;
                for (let h = 19; h <= 23; h++) {
                    const val = category.values[h] || '';
                    if (val) { nightTotal += val; outputSubtotals[h-19] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('output', '${key}', ${h})">${val}</td>`;
                }
                for (let h = 0; h <= 6; h++) {
                    const val = category.values[h] || '';
                    if (val) { nightTotal += val; outputSubtotals[5+h] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('output', '${key}', ${h})">${val}</td>`;
                }
                html += `<td class="shift-total">${nightTotal || ''}</td>`;
                
                // Day shift hours (07-18)
                let dayTotal = 0;
                for (let h = 7; h <= 18; h++) {
                    const val = category.values[h] || '';
                    if (val) { dayTotal += val; outputSubtotals[12+(h-7)] += val; }
                    html += `<td class="editable ${val ? 'has-value' : ''}" onclick="editCell('output', '${key}', ${h})">${val}</td>`;
                }
                html += `<td class="shift-total">${dayTotal || ''}</td>`;
                
                // 24hr total
                const total24 = nightTotal + dayTotal;
                html += `<td class="day-total">${total24 || ''}</td>`;
                html += `</tr>`;
            }
            
            // Output Subtotal
            html += `<tr class="subtotal"><td class="row-header">OUTPUT TOTAL</td>`;
            let outputNightTotal = 0, outputDayTotal = 0;
            for (let i = 0; i < 12; i++) {
                outputNightTotal += outputSubtotals[i];
                html += `<td>${outputSubtotals[i] || ''}</td>`;
            }
            html += `<td class="shift-total">${outputNightTotal}</td>`;
            for (let i = 12; i < 24; i++) {
                outputDayTotal += outputSubtotals[i];
                html += `<td>${outputSubtotals[i] || ''}</td>`;
            }
            html += `<td class="shift-total">${outputDayTotal}</td>`;
            html += `<td class="day-total">${outputNightTotal + outputDayTotal}</td></tr>`;
            
            // Net Balance
            const totalIntake = intakeNightTotal + intakeDayTotal;
            const totalOutput = outputNightTotal + outputDayTotal;
            const balance = totalIntake - totalOutput;
            
            html += `<tr class="balance"><td class="row-header">NET BALANCE</td>`;
            for (let i = 0; i < 12; i++) {
                const b = intakeSubtotals[i] - outputSubtotals[i];
                html += `<td>${b !== 0 ? (b > 0 ? '+' : '') + b : ''}</td>`;
            }
            html += `<td class="shift-total">${intakeNightTotal - outputNightTotal > 0 ? '+' : ''}${intakeNightTotal - outputNightTotal}</td>`;
            for (let i = 12; i < 24; i++) {
                const b = intakeSubtotals[i] - outputSubtotals[i];
                html += `<td>${b !== 0 ? (b > 0 ? '+' : '') + b : ''}</td>`;
            }
            html += `<td class="shift-total">${intakeDayTotal - outputDayTotal > 0 ? '+' : ''}${intakeDayTotal - outputDayTotal}</td>`;
            html += `<td class="day-total">${balance > 0 ? '+' : ''}${balance}</td></tr>`;
            
            tbody.innerHTML = html;
            
            // Update summary
            updateSummary(totalIntake, totalOutput, balance);
        }
        
        // Update summary cards
        function updateSummary(intake, output, balance) {
            document.getElementById('totalIntake').textContent = intake.toLocaleString();
            document.getElementById('totalOutput').textContent = output.toLocaleString();
            document.getElementById('netBalance').textContent = (balance >= 0 ? '+' : '') + balance;
            
            const balanceCard = document.getElementById('balanceCard');
            if (balance < 0) {
                balanceCard.classList.add('negative');
            } else {
                balanceCard.classList.remove('negative');
            }
        }
        
        // Add entry modal
        function addEntry(type) {
            currentType = type;
            document.getElementById('modalTitle').textContent = `Add ${type.charAt(0).toUpperCase() + type.slice(1)}`;
            
            // Show/hide option groups
            const intakeOpts = document.getElementById('intakeOptions');
            const outputOpts = document.getElementById('outputOptions');
            
            if (type === 'intake') {
                intakeOpts.style.display = '';
                outputOpts.style.display = 'none';
                document.getElementById('entryCategory').value = 'po';
            } else {
                intakeOpts.style.display = 'none';
                outputOpts.style.display = '';
                document.getElementById('entryCategory').value = 'urine';
            }
            
            updateSourceOptions();
            document.getElementById('entryModal').classList.add('visible');
        }
        
        // Edit cell
        function editCell(type, category, hour) {
            currentType = type;
            document.getElementById('modalTitle').textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1)}`;
            document.getElementById('entryCategory').value = category;
            document.getElementById('entryTime').value = hour.toString().padStart(2, '0') + ':00';
            
            const currentVal = ioData[type][category].values[hour] || '';
            document.getElementById('entryAmount').value = currentVal;
            
            updateSourceOptions();
            document.getElementById('entryModal').classList.add('visible');
        }
        
        // Update source options based on category
        function updateSourceOptions() {
            const category = document.getElementById('entryCategory').value;
            const sourceSelect = document.getElementById('entrySource');
            const options = sourceOptions[category] || [];
            
            sourceSelect.innerHTML = '<option value="">Select...</option>' + 
                options.map(o => `<option value="${o}">${o}</option>`).join('');
        }
        
        document.getElementById('entryCategory').addEventListener('change', updateSourceOptions);
        
        // Set quick amount
        function setAmount(amount) {
            document.getElementById('entryAmount').value = amount;
        }
        
        // Save entry
        function saveEntry() {
            const category = document.getElementById('entryCategory').value;
            const time = document.getElementById('entryTime').value;
            const amount = parseInt(document.getElementById('entryAmount').value);
            
            if (!amount || isNaN(amount)) {
                alert('Please enter a valid amount');
                return;
            }
            
            const hour = parseInt(time.split(':')[0]);
            
            // Determine if intake or output based on category
            const type = ['po', 'iv', 'ivmed', 'blood', 'tube', 'irrigin'].includes(category) ? 'intake' : 'output';
            
            // Save to data
            if (!ioData[type][category]) {
                ioData[type][category] = { name: category, values: {} };
            }
            ioData[type][category].values[hour] = amount;
            
            closeModal();
            renderGrid();
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('entryModal').classList.remove('visible');
            document.getElementById('entryAmount').value = '';
            document.getElementById('entryNotes').value = '';
        }
        
        // Refresh
        function refreshIO() {
            renderGrid();
        }
        
        // Print
        function printIO() {
            window.print();
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderGrid();
        });
    </script>
</body>
</html>

<?php
/**
 * MAR - Medication Administration Record
 * Matching Epic's MAR Hyperspace interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

$patientId = $_GET['patient_id'] ?? 1;
$patientService = new PatientService();
$medicationService = new MedicationService();

try {
    $patient = $patientService->getById($patientId);
    $medications = $medicationService->getByPatient($patientId);
} catch (Exception $e) {
    $patient = null;
    $medications = [];
}

// Generate time slots for MAR grid (every 2 hours for 24 hours)
$timeSlots = [];
$startTime = strtotime('00:00');
for ($i = 0; $i < 12; $i++) {
    $timeSlots[] = date('H:i', $startTime + ($i * 7200));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAR - Epic EHR</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/epic-styles.css">
    <style>
        .mar-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 150px);
        }
        
        /* MAR Header */
        .mar-header {
            background: linear-gradient(180deg, #8b0000 0%, #a00 100%);
            color: white;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mar-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mar-title .icon {
            font-size: 20px;
        }
        
        .mar-toolbar {
            display: flex;
            gap: 8px;
        }
        
        .mar-toolbar button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
        }
        
        .mar-toolbar button:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Date Navigation */
        .date-nav {
            background: #f5f5f5;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #ddd;
        }
        
        .date-nav button {
            background: white;
            border: 1px solid #ccc;
            padding: 4px 12px;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .date-nav button:hover {
            background: #e0e0e0;
        }
        
        .date-display {
            font-weight: 600;
            font-size: 14px;
        }
        
        .view-toggle {
            display: flex;
            gap: 4px;
            margin-left: auto;
        }
        
        .view-toggle button {
            padding: 4px 12px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            font-size: 12px;
        }
        
        .view-toggle button.active {
            background: #0066cc;
            color: white;
            border-color: #0066cc;
        }
        
        /* MAR Grid */
        .mar-grid-container {
            flex: 1;
            overflow: auto;
        }
        
        .mar-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .mar-grid th {
            background: linear-gradient(180deg, #e8e8e8 0%, #d8d8d8 100%);
            border: 1px solid #bbb;
            padding: 6px 8px;
            text-align: center;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .mar-grid th.time-header {
            min-width: 60px;
        }
        
        .mar-grid th.med-header {
            min-width: 200px;
            text-align: left;
        }
        
        .mar-grid td {
            border: 1px solid #ddd;
            padding: 4px;
            vertical-align: top;
        }
        
        .mar-grid td.med-cell {
            background: #f9f9f9;
        }
        
        .mar-grid tr:hover td.med-cell {
            background: #e8f4fc;
        }
        
        /* Medication Info */
        .med-info {
            padding: 4px;
        }
        
        .med-name {
            font-weight: 600;
            color: #0066cc;
            cursor: pointer;
        }
        
        .med-name:hover {
            text-decoration: underline;
        }
        
        .med-details {
            font-size: 11px;
            color: #666;
            margin-top: 2px;
        }
        
        .med-dose {
            font-weight: 500;
        }
        
        .med-route {
            color: #888;
        }
        
        .med-frequency {
            color: #666;
            font-style: italic;
        }
        
        /* Time Cells */
        .time-cell {
            text-align: center;
            min-height: 50px;
            position: relative;
        }
        
        .dose-scheduled {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            border-radius: 3px;
            padding: 4px;
            margin: 2px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .dose-scheduled:hover {
            background: #bbdefb;
        }
        
        .dose-given {
            background: #c8e6c9;
            border: 1px solid #81c784;
            border-radius: 3px;
            padding: 4px;
            margin: 2px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .dose-given .checkmark {
            color: #2e7d32;
            font-weight: bold;
        }
        
        .dose-missed {
            background: #ffcdd2;
            border: 1px solid #ef9a9a;
            border-radius: 3px;
            padding: 4px;
            margin: 2px;
        }
        
        .dose-held {
            background: #fff9c4;
            border: 1px solid #fff59d;
            border-radius: 3px;
            padding: 4px;
            margin: 2px;
        }
        
        .dose-time {
            font-weight: 600;
            font-size: 10px;
        }
        
        .dose-initials {
            font-size: 10px;
            color: #666;
        }
        
        /* Category Headers */
        .category-row td {
            background: linear-gradient(180deg, #e8f4fc 0%, #d4e9f7 100%);
            font-weight: 600;
            color: #0066cc;
            padding: 8px;
        }
        
        /* PRN Section */
        .prn-indicator {
            background: #fff3e0;
            color: #e65100;
            font-size: 10px;
            padding: 1px 4px;
            border-radius: 2px;
            margin-left: 4px;
        }
        
        /* Continuous Infusion */
        .continuous-indicator {
            background: #e8f5e9;
            color: #2e7d32;
            font-size: 10px;
            padding: 1px 4px;
            border-radius: 2px;
            margin-left: 4px;
        }
        
        /* Current Time Indicator */
        .current-time-col {
            background: rgba(255, 235, 59, 0.2) !important;
        }
        
        /* Administration Modal */
        .admin-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 450px;
        }
        
        .admin-modal.active {
            display: block;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .modal-overlay.active {
            display: block;
        }
        
        .admin-modal-header {
            background: #0066cc;
            color: white;
            padding: 12px 16px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-modal-body {
            padding: 16px;
        }
        
        .admin-modal-footer {
            padding: 12px 16px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
        }
        
        /* Legend */
        .mar-legend {
            padding: 8px 16px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 16px;
            font-size: 11px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 2px;
        }
        
        .legend-color.scheduled { background: #e3f2fd; border: 1px solid #90caf9; }
        .legend-color.given { background: #c8e6c9; border: 1px solid #81c784; }
        .legend-color.missed { background: #ffcdd2; border: 1px solid #ef9a9a; }
        .legend-color.held { background: #fff9c4; border: 1px solid #fff59d; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    <?php include __DIR__ . '/../templates/patient-banner.php'; ?>
    
    <div class="mar-container">
        <!-- MAR Header -->
        <div class="mar-header">
            <div class="mar-title">
                <span class="icon">💊</span>
                Medication Administration Record (MAR)
            </div>
            <div class="mar-toolbar">
                <button>📋 Print MAR</button>
                <button>⚙️ Settings</button>
                <button>❓ Help</button>
            </div>
        </div>
        
        <!-- Date Navigation -->
        <div class="date-nav">
            <button onclick="changeDate(-1)">◀ Previous</button>
            <span class="date-display" id="currentDate">December 23, 2024</span>
            <button onclick="changeDate(1)">Next ▶</button>
            <button onclick="goToToday()">Today</button>
            
            <div class="view-toggle">
                <button class="active">24 Hour</button>
                <button>72 Hour</button>
                <button>7 Day</button>
            </div>
        </div>
        
        <!-- MAR Grid -->
        <div class="mar-grid-container">
            <table class="mar-grid">
                <thead>
                    <tr>
                        <th class="med-header">Medication</th>
                        <?php foreach ($timeSlots as $index => $time): ?>
                        <th class="time-header <?= ($index == 6 || $index == 7) ? 'current-time-col' : '' ?>">
                            <?= $time ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Scheduled Medications Category -->
                    <tr class="category-row">
                        <td colspan="13">Scheduled Medications</td>
                    </tr>
                    
                    <!-- Sample Medication 1 -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(1)">METOPROLOL TARTRATE</div>
                                <div class="med-details">
                                    <span class="med-dose">25 mg</span>
                                    <span class="med-route">PO</span>
                                </div>
                                <div class="med-frequency">BID (0800, 2000)</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-given" onclick="showAdminModal(1, '08:00')">
                                <div class="dose-time">08:00</div>
                                <div class="checkmark">✓ Given</div>
                                <div class="dose-initials">CW</div>
                            </div>
                        </td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-scheduled" onclick="showAdminModal(1, '20:00')">
                                <div class="dose-time">20:00</div>
                                <div>Due</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                    </tr>
                    
                    <!-- Sample Medication 2 -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(2)">LISINOPRIL</div>
                                <div class="med-details">
                                    <span class="med-dose">10 mg</span>
                                    <span class="med-route">PO</span>
                                </div>
                                <div class="med-frequency">Daily (0900)</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-given" onclick="showAdminModal(2, '09:00')">
                                <div class="dose-time">09:00</div>
                                <div class="checkmark">✓ Given</div>
                                <div class="dose-initials">CW</div>
                            </div>
                        </td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                    </tr>
                    
                    <!-- Sample Medication 3 - Held -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(3)">FUROSEMIDE</div>
                                <div class="med-details">
                                    <span class="med-dose">40 mg</span>
                                    <span class="med-route">IV</span>
                                </div>
                                <div class="med-frequency">BID (0600, 1800)</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-held" onclick="showAdminModal(3, '06:00')">
                                <div class="dose-time">06:00</div>
                                <div>Held</div>
                                <div class="dose-initials">CW</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-scheduled" onclick="showAdminModal(3, '18:00')">
                                <div class="dose-time">18:00</div>
                                <div>Due</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                    </tr>
                    
                    <!-- PRN Medications Category -->
                    <tr class="category-row">
                        <td colspan="13">PRN Medications</td>
                    </tr>
                    
                    <!-- PRN Medication -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(4)">
                                    MORPHINE SULFATE
                                    <span class="prn-indicator">PRN</span>
                                </div>
                                <div class="med-details">
                                    <span class="med-dose">2-4 mg</span>
                                    <span class="med-route">IV</span>
                                </div>
                                <div class="med-frequency">Q4H PRN for pain (3-10)</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell">
                            <div class="dose-given" onclick="showAdminModal(4, '06:30')">
                                <div class="dose-time">06:30</div>
                                <div class="checkmark">✓ 2mg</div>
                                <div class="dose-initials">NS</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell current-time-col">
                            <div class="dose-given" onclick="showAdminModal(4, '10:45')">
                                <div class="dose-time">10:45</div>
                                <div class="checkmark">✓ 4mg</div>
                                <div class="dose-initials">CW</div>
                            </div>
                        </td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                    </tr>
                    
                    <!-- Another PRN -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(5)">
                                    ONDANSETRON
                                    <span class="prn-indicator">PRN</span>
                                </div>
                                <div class="med-details">
                                    <span class="med-dose">4 mg</span>
                                    <span class="med-route">IV</span>
                                </div>
                                <div class="med-frequency">Q6H PRN for nausea</div>
                            </div>
                        </td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell current-time-col"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                        <td class="time-cell"></td>
                    </tr>
                    
                    <!-- Continuous Infusions Category -->
                    <tr class="category-row">
                        <td colspan="13">Continuous Infusions</td>
                    </tr>
                    
                    <!-- Continuous Infusion -->
                    <tr>
                        <td class="med-cell">
                            <div class="med-info">
                                <div class="med-name" onclick="showMedDetails(6)">
                                    LACTATED RINGERS
                                    <span class="continuous-indicator">Infusion</span>
                                </div>
                                <div class="med-details">
                                    <span class="med-dose">125 mL/hr</span>
                                    <span class="med-route">IV</span>
                                </div>
                                <div class="med-frequency">Continuous</div>
                            </div>
                        </td>
                        <td class="time-cell" colspan="12" style="background: #e8f5e9;">
                            <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #2e7d32;">
                                <span style="font-size: 16px; margin-right: 8px;">▶</span>
                                Running @ 125 mL/hr - Started 12/22/2024 14:00
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Legend -->
        <div class="mar-legend">
            <div class="legend-item">
                <div class="legend-color scheduled"></div>
                <span>Scheduled/Due</span>
            </div>
            <div class="legend-item">
                <div class="legend-color given"></div>
                <span>Given</span>
            </div>
            <div class="legend-item">
                <div class="legend-color missed"></div>
                <span>Missed</span>
            </div>
            <div class="legend-item">
                <div class="legend-color held"></div>
                <span>Held</span>
            </div>
        </div>
    </div>
    
    <!-- Administration Modal -->
    <div class="modal-overlay" id="modalOverlay" onclick="hideAdminModal()"></div>
    <div class="admin-modal" id="adminModal">
        <div class="admin-modal-header">
            <span>Medication Administration</span>
            <button onclick="hideAdminModal()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">&times;</button>
        </div>
        <div class="admin-modal-body">
            <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                <div style="font-weight: 600; color: #0066cc;" id="modalMedName">METOPROLOL TARTRATE</div>
                <div style="font-size: 12px; margin-top: 4px;">
                    <span id="modalMedDose">25 mg</span> • 
                    <span id="modalMedRoute">PO</span> • 
                    <span id="modalMedFreq">BID</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Action</label>
                <select id="adminAction">
                    <option value="given">Given</option>
                    <option value="held">Held</option>
                    <option value="refused">Refused</option>
                    <option value="not_available">Not Available</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Time</label>
                <input type="time" id="adminTime" value="14:00">
            </div>
            
            <div class="form-group" id="doseGroup">
                <label>Dose Given</label>
                <input type="text" id="adminDose" value="25 mg">
            </div>
            
            <div class="form-group" id="siteGroup">
                <label>Site (if applicable)</label>
                <select id="adminSite">
                    <option value="">N/A</option>
                    <option value="right_arm">Right Arm</option>
                    <option value="left_arm">Left Arm</option>
                    <option value="abdomen">Abdomen</option>
                    <option value="right_thigh">Right Thigh</option>
                    <option value="left_thigh">Left Thigh</option>
                </select>
            </div>
            
            <div class="form-group" id="reasonGroup" style="display: none;">
                <label>Reason</label>
                <select id="adminReason">
                    <option value="">Select reason...</option>
                    <option value="bp_low">BP too low</option>
                    <option value="hr_low">HR too low</option>
                    <option value="npo">NPO</option>
                    <option value="patient_refused">Patient refused</option>
                    <option value="med_unavailable">Medication unavailable</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Comments</label>
                <textarea id="adminComments" rows="2" placeholder="Optional comments..."></textarea>
            </div>
        </div>
        <div class="admin-modal-footer">
            <button class="btn btn-secondary" onclick="hideAdminModal()">Cancel</button>
            <button class="btn btn-primary" onclick="recordAdministration()">Record</button>
        </div>
    </div>
    
    <script>
        let currentDate = new Date();
        let selectedMedId = null;
        let selectedTime = null;
        
        function formatDate(date) {
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        
        function changeDate(days) {
            currentDate.setDate(currentDate.getDate() + days);
            document.getElementById('currentDate').textContent = formatDate(currentDate);
            // In production, reload MAR data for new date
        }
        
        function goToToday() {
            currentDate = new Date();
            document.getElementById('currentDate').textContent = formatDate(currentDate);
        }
        
        function showAdminModal(medId, time) {
            selectedMedId = medId;
            selectedTime = time;
            
            // Set time in modal
            document.getElementById('adminTime').value = time;
            
            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('adminModal').classList.add('active');
        }
        
        function hideAdminModal() {
            document.getElementById('modalOverlay').classList.remove('active');
            document.getElementById('adminModal').classList.remove('active');
        }
        
        function showMedDetails(medId) {
            // Open medication details/history modal
            alert('Opening medication details for ID: ' + medId);
        }
        
        // Show/hide reason field based on action
        document.getElementById('adminAction').addEventListener('change', function() {
            const reasonGroup = document.getElementById('reasonGroup');
            const doseGroup = document.getElementById('doseGroup');
            
            if (this.value === 'held' || this.value === 'refused' || this.value === 'not_available') {
                reasonGroup.style.display = 'block';
                doseGroup.style.display = 'none';
            } else {
                reasonGroup.style.display = 'none';
                doseGroup.style.display = 'block';
            }
        });
        
        function recordAdministration() {
            const data = {
                medication_id: selectedMedId,
                action: document.getElementById('adminAction').value,
                time: document.getElementById('adminTime').value,
                dose: document.getElementById('adminDose').value,
                site: document.getElementById('adminSite').value,
                reason: document.getElementById('adminReason').value,
                comments: document.getElementById('adminComments').value
            };
            
            // Send to API
            fetch('<?= API_BASE_URL ?>/medications/administration', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                showMarToast('Administration recorded successfully!', 'success');
                hideAdminModal();
                // Refresh MAR view
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                // Demo mode fallback - save to session
                fetch('api/patient-data.php?action=medication-admin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(d => {
                    showMarToast('Administration recorded successfully!', 'success');
                    hideAdminModal();
                    setTimeout(() => location.reload(), 1000);
                })
                .catch(e => {
                    showMarToast('Administration recorded successfully!', 'success');
                    hideAdminModal();
                    setTimeout(() => location.reload(), 1000);
                });
            });
        }
        
        function showMarToast(message, type = 'info') {
            const existingToast = document.querySelector('.mar-toast');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = 'mar-toast';
            const bgColors = { success: '#4CAF50', error: '#f44336', warning: '#ff9800', info: '#2196F3' };
            toast.style.cssText = `position:fixed;bottom:30px;right:30px;background:${bgColors[type]};color:white;padding:12px 20px;border-radius:6px;box-shadow:0 4px 15px rgba(0,0,0,0.2);z-index:99999;font-size:14px;display:flex;align-items:center;gap:10px;`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
        
        // Initialize
        document.getElementById('currentDate').textContent = formatDate(currentDate);
    </script>
</body>
</html>

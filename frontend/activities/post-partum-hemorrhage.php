<?php
/**
 * Post Partum Hemorrhage View
 * Specialized flowsheet for OB emergencies
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/api.php';

// Get patient ID - default to Mary Smith (PPH patient)
$patientId = $_GET['patient_id'] ?? 2;

// Fetch patient data
$headerData = $patientService->getHeader($patientId);
$patient = $headerData['success'] ? $headerData['data']['patient'] : null;
$encounter = $headerData['success'] ? $headerData['data']['encounter'] : null;
$allergies = $headerData['success'] ? $headerData['data']['allergies'] : [];

// Fetch additional data
$medications = $medicationService->getCategorized($patientId);
$vitals = $vitalService->getLatest($patientId);
$flowsheetData = $flowsheetService->getGrouped($patientId, 'Post Partum Hemorrhage');

$pageTitle = 'Post Partum Hemorrhage';
$currentActivity = 'flowsheets';

include __DIR__ . '/../templates/header.php';
?>

    <!-- Main Content Container -->
    <div class="main-container">
        <!-- Navigation Sidebar -->
        <?php include __DIR__ . '/../templates/navigation.php'; ?>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Activity Header -->
            <div class="content-header">
                <span class="content-title">Post Partum Hemorrhage</span>
                <div class="content-toolbar">
                    <button class="content-btn">▶ Expand All</button>
                    <button class="content-btn">◀ Collapse All</button>
                    <span style="color: red; font-weight: bold; margin-left: 20px;">⚠️ Not Scanned</span>
                </div>
            </div>

            <!-- Content Body - Split Layout -->
            <div class="content-body" style="display: flex; gap: 10px; padding: 10px;">
                <!-- Left Panel - Flowsheet Tree -->
                <div style="width: 200px; background: #f8f8f8; border: 1px solid #ccc; overflow-y: auto;">
                    <!-- Favorites -->
                    <div class="panel-section-header" style="background: #4a90c2; color: white;">
                        ★ Favorites
                    </div>
                    <div style="padding: 5px;">
                        <div class="nav-item" style="border: none; padding: 4px 8px;">
                            <span class="icon">👥</span> Staff
                        </div>
                        <div class="nav-item active" style="border: none; padding: 4px 8px;">
                            <span class="icon">⭐</span> Staff
                        </div>
                    </div>

                    <!-- Essential Flowsheets -->
                    <div class="panel-section-header" style="background: #6ba3d6; color: white;">
                        📋 Essential Flowsheets
                    </div>
                    
                    <!-- Events Section -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Events
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Code Start
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Code End
                        </div>
                    </div>

                    <!-- Documentation Section -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Documentation
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Level of consciousness
                        </div>
                    </div>

                    <!-- Bleeding Assessment Summary -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Bleeding Assessment Summary
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Fundus
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Lochia or Bleeding
                        </div>
                    </div>

                    <!-- Interventions -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Interventions
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            Blood Collection without LDA
                        </div>
                        <div class="nav-item" style="border: none; padding: 3px 8px 3px 20px; font-size: 11px;">
                            ABO
                        </div>
                    </div>

                    <!-- Drains -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Drains
                        </div>
                    </div>

                    <!-- Airways -->
                    <div style="padding-left: 10px;">
                        <div class="panel-section-header" style="font-size: 11px; padding: 4px 8px;">
                            📁 Airways
                        </div>
                    </div>
                </div>

                <!-- Center Panel - Main Data Entry -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
                    <!-- QuickFlip Vitals -->
                    <div style="background: white; border: 1px solid #ccc;">
                        <div class="panel-section-header">
                            <span>QuickFlip</span>
                        </div>
                        <div style="padding: 10px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Vitals</th>
                                        <th>Heart Rate</th>
                                        <th>Respirations</th>
                                        <th>File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td><input type="text" class="form-control form-control-sm" placeholder="HR"></td>
                                        <td><input type="text" class="form-control form-control-sm" placeholder="RR"></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>BP</td>
                                        <td><input type="text" class="form-control form-control-sm" placeholder="Systolic"></td>
                                        <td>SpO2</td>
                                        <td><input type="text" class="form-control form-control-sm" placeholder="%"></td>
                                    </tr>
                                    <tr>
                                        <td>Temp</td>
                                        <td><input type="text" class="form-control form-control-sm" placeholder="°F"></td>
                                        <td>Temp Source</td>
                                        <td>
                                            <select class="form-control form-control-sm">
                                                <option>Oral</option>
                                                <option>Axillary</option>
                                                <option>Tympanic</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div style="margin-top: 10px;">
                                <button class="content-btn">Load Past Event</button>
                                <label style="margin-left: 20px;">
                                    <input type="checkbox"> Show: ☐ Deleted ☑ Status Changes
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Event Log -->
                    <div style="background: white; border: 1px solid #ccc; flex: 1;">
                        <div class="flowsheet-tabs" style="background: #e0e0e0;">
                            <div class="flowsheet-tab">Event Log</div>
                            <div class="flowsheet-tab active">Patient Summary</div>
                            <div class="flowsheet-tab">Physical Diagram</div>
                            <div class="flowsheet-tab">Orders</div>
                        </div>
                        
                        <div style="padding: 10px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time ▼</th>
                                        <th>Full Time</th>
                                        <th>Event</th>
                                        <th>Details</th>
                                        <th>User Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $flowData = $flowsheetData['success'] ? $flowsheetData['data'] : [];
                                    $allEntries = [];
                                    foreach ($flowData as $section => $rows) {
                                        foreach ($rows as $rowName => $entries) {
                                            foreach ($entries as $entry) {
                                                $entry['section'] = $section;
                                                $entry['row_name'] = $rowName;
                                                $allEntries[] = $entry;
                                            }
                                        }
                                    }
                                    usort($allEntries, function($a, $b) {
                                        return strcmp($b['entry_datetime'], $a['entry_datetime']);
                                    });
                                    
                                    foreach (array_slice($allEntries, 0, 10) as $entry):
                                        $dt = explode(' ', $entry['entry_datetime']);
                                    ?>
                                    <tr>
                                        <td><?php echo sanitize($dt[0] ?? ''); ?></td>
                                        <td><?php echo sanitize($entry['entry_time'] ?? ''); ?></td>
                                        <td><?php echo sanitize($dt[1] ?? ''); ?></td>
                                        <td><?php echo sanitize($entry['row_name']); ?></td>
                                        <td><?php echo sanitize($entry['value']); ?></td>
                                        <td><?php echo sanitize($entry['documented_by'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Medications -->
                <div style="width: 300px; background: #f8f8f8; border: 1px solid #ccc;">
                    <div class="panel-header" style="display: flex; justify-content: space-between;">
                        <span>Medications</span>
                        <div>
                            <button style="background: none; border: none; color: white; cursor: pointer;">▲</button>
                            <button style="background: none; border: none; color: white; cursor: pointer;">◀ Collapse All</button>
                        </div>
                    </div>

                    <!-- Post Partum Hemorrhage Meds -->
                    <div class="panel-section">
                        <div class="panel-section-header" style="background: #ffcccc;">
                            ▼ Post Partum Hemorrhage Meds
                        </div>
                        <div class="panel-section-content">
                            <ul class="med-list">
                                <?php 
                                $meds = $medications['success'] ? $medications['data'] : [];
                                $allMeds = array_merge(
                                    $meds['scheduled'] ?? [],
                                    $meds['continuous'] ?? [],
                                    $meds['prn'] ?? []
                                );
                                foreach ($allMeds as $med):
                                ?>
                                <li class="med-item <?php echo $med['is_high_alert'] ? 'high-alert' : ''; ?>">
                                    <div class="d-flex align-center gap-1">
                                        <input type="checkbox">
                                        <div>
                                            <div class="med-name"><?php echo sanitize($med['name']); ?></div>
                                            <div class="med-dose"><?php echo sanitize($med['full_dose']); ?></div>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Orders Section -->
                    <div class="panel-header" style="margin-top: 10px;">
                        Orders
                    </div>

                    <!-- Acknowledge Orders -->
                    <div class="panel-section">
                        <div class="panel-section-header">
                            ▼ Acknowledge Orders
                        </div>
                        <div class="panel-section-content">
                            <ul class="order-list">
                                <li class="order-item">
                                    <input type="checkbox"> Sequential Compression Device
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Orders to be Completed -->
                    <div class="panel-section">
                        <div class="panel-section-header">
                            ▼ Orders to be Completed
                        </div>
                        <div class="panel-section-content">
                            <ul class="order-list">
                                <li class="order-item">
                                    <input type="checkbox"> Sequential Compression Device
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Code Start Modal -->
    <div id="code-start-modal" class="modal-overlay" style="display: none;">
        <div class="modal-dialog" style="min-width: 350px;">
            <div class="modal-header" style="background: #dc3545;">
                Code Start (Editing)
                <button class="modal-close" onclick="document.getElementById('code-start-modal').style.display='none'">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Time:</label>
                    <div class="d-flex gap-1">
                        <input type="text" class="form-control" id="code-time" value="<?php echo date('H:i:s'); ?>" style="width: 100px;">
                        <span>⌚</span>
                        <label class="form-label">Date:</label>
                        <input type="date" class="form-control" id="code-date" value="<?php echo date('Y-m-d'); ?>" style="width: 130px;">
                        <span>▼</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Comments:</label>
                    <textarea class="form-control" rows="4" id="code-comments"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" onclick="saveCodeStart()">✓ Accept</button>
                <button class="btn btn-secondary" onclick="document.getElementById('code-start-modal').style.display='none'">✕ Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Open code start modal for Code Start row
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.textContent.trim() === 'Code Start') {
                item.addEventListener('click', () => {
                    document.getElementById('code-start-modal').style.display = 'flex';
                });
            }
        });

        async function saveCodeStart() {
            const time = document.getElementById('code-time').value;
            const date = document.getElementById('code-date').value;
            const comments = document.getElementById('code-comments').value;
            
            // Save via API
            const response = await api.post('flowsheets/entry', {
                patient_id: <?php echo $patientId; ?>,
                row_name: 'Code Start',
                value: time,
                section: 'Events',
                flowsheet_group: 'Post Partum Hemorrhage',
                entry_datetime: `${date} ${time}`,
                documented_by: currentUser?.name || 'Unknown',
                comments: comments
            });
            
            if (response.success) {
                document.getElementById('code-start-modal').style.display = 'none';
                window.location.reload();
            } else {
                alert('Failed to save: ' + (response.error || 'Unknown error'));
            }
        }
    </script>

<?php include __DIR__ . '/../templates/footer.php'; ?>

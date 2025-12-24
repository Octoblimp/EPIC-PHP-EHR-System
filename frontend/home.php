<?php
/**
 * Openspace EHR - Home Dashboard
 * Epic Hyperspace-style home page
 */

require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Home - ' . APP_NAME;

// Fetch patients for the dashboard using the patientService
$patientsData = $patientService->getAll();
$patients = ($patientsData['success'] ?? false) ? ($patientsData['data'] ?? []) : [];

// Default demo patients if API not available
if (empty($patients)) {
    $patients = [
        ['id' => 1, 'first_name' => 'John', 'last_name' => 'Smith', 'mrn' => 'MRN000001', 'room' => '412-A', 'date_of_birth' => '1955-03-15', 'gender' => 'Male', 'diagnosis' => 'Community-acquired pneumonia'],
        ['id' => 2, 'first_name' => 'Mary', 'last_name' => 'Johnson', 'mrn' => 'MRN000002', 'room' => '415-B', 'date_of_birth' => '1948-07-22', 'gender' => 'Female', 'diagnosis' => 'CHF exacerbation'],
        ['id' => 3, 'first_name' => 'Robert', 'last_name' => 'Williams', 'mrn' => 'MRN000003', 'room' => '420-A', 'date_of_birth' => '1960-11-08', 'gender' => 'Male', 'diagnosis' => 'Post-op hip replacement'],
        ['id' => 4, 'first_name' => 'Linda', 'last_name' => 'Davis', 'mrn' => 'MRN000004', 'room' => '418-A', 'date_of_birth' => '1972-02-14', 'gender' => 'Female', 'diagnosis' => 'Diabetic ketoacidosis'],
        ['id' => 5, 'first_name' => 'James', 'last_name' => 'Wilson', 'mrn' => 'MRN000005', 'room' => '422-B', 'date_of_birth' => '1945-09-30', 'gender' => 'Male', 'diagnosis' => 'COPD exacerbation'],
    ];
}

include 'includes/header.php';
?>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Census Summary Cards -->
        <div class="dashboard-grid" style="margin-bottom: 20px;">
            <div class="census-card">
                <div class="census-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="census-info">
                    <h3><?php echo count($patients); ?></h3>
                    <p>My Patients</p>
                </div>
            </div>
            <div class="census-card">
                <div class="census-icon orange">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="census-info">
                    <h3>3</h3>
                    <p>Critical Results</p>
                </div>
            </div>
            <div class="census-card">
                <div class="census-icon green">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="census-info">
                    <h3>12</h3>
                    <p>Meds Due Now</p>
                </div>
            </div>
            <div class="census-card">
                <div class="census-icon purple">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="census-info">
                    <h3>8</h3>
                    <p>In Basket Messages</p>
                </div>
            </div>
        </div>

        <div class="content-columns">
            <div class="content-column" style="flex: 2;">
                <!-- My Patient List -->
                <div class="content-panel">
                    <div class="panel-header blue">
                        <span><i class="fas fa-clipboard-list"></i> My Patient List</span>
                        <div class="panel-header-actions">
                            <a href="patients.php">View All</a>
                            <a href="#">Print List</a>
                        </div>
                    </div>
                    <div class="panel-content" style="padding: 0;">
                        <table class="patient-list-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Patient</th>
                                    <th>MRN</th>
                                    <th>Age/Sex</th>
                                    <th>Diagnosis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): 
                                    $dob = new DateTime($patient['date_of_birth'] ?? '1950-01-01');
                                    $age = $dob->diff(new DateTime())->y;
                                    $gender_abbr = substr($patient['gender'] ?? 'U', 0, 1);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($patient['room'] ?? 'N/A'); ?></strong></td>
                                    <td>
                                        <a href="patient-chart.php?id=<?php echo $patient['id']; ?>" class="patient-link">
                                            <?php echo htmlspecialchars(($patient['last_name'] ?? '') . ', ' . ($patient['first_name'] ?? '')); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['mrn'] ?? ''); ?></td>
                                    <td><?php echo $age; ?> <?php echo $gender_abbr; ?></td>
                                    <td><?php echo htmlspecialchars($patient['diagnosis'] ?? $patient['chief_complaint'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="patient-chart.php?id=<?php echo $patient['id']; ?>&tab=summary">Summary</a> |
                                        <a href="patient-chart.php?id=<?php echo $patient['id']; ?>&tab=mar">MAR</a> |
                                        <a href="patient-chart.php?id=<?php echo $patient['id']; ?>&tab=orders">Orders</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders Needing Action -->
                <div class="content-panel">
                    <div class="panel-header orange">
                        <span><i class="fas fa-vial"></i> Orders Needing Unit Collect</span>
                        <div class="panel-header-actions">
                            <a href="#">View All</a>
                        </div>
                    </div>
                    <div class="panel-content compact">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Room</th>
                                    <th>Order</th>
                                    <th>Priority</th>
                                    <th>Ordered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="patient-chart.php?id=1">Smith, John</a></td>
                                    <td>412-A</td>
                                    <td>CBC with Diff</td>
                                    <td><span class="text-danger font-bold">STAT</span></td>
                                    <td>Today 06:00</td>
                                </tr>
                                <tr>
                                    <td><a href="patient-chart.php?id=2">Johnson, Mary</a></td>
                                    <td>415-B</td>
                                    <td>BNP</td>
                                    <td><span class="text-danger font-bold">STAT</span></td>
                                    <td>Today 06:00</td>
                                </tr>
                                <tr>
                                    <td><a href="patient-chart.php?id=4">Davis, Linda</a></td>
                                    <td>418-A</td>
                                    <td>Basic Metabolic Panel</td>
                                    <td>Routine</td>
                                    <td>Today 06:00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Medications Due -->
                <div class="content-panel">
                    <div class="panel-header purple">
                        <span><i class="fas fa-pills"></i> Medications Due Now</span>
                        <div class="panel-header-actions">
                            <a href="#">View All</a>
                        </div>
                    </div>
                    <div class="panel-content compact">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Room</th>
                                    <th>Medication</th>
                                    <th>Due</th>
                                    <th>Route</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="patient-chart.php?id=1&tab=mar">Smith, John</a></td>
                                    <td>412-A</td>
                                    <td>Metformin 500mg</td>
                                    <td>08:00</td>
                                    <td>PO</td>
                                </tr>
                                <tr>
                                    <td><a href="patient-chart.php?id=1&tab=mar">Smith, John</a></td>
                                    <td>412-A</td>
                                    <td>Lisinopril 10mg</td>
                                    <td>09:00</td>
                                    <td>PO</td>
                                </tr>
                                <tr>
                                    <td><a href="patient-chart.php?id=2&tab=mar">Johnson, Mary</a></td>
                                    <td>415-B</td>
                                    <td>Furosemide 40mg</td>
                                    <td>08:00</td>
                                    <td>IV</td>
                                </tr>
                                <tr>
                                    <td><a href="patient-chart.php?id=5&tab=mar">Wilson, James</a></td>
                                    <td>422-B</td>
                                    <td>Albuterol Neb</td>
                                    <td>08:00</td>
                                    <td>INH</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="content-column" style="flex: 1;">
                <!-- Critical Results -->
                <div class="content-panel">
                    <div class="panel-header red">
                        <span><i class="fas fa-exclamation-triangle"></i> Critical Results</span>
                    </div>
                    <div class="panel-content">
                        <div style="border-left: 4px solid #d04040; padding: 8px 12px; margin-bottom: 10px; background: #fff8f8;">
                            <div class="font-bold"><a href="patient-chart.php?id=4&tab=results">Davis, Linda</a></div>
                            <div class="font-sm">Potassium: <span class="text-danger font-bold">6.8 mEq/L</span></div>
                            <div class="font-xs text-muted">Today 05:30 - Acknowledged</div>
                        </div>
                        <div style="border-left: 4px solid #d04040; padding: 8px 12px; margin-bottom: 10px; background: #fff8f8;">
                            <div class="font-bold"><a href="patient-chart.php?id=2&tab=results">Johnson, Mary</a></div>
                            <div class="font-sm">Troponin: <span class="text-danger font-bold">0.45 ng/mL</span></div>
                            <div class="font-xs text-muted">Today 04:00 - Pending Review</div>
                        </div>
                        <div style="border-left: 4px solid #cc6600; padding: 8px 12px; background: #fffaf0;">
                            <div class="font-bold"><a href="patient-chart.php?id=1&tab=results">Smith, John</a></div>
                            <div class="font-sm">Glucose: <span class="text-warning font-bold">312 mg/dL</span></div>
                            <div class="font-xs text-muted">Today 06:00 - Pending Review</div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Notes -->
                <div class="content-panel sticky-note-panel">
                    <div class="panel-header yellow">
                        <span><i class="fas fa-sticky-note"></i> Unit Notes</span>
                        <div class="panel-header-actions">
                            <a href="#" style="color: #333;">+ Add</a>
                        </div>
                    </div>
                    <div class="panel-content">
                        <div class="sticky-note-content">
                            <strong>412-A Smith:</strong> Family meeting scheduled 14:00. Daughter to attend.<br><br>
                            <strong>415-B Johnson:</strong> Echo scheduled today 10:00. Keep NPO until after.<br><br>
                            <strong>Reminder:</strong> Unit meeting 15:00 in conference room.
                        </div>
                        <div class="sticky-note-footer">
                            Last updated: Today 06:30
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="content-panel">
                    <div class="panel-header gray">
                        <span><i class="fas fa-link"></i> Quick Links</span>
                    </div>
                    <div class="panel-content">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <a href="patients.php" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                                <i class="fas fa-search"></i> Find Patient
                            </a>
                            <a href="schedule.php" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                                <i class="fas fa-calendar"></i> Schedule
                            </a>
                            <a href="inbox.php" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                                <i class="fas fa-inbox"></i> In Basket
                            </a>
                            <a href="reports.php" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Discharge Tracking -->
                <div class="content-panel">
                    <div class="panel-header green">
                        <span><i class="fas fa-door-open"></i> Pending Discharges</span>
                    </div>
                    <div class="panel-content compact">
                        <div style="padding: 6px 0; border-bottom: 1px solid #eee;">
                            <a href="patient-chart.php?id=3" class="font-bold">Williams, Robert</a>
                            <div class="font-xs text-muted">420-A | Expected: Today PM</div>
                        </div>
                        <div style="padding: 6px 0;">
                            <a href="patient-chart.php?id=1" class="font-bold">Smith, John</a>
                            <div class="font-xs text-muted">412-A | Expected: Tomorrow</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * Patient List Page - Openspace EHR
 * Browse and search patients
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/api.php';

// Fetch all patients
$patientsData = $patientService->getAll();
$patients = $patientsData['success'] ? $patientsData['data'] : [];

$pageTitle = 'Patient List';
$page_title = $pageTitle . ' - ' . APP_NAME;

$user = getCurrentUser();

include 'includes/header.php';
?>

    <!-- Content -->
    <div class="dashboard-content">
        <div style="background: white; border: 1px solid #ccc; max-width: 1200px; margin: 0 auto; border-radius: 4px;">
            <!-- Header -->
            <div class="panel-header blue">
                <span><i class="fas fa-clipboard-list"></i> Patient List</span>
            </div>

            <!-- Search Bar -->
            <div style="padding: 15px; background: #f5f5f5; border-bottom: 1px solid #ccc;">
                <div class="d-flex gap-2 align-center">
                    <label class="form-label" style="margin: 0; white-space: nowrap;">Search Patient:</label>
                    <input type="text" class="form-control" id="patient-search" placeholder="Enter name or MRN..." style="max-width: 300px;">
                    <button class="btn btn-primary" onclick="searchPatients()">🔍 Search</button>
                    <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                </div>
            </div>

            <!-- Patient Table -->
            <div style="padding: 15px;">
                <table class="data-table" id="patient-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Open</th>
                            <th>Patient Name</th>
                            <th>MRN</th>
                            <th>DOB</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Location</th>
                            <th>Attending</th>
                        </tr>
                    </thead>
                    <tbody id="patient-list">
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td class="text-center">
                                <a href="patient-chart.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary" style="padding: 2px 8px; font-size: 10px;">
                                    Open
                                </a>
                            </td>
                            <td>
                                <strong><?php echo sanitize($patient['full_name']); ?></strong>
                            </td>
                            <td><?php echo sanitize($patient['mrn']); ?></td>
                            <td><?php echo sanitize($patient['date_of_birth']); ?></td>
                            <td><?php echo $patient['age']; ?></td>
                            <td><?php echo sanitize($patient['gender']); ?></td>
                            <td>--</td>
                            <td><?php echo sanitize($patient['primary_care_provider'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                                No patients found. Make sure the Python backend is running.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div style="padding: 10px 15px; background: #f5f5f5; border-top: 1px solid #ccc; display: flex; justify-content: space-between; align-items: center;">
                <span class="text-muted text-small">
                    Showing <?php echo count($patients); ?> patient(s)
                </span>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary" disabled>◀ Previous</button>
                    <button class="btn btn-secondary" disabled>Next ▶</button>
                </div>
            </div>
        </div>

        <!-- Quick Demo Links -->
        <div style="max-width: 1200px; margin: 20px auto; background: white; border: 1px solid #ccc; padding: 15px;">
            <h3 style="margin-bottom: 10px;">🎯 Demo Scenarios</h3>
            <div class="d-flex gap-2 flex-wrap">
                <a href="patient-chart.php?id=1" class="btn btn-secondary">
                    👤 Pastoral Services Case (Melissa Testmonday)
                </a>
                <a href="patient-chart.php?id=2&tab=summary" class="btn btn-secondary" style="background: #ffcccc;">
                    🩸 Post Partum Hemorrhage (Mary Smith)
                </a>
                <a href="patient-chart.php?id=1&tab=flowsheets" class="btn btn-secondary">
                    📋 Flowsheets View
                </a>
            </div>
        </div>
    </div>

    <script>
        // Patient search
        async function searchPatients() {
            const query = document.getElementById('patient-search').value.trim();
            if (query.length < 2) {
                alert('Please enter at least 2 characters to search');
                return;
            }

            const response = await api.get('patients/search', { q: query });
            if (response.success) {
                updatePatientTable(response.data);
            }
        }

        function clearSearch() {
            document.getElementById('patient-search').value = '';
            window.location.reload();
        }

        function updatePatientTable(patients) {
            const tbody = document.getElementById('patient-list');
            
            if (patients.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 30px;">
                            No patients found matching your search.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = patients.map(patient => `
                <tr>
                    <td class="text-center">
                        <a href="patient-chart.php?id=${patient.id}" class="btn btn-primary" style="padding: 2px 8px; font-size: 10px;">
                            Open
                        </a>
                    </td>
                    <td><strong>${patient.full_name}</strong></td>
                    <td>${patient.mrn}</td>
                    <td>${patient.date_of_birth}</td>
                    <td>${patient.age}</td>
                    <td>${patient.gender}</td>
                    <td>--</td>
                    <td>${patient.primary_care_provider || 'N/A'}</td>
                </tr>
            `).join('');
        }

        // Enter key to search
        document.getElementById('patient-search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchPatients();
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
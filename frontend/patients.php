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
        <div style="background: white; border: 1px solid #ccc; max-width: 1400px; margin: 0 auto; border-radius: 4px;">
            <!-- Header -->
            <div class="panel-header blue">
                <span><i class="fas fa-clipboard-list"></i> Patient List</span>
                <div style="margin-left: auto;">
                    <button class="btn" style="background: rgba(255,255,255,0.2); color: white; border: none; margin-right: 8px;" onclick="toggleAdvancedSearch()">
                        <i class="fas fa-filter"></i> Advanced Search
                    </button>
                    <button class="btn" style="background: rgba(255,255,255,0.2); color: white; border: none;" onclick="exportPatients()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Basic Search Bar -->
            <div style="padding: 15px; background: #f5f5f5; border-bottom: 1px solid #ccc;">
                <div class="d-flex gap-2 align-center">
                    <label class="form-label" style="margin: 0; white-space: nowrap;">Search Patient:</label>
                    <input type="text" class="form-control" id="patient-search" placeholder="Enter name, MRN, or DOB..." style="max-width: 300px;">
                    <button class="btn btn-primary" onclick="searchPatients()"><i class="fas fa-search"></i> Search</button>
                    <button class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                </div>
            </div>
            
            <!-- Advanced Search Panel (Hidden by default) -->
            <div id="advancedSearchPanel" style="display: none; padding: 20px; background: #e8f4fc; border-bottom: 2px solid #1a4a5e;">
                <h4 style="margin: 0 0 15px; color: #1a4a5e;"><i class="fas fa-sliders-h"></i> Advanced Search Filters</h4>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="filter-firstname" placeholder="First name">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="filter-lastname" placeholder="Last name">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">MRN</label>
                        <input type="text" class="form-control" id="filter-mrn" placeholder="Medical Record #">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="filter-dob">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Age Range</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="number" class="form-control" id="filter-age-min" placeholder="Min" min="0" max="120" style="width: 70px;">
                            <span style="align-self: center;">-</span>
                            <input type="number" class="form-control" id="filter-age-max" placeholder="Max" min="0" max="120" style="width: 70px;">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Gender</label>
                        <select class="form-control" id="filter-gender">
                            <option value="">All</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Location</label>
                        <select class="form-control" id="filter-location">
                            <option value="">All Locations</option>
                            <option value="ED">Emergency Department</option>
                            <option value="ICU">Intensive Care Unit</option>
                            <option value="MedSurg">Medical/Surgical</option>
                            <option value="L&D">Labor & Delivery</option>
                            <option value="NICU">NICU</option>
                            <option value="Outpatient">Outpatient</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Primary Care Provider</label>
                        <select class="form-control" id="filter-pcp">
                            <option value="">All Providers</option>
                            <option value="Dr. Smith">Dr. Smith</option>
                            <option value="Dr. Wilson">Dr. Wilson</option>
                            <option value="Dr. Johnson">Dr. Johnson</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Insurance</label>
                        <select class="form-control" id="filter-insurance">
                            <option value="">All Insurance</option>
                            <option value="Medicare">Medicare</option>
                            <option value="Medicaid">Medicaid</option>
                            <option value="BCBS">Blue Cross Blue Shield</option>
                            <option value="Aetna">Aetna</option>
                            <option value="UHC">UnitedHealthcare</option>
                            <option value="Self-Pay">Self-Pay</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Last Visit</label>
                        <select class="form-control" id="filter-lastvisit">
                            <option value="">Any Time</option>
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Has Upcoming Appt</label>
                        <select class="form-control" id="filter-upcoming">
                            <option value="">Any</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Patient Status</label>
                        <select class="form-control" id="filter-status">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="deceased">Deceased</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="applyAdvancedFilters()"><i class="fas fa-search"></i> Apply Filters</button>
                    <button class="btn btn-secondary" onclick="clearAdvancedFilters()"><i class="fas fa-times"></i> Clear All</button>
                    <button class="btn btn-secondary" onclick="saveSearchPreset()"><i class="fas fa-save"></i> Save Preset</button>
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
                    Showing <span id="patient-count"><?php echo count($patients); ?></span> patient(s)
                </span>
                <div class="d-flex gap-2">
                    <button class="btn btn-secondary" disabled>◀ Previous</button>
                    <button class="btn btn-secondary" disabled>Next ▶</button>
                </div>
            </div>
        </div>

        <!-- Quick Demo Links -->
        <div style="max-width: 1400px; margin: 20px auto; background: white; border: 1px solid #ccc; padding: 15px;">
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
        // Store all patients for client-side filtering
        const allPatients = <?php echo json_encode($patients); ?>;
        
        // Toggle advanced search panel
        function toggleAdvancedSearch() {
            const panel = document.getElementById('advancedSearchPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }
        
        // Patient search (basic)
        async function searchPatients() {
            const query = document.getElementById('patient-search').value.trim();
            if (query.length < 2) {
                alert('Please enter at least 2 characters to search');
                return;
            }

            const response = await api.get('patients/search', { q: query });
            if (response.success) {
                updatePatientTable(response.data);
            } else {
                // Client-side fallback filtering
                const filtered = allPatients.filter(p => {
                    const searchLower = query.toLowerCase();
                    return (p.full_name && p.full_name.toLowerCase().includes(searchLower)) ||
                           (p.mrn && p.mrn.toLowerCase().includes(searchLower)) ||
                           (p.date_of_birth && p.date_of_birth.includes(query));
                });
                updatePatientTable(filtered);
            }
        }

        // Apply advanced filters (client-side)
        function applyAdvancedFilters() {
            const firstName = document.getElementById('filter-firstname').value.toLowerCase();
            const lastName = document.getElementById('filter-lastname').value.toLowerCase();
            const mrn = document.getElementById('filter-mrn').value.toLowerCase();
            const dob = document.getElementById('filter-dob').value;
            const ageMin = parseInt(document.getElementById('filter-age-min').value) || 0;
            const ageMax = parseInt(document.getElementById('filter-age-max').value) || 150;
            const gender = document.getElementById('filter-gender').value;
            const location = document.getElementById('filter-location').value;
            const pcp = document.getElementById('filter-pcp').value;
            const insurance = document.getElementById('filter-insurance').value;
            const status = document.getElementById('filter-status').value;
            
            let filtered = allPatients.filter(p => {
                // Name filters
                if (firstName && !(p.first_name || '').toLowerCase().includes(firstName)) return false;
                if (lastName && !(p.last_name || '').toLowerCase().includes(lastName)) return false;
                
                // MRN filter
                if (mrn && !(p.mrn || '').toLowerCase().includes(mrn)) return false;
                
                // DOB filter
                if (dob && p.date_of_birth !== dob) return false;
                
                // Age filter
                const age = parseInt(p.age) || 0;
                if (age < ageMin || age > ageMax) return false;
                
                // Gender filter
                if (gender && p.gender !== gender) return false;
                
                // Location filter (demo - would need real data)
                if (location && p.location !== location) return false;
                
                // PCP filter
                if (pcp && p.primary_care_provider !== pcp) return false;
                
                // Status filter (demo)
                if (status && p.status !== status) return false;
                
                return true;
            });
            
            updatePatientTable(filtered);
            
            // Show filter summary
            const filterCount = [firstName, lastName, mrn, dob, gender, location, pcp, status]
                .filter(f => f).length + (ageMin > 0 || ageMax < 150 ? 1 : 0);
            if (filterCount > 0) {
                console.log(`Applied ${filterCount} filter(s), ${filtered.length} results`);
            }
        }
        
        // Clear advanced filters
        function clearAdvancedFilters() {
            document.getElementById('filter-firstname').value = '';
            document.getElementById('filter-lastname').value = '';
            document.getElementById('filter-mrn').value = '';
            document.getElementById('filter-dob').value = '';
            document.getElementById('filter-age-min').value = '';
            document.getElementById('filter-age-max').value = '';
            document.getElementById('filter-gender').value = '';
            document.getElementById('filter-location').value = '';
            document.getElementById('filter-pcp').value = '';
            document.getElementById('filter-insurance').value = '';
            document.getElementById('filter-lastvisit').value = '';
            document.getElementById('filter-upcoming').value = '';
            document.getElementById('filter-status').value = '';
            
            updatePatientTable(allPatients);
        }
        
        // Save search preset
        function saveSearchPreset() {
            const presetName = prompt('Enter a name for this search preset:');
            if (!presetName) return;
            
            const preset = {
                name: presetName,
                filters: {
                    firstName: document.getElementById('filter-firstname').value,
                    lastName: document.getElementById('filter-lastname').value,
                    mrn: document.getElementById('filter-mrn').value,
                    dob: document.getElementById('filter-dob').value,
                    ageMin: document.getElementById('filter-age-min').value,
                    ageMax: document.getElementById('filter-age-max').value,
                    gender: document.getElementById('filter-gender').value,
                    location: document.getElementById('filter-location').value,
                    pcp: document.getElementById('filter-pcp').value,
                }
            };
            
            // Save to localStorage
            let presets = JSON.parse(localStorage.getItem('patientSearchPresets') || '[]');
            presets.push(preset);
            localStorage.setItem('patientSearchPresets', JSON.stringify(presets));
            
            alert('Search preset saved: ' + presetName);
        }
        
        // Export patients to CSV
        function exportPatients() {
            const tbody = document.getElementById('patient-list');
            const rows = tbody.querySelectorAll('tr');
            
            const headers = ['Patient Name', 'MRN', 'DOB', 'Age', 'Gender', 'Location', 'Attending'];
            const data = [];
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) { // Skip "no results" row
                    data.push([
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim(),
                        cells[5].textContent.trim(),
                        cells[6].textContent.trim(),
                        cells[7].textContent.trim(),
                    ]);
                }
            });
            
            const csv = [headers.join(','), ...data.map(r => r.map(c => `"${c}"`).join(','))].join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'patients_export_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            
            URL.revokeObjectURL(url);
        }

        function clearSearch() {
            document.getElementById('patient-search').value = '';
            updatePatientTable(allPatients);
        }

        function updatePatientTable(patients) {
            const tbody = document.getElementById('patient-list');
            document.getElementById('patient-count').textContent = patients.length;
            
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
                    <td><strong>${escapeHtml(patient.full_name || '')}</strong></td>
                    <td>${escapeHtml(patient.mrn || '')}</td>
                    <td>${escapeHtml(patient.date_of_birth || '')}</td>
                    <td>${patient.age || ''}</td>
                    <td>${escapeHtml(patient.gender || '')}</td>
                    <td>--</td>
                    <td>${escapeHtml(patient.primary_care_provider || 'N/A')}</td>
                </tr>
            `).join('');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Enter key to search
        document.getElementById('patient-search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchPatients();
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
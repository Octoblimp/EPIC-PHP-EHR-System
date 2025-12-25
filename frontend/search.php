<?php
/**
 * Openspace EHR - Global Search Page
 * AJAX-powered search across patients, orders, results, and more
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Search - ' . APP_NAME;
$query = $_GET['q'] ?? '';

include 'includes/header.php';
?>

<style>
.search-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.search-header {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-header h1 {
    font-size: 24px;
    color: #1a4a5e;
    margin: 0 0 15px;
}

.search-input-wrapper {
    display: flex;
    gap: 10px;
}

.search-input-wrapper input {
    flex: 1;
    padding: 12px 16px;
    font-size: 16px;
    border: 2px solid #d0d8e0;
    border-radius: 6px;
}

.search-input-wrapper input:focus {
    outline: none;
    border-color: #1a4a5e;
}

.search-input-wrapper button {
    padding: 12px 24px;
    background: #1a4a5e;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.search-input-wrapper button:hover {
    background: #0d3545;
}

.search-filters {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.filter-chip {
    padding: 6px 14px;
    background: #e8eef2;
    border: 1px solid #c0c8d0;
    border-radius: 20px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.filter-chip:hover, .filter-chip.active {
    background: #1a4a5e;
    color: white;
    border-color: #1a4a5e;
}

.search-results {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.results-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.results-count {
    font-size: 14px;
    color: #666;
}

.result-category {
    border-bottom: 1px solid #e8e8e8;
}

.category-header {
    padding: 12px 20px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-header .count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
}

.result-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.15s;
}

.result-item:hover {
    background: #f0f8ff;
}

.result-item:last-child {
    border-bottom: none;
}

.result-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 18px;
}

.result-icon.patient { background: #e3f2fd; color: #1976d2; }
.result-icon.order { background: #fff3e0; color: #f57c00; }
.result-icon.result { background: #e8f5e9; color: #388e3c; }
.result-icon.note { background: #fce4ec; color: #c2185b; }
.result-icon.medication { background: #f3e5f5; color: #7b1fa2; }

.result-info {
    flex: 1;
}

.result-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.result-subtitle {
    font-size: 12px;
    color: #666;
}

.result-meta {
    text-align: right;
    font-size: 12px;
    color: #888;
}

.no-results {
    padding: 60px 20px;
    text-align: center;
    color: #888;
}

.no-results i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.search-loading {
    padding: 40px;
    text-align: center;
    display: none;
}

.search-loading i {
    font-size: 32px;
    color: #1a4a5e;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Recent searches */
.recent-searches {
    margin-top: 15px;
}

.recent-searches h4 {
    font-size: 12px;
    color: #888;
    margin-bottom: 8px;
}

.recent-item {
    display: inline-block;
    padding: 4px 10px;
    background: #f5f5f5;
    border-radius: 4px;
    font-size: 12px;
    color: #666;
    margin-right: 8px;
    margin-bottom: 8px;
    cursor: pointer;
}

.recent-item:hover {
    background: #e8e8e8;
}
</style>

<div class="dashboard-content">
    <div class="search-page">
        <div class="search-header">
            <h1><i class="fas fa-search"></i> Search Openspace</h1>
            <div class="search-input-wrapper">
                <input type="text" id="searchInput" placeholder="Search patients, orders, results, notes..." 
                       value="<?php echo htmlspecialchars($query); ?>" autofocus>
                <button onclick="performSearch()"><i class="fas fa-search"></i> Search</button>
            </div>
            
            <div class="search-filters">
                <span class="filter-chip active" data-filter="all">All</span>
                <span class="filter-chip" data-filter="patients">Patients</span>
                <span class="filter-chip" data-filter="orders">Orders</span>
                <span class="filter-chip" data-filter="results">Lab Results</span>
                <span class="filter-chip" data-filter="notes">Notes</span>
                <span class="filter-chip" data-filter="medications">Medications</span>
            </div>
            
            <div class="recent-searches" id="recentSearches">
                <h4>Recent Searches</h4>
                <span class="recent-item" onclick="searchFor('Smith')">Smith</span>
                <span class="recent-item" onclick="searchFor('MRN000001')">MRN000001</span>
                <span class="recent-item" onclick="searchFor('CBC')">CBC</span>
            </div>
        </div>
        
        <div class="search-loading" id="searchLoading">
            <i class="fas fa-circle-notch"></i>
            <p>Searching...</p>
        </div>
        
        <div class="search-results" id="searchResults">
            <?php if (!$query): ?>
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Enter a search term</h3>
                <p>Search across patients, orders, lab results, notes, and more</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let currentFilter = 'all';
let searchTimeout = null;

// Demo data for search results
const demoData = {
    patients: [
        { id: 1, name: 'Smith, John', mrn: 'MRN000001', dob: '03/15/1955', room: '412-A' },
        { id: 2, name: 'Johnson, Mary', mrn: 'MRN000002', dob: '07/22/1948', room: '415-B' },
        { id: 3, name: 'Williams, Robert', mrn: 'MRN000003', dob: '11/08/1960', room: '420-A' },
        { id: 4, name: 'Davis, Linda', mrn: 'MRN000004', dob: '02/14/1972', room: '418-A' },
        { id: 5, name: 'Testmonday, Melissa', mrn: 'E1404907', dob: '02/12/1974', room: 'N/A' },
    ],
    orders: [
        { id: 101, type: 'Lab', name: 'CBC with Diff', patient: 'Smith, John', status: 'Pending', date: 'Today 08:00' },
        { id: 102, type: 'Lab', name: 'Basic Metabolic Panel', patient: 'Smith, John', status: 'Collected', date: 'Today 06:00' },
        { id: 103, type: 'Imaging', name: 'Chest X-Ray', patient: 'Johnson, Mary', status: 'Completed', date: 'Yesterday' },
        { id: 104, type: 'Medication', name: 'Vancomycin 1g IV', patient: 'Williams, Robert', status: 'Active', date: 'Today 04:00' },
    ],
    results: [
        { id: 201, test: 'Hemoglobin A1C', value: '7.2%', patient: 'Smith, John', date: 'Today 05:30', status: 'Final' },
        { id: 202, test: 'Glucose', value: '186 mg/dL', patient: 'Smith, John', date: 'Today 05:30', status: 'Final', flag: 'H' },
        { id: 203, test: 'WBC', value: '12.5 K/uL', patient: 'Johnson, Mary', date: 'Yesterday', status: 'Final', flag: 'H' },
    ],
    notes: [
        { id: 301, type: 'Progress Note', author: 'Dr. Wilson', patient: 'Smith, John', date: 'Today 07:00' },
        { id: 302, type: 'H&P', author: 'Dr. Smith', patient: 'Johnson, Mary', date: 'Yesterday' },
        { id: 303, type: 'Nursing Note', author: 'RN Jones', patient: 'Williams, Robert', date: 'Today 06:00' },
    ],
    medications: [
        { id: 401, name: 'Metformin 500mg', route: 'PO', frequency: 'BID', patient: 'Smith, John' },
        { id: 402, name: 'Lisinopril 10mg', route: 'PO', frequency: 'Daily', patient: 'Smith, John' },
        { id: 403, name: 'Vancomycin 1g', route: 'IV', frequency: 'Q12H', patient: 'Williams, Robert' },
    ]
};

// Filter chips
document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        performSearch();
    });
});

// Search input with debounce
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
});

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimeout);
        performSearch();
    }
});

function searchFor(term) {
    document.getElementById('searchInput').value = term;
    performSearch();
}

function performSearch() {
    const query = document.getElementById('searchInput').value.trim().toLowerCase();
    
    if (query.length < 1) {
        document.getElementById('searchResults').innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Enter a search term</h3>
                <p>Search across patients, orders, lab results, notes, and more</p>
            </div>
        `;
        return;
    }
    
    // Show loading
    document.getElementById('searchLoading').style.display = 'block';
    document.getElementById('searchResults').style.display = 'none';
    
    // Simulate AJAX delay
    setTimeout(() => {
        const results = searchData(query, currentFilter);
        displayResults(results, query);
        
        document.getElementById('searchLoading').style.display = 'none';
        document.getElementById('searchResults').style.display = 'block';
        
        // Save to recent searches
        saveRecentSearch(query);
    }, 300);
}

function searchData(query, filter) {
    const results = {
        patients: [],
        orders: [],
        results: [],
        notes: [],
        medications: []
    };
    
    // Search patients
    if (filter === 'all' || filter === 'patients') {
        results.patients = demoData.patients.filter(p => 
            p.name.toLowerCase().includes(query) ||
            p.mrn.toLowerCase().includes(query)
        );
    }
    
    // Search orders
    if (filter === 'all' || filter === 'orders') {
        results.orders = demoData.orders.filter(o =>
            o.name.toLowerCase().includes(query) ||
            o.patient.toLowerCase().includes(query) ||
            o.type.toLowerCase().includes(query)
        );
    }
    
    // Search results
    if (filter === 'all' || filter === 'results') {
        results.results = demoData.results.filter(r =>
            r.test.toLowerCase().includes(query) ||
            r.patient.toLowerCase().includes(query)
        );
    }
    
    // Search notes
    if (filter === 'all' || filter === 'notes') {
        results.notes = demoData.notes.filter(n =>
            n.type.toLowerCase().includes(query) ||
            n.patient.toLowerCase().includes(query) ||
            n.author.toLowerCase().includes(query)
        );
    }
    
    // Search medications
    if (filter === 'all' || filter === 'medications') {
        results.medications = demoData.medications.filter(m =>
            m.name.toLowerCase().includes(query) ||
            m.patient.toLowerCase().includes(query)
        );
    }
    
    return results;
}

function displayResults(results, query) {
    const totalCount = Object.values(results).reduce((sum, arr) => sum + arr.length, 0);
    
    if (totalCount === 0) {
        document.getElementById('searchResults').innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>No results found</h3>
                <p>Try different keywords or check your spelling</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="results-header">
            <span class="results-count">Found ${totalCount} result(s) for "${query}"</span>
        </div>
    `;
    
    // Patients
    if (results.patients.length > 0) {
        html += `
            <div class="result-category">
                <div class="category-header">
                    <i class="fas fa-user"></i> Patients
                    <span class="count">${results.patients.length}</span>
                </div>
                ${results.patients.map(p => `
                    <div class="result-item" onclick="window.location.href='patient-chart.php?id=${p.id}'">
                        <div class="result-icon patient"><i class="fas fa-user"></i></div>
                        <div class="result-info">
                            <div class="result-title">${highlightMatch(p.name, query)}</div>
                            <div class="result-subtitle">MRN: ${highlightMatch(p.mrn, query)} | DOB: ${p.dob}</div>
                        </div>
                        <div class="result-meta">Room: ${p.room}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Orders
    if (results.orders.length > 0) {
        html += `
            <div class="result-category">
                <div class="category-header">
                    <i class="fas fa-clipboard-list"></i> Orders
                    <span class="count">${results.orders.length}</span>
                </div>
                ${results.orders.map(o => `
                    <div class="result-item">
                        <div class="result-icon order"><i class="fas fa-file-medical"></i></div>
                        <div class="result-info">
                            <div class="result-title">${highlightMatch(o.name, query)}</div>
                            <div class="result-subtitle">${o.type} | ${highlightMatch(o.patient, query)}</div>
                        </div>
                        <div class="result-meta">${o.status}<br>${o.date}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Lab Results
    if (results.results.length > 0) {
        html += `
            <div class="result-category">
                <div class="category-header">
                    <i class="fas fa-flask"></i> Lab Results
                    <span class="count">${results.results.length}</span>
                </div>
                ${results.results.map(r => `
                    <div class="result-item">
                        <div class="result-icon result"><i class="fas fa-vial"></i></div>
                        <div class="result-info">
                            <div class="result-title">${highlightMatch(r.test, query)}</div>
                            <div class="result-subtitle">${highlightMatch(r.patient, query)} | ${r.value} ${r.flag ? '<span style="color:red">(' + r.flag + ')</span>' : ''}</div>
                        </div>
                        <div class="result-meta">${r.status}<br>${r.date}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Notes
    if (results.notes.length > 0) {
        html += `
            <div class="result-category">
                <div class="category-header">
                    <i class="fas fa-sticky-note"></i> Notes
                    <span class="count">${results.notes.length}</span>
                </div>
                ${results.notes.map(n => `
                    <div class="result-item">
                        <div class="result-icon note"><i class="fas fa-file-alt"></i></div>
                        <div class="result-info">
                            <div class="result-title">${highlightMatch(n.type, query)}</div>
                            <div class="result-subtitle">${highlightMatch(n.author, query)} | ${highlightMatch(n.patient, query)}</div>
                        </div>
                        <div class="result-meta">${n.date}</div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Medications
    if (results.medications.length > 0) {
        html += `
            <div class="result-category">
                <div class="category-header">
                    <i class="fas fa-pills"></i> Medications
                    <span class="count">${results.medications.length}</span>
                </div>
                ${results.medications.map(m => `
                    <div class="result-item">
                        <div class="result-icon medication"><i class="fas fa-capsules"></i></div>
                        <div class="result-info">
                            <div class="result-title">${highlightMatch(m.name, query)}</div>
                            <div class="result-subtitle">${m.route} ${m.frequency} | ${highlightMatch(m.patient, query)}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    document.getElementById('searchResults').innerHTML = html;
}

function highlightMatch(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

function saveRecentSearch(query) {
    let recent = JSON.parse(localStorage.getItem('recentSearches') || '[]');
    recent = recent.filter(q => q !== query);
    recent.unshift(query);
    recent = recent.slice(0, 5);
    localStorage.setItem('recentSearches', JSON.stringify(recent));
}

// Initial search if query provided
<?php if ($query): ?>
performSearch();
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>

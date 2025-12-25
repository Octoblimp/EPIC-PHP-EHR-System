<?php
/**
 * Quick Access / Command Palette Component
 * Include this file to add Ctrl+K command palette functionality
 */
?>

<!-- Quick Access Modal (Command Palette) -->
<div class="quick-access-overlay" id="quickAccessOverlay">
    <div class="quick-access-modal">
        <div class="quick-access-search">
            <i class="fas fa-search"></i>
            <input type="text" id="quickAccessInput" placeholder="Search for pages, patients, actions..." autocomplete="off">
            <kbd>ESC</kbd>
        </div>
        <div class="quick-access-results" id="quickAccessResults">
            <!-- Results populated by JavaScript -->
        </div>
        <div class="quick-access-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
            <span><kbd>Enter</kbd> Select</span>
            <span><kbd>ESC</kbd> Close</span>
        </div>
    </div>
</div>

<style>
.quick-access-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: flex-start;
    padding-top: 100px;
    z-index: 10000;
    backdrop-filter: blur(4px);
}

.quick-access-overlay.show {
    display: flex;
}

.quick-access-modal {
    background: white;
    border-radius: 12px;
    width: 600px;
    max-width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideDown 0.15s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.quick-access-search {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    gap: 12px;
}

.quick-access-search i {
    color: #888;
    font-size: 18px;
}

.quick-access-search input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 16px;
    padding: 5px 0;
}

.quick-access-search kbd {
    background: #f0f0f0;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    color: #666;
    border: 1px solid #ddd;
}

.quick-access-results {
    max-height: 400px;
    overflow-y: auto;
}

.quick-access-section {
    padding: 8px 15px;
    font-size: 11px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    background: #f8f9fa;
    border-bottom: 1px solid #e8e8e8;
}

.quick-access-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    gap: 15px;
}

.quick-access-item:hover,
.quick-access-item.selected {
    background: #e8f4ff;
}

.quick-access-item .qa-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
}

.quick-access-item .qa-icon.page { background: #1a4a5e; }
.quick-access-item .qa-icon.patient { background: #28a745; }
.quick-access-item .qa-icon.action { background: #fd7e14; }
.quick-access-item .qa-icon.search { background: #6f42c1; }
.quick-access-item .qa-icon.admin { background: #dc3545; }

.quick-access-item .qa-content {
    flex: 1;
}

.quick-access-item .qa-title {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.quick-access-item .qa-subtitle {
    font-size: 12px;
    color: #888;
    margin-top: 2px;
}

.quick-access-item .qa-shortcut {
    display: flex;
    gap: 4px;
}

.quick-access-item .qa-shortcut kbd {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #666;
    border: 1px solid #ddd;
}

.quick-access-footer {
    background: #f8f9fa;
    padding: 10px 20px;
    display: flex;
    gap: 20px;
    font-size: 12px;
    color: #888;
    border-top: 1px solid #e0e0e0;
}

.quick-access-footer kbd {
    background: #e8e8e8;
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 10px;
    margin-right: 4px;
}

.quick-access-empty {
    padding: 40px 20px;
    text-align: center;
    color: #888;
}

.quick-access-empty i {
    font-size: 32px;
    margin-bottom: 10px;
    display: block;
}
</style>

<script>
// Quick Access Data
const quickAccessItems = [
    // Pages
    { type: 'page', title: 'Home', subtitle: 'Dashboard overview', icon: 'fas fa-home', url: 'home.php', shortcut: ['Alt', 'H'] },
    { type: 'page', title: 'Patients', subtitle: 'Patient list and search', icon: 'fas fa-users', url: 'patients.php', shortcut: ['Alt', 'P'] },
    { type: 'page', title: 'Inbox', subtitle: 'Messages and notifications', icon: 'fas fa-inbox', url: 'inbox.php', shortcut: ['Alt', 'I'] },
    { type: 'page', title: 'Schedule', subtitle: 'Appointments calendar', icon: 'fas fa-calendar-alt', url: 'schedule.php', shortcut: ['Alt', 'S'] },
    { type: 'page', title: 'Search', subtitle: 'Global search', icon: 'fas fa-search', url: 'search.php', shortcut: ['Alt', 'F'] },
    { type: 'page', title: 'Patient Lists', subtitle: 'Manage patient lists', icon: 'fas fa-list', url: 'patient-lists.php' },
    { type: 'page', title: 'Notes', subtitle: 'Clinical notes', icon: 'fas fa-file-medical', url: 'notes.php' },
    { type: 'page', title: 'Billing', subtitle: 'Charge capture and claims', icon: 'fas fa-file-invoice-dollar', url: 'billing.php' },
    { type: 'page', title: 'Quality Reporting', subtitle: 'MIPS/QPP compliance', icon: 'fas fa-chart-line', url: 'quality-reporting.php' },
    { type: 'page', title: 'Insurance', subtitle: 'Insurance verification', icon: 'fas fa-id-card', url: 'insurance.php' },
    { type: 'page', title: 'Settings', subtitle: 'User preferences', icon: 'fas fa-cog', url: 'settings.php' },
    { type: 'page', title: 'Profile', subtitle: 'Your profile', icon: 'fas fa-user', url: 'profile.php' },
    
    // Admin Pages
    { type: 'admin', title: 'Admin Dashboard', subtitle: 'System administration', icon: 'fas fa-tachometer-alt', url: 'admin/index.php' },
    { type: 'admin', title: 'User Management', subtitle: 'Manage users', icon: 'fas fa-users-cog', url: 'admin/users.php' },
    { type: 'admin', title: 'Audit Log', subtitle: 'View system audit trail', icon: 'fas fa-clipboard-list', url: 'admin/audit.php' },
    { type: 'admin', title: 'Roles & Permissions', subtitle: 'Manage roles', icon: 'fas fa-user-shield', url: 'admin/roles.php' },
    
    // Actions
    { type: 'action', title: 'New Patient', subtitle: 'Register a new patient', icon: 'fas fa-user-plus', action: 'newPatient' },
    { type: 'action', title: 'New Encounter', subtitle: 'Start a new visit', icon: 'fas fa-stethoscope', action: 'newEncounter' },
    { type: 'action', title: 'New Order', subtitle: 'Create an order', icon: 'fas fa-prescription', action: 'newOrder' },
    { type: 'action', title: 'New Note', subtitle: 'Write a clinical note', icon: 'fas fa-edit', action: 'newNote' },
    { type: 'action', title: 'Logout', subtitle: 'Sign out of the system', icon: 'fas fa-sign-out-alt', action: 'logout' },
    
    // Recent Patients (Demo)
    { type: 'patient', title: 'Smith, John', subtitle: 'MRN: 000001 • DOB: 03/15/1958', icon: 'fas fa-user', url: 'patient-chart.php?mrn=000001' },
    { type: 'patient', title: 'Johnson, Mary', subtitle: 'MRN: 000002 • DOB: 07/22/1975', icon: 'fas fa-user', url: 'patient-chart.php?mrn=000002' },
    { type: 'patient', title: 'Williams, Robert', subtitle: 'MRN: 000003 • DOB: 11/08/1962', icon: 'fas fa-user', url: 'patient-chart.php?mrn=000003' },
];

let selectedIndex = 0;
let filteredItems = [...quickAccessItems];

// Open Quick Access
function openQuickAccess() {
    document.getElementById('quickAccessOverlay').classList.add('show');
    document.getElementById('quickAccessInput').value = '';
    document.getElementById('quickAccessInput').focus();
    filteredItems = [...quickAccessItems];
    selectedIndex = 0;
    renderQuickAccessResults();
}

// Close Quick Access
function closeQuickAccess() {
    document.getElementById('quickAccessOverlay').classList.remove('show');
}

// Render Results
function renderQuickAccessResults() {
    const resultsContainer = document.getElementById('quickAccessResults');
    
    if (filteredItems.length === 0) {
        resultsContainer.innerHTML = `
            <div class="quick-access-empty">
                <i class="fas fa-search"></i>
                No results found
            </div>
        `;
        return;
    }
    
    // Group by type
    const grouped = {};
    filteredItems.forEach(item => {
        const type = item.type;
        if (!grouped[type]) grouped[type] = [];
        grouped[type].push(item);
    });
    
    const typeLabels = {
        page: 'Pages',
        patient: 'Recent Patients',
        action: 'Actions',
        admin: 'Administration',
        search: 'Search'
    };
    
    let html = '';
    let itemIndex = 0;
    
    for (const [type, items] of Object.entries(grouped)) {
        html += `<div class="quick-access-section">${typeLabels[type] || type}</div>`;
        items.forEach(item => {
            const isSelected = itemIndex === selectedIndex;
            html += `
                <div class="quick-access-item ${isSelected ? 'selected' : ''}" 
                     data-index="${itemIndex}"
                     onclick="selectQuickAccessItem(${itemIndex})">
                    <div class="qa-icon ${item.type}"><i class="${item.icon}"></i></div>
                    <div class="qa-content">
                        <div class="qa-title">${escapeHtml(item.title)}</div>
                        <div class="qa-subtitle">${escapeHtml(item.subtitle)}</div>
                    </div>
                    ${item.shortcut ? `
                        <div class="qa-shortcut">
                            ${item.shortcut.map(k => `<kbd>${k}</kbd>`).join('')}
                        </div>
                    ` : ''}
                </div>
            `;
            itemIndex++;
        });
    }
    
    resultsContainer.innerHTML = html;
}

// Filter Results
function filterQuickAccess(query) {
    query = query.toLowerCase().trim();
    
    if (!query) {
        filteredItems = [...quickAccessItems];
    } else {
        filteredItems = quickAccessItems.filter(item => {
            return item.title.toLowerCase().includes(query) ||
                   item.subtitle.toLowerCase().includes(query) ||
                   item.type.toLowerCase().includes(query);
        });
    }
    
    selectedIndex = 0;
    renderQuickAccessResults();
}

// Select Item
function selectQuickAccessItem(index) {
    const item = filteredItems[index];
    if (!item) return;
    
    closeQuickAccess();
    
    if (item.url) {
        window.location.href = item.url;
    } else if (item.action) {
        executeQuickAction(item.action);
    }
}

// Execute Action
function executeQuickAction(action) {
    switch (action) {
        case 'newPatient':
            alert('Opening new patient registration... (Demo)');
            break;
        case 'newEncounter':
            alert('Starting new encounter... (Demo)');
            break;
        case 'newOrder':
            alert('Opening order entry... (Demo)');
            break;
        case 'newNote':
            alert('Opening note editor... (Demo)');
            break;
        case 'logout':
            window.location.href = 'login.php?logout=1';
            break;
        default:
            console.log('Unknown action:', action);
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Keyboard Navigation
document.addEventListener('keydown', function(e) {
    const overlay = document.getElementById('quickAccessOverlay');
    const isOpen = overlay.classList.contains('show');
    
    // Open with Ctrl+K or Cmd+K
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        if (isOpen) {
            closeQuickAccess();
        } else {
            openQuickAccess();
        }
        return;
    }
    
    // Handle shortcuts when not in quick access
    if (!isOpen && e.altKey) {
        const shortcuts = {
            'h': 'home.php',
            'p': 'patients.php',
            'i': 'inbox.php',
            's': 'schedule.php',
            'f': 'search.php',
        };
        if (shortcuts[e.key.toLowerCase()]) {
            e.preventDefault();
            window.location.href = shortcuts[e.key.toLowerCase()];
        }
    }
    
    // Quick access keyboard navigation
    if (!isOpen) return;
    
    switch (e.key) {
        case 'Escape':
            closeQuickAccess();
            break;
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, filteredItems.length - 1);
            renderQuickAccessResults();
            scrollToSelected();
            break;
        case 'ArrowUp':
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            renderQuickAccessResults();
            scrollToSelected();
            break;
        case 'Enter':
            e.preventDefault();
            selectQuickAccessItem(selectedIndex);
            break;
    }
});

// Scroll to selected item
function scrollToSelected() {
    const selected = document.querySelector('.quick-access-item.selected');
    if (selected) {
        selected.scrollIntoView({ block: 'nearest' });
    }
}

// Click outside to close
document.getElementById('quickAccessOverlay')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickAccess();
    }
});

// Input handler
document.getElementById('quickAccessInput')?.addEventListener('input', function(e) {
    filterQuickAccess(e.target.value);
});
</script>

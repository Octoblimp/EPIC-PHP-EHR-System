/**
 * Epic EHR Frontend JavaScript
 * Handles UI interactions and API calls
 */

// API Client
class EpicApi {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    async request(method, endpoint, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        let url = `${this.baseUrl}/${endpoint}`;
        if (data && method === 'GET') {
            const params = new URLSearchParams(data);
            url += `?${params}`;
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    }

    get(endpoint, params = {}) {
        return this.request('GET', endpoint, params);
    }

    post(endpoint, data) {
        return this.request('POST', endpoint, data);
    }

    put(endpoint, data) {
        return this.request('PUT', endpoint, data);
    }

    delete(endpoint) {
        return this.request('DELETE', endpoint);
    }
}

// Initialize API
const api = new EpicApi();

// Modal Management
class ModalManager {
    static show(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('active');
        }
    }

    static hide(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('active');
        }
    }

    static create(options) {
        const { title, content, buttons = [], onClose } = options;
        
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'dynamic-modal';

        const dialog = document.createElement('div');
        dialog.className = 'modal-dialog';

        const header = document.createElement('div');
        header.className = 'modal-header';
        header.innerHTML = `
            <span>${title}</span>
            <button class="modal-close" onclick="ModalManager.hide('dynamic-modal')">&times;</button>
        `;

        const body = document.createElement('div');
        body.className = 'modal-body';
        if (typeof content === 'string') {
            body.innerHTML = content;
        } else {
            body.appendChild(content);
        }

        const footer = document.createElement('div');
        footer.className = 'modal-footer';
        
        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.className = `btn ${btn.class || 'btn-secondary'}`;
            button.textContent = btn.text;
            button.onclick = () => {
                if (btn.onClick) btn.onClick();
                if (btn.closeOnClick !== false) {
                    ModalManager.hide('dynamic-modal');
                    overlay.remove();
                }
            };
            footer.appendChild(button);
        });

        dialog.appendChild(header);
        dialog.appendChild(body);
        dialog.appendChild(footer);
        overlay.appendChild(dialog);

        document.body.appendChild(overlay);
        
        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                ModalManager.hide('dynamic-modal');
                overlay.remove();
                if (onClose) onClose();
            }
        });

        return overlay;
    }
}

// Selection Form (like Epic's multi-select)
class SelectionForm {
    constructor(options) {
        this.options = options;
        this.selected = new Set(options.selected || []);
        this.multiple = options.multiple !== false;
    }

    render() {
        const container = document.createElement('div');
        container.className = 'selection-form';

        if (this.options.label) {
            const label = document.createElement('div');
            label.className = 'text-bold mb-1';
            label.textContent = this.options.label;
            container.appendChild(label);
        }

        const list = document.createElement('div');
        list.className = 'selection-list';

        this.options.items.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'selection-item';
            if (this.selected.has(item.value)) {
                itemEl.classList.add('selected');
            }
            itemEl.textContent = item.label;
            itemEl.dataset.value = item.value;

            itemEl.addEventListener('click', () => {
                if (this.multiple) {
                    if (this.selected.has(item.value)) {
                        this.selected.delete(item.value);
                        itemEl.classList.remove('selected');
                    } else {
                        this.selected.add(item.value);
                        itemEl.classList.add('selected');
                    }
                } else {
                    list.querySelectorAll('.selection-item').forEach(el => el.classList.remove('selected'));
                    this.selected.clear();
                    this.selected.add(item.value);
                    itemEl.classList.add('selected');
                }
            });

            list.appendChild(itemEl);
        });

        container.appendChild(list);

        if (this.multiple) {
            const hint = document.createElement('div');
            hint.className = 'text-muted text-small mt-1';
            hint.textContent = 'Select Multiple Options (F5)';
            container.appendChild(hint);
        }

        return container;
    }

    getSelected() {
        return Array.from(this.selected);
    }
}

// Flowsheet Management
class FlowsheetManager {
    constructor(containerId, patientId) {
        this.container = document.getElementById(containerId);
        this.patientId = patientId;
        this.currentGroup = null;
        this.data = {};
    }

    async loadGroup(groupName) {
        this.currentGroup = groupName;
        const response = await api.get(`flowsheets/patient/${this.patientId}/grouped`, { group: groupName });
        
        if (response.success) {
            this.data = response.data;
            this.render();
        }
    }

    render() {
        if (!this.container) return;

        let html = '<div class="flowsheet-grid">';
        
        // Row headers
        html += '<div class="flowsheet-rows">';
        for (const section in this.data) {
            html += `<div class="flowsheet-row-header section">${section}</div>`;
            for (const row in this.data[section]) {
                html += `<div class="flowsheet-row-header row">${row}</div>`;
            }
        }
        html += '</div>';

        // Data columns
        html += '<div class="flowsheet-data">';
        html += '<div class="flowsheet-time-header">';
        // Get unique timestamps
        const timestamps = new Set();
        for (const section in this.data) {
            for (const row in this.data[section]) {
                this.data[section][row].forEach(entry => {
                    timestamps.add(entry.entry_datetime);
                });
            }
        }
        Array.from(timestamps).sort().forEach(ts => {
            html += `<div class="flowsheet-time-cell">
                <div class="date">${ts.split(' ')[0]}</div>
                <div class="time">${ts.split(' ')[1] || ''}</div>
            </div>`;
        });
        html += '</div>';

        // Values
        html += '<div class="flowsheet-values">';
        for (const section in this.data) {
            for (const row in this.data[section]) {
                html += '<div class="flowsheet-value-row">';
                Array.from(timestamps).sort().forEach(ts => {
                    const entry = this.data[section][row].find(e => e.entry_datetime === ts);
                    html += `<div class="flowsheet-value-cell ${entry ? '' : 'editable'}" 
                                 data-section="${section}" 
                                 data-row="${row}" 
                                 data-timestamp="${ts}">
                        ${entry ? entry.value : ''}
                    </div>`;
                });
                html += '</div>';
            }
        }
        html += '</div></div></div>';

        this.container.innerHTML = html;

        // Add click handlers for editable cells
        this.container.querySelectorAll('.flowsheet-value-cell.editable').forEach(cell => {
            cell.addEventListener('click', () => this.editCell(cell));
        });
    }

    editCell(cell) {
        const currentValue = cell.textContent.trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentValue;
        input.className = 'form-control-sm';

        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();

        input.addEventListener('blur', async () => {
            const newValue = input.value.trim();
            if (newValue !== currentValue) {
                await this.saveEntry({
                    section: cell.dataset.section,
                    row_name: cell.dataset.row,
                    value: newValue,
                    entry_datetime: cell.dataset.timestamp
                });
            }
            cell.textContent = newValue || currentValue;
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                input.blur();
            }
        });
    }

    async saveEntry(data) {
        data.patient_id = this.patientId;
        data.flowsheet_group = this.currentGroup;
        data.documented_by = currentUser?.name || 'Unknown';

        const response = await api.post('flowsheets/entry', data);
        if (!response.success) {
            alert('Failed to save entry: ' + (response.error || 'Unknown error'));
        }
    }
}

// Medications Panel
class MedicationsPanel {
    constructor(containerId, patientId) {
        this.container = document.getElementById(containerId);
        this.patientId = patientId;
    }

    async load() {
        const response = await api.get(`medications/patient/${this.patientId}/categorized`);
        
        if (response.success) {
            this.render(response.data);
        }
    }

    render(data) {
        if (!this.container) return;

        let html = '';

        // Scheduled medications
        if (data.scheduled && data.scheduled.length > 0) {
            html += this.renderSection('Scheduled Medications', data.scheduled, 'scheduled');
        }

        // PRN medications
        if (data.prn && data.prn.length > 0) {
            html += this.renderSection('PRN Medications', data.prn, 'prn');
        }

        // Continuous infusions
        if (data.continuous && data.continuous.length > 0) {
            html += this.renderSection('Continuous Infusions', data.continuous, 'continuous');
        }

        // Home medications
        if (data.home_meds && data.home_meds.length > 0) {
            html += this.renderSection('Home Medications', data.home_meds, 'home');
        }

        this.container.innerHTML = html || '<div class="p-2 text-muted">No active medications</div>';
    }

    renderSection(title, meds, type) {
        let html = `
            <div class="panel-section">
                <div class="panel-section-header" onclick="this.nextElementSibling.classList.toggle('collapsed')">
                    <span>${title}</span>
                    <span class="badge badge-info">${meds.length}</span>
                </div>
                <div class="panel-section-content">
                    <ul class="med-list">
        `;

        meds.forEach(med => {
            const alertClass = med.is_high_alert ? 'high-alert' : '';
            html += `
                <li class="med-item ${alertClass}" onclick="showMedDetails(${med.id})">
                    <div class="med-name">${med.name}</div>
                    <div class="med-dose">${med.full_dose}</div>
                </li>
            `;
        });

        html += '</ul></div></div>';
        return html;
    }
}

// Orders Panel
class OrdersPanel {
    constructor(containerId, patientId) {
        this.container = document.getElementById(containerId);
        this.patientId = patientId;
    }

    async load() {
        const [pending, toComplete] = await Promise.all([
            api.get(`orders/patient/${this.patientId}/pending`),
            api.get(`orders/patient/${this.patientId}/to-complete`)
        ]);

        this.render({
            pending: pending.success ? pending.data : [],
            toComplete: toComplete.success ? toComplete.data : []
        });
    }

    render(data) {
        if (!this.container) return;

        let html = '';

        // Orders to acknowledge
        if (data.pending && data.pending.length > 0) {
            html += `
                <div class="panel-section">
                    <div class="panel-section-header">
                        <span>Acknowledge Orders</span>
                        <span class="badge badge-warning">${data.pending.length}</span>
                    </div>
                    <div class="panel-section-content">
                        <ul class="order-list">
            `;
            data.pending.forEach(order => {
                html += `
                    <li class="order-item pending">
                        <div class="d-flex justify-between align-center">
                            <div>
                                <div class="order-name">${order.order_name}</div>
                                <div class="order-details">${order.ordering_provider} - ${order.order_date}</div>
                            </div>
                            <button class="btn btn-primary btn-sm" onclick="acknowledgeOrder(${order.id})">Ack</button>
                        </div>
                    </li>
                `;
            });
            html += '</ul></div></div>';
        }

        // Orders to complete
        if (data.toComplete && data.toComplete.length > 0) {
            html += `
                <div class="panel-section">
                    <div class="panel-section-header">
                        <span>Orders to be Completed</span>
                        <span class="badge badge-info">${data.toComplete.length}</span>
                    </div>
                    <div class="panel-section-content">
                        <ul class="order-list">
            `;
            data.toComplete.forEach(order => {
                const priorityClass = order.priority.toLowerCase();
                html += `
                    <li class="order-item">
                        <div class="order-name">
                            ${order.order_name}
                            <span class="order-status ${priorityClass}">${order.priority}</span>
                        </div>
                        <div class="order-details">${order.order_type || ''} - ${order.status}</div>
                    </li>
                `;
            });
            html += '</ul></div></div>';
        }

        this.container.innerHTML = html || '<div class="p-2 text-muted">No pending orders</div>';
    }
}

// Vitals Widget
class VitalsWidget {
    constructor(containerId, patientId) {
        this.container = document.getElementById(containerId);
        this.patientId = patientId;
    }

    async load() {
        const response = await api.get(`vitals/patient/${this.patientId}/latest`);
        
        if (response.success) {
            this.render(response.data);
        }
    }

    render(data) {
        if (!this.container || !data) return;

        this.container.innerHTML = `
            <div class="vitals-widget">
                <div class="vital-item">
                    <div class="vital-label">Temp</div>
                    <div class="vital-value">${data.temperature || '--'}</div>
                    <div class="vital-unit">°F</div>
                </div>
                <div class="vital-item">
                    <div class="vital-label">HR</div>
                    <div class="vital-value">${data.heart_rate || '--'}</div>
                    <div class="vital-unit">bpm</div>
                </div>
                <div class="vital-item">
                    <div class="vital-label">BP</div>
                    <div class="vital-value">${data.blood_pressure || '--'}</div>
                    <div class="vital-unit">mmHg</div>
                </div>
                <div class="vital-item">
                    <div class="vital-label">RR</div>
                    <div class="vital-value">${data.respiratory_rate || '--'}</div>
                    <div class="vital-unit">/min</div>
                </div>
                <div class="vital-item">
                    <div class="vital-label">SpO2</div>
                    <div class="vital-value">${data.spo2 || '--'}</div>
                    <div class="vital-unit">%</div>
                </div>
                <div class="vital-item">
                    <div class="vital-label">Pain</div>
                    <div class="vital-value ${(data.pain_score || 0) >= 7 ? 'abnormal' : ''}">${data.pain_score ?? '--'}</div>
                    <div class="vital-unit">/10</div>
                </div>
            </div>
            <div class="text-center text-muted text-small mt-1">
                ${data.recorded_date || 'No recent vitals'}
            </div>
        `;
    }
}

// Navigation
function navigateTo(activity) {
    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.activity === activity) {
            item.classList.add('active');
        }
    });

    // Load content based on activity
    loadActivity(activity);
}

async function loadActivity(activity) {
    const contentBody = document.querySelector('.content-body');
    const contentTitle = document.querySelector('.content-title');
    
    if (!contentBody) return;

    switch (activity) {
        case 'summary':
            contentTitle.textContent = 'Summary';
            contentBody.innerHTML = '<div class="p-2">Loading patient summary...</div>';
            // Load summary content
            break;
        case 'flowsheets':
            contentTitle.textContent = 'Flowsheets';
            await loadFlowsheetsView(contentBody);
            break;
        case 'orders':
            contentTitle.textContent = 'Orders';
            await loadOrdersView(contentBody);
            break;
        case 'notes':
            contentTitle.textContent = 'Notes';
            await loadNotesView(contentBody);
            break;
        case 'results':
            contentTitle.textContent = 'Results Review';
            await loadResultsView(contentBody);
            break;
        default:
            contentBody.innerHTML = `<div class="p-2">Activity: ${activity}</div>`;
    }
}

// Global functions
function showMedDetails(medId) {
    console.log('Show med details:', medId);
    // Implementation for medication details modal
}

async function acknowledgeOrder(orderId) {
    const response = await api.post(`orders/${orderId}/acknowledge`, {
        acknowledged_by: currentUser?.name || 'Unknown'
    });
    
    if (response.success) {
        // Refresh orders panel
        if (window.ordersPanel) {
            window.ordersPanel.load();
        }
    } else {
        alert('Failed to acknowledge order');
    }
}

// Patient search
async function searchPatients(query) {
    if (query.length < 2) return [];
    
    const response = await api.get('patients/search', { q: query });
    return response.success ? response.data : [];
}

// Current user (set from PHP)
let currentUser = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Initialize components based on current page
    const patientId = document.body.dataset.patientId;
    
    if (patientId) {
        // Initialize panels
        if (document.getElementById('medications-panel')) {
            window.medsPanel = new MedicationsPanel('medications-panel', patientId);
            window.medsPanel.load();
        }
        
        if (document.getElementById('orders-panel')) {
            window.ordersPanel = new OrdersPanel('orders-panel', patientId);
            window.ordersPanel.load();
        }
        
        if (document.getElementById('vitals-widget')) {
            window.vitalsWidget = new VitalsWidget('vitals-widget', patientId);
            window.vitalsWidget.load();
        }
    }

    // Navigation click handlers
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            const activity = item.dataset.activity;
            if (activity) {
                navigateTo(activity);
            }
        });
    });
});

// Export for use in PHP templates
window.EpicApi = EpicApi;
window.ModalManager = ModalManager;
window.SelectionForm = SelectionForm;
window.FlowsheetManager = FlowsheetManager;

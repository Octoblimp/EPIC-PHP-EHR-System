<?php
/**
 * Openspace EHR - Scheduling Module
 * Appointment scheduling and calendar management
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Schedule - ' . APP_NAME;
$view = $_GET['view'] ?? 'day';
$date = $_GET['date'] ?? date('Y-m-d');

// Demo appointments
$appointments = [
    ['id' => 1, 'time' => '08:00', 'duration' => 30, 'patient' => 'Smith, John', 'mrn' => 'MRN000001', 'type' => 'Follow-up', 'provider' => 'Dr. Wilson', 'status' => 'confirmed', 'room' => 'Exam 1'],
    ['id' => 2, 'time' => '08:30', 'duration' => 45, 'patient' => 'Johnson, Mary', 'mrn' => 'MRN000002', 'type' => 'New Patient', 'provider' => 'Dr. Wilson', 'status' => 'confirmed', 'room' => 'Exam 2'],
    ['id' => 3, 'time' => '09:30', 'duration' => 30, 'patient' => 'Williams, Robert', 'mrn' => 'MRN000003', 'type' => 'Lab Review', 'provider' => 'Dr. Wilson', 'status' => 'checked-in', 'room' => 'Exam 1'],
    ['id' => 4, 'time' => '10:00', 'duration' => 60, 'patient' => 'Davis, Linda', 'mrn' => 'MRN000004', 'type' => 'Physical Exam', 'provider' => 'Dr. Smith', 'status' => 'scheduled', 'room' => 'Exam 3'],
    ['id' => 5, 'time' => '11:00', 'duration' => 30, 'patient' => 'Brown, James', 'mrn' => 'MRN000005', 'type' => 'Sick Visit', 'provider' => 'Dr. Wilson', 'status' => 'confirmed', 'room' => 'Exam 1'],
    ['id' => 6, 'time' => '13:00', 'duration' => 30, 'patient' => 'Garcia, Maria', 'mrn' => 'MRN000006', 'type' => 'Follow-up', 'provider' => 'Dr. Smith', 'status' => 'scheduled', 'room' => 'Exam 2'],
    ['id' => 7, 'time' => '14:00', 'duration' => 45, 'patient' => 'Miller, David', 'mrn' => 'MRN000007', 'type' => 'Procedure', 'provider' => 'Dr. Wilson', 'status' => 'confirmed', 'room' => 'Procedure'],
    ['id' => 8, 'time' => '15:00', 'duration' => 30, 'patient' => 'Taylor, Susan', 'mrn' => 'MRN000008', 'type' => 'Medication Review', 'provider' => 'Dr. Smith', 'status' => 'scheduled', 'room' => 'Exam 1'],
];

// Demo providers
$providers = [
    ['id' => 1, 'name' => 'Dr. Wilson', 'specialty' => 'Internal Medicine', 'color' => '#4080c0'],
    ['id' => 2, 'name' => 'Dr. Smith', 'specialty' => 'Family Medicine', 'color' => '#40a060'],
    ['id' => 3, 'name' => 'Dr. Johnson', 'specialty' => 'Cardiology', 'color' => '#a04080'],
];

// Demo rooms
$rooms = [
    ['id' => 1, 'name' => 'Exam 1', 'type' => 'Exam Room'],
    ['id' => 2, 'name' => 'Exam 2', 'type' => 'Exam Room'],
    ['id' => 3, 'name' => 'Exam 3', 'type' => 'Exam Room'],
    ['id' => 4, 'name' => 'Procedure', 'type' => 'Procedure Room'],
];

include 'includes/header.php';
?>

<style>
.schedule-page {
    display: flex;
    height: calc(100vh - 54px);
    overflow: hidden;
}

.schedule-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #d0d8e0;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.sidebar-header {
    padding: 15px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
}

.sidebar-header h2 {
    font-size: 16px;
    margin: 0 0 10px;
}

/* Mini calendar */
.mini-calendar {
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.mini-calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mini-calendar-header h3 {
    font-size: 14px;
    margin: 0;
}

.mini-calendar-header button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    color: #666;
}

.mini-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
    font-size: 11px;
    text-align: center;
}

.mini-calendar-grid .day-header {
    font-weight: 600;
    color: #888;
    padding: 4px;
}

.mini-calendar-grid .day {
    padding: 6px 4px;
    border-radius: 4px;
    cursor: pointer;
}

.mini-calendar-grid .day:hover {
    background: #e8f0f8;
}

.mini-calendar-grid .day.today {
    background: #1a4a5e;
    color: white;
}

.mini-calendar-grid .day.selected {
    background: #f0a030;
    color: white;
}

.mini-calendar-grid .day.other-month {
    color: #ccc;
}

.mini-calendar-grid .day.has-appointments {
    font-weight: 600;
}

/* Provider list */
.provider-list {
    padding: 10px;
    flex: 1;
    overflow-y: auto;
}

.provider-list h4 {
    font-size: 12px;
    color: #888;
    margin: 0 0 8px;
    text-transform: uppercase;
}

.provider-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 4px;
}

.provider-item:hover {
    background: #f0f4f8;
}

.provider-item.active {
    background: #e8f0f8;
}

.provider-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.provider-info {
    flex: 1;
}

.provider-name {
    font-size: 13px;
    font-weight: 500;
}

.provider-specialty {
    font-size: 11px;
    color: #888;
}

/* Main calendar area */
.schedule-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #f5f7f9;
}

.schedule-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: white;
    border-bottom: 1px solid #d0d8e0;
}

.schedule-nav {
    display: flex;
    align-items: center;
    gap: 10px;
}

.schedule-nav button {
    padding: 6px 12px;
    border: 1px solid #d0d8e0;
    background: white;
    border-radius: 4px;
    cursor: pointer;
}

.schedule-nav button:hover {
    background: #f0f0f0;
}

.schedule-nav .current-date {
    font-size: 16px;
    font-weight: 600;
    color: #1a4a5e;
    min-width: 200px;
    text-align: center;
}

.view-toggle {
    display: flex;
    gap: 2px;
    background: #e8e8e8;
    border-radius: 4px;
    padding: 2px;
}

.view-toggle button {
    padding: 6px 14px;
    border: none;
    background: transparent;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.view-toggle button.active {
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.schedule-actions button {
    padding: 8px 16px;
    background: #1a4a5e;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.schedule-actions button:hover {
    background: #0d3545;
}

/* Calendar grid */
.calendar-container {
    flex: 1;
    overflow: auto;
    padding: 15px;
}

.day-view {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.time-grid {
    display: grid;
    grid-template-columns: 70px 1fr;
}

.time-labels {
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
}

.time-label {
    height: 60px;
    padding: 4px 8px;
    font-size: 11px;
    color: #888;
    text-align: right;
    border-bottom: 1px solid #f0f0f0;
}

.appointments-column {
    position: relative;
}

.time-slot {
    height: 60px;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
}

.time-slot:nth-child(2n) {
    background: #fafafa;
}

.appointment-block {
    position: absolute;
    left: 4px;
    right: 4px;
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 11px;
    cursor: pointer;
    overflow: hidden;
    border-left: 3px solid;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.appointment-block.confirmed {
    background: #e3f2fd;
    border-color: #1976d2;
}

.appointment-block.checked-in {
    background: #e8f5e9;
    border-color: #388e3c;
}

.appointment-block.scheduled {
    background: #fff3e0;
    border-color: #f57c00;
}

.appointment-block:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.appt-time {
    font-weight: 600;
    color: #333;
}

.appt-patient {
    font-weight: 500;
    color: #1a4a5e;
    margin: 2px 0;
}

.appt-type {
    color: #666;
}

.appt-room {
    color: #888;
    font-size: 10px;
}

/* New appointment modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 500px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 15px 20px;
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 16px;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1a4a5e;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-footer button {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.btn-cancel {
    background: #e0e0e0;
    border: none;
    color: #333;
}

.btn-save {
    background: #1a4a5e;
    border: none;
    color: white;
}
</style>

<div class="schedule-page">
    <!-- Sidebar -->
    <div class="schedule-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-calendar-alt"></i> Scheduling</h2>
        </div>
        
        <!-- Mini Calendar -->
        <div class="mini-calendar">
            <div class="mini-calendar-header">
                <button onclick="prevMonth()"><i class="fas fa-chevron-left"></i></button>
                <h3 id="miniCalendarMonth"><?php echo date('F Y'); ?></h3>
                <button onclick="nextMonth()"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="mini-calendar-grid" id="miniCalendarGrid">
                <!-- Generated by JS -->
            </div>
        </div>
        
        <!-- Provider List -->
        <div class="provider-list">
            <h4>Providers</h4>
            <?php foreach ($providers as $provider): ?>
            <div class="provider-item active" onclick="toggleProvider(<?php echo $provider['id']; ?>)">
                <input type="checkbox" checked>
                <div class="provider-color" style="background: <?php echo $provider['color']; ?>"></div>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $provider['name']; ?></div>
                    <div class="provider-specialty"><?php echo $provider['specialty']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <h4 style="margin-top: 15px;">Rooms</h4>
            <?php foreach ($rooms as $room): ?>
            <div class="provider-item">
                <input type="checkbox" checked>
                <div class="provider-info">
                    <div class="provider-name"><?php echo $room['name']; ?></div>
                    <div class="provider-specialty"><?php echo $room['type']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Main Calendar -->
    <div class="schedule-main">
        <div class="schedule-toolbar">
            <div class="schedule-nav">
                <button onclick="goToday()">Today</button>
                <button onclick="prevDay()"><i class="fas fa-chevron-left"></i></button>
                <span class="current-date" id="currentDate"><?php echo date('l, F j, Y', strtotime($date)); ?></span>
                <button onclick="nextDay()"><i class="fas fa-chevron-right"></i></button>
            </div>
            
            <div class="view-toggle">
                <button class="<?php echo $view === 'day' ? 'active' : ''; ?>" onclick="setView('day')">Day</button>
                <button class="<?php echo $view === 'week' ? 'active' : ''; ?>" onclick="setView('week')">Week</button>
                <button class="<?php echo $view === 'month' ? 'active' : ''; ?>" onclick="setView('month')">Month</button>
            </div>
            
            <div class="schedule-actions">
                <button onclick="showNewAppointment()">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
            </div>
        </div>
        
        <div class="calendar-container">
            <div class="day-view">
                <div class="time-grid">
                    <div class="time-labels">
                        <?php for ($h = 7; $h < 18; $h++): ?>
                        <div class="time-label"><?php echo sprintf('%d:00 %s', $h > 12 ? $h - 12 : $h, $h >= 12 ? 'PM' : 'AM'); ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="appointments-column">
                        <?php for ($h = 7; $h < 18; $h++): ?>
                        <div class="time-slot" data-hour="<?php echo $h; ?>"></div>
                        <?php endfor; ?>
                        
                        <?php foreach ($appointments as $appt): 
                            $timeParts = explode(':', $appt['time']);
                            $hour = intval($timeParts[0]);
                            $minute = intval($timeParts[1]);
                            $top = (($hour - 7) * 60 + $minute);
                            $height = $appt['duration'];
                        ?>
                        <div class="appointment-block <?php echo $appt['status']; ?>" 
                             style="top: <?php echo $top; ?>px; height: <?php echo $height; ?>px;"
                             onclick="showAppointmentDetails(<?php echo $appt['id']; ?>)">
                            <div class="appt-time"><?php echo date('g:i A', strtotime($appt['time'])); ?></div>
                            <div class="appt-patient"><?php echo $appt['patient']; ?></div>
                            <div class="appt-type"><?php echo $appt['type']; ?></div>
                            <div class="appt-room"><?php echo $appt['room']; ?> • <?php echo $appt['provider']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Appointment Modal -->
<div class="modal" id="newAppointmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> New Appointment</h3>
            <button class="modal-close" onclick="closeModal('newAppointmentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Patient</label>
                <input type="text" id="apptPatient" placeholder="Search patient...">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="apptDate" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" id="apptTime" value="09:00">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Duration</label>
                    <select id="apptDuration">
                        <option value="15">15 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90">1.5 hours</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Visit Type</label>
                    <select id="apptType">
                        <option>Follow-up</option>
                        <option>New Patient</option>
                        <option>Physical Exam</option>
                        <option>Sick Visit</option>
                        <option>Lab Review</option>
                        <option>Medication Review</option>
                        <option>Procedure</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Provider</label>
                    <select id="apptProvider">
                        <?php foreach ($providers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select id="apptRoom">
                        <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo $r['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" id="apptNotes" placeholder="Appointment notes...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal('newAppointmentModal')">Cancel</button>
            <button class="btn-save" onclick="saveAppointment()">Save Appointment</button>
        </div>
    </div>
</div>

<script>
let currentDate = new Date('<?php echo $date; ?>');
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();

// Initialize mini calendar
function renderMiniCalendar() {
    const grid = document.getElementById('miniCalendarGrid');
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    document.getElementById('miniCalendarMonth').textContent = monthNames[currentMonth] + ' ' + currentYear;
    
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const today = new Date();
    
    let html = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map(d => `<div class="day-header">${d}</div>`).join('');
    
    // Previous month days
    const prevMonthDays = new Date(currentYear, currentMonth, 0).getDate();
    for (let i = firstDay - 1; i >= 0; i--) {
        html += `<div class="day other-month">${prevMonthDays - i}</div>`;
    }
    
    // Current month days
    for (let d = 1; d <= daysInMonth; d++) {
        const isToday = d === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear();
        const isSelected = d === currentDate.getDate() && currentMonth === currentDate.getMonth() && currentYear === currentDate.getFullYear();
        let classes = 'day';
        if (isToday) classes += ' today';
        if (isSelected) classes += ' selected';
        if ([1, 5, 8, 12, 15, 20].includes(d)) classes += ' has-appointments';
        html += `<div class="${classes}" onclick="selectDay(${d})">${d}</div>`;
    }
    
    grid.innerHTML = html;
}

function prevMonth() {
    currentMonth--;
    if (currentMonth < 0) { currentMonth = 11; currentYear--; }
    renderMiniCalendar();
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    renderMiniCalendar();
}

function selectDay(day) {
    currentDate = new Date(currentYear, currentMonth, day);
    updateDateDisplay();
    renderMiniCalendar();
    // In real app, would reload appointments for selected day
}

function updateDateDisplay() {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('currentDate').textContent = currentDate.toLocaleDateString('en-US', options);
}

function goToday() {
    currentDate = new Date();
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    updateDateDisplay();
    renderMiniCalendar();
}

function prevDay() {
    currentDate.setDate(currentDate.getDate() - 1);
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    updateDateDisplay();
    renderMiniCalendar();
}

function nextDay() {
    currentDate.setDate(currentDate.getDate() + 1);
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    updateDateDisplay();
    renderMiniCalendar();
}

function setView(view) {
    window.location.href = `schedule.php?view=${view}&date=${currentDate.toISOString().split('T')[0]}`;
}

function toggleProvider(id) {
    // Toggle provider visibility
}

function showNewAppointment() {
    document.getElementById('newAppointmentModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function saveAppointment() {
    alert('Appointment saved! (Demo)');
    closeModal('newAppointmentModal');
}

function showAppointmentDetails(id) {
    alert('Opening appointment #' + id + ' details... (Demo)');
}

// Initialize
renderMiniCalendar();
</script>

<?php include 'includes/footer.php'; ?>

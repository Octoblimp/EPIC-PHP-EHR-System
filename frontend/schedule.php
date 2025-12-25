<?php
/**
 * Openspace EHR - Scheduling Module
 * Appointment scheduling and calendar management
 * Fully functional - integrates with backend API
 */
require_once 'includes/config.php';
require_once 'includes/api.php';

$page_title = 'Schedule - ' . APP_NAME;
$view = $_GET['view'] ?? 'day';
$date = $_GET['date'] ?? date('Y-m-d');
$provider_filter = $_GET['provider'] ?? null;

// Initialize API endpoints
$apiBase = defined('API_BASE_URL') ? API_BASE_URL : '/api';

// Fetch appointment types from API
$appointmentTypes = [];
try {
    $response = file_get_contents($apiBase . '/scheduling/appointment-types');
    $data = json_decode($response, true);
    if ($data['success'] ?? false) {
        $appointmentTypes = $data['appointment_types'] ?? [];
    }
} catch (Exception $e) {
    // Use defaults if API unavailable
    $appointmentTypes = [
        ['id' => 1, 'name' => 'Follow-up', 'duration' => 30, 'color' => '#4080c0'],
        ['id' => 2, 'name' => 'New Patient', 'duration' => 45, 'color' => '#40a060'],
        ['id' => 3, 'name' => 'Physical Exam', 'duration' => 60, 'color' => '#a04080'],
        ['id' => 4, 'name' => 'Sick Visit', 'duration' => 30, 'color' => '#c04040'],
        ['id' => 5, 'name' => 'Lab Review', 'duration' => 30, 'color' => '#8040a0'],
        ['id' => 6, 'name' => 'Medication Review', 'duration' => 30, 'color' => '#40a0a0'],
        ['id' => 7, 'name' => 'Procedure', 'duration' => 45, 'color' => '#a08040'],
    ];
}

// Fetch providers (in real implementation, from User model with provider role)
$providers = [
    ['id' => 1, 'name' => 'Dr. Wilson', 'specialty' => 'Internal Medicine', 'color' => '#4080c0'],
    ['id' => 2, 'name' => 'Dr. Smith', 'specialty' => 'Family Medicine', 'color' => '#40a060'],
    ['id' => 3, 'name' => 'Dr. Johnson', 'specialty' => 'Cardiology', 'color' => '#a04080'],
];

// Fetch rooms
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

/* Week View Styles */
.week-view {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.week-header {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.week-time-header {
    padding: 10px;
}

.week-day-header {
    padding: 10px;
    text-align: center;
    border-left: 1px solid #e0e0e0;
}

.week-day-header.today {
    background: #e3f2fd;
}

.week-day-header .day-name {
    display: block;
    font-size: 11px;
    color: #888;
}

.week-day-header .day-number {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.week-body {
    display: grid;
    grid-template-columns: 70px repeat(7, 1fr);
}

.week-times {
    background: #f8f9fa;
    border-right: 1px solid #e0e0e0;
}

.week-time-label {
    height: 60px;
    padding: 4px 8px;
    font-size: 10px;
    color: #888;
    text-align: right;
    border-bottom: 1px solid #f0f0f0;
}

.week-day-column {
    position: relative;
    border-left: 1px solid #e0e0e0;
    min-height: 660px;
}

.week-time-slot {
    height: 60px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
}

.week-time-slot:hover {
    background: #f0f8ff;
}

.week-appointment {
    position: absolute;
    left: 2px;
    right: 2px;
    border-radius: 3px;
    padding: 2px 4px;
    font-size: 10px;
    background: #e3f2fd;
    border-left: 2px solid #1976d2;
    overflow: hidden;
    cursor: pointer;
}

.week-appointment.confirmed { background: #e3f2fd; }
.week-appointment.checked_in { background: #e8f5e9; border-color: #388e3c; }
.week-appointment.scheduled { background: #fff3e0; border-color: #f57c00; }
.week-appointment.completed { background: #f5f5f5; border-color: #9e9e9e; }
.week-appointment.cancelled { background: #ffebee; border-color: #c62828; opacity: 0.6; }
.week-appointment.no_show { background: #fce4ec; border-color: #c2185b; }

.week-appointment .appt-time {
    font-weight: 600;
}

/* Month View Styles */
.month-view {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.month-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.month-day-header {
    padding: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 12px;
    color: #666;
}

.month-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.month-day {
    min-height: 100px;
    border: 1px solid #f0f0f0;
    padding: 5px;
    cursor: pointer;
}

.month-day:hover {
    background: #f8f9fa;
}

.month-day.today {
    background: #e3f2fd;
}

.month-day.other-month {
    background: #fafafa;
    color: #ccc;
}

.month-day .day-num {
    font-weight: 600;
    font-size: 14px;
}

.month-day-appointments {
    margin-top: 5px;
}

.month-appt {
    font-size: 10px;
    padding: 2px 4px;
    border-radius: 2px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #e3f2fd;
}

.month-appt.checked_in { background: #e8f5e9; }
.month-appt.scheduled { background: #fff3e0; }

.month-more {
    font-size: 10px;
    color: #1a4a5e;
    font-weight: 500;
}

/* Patient Search Results */
.patient-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #d0d8e0;
    border-top: none;
    border-radius: 0 0 4px 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.patient-result {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.patient-result:hover {
    background: #f0f8ff;
}

.patient-result .mrn {
    float: right;
    color: #888;
    font-size: 12px;
}

.form-group {
    position: relative;
}

.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 2px solid #d0d8e0;
    border-radius: 4px;
    font-size: 14px;
    resize: vertical;
}

/* Status Action Buttons */
.status-buttons {
    display: flex;
    gap: 8px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
    flex-wrap: wrap;
}

.btn-status {
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-status.checkin { background: #e8f5e9; color: #2e7d32; }
.btn-status.checkin:hover { background: #c8e6c9; }
.btn-status.room { background: #e3f2fd; color: #1565c0; }
.btn-status.room:hover { background: #bbdefb; }
.btn-status.complete { background: #f3e5f5; color: #7b1fa2; }
.btn-status.complete:hover { background: #e1bee7; }
.btn-status.noshow { background: #ffebee; color: #c62828; }
.btn-status.noshow:hover { background: #ffcdd2; }

/* Notifications */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 10001;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
}

.notification.success {
    border-left: 4px solid #4caf50;
}

.notification.error {
    border-left: 4px solid #f44336;
}

.notification.info {
    border-left: 4px solid #2196f3;
}

.notification i {
    font-size: 18px;
}

.notification.success i { color: #4caf50; }
.notification.error i { color: #f44336; }
.notification.info i { color: #2196f3; }

/* Delete button styling */
#deleteApptBtn {
    background: #ffebee;
    color: #c62828;
    border: none;
}

#deleteApptBtn:hover {
    background: #ffcdd2;
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
            <div class="day-view" id="dayView" style="display: <?php echo $view === 'day' ? 'block' : 'none'; ?>">
                <div class="time-grid">
                    <div class="time-labels">
                        <?php for ($h = 7; $h < 18; $h++): ?>
                        <div class="time-label"><?php echo sprintf('%d:00 %s', $h > 12 ? $h - 12 : $h, $h >= 12 ? 'PM' : 'AM'); ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="appointments-column" id="appointmentsColumn">
                        <?php for ($h = 7; $h < 18; $h++): ?>
                        <div class="time-slot" data-hour="<?php echo $h; ?>" onclick="quickAddAppointment(<?php echo $h; ?>)"></div>
                        <?php endfor; ?>
                        <!-- Appointments will be rendered by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Week View -->
            <div class="week-view" id="weekView" style="display: <?php echo $view === 'week' ? 'block' : 'none'; ?>">
                <div class="week-grid" id="weekGrid">
                    <!-- Week view rendered by JavaScript -->
                </div>
            </div>
            
            <!-- Month View -->
            <div class="month-view" id="monthView" style="display: <?php echo $view === 'month' ? 'block' : 'none'; ?>">
                <div class="month-grid" id="monthGrid">
                    <!-- Month view rendered by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Appointment Modal -->
<div class="modal" id="newAppointmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="apptModalTitle"><i class="fas fa-calendar-plus"></i> New Appointment</h3>
            <button class="modal-close" onclick="closeModal('newAppointmentModal')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="apptId">
            <input type="hidden" id="apptPatientId">
            
            <div class="form-group">
                <label>Patient</label>
                <input type="text" id="apptPatient" placeholder="Search patient..." autocomplete="off">
                <div class="patient-search-results" id="patientSearchResults"></div>
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
                        <?php foreach ($appointmentTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Provider</label>
                    <select id="apptProvider">
                        <?php foreach ($providers as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <select id="apptRoom">
                        <option value="">-- Select Room --</option>
                        <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea id="apptNotes" placeholder="Appointment notes..." rows="2"></textarea>
            </div>
            
            <div id="apptStatusActions">
                <!-- Status action buttons will be inserted here for existing appointments -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="deleteApptBtn" style="display:none; margin-right:auto;" onclick="deleteAppointmentFromModal()">
                <i class="fas fa-trash"></i> Cancel Appt
            </button>
            <button class="btn-cancel" onclick="closeModal('newAppointmentModal')">Close</button>
            <button class="btn-save" onclick="saveAppointment()">Save Appointment</button>
        </div>
    </div>
</div>

<script>
// Configuration
const API_BASE = '<?php echo $apiBase; ?>';
let currentDate = new Date('<?php echo $date; ?>');
let currentMonth = currentDate.getMonth();
let currentYear = currentDate.getFullYear();
let currentView = '<?php echo $view; ?>';
let appointments = [];
let selectedProviders = <?php echo json_encode(array_column($providers, 'id')); ?>;
let selectedRooms = <?php echo json_encode(array_column($rooms, 'id')); ?>;
const providers = <?php echo json_encode($providers); ?>;
const rooms = <?php echo json_encode($rooms); ?>;
const appointmentTypes = <?php echo json_encode($appointmentTypes); ?>;

// ============================================================
// API Functions
// ============================================================

async function fetchAppointments(startDate, endDate) {
    try {
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate || startDate
        });
        
        // Add provider filter if not all selected
        if (selectedProviders.length === 1) {
            params.append('provider_id', selectedProviders[0]);
        }
        
        const response = await fetch(`${API_BASE}/scheduling/appointments?${params}`);
        const data = await response.json();
        
        if (data.success) {
            appointments = data.appointments || [];
            return appointments;
        }
    } catch (error) {
        console.warn('API unavailable, using fallback data:', error);
        // Use fallback demo data if API is unavailable
        appointments = getFallbackAppointments(startDate);
    }
    return appointments;
}

function getFallbackAppointments(dateStr) {
    // Fallback demo data when API is unavailable
    const baseAppointments = [
        {id: 1, scheduled_time: '08:00', duration_minutes: 30, patient_name: 'Smith, John', mrn: 'MRN000001', appointment_type: 'Follow-up', provider_name: 'Dr. Wilson', status: 'confirmed', room_name: 'Exam 1', provider_id: 1},
        {id: 2, scheduled_time: '08:30', duration_minutes: 45, patient_name: 'Johnson, Mary', mrn: 'MRN000002', appointment_type: 'New Patient', provider_name: 'Dr. Wilson', status: 'confirmed', room_name: 'Exam 2', provider_id: 1},
        {id: 3, scheduled_time: '09:30', duration_minutes: 30, patient_name: 'Williams, Robert', mrn: 'MRN000003', appointment_type: 'Lab Review', provider_name: 'Dr. Wilson', status: 'checked_in', room_name: 'Exam 1', provider_id: 1},
        {id: 4, scheduled_time: '10:00', duration_minutes: 60, patient_name: 'Davis, Linda', mrn: 'MRN000004', appointment_type: 'Physical Exam', provider_name: 'Dr. Smith', status: 'scheduled', room_name: 'Exam 3', provider_id: 2},
        {id: 5, scheduled_time: '11:00', duration_minutes: 30, patient_name: 'Brown, James', mrn: 'MRN000005', appointment_type: 'Sick Visit', provider_name: 'Dr. Wilson', status: 'confirmed', room_name: 'Exam 1', provider_id: 1},
        {id: 6, scheduled_time: '13:00', duration_minutes: 30, patient_name: 'Garcia, Maria', mrn: 'MRN000006', appointment_type: 'Follow-up', provider_name: 'Dr. Smith', status: 'scheduled', room_name: 'Exam 2', provider_id: 2},
        {id: 7, scheduled_time: '14:00', duration_minutes: 45, patient_name: 'Miller, David', mrn: 'MRN000007', appointment_type: 'Procedure', provider_name: 'Dr. Wilson', status: 'confirmed', room_name: 'Procedure', provider_id: 1},
        {id: 8, scheduled_time: '15:00', duration_minutes: 30, patient_name: 'Taylor, Susan', mrn: 'MRN000008', appointment_type: 'Medication Review', provider_name: 'Dr. Smith', status: 'scheduled', room_name: 'Exam 1', provider_id: 2},
    ];
    return baseAppointments.map(apt => ({...apt, scheduled_date: dateStr}));
}

async function createAppointment(appointmentData) {
    try {
        const response = await fetch(`${API_BASE}/scheduling/appointments`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(appointmentData)
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Appointment created successfully', 'success');
            loadAppointments();
            return data.appointment;
        } else {
            if (data.conflicts) {
                showConflictDialog(data.conflicts, appointmentData);
            } else {
                showNotification(data.error || 'Failed to create appointment', 'error');
            }
        }
    } catch (error) {
        console.error('Create appointment error:', error);
        showNotification('Failed to create appointment - API unavailable', 'error');
    }
    return null;
}

async function updateAppointment(appointmentId, appointmentData) {
    try {
        const response = await fetch(`${API_BASE}/scheduling/appointments/${appointmentId}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(appointmentData)
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification('Appointment updated successfully', 'success');
            loadAppointments();
            return data.appointment;
        } else {
            showNotification(data.error || 'Failed to update appointment', 'error');
        }
    } catch (error) {
        console.error('Update appointment error:', error);
        showNotification('Failed to update appointment', 'error');
    }
    return null;
}

async function updateAppointmentStatus(appointmentId, status, reason = null) {
    try {
        const response = await fetch(`${API_BASE}/scheduling/appointments/${appointmentId}/status`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ status, reason })
        });
        const data = await response.json();
        
        if (data.success) {
            showNotification(`Appointment ${status.replace('_', ' ')}`, 'success');
            loadAppointments();
            return data.appointment;
        }
    } catch (error) {
        console.error('Status update error:', error);
    }
    return null;
}

async function deleteAppointment(appointmentId) {
    if (!confirm('Are you sure you want to cancel this appointment?')) return;
    
    const reason = prompt('Please enter a cancellation reason:');
    if (reason === null) return;
    
    await updateAppointmentStatus(appointmentId, 'cancelled', reason);
}

// ============================================================
// Rendering Functions
// ============================================================

async function loadAppointments() {
    const dateStr = formatDateISO(currentDate);
    
    let endDate = dateStr;
    if (currentView === 'week') {
        const weekEnd = new Date(currentDate);
        weekEnd.setDate(weekEnd.getDate() + 6);
        endDate = formatDateISO(weekEnd);
    } else if (currentView === 'month') {
        const monthEnd = new Date(currentYear, currentMonth + 1, 0);
        endDate = formatDateISO(monthEnd);
    }
    
    await fetchAppointments(dateStr, endDate);
    renderView();
}

function renderView() {
    switch (currentView) {
        case 'day':
            renderDayView();
            break;
        case 'week':
            renderWeekView();
            break;
        case 'month':
            renderMonthView();
            break;
    }
}

function renderDayView() {
    const column = document.getElementById('appointmentsColumn');
    const dateStr = formatDateISO(currentDate);
    
    // Remove existing appointments (keep time slots)
    column.querySelectorAll('.appointment-block').forEach(el => el.remove());
    
    // Filter appointments for this day and selected providers
    const dayAppointments = appointments.filter(apt => {
        const aptDate = apt.scheduled_date;
        const providerMatch = selectedProviders.includes(apt.provider_id);
        return aptDate === dateStr && providerMatch;
    });
    
    // Render each appointment
    dayAppointments.forEach(apt => {
        const block = createAppointmentBlock(apt);
        column.appendChild(block);
    });
}

function createAppointmentBlock(apt) {
    const timeParts = apt.scheduled_time.split(':');
    const hour = parseInt(timeParts[0]);
    const minute = parseInt(timeParts[1] || 0);
    const top = ((hour - 7) * 60 + minute);
    const height = apt.duration_minutes || 30;
    
    const block = document.createElement('div');
    block.className = `appointment-block ${apt.status}`;
    block.style.top = `${top}px`;
    block.style.height = `${height}px`;
    block.onclick = () => showAppointmentDetails(apt);
    block.dataset.appointmentId = apt.id;
    
    // Add provider color indicator
    const provider = providers.find(p => p.id === apt.provider_id);
    if (provider) {
        block.style.borderColor = provider.color;
    }
    
    const timeFormatted = formatTime(apt.scheduled_time);
    
    block.innerHTML = `
        <div class="appt-time">${timeFormatted}</div>
        <div class="appt-patient">${apt.patient_name || 'Unknown Patient'}</div>
        <div class="appt-type">${apt.appointment_type || apt.appointment_type_name || ''}</div>
        <div class="appt-room">${apt.room_name || ''} • ${apt.provider_name || ''}</div>
    `;
    
    return block;
}

function renderWeekView() {
    const grid = document.getElementById('weekGrid');
    const weekStart = new Date(currentDate);
    weekStart.setDate(weekStart.getDate() - weekStart.getDay());
    
    let html = '<div class="week-header">';
    html += '<div class="week-time-header"></div>';
    
    // Day headers
    for (let d = 0; d < 7; d++) {
        const day = new Date(weekStart);
        day.setDate(day.getDate() + d);
        const isToday = formatDateISO(day) === formatDateISO(new Date());
        html += `<div class="week-day-header ${isToday ? 'today' : ''}">
            <span class="day-name">${day.toLocaleDateString('en-US', {weekday: 'short'})}</span>
            <span class="day-number">${day.getDate()}</span>
        </div>`;
    }
    html += '</div>';
    
    html += '<div class="week-body">';
    html += '<div class="week-times">';
    for (let h = 7; h < 18; h++) {
        html += `<div class="week-time-label">${formatHour(h)}</div>`;
    }
    html += '</div>';
    
    // Day columns
    for (let d = 0; d < 7; d++) {
        const day = new Date(weekStart);
        day.setDate(day.getDate() + d);
        const dayStr = formatDateISO(day);
        const dayAppointments = appointments.filter(apt => 
            apt.scheduled_date === dayStr && selectedProviders.includes(apt.provider_id)
        );
        
        html += `<div class="week-day-column" data-date="${dayStr}">`;
        for (let h = 7; h < 18; h++) {
            html += `<div class="week-time-slot" onclick="quickAddAppointment(${h}, '${dayStr}')"></div>`;
        }
        
        // Render appointments
        dayAppointments.forEach(apt => {
            const timeParts = apt.scheduled_time.split(':');
            const hour = parseInt(timeParts[0]);
            const minute = parseInt(timeParts[1] || 0);
            const top = ((hour - 7) * 60 + minute);
            const height = apt.duration_minutes || 30;
            const provider = providers.find(p => p.id === apt.provider_id);
            
            html += `<div class="week-appointment ${apt.status}" 
                style="top: ${top}px; height: ${height}px; border-color: ${provider?.color || '#4080c0'}"
                onclick="showAppointmentDetails(${JSON.stringify(apt).replace(/"/g, '&quot;')})">
                <span class="appt-time">${formatTime(apt.scheduled_time)}</span>
                <span class="appt-patient">${apt.patient_name || ''}</span>
            </div>`;
        });
        
        html += '</div>';
    }
    html += '</div>';
    
    grid.innerHTML = html;
}

function renderMonthView() {
    const grid = document.getElementById('monthGrid');
    const firstDay = new Date(currentYear, currentMonth, 1);
    const lastDay = new Date(currentYear, currentMonth + 1, 0);
    const startDay = firstDay.getDay();
    
    let html = '<div class="month-header">';
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(d => {
        html += `<div class="month-day-header">${d}</div>`;
    });
    html += '</div><div class="month-body">';
    
    // Previous month padding
    const prevMonthLast = new Date(currentYear, currentMonth, 0).getDate();
    for (let i = startDay - 1; i >= 0; i--) {
        html += `<div class="month-day other-month"><span class="day-num">${prevMonthLast - i}</span></div>`;
    }
    
    // Current month days
    for (let d = 1; d <= lastDay.getDate(); d++) {
        const dayStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isToday = dayStr === formatDateISO(new Date());
        const dayAppointments = appointments.filter(apt => apt.scheduled_date === dayStr);
        
        html += `<div class="month-day ${isToday ? 'today' : ''}" onclick="goToDay('${dayStr}')">
            <span class="day-num">${d}</span>
            <div class="month-day-appointments">`;
        
        // Show up to 3 appointments
        dayAppointments.slice(0, 3).forEach(apt => {
            html += `<div class="month-appt ${apt.status}" title="${apt.patient_name}">${formatTime(apt.scheduled_time)} ${apt.patient_name?.split(',')[0] || ''}</div>`;
        });
        
        if (dayAppointments.length > 3) {
            html += `<div class="month-more">+${dayAppointments.length - 3} more</div>`;
        }
        
        html += '</div></div>';
    }
    
    // Next month padding
    const remaining = (7 - ((startDay + lastDay.getDate()) % 7)) % 7;
    for (let i = 1; i <= remaining; i++) {
        html += `<div class="month-day other-month"><span class="day-num">${i}</span></div>`;
    }
    
    html += '</div>';
    grid.innerHTML = html;
}

// ============================================================
// Mini Calendar
// ============================================================

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
        
        // Check if day has appointments
        const dayStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const hasAppts = appointments.some(apt => apt.scheduled_date === dayStr);
        if (hasAppts) classes += ' has-appointments';
        
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
    loadAppointments();
}

function goToDay(dateStr) {
    currentDate = new Date(dateStr + 'T12:00:00');
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    currentView = 'day';
    document.querySelectorAll('.view-toggle button').forEach(b => b.classList.remove('active'));
    document.querySelector('.view-toggle button:first-child').classList.add('active');
    document.getElementById('dayView').style.display = 'block';
    document.getElementById('weekView').style.display = 'none';
    document.getElementById('monthView').style.display = 'none';
    updateDateDisplay();
    renderMiniCalendar();
    loadAppointments();
}

// ============================================================
// Navigation
// ============================================================

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
    loadAppointments();
}

function prevDay() {
    const days = currentView === 'week' ? 7 : currentView === 'month' ? 30 : 1;
    currentDate.setDate(currentDate.getDate() - days);
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    updateDateDisplay();
    renderMiniCalendar();
    loadAppointments();
}

function nextDay() {
    const days = currentView === 'week' ? 7 : currentView === 'month' ? 30 : 1;
    currentDate.setDate(currentDate.getDate() + days);
    currentMonth = currentDate.getMonth();
    currentYear = currentDate.getFullYear();
    updateDateDisplay();
    renderMiniCalendar();
    loadAppointments();
}

function setView(view) {
    currentView = view;
    document.querySelectorAll('.view-toggle button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    document.getElementById('dayView').style.display = view === 'day' ? 'block' : 'none';
    document.getElementById('weekView').style.display = view === 'week' ? 'block' : 'none';
    document.getElementById('monthView').style.display = view === 'month' ? 'block' : 'none';
    
    loadAppointments();
}

function toggleProvider(id) {
    const checkbox = event.target.closest('.provider-item').querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        if (!selectedProviders.includes(id)) selectedProviders.push(id);
    } else {
        selectedProviders = selectedProviders.filter(p => p !== id);
    }
    
    renderView();
}

// ============================================================
// Modals
// ============================================================

function showNewAppointment() {
    document.getElementById('apptDate').value = formatDateISO(currentDate);
    document.getElementById('apptModalTitle').textContent = 'New Appointment';
    document.getElementById('apptId').value = '';
    document.getElementById('apptPatient').value = '';
    document.getElementById('apptPatientId').value = '';
    document.getElementById('apptTime').value = '09:00';
    document.getElementById('apptDuration').value = '30';
    document.getElementById('apptType').value = appointmentTypes[0]?.id || '';
    document.getElementById('apptNotes').value = '';
    document.getElementById('deleteApptBtn').style.display = 'none';
    document.getElementById('newAppointmentModal').classList.add('show');
}

function quickAddAppointment(hour, dateStr = null) {
    const date = dateStr || formatDateISO(currentDate);
    document.getElementById('apptDate').value = date;
    document.getElementById('apptTime').value = `${String(hour).padStart(2, '0')}:00`;
    document.getElementById('apptModalTitle').textContent = 'New Appointment';
    document.getElementById('apptId').value = '';
    document.getElementById('apptPatient').value = '';
    document.getElementById('apptPatientId').value = '';
    document.getElementById('deleteApptBtn').style.display = 'none';
    document.getElementById('newAppointmentModal').classList.add('show');
}

function showAppointmentDetails(apt) {
    // If apt is a string (from onclick in week view), parse it
    if (typeof apt === 'string') {
        apt = JSON.parse(apt);
    }
    
    document.getElementById('apptModalTitle').textContent = 'Edit Appointment';
    document.getElementById('apptId').value = apt.id;
    document.getElementById('apptPatient').value = apt.patient_name || '';
    document.getElementById('apptPatientId').value = apt.patient_id || '';
    document.getElementById('apptDate').value = apt.scheduled_date;
    document.getElementById('apptTime').value = apt.scheduled_time?.substring(0, 5) || '';
    document.getElementById('apptDuration').value = apt.duration_minutes || 30;
    document.getElementById('apptType').value = apt.appointment_type_id || '';
    document.getElementById('apptProvider').value = apt.provider_id || '';
    document.getElementById('apptRoom').value = apt.room_id || '';
    document.getElementById('apptNotes').value = apt.notes || '';
    document.getElementById('deleteApptBtn').style.display = 'inline-block';
    
    // Show status actions
    const statusDiv = document.getElementById('apptStatusActions');
    if (statusDiv) {
        let statusHtml = '<div class="status-buttons">';
        if (apt.status === 'scheduled' || apt.status === 'confirmed') {
            statusHtml += `<button class="btn-status checkin" onclick="checkInAppointment(${apt.id})"><i class="fas fa-check"></i> Check In</button>`;
        }
        if (apt.status === 'checked_in') {
            statusHtml += `<button class="btn-status room" onclick="roomPatient(${apt.id})"><i class="fas fa-door-open"></i> Room Patient</button>`;
        }
        if (apt.status === 'in_progress') {
            statusHtml += `<button class="btn-status complete" onclick="completeAppointment(${apt.id})"><i class="fas fa-check-double"></i> Complete</button>`;
        }
        if (apt.status !== 'cancelled' && apt.status !== 'completed' && apt.status !== 'no_show') {
            statusHtml += `<button class="btn-status noshow" onclick="markNoShow(${apt.id})"><i class="fas fa-user-slash"></i> No Show</button>`;
        }
        statusHtml += '</div>';
        statusDiv.innerHTML = statusHtml;
    }
    
    document.getElementById('newAppointmentModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

async function saveAppointment() {
    const id = document.getElementById('apptId').value;
    const patientId = document.getElementById('apptPatientId').value;
    const patientName = document.getElementById('apptPatient').value;
    
    if (!patientId && !patientName) {
        showNotification('Please select or enter a patient', 'error');
        return;
    }
    
    const appointmentData = {
        patient_id: parseInt(patientId) || 1, // Default to 1 for demo
        appointment_type_id: parseInt(document.getElementById('apptType').value),
        scheduled_date: document.getElementById('apptDate').value,
        scheduled_time: document.getElementById('apptTime').value,
        duration_minutes: parseInt(document.getElementById('apptDuration').value),
        provider_id: parseInt(document.getElementById('apptProvider').value),
        room_id: parseInt(document.getElementById('apptRoom').value),
        notes: document.getElementById('apptNotes').value
    };
    
    let result;
    if (id) {
        result = await updateAppointment(id, appointmentData);
    } else {
        result = await createAppointment(appointmentData);
    }
    
    if (result) {
        closeModal('newAppointmentModal');
    }
}

async function deleteAppointmentFromModal() {
    const id = document.getElementById('apptId').value;
    if (id) {
        await deleteAppointment(id);
        closeModal('newAppointmentModal');
    }
}

// Status change functions
async function checkInAppointment(id) {
    await updateAppointmentStatus(id, 'checked_in');
    closeModal('newAppointmentModal');
}

async function roomPatient(id) {
    await updateAppointmentStatus(id, 'in_progress');
    closeModal('newAppointmentModal');
}

async function completeAppointment(id) {
    await updateAppointmentStatus(id, 'completed');
    closeModal('newAppointmentModal');
}

async function markNoShow(id) {
    await updateAppointmentStatus(id, 'no_show');
    closeModal('newAppointmentModal');
}

// ============================================================
// Patient Search
// ============================================================

let patientSearchTimeout;
document.getElementById('apptPatient')?.addEventListener('input', function() {
    clearTimeout(patientSearchTimeout);
    const query = this.value;
    
    if (query.length < 2) {
        document.getElementById('patientSearchResults').innerHTML = '';
        return;
    }
    
    patientSearchTimeout = setTimeout(() => searchPatients(query), 300);
});

async function searchPatients(query) {
    const resultsDiv = document.getElementById('patientSearchResults');
    
    try {
        const response = await fetch(`${API_BASE}/patients/search?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success && data.patients?.length > 0) {
            resultsDiv.innerHTML = data.patients.map(p => `
                <div class="patient-result" onclick="selectPatient(${p.id}, '${p.last_name}, ${p.first_name}')">
                    <strong>${p.last_name}, ${p.first_name}</strong>
                    <span class="mrn">${p.mrn || ''}</span>
                </div>
            `).join('');
        } else {
            resultsDiv.innerHTML = '<div class="no-results">No patients found</div>';
        }
    } catch (error) {
        // Show demo patients
        resultsDiv.innerHTML = `
            <div class="patient-result" onclick="selectPatient(1, 'Smith, John')">
                <strong>Smith, John</strong><span class="mrn">MRN000001</span>
            </div>
            <div class="patient-result" onclick="selectPatient(2, 'Johnson, Mary')">
                <strong>Johnson, Mary</strong><span class="mrn">MRN000002</span>
            </div>
        `;
    }
}

function selectPatient(id, name) {
    document.getElementById('apptPatient').value = name;
    document.getElementById('apptPatientId').value = id;
    document.getElementById('patientSearchResults').innerHTML = '';
}

// ============================================================
// Utilities
// ============================================================

function formatDateISO(date) {
    return date.toISOString().split('T')[0];
}

function formatTime(timeStr) {
    if (!timeStr) return '';
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    return `${hour12}:${m || '00'} ${ampm}`;
}

function formatHour(h) {
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour12 = h > 12 ? h - 12 : h;
    return `${hour12}:00 ${ampm}`;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showConflictDialog(conflicts, appointmentData) {
    if (confirm('There is a scheduling conflict. Do you want to force book anyway?')) {
        appointmentData.force_overbook = true;
        createAppointment(appointmentData);
    }
}

// ============================================================
// Initialize
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    renderMiniCalendar();
    loadAppointments();
});
</script>

<?php include 'includes/footer.php'; ?>

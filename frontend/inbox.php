<?php
/**
 * Openspace EHR - In Basket (Inbox)
 * Messaging and task management
 */

$page_title = "In Basket - Openspace EHR";
require_once 'includes/header.php';

// Check authentication
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: login.php');
    exit;
}

// Demo messages data
$messages = [
    [
        'id' => 1,
        'type' => 'result',
        'from' => 'Lab System',
        'subject' => 'Critical Lab Result - Smith, John',
        'preview' => 'Potassium level 6.2 mEq/L (Critical High)',
        'date' => date('Y-m-d H:i', strtotime('-1 hour')),
        'priority' => 'critical',
        'read' => false,
        'patient_id' => 1
    ],
    [
        'id' => 2,
        'type' => 'message',
        'from' => 'Dr. Sarah Smith',
        'subject' => 'Patient Consultation Request',
        'preview' => 'Would like your opinion on the cardiology consult for Johnson, Mary...',
        'date' => date('Y-m-d H:i', strtotime('-2 hours')),
        'priority' => 'normal',
        'read' => false,
        'patient_id' => 2
    ],
    [
        'id' => 3,
        'type' => 'rx_request',
        'from' => 'CVS Pharmacy',
        'subject' => 'Refill Request - Lisinopril 10mg',
        'preview' => 'Patient Williams, Robert is requesting a refill for Lisinopril 10mg #90',
        'date' => date('Y-m-d H:i', strtotime('-3 hours')),
        'priority' => 'normal',
        'read' => false,
        'patient_id' => 3
    ],
    [
        'id' => 4,
        'type' => 'order',
        'from' => 'Nursing',
        'subject' => 'Order Clarification Needed',
        'preview' => 'Please clarify PRN frequency for morphine order on Davis, Linda',
        'date' => date('Y-m-d H:i', strtotime('-4 hours')),
        'priority' => 'high',
        'read' => true,
        'patient_id' => 4
    ],
    [
        'id' => 5,
        'type' => 'staff',
        'from' => 'HR Department',
        'subject' => 'Mandatory Training Reminder',
        'preview' => 'HIPAA refresher training is due by end of month',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'priority' => 'normal',
        'read' => true,
        'patient_id' => null
    ],
    [
        'id' => 6,
        'type' => 'result',
        'from' => 'Radiology',
        'subject' => 'Imaging Results Available - Wilson, James',
        'preview' => 'Chest X-ray results are available for review',
        'date' => date('Y-m-d', strtotime('-1 day')),
        'priority' => 'normal',
        'read' => true,
        'patient_id' => 5
    ],
];

// Filter categories
$categories = [
    'all' => ['label' => 'All Messages', 'icon' => 'fa-inbox', 'count' => count($messages)],
    'result' => ['label' => 'Results', 'icon' => 'fa-flask', 'count' => 2],
    'message' => ['label' => 'Messages', 'icon' => 'fa-envelope', 'count' => 1],
    'rx_request' => ['label' => 'Rx Requests', 'icon' => 'fa-prescription', 'count' => 1],
    'order' => ['label' => 'Orders', 'icon' => 'fa-file-medical', 'count' => 1],
    'staff' => ['label' => 'Staff Messages', 'icon' => 'fa-users', 'count' => 1],
];

$current_filter = $_GET['filter'] ?? 'all';
$unread_count = count(array_filter($messages, fn($m) => !$m['read']));
?>

<style>
.inbox-container {
    display: flex;
    height: calc(100vh - 100px);
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.inbox-sidebar {
    width: 200px;
    background: #f5f8fa;
    border-right: 1px solid #ddd;
    padding: 15px 0;
}

.inbox-sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

.inbox-sidebar-item:hover {
    background: #e8f0f4;
}

.inbox-sidebar-item.active {
    background: #1a4a5e;
    color: white;
}

.inbox-sidebar-item i {
    width: 20px;
    text-align: center;
}

.inbox-sidebar-item .count {
    margin-left: auto;
    background: #ddd;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
}

.inbox-sidebar-item.active .count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.inbox-list {
    flex: 1;
    overflow-y: auto;
    border-right: 1px solid #ddd;
}

.inbox-header {
    padding: 15px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
}

.inbox-header h2 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

.inbox-actions {
    display: flex;
    gap: 8px;
}

.inbox-actions button {
    padding: 6px 12px;
    border: 1px solid #ccc;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.inbox-actions button:hover {
    background: #f0f0f0;
}

.message-item {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    display: flex;
    gap: 12px;
}

.message-item:hover {
    background: #f8fafc;
}

.message-item.unread {
    background: #f0f8ff;
    border-left: 3px solid #1a4a5e;
}

.message-item.selected {
    background: #e3f2fd;
}

.message-checkbox {
    margin-top: 3px;
}

.message-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.message-icon.result { background: #e3f2fd; color: #1565c0; }
.message-icon.message { background: #e8f5e9; color: #388e3c; }
.message-icon.rx_request { background: #fff3e0; color: #ef6c00; }
.message-icon.order { background: #f3e5f5; color: #7b1fa2; }
.message-icon.staff { background: #e0f2f1; color: #00695c; }

.message-content {
    flex: 1;
    min-width: 0;
}

.message-from {
    font-weight: 600;
    font-size: 13px;
    color: #333;
}

.message-subject {
    font-size: 13px;
    color: #333;
    margin: 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-item.unread .message-subject {
    font-weight: 600;
}

.message-preview {
    font-size: 12px;
    color: #666;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-meta {
    text-align: right;
    flex-shrink: 0;
    min-width: 70px;
}

.message-date {
    font-size: 11px;
    color: #888;
}

.message-priority {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 4px;
}

.message-priority.critical { background: #ffebee; color: #c62828; }
.message-priority.high { background: #fff3e0; color: #ef6c00; }
.message-priority.normal { display: none; }

.message-detail {
    width: 400px;
    background: white;
    padding: 20px;
    overflow-y: auto;
}

.message-detail-header {
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
    margin-bottom: 15px;
}

.message-detail-subject {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.message-detail-meta {
    font-size: 12px;
    color: #666;
}

.message-detail-meta span {
    display: block;
    margin-bottom: 4px;
}

.message-detail-body {
    font-size: 14px;
    line-height: 1.6;
    color: #333;
}

.message-detail-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    display: flex;
    gap: 10px;
}

.detail-btn {
    padding: 8px 16px;
    border: 1px solid #1a4a5e;
    background: white;
    color: #1a4a5e;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-btn:hover {
    background: #f0f8ff;
}

.detail-btn.primary {
    background: #1a4a5e;
    color: white;
}

.detail-btn.primary:hover {
    background: #0d3545;
}

.empty-detail {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #888;
}

.empty-detail i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Compose Modal */
.compose-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.compose-modal.show {
    display: flex;
}

.compose-box {
    background: white;
    border-radius: 8px;
    width: 600px;
    max-height: 80vh;
    overflow: hidden;
}

.compose-header {
    background: linear-gradient(to bottom, #1a4a5e, #0d3545);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.compose-header h3 {
    margin: 0;
    font-size: 16px;
}

.compose-close {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.compose-body {
    padding: 20px;
}

.compose-field {
    margin-bottom: 15px;
}

.compose-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 13px;
}

.compose-field input,
.compose-field select,
.compose-field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.compose-field textarea {
    height: 150px;
    resize: vertical;
}

.compose-footer {
    padding: 15px 20px;
    background: #f5f5f5;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div class="inbox-container">
    <div class="inbox-sidebar">
        <a href="?filter=all" class="inbox-sidebar-item <?php echo $current_filter === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-inbox"></i>
            <span>All Messages</span>
            <?php if ($unread_count > 0): ?>
            <span class="count"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <?php foreach ($categories as $key => $cat): if ($key === 'all') continue; ?>
        <a href="?filter=<?php echo $key; ?>" class="inbox-sidebar-item <?php echo $current_filter === $key ? 'active' : ''; ?>">
            <i class="fas <?php echo $cat['icon']; ?>"></i>
            <span><?php echo $cat['label']; ?></span>
            <span class="count"><?php echo $cat['count']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    
    <div class="inbox-list">
        <div class="inbox-header">
            <h2>
                <i class="fas <?php echo $categories[$current_filter]['icon']; ?>"></i>
                <?php echo $categories[$current_filter]['label']; ?>
            </h2>
            <div class="inbox-actions">
                <button onclick="showCompose()">
                    <i class="fas fa-pen"></i> Compose
                </button>
                <button onclick="refreshInbox()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <?php 
        $filtered_messages = $current_filter === 'all' 
            ? $messages 
            : array_filter($messages, fn($m) => $m['type'] === $current_filter);
        
        foreach ($filtered_messages as $msg): 
        ?>
        <div class="message-item <?php echo $msg['read'] ? '' : 'unread'; ?>" 
             onclick="selectMessage(<?php echo $msg['id']; ?>)" data-id="<?php echo $msg['id']; ?>">
            <input type="checkbox" class="message-checkbox" onclick="event.stopPropagation()">
            <div class="message-icon <?php echo $msg['type']; ?>">
                <i class="fas <?php echo $categories[$msg['type']]['icon']; ?>"></i>
            </div>
            <div class="message-content">
                <div class="message-from"><?php echo htmlspecialchars($msg['from']); ?></div>
                <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                <div class="message-preview"><?php echo htmlspecialchars($msg['preview']); ?></div>
            </div>
            <div class="message-meta">
                <div class="message-date"><?php echo date('M j, g:i A', strtotime($msg['date'])); ?></div>
                <div class="message-priority <?php echo $msg['priority']; ?>"><?php echo $msg['priority']; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="message-detail" id="messageDetail">
        <div class="empty-detail">
            <i class="fas fa-envelope-open-text"></i>
            <p>Select a message to view</p>
        </div>
    </div>
</div>

<!-- Compose Modal -->
<div class="compose-modal" id="composeModal">
    <div class="compose-box">
        <div class="compose-header">
            <h3><i class="fas fa-pen"></i> New Message</h3>
            <button class="compose-close" onclick="hideCompose()">×</button>
        </div>
        <div class="compose-body">
            <div class="compose-field">
                <label>To:</label>
                <input type="text" id="composeTo" placeholder="Search for recipient...">
            </div>
            <div class="compose-field">
                <label>Patient (optional):</label>
                <input type="text" id="composePatient" placeholder="Link to patient...">
            </div>
            <div class="compose-field">
                <label>Subject:</label>
                <input type="text" id="composeSubject">
            </div>
            <div class="compose-field">
                <label>Message:</label>
                <textarea id="composeBody"></textarea>
            </div>
        </div>
        <div class="compose-footer">
            <button class="btn btn-secondary" onclick="hideCompose()">Cancel</button>
            <button class="btn btn-primary" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </div>
    </div>
</div>

<script>
// Message data for detail view
const messagesData = <?php echo json_encode($messages); ?>;

function selectMessage(id) {
    // Mark as selected in list
    document.querySelectorAll('.message-item').forEach(item => {
        item.classList.remove('selected');
        if (item.dataset.id == id) {
            item.classList.add('selected');
            item.classList.remove('unread');
        }
    });
    
    // Find message data
    const msg = messagesData.find(m => m.id === id);
    if (!msg) return;
    
    // Show detail
    const detail = document.getElementById('messageDetail');
    detail.innerHTML = `
        <div class="message-detail-header">
            <div class="message-detail-subject">${msg.subject}</div>
            <div class="message-detail-meta">
                <span><strong>From:</strong> ${msg.from}</span>
                <span><strong>Date:</strong> ${new Date(msg.date).toLocaleString()}</span>
                ${msg.patient_id ? `<span><strong>Patient:</strong> <a href="patient-chart.php?id=${msg.patient_id}">View Chart</a></span>` : ''}
            </div>
        </div>
        <div class="message-detail-body">
            <p>${msg.preview}</p>
            <p>This is the full message content. In a production system, this would contain the complete message text, any attachments, and related clinical information.</p>
        </div>
        <div class="message-detail-actions">
            <button class="detail-btn" onclick="replyToMessage(${msg.id})">
                <i class="fas fa-reply"></i> Reply
            </button>
            <button class="detail-btn" onclick="forwardMessage(${msg.id})">
                <i class="fas fa-share"></i> Forward
            </button>
            ${msg.patient_id ? `
            <button class="detail-btn primary" onclick="window.location.href='patient-chart.php?id=${msg.patient_id}'">
                <i class="fas fa-user"></i> View Patient
            </button>
            ` : ''}
            <button class="detail-btn" onclick="archiveMessage(${msg.id})">
                <i class="fas fa-archive"></i> Archive
            </button>
        </div>
    `;
}

function showCompose() {
    document.getElementById('composeModal').classList.add('show');
}

function hideCompose() {
    document.getElementById('composeModal').classList.remove('show');
}

function sendMessage() {
    alert('Message sent! (Demo mode)');
    hideCompose();
}

function refreshInbox() {
    window.location.reload();
}

function replyToMessage(id) {
    showCompose();
    const msg = messagesData.find(m => m.id === id);
    if (msg) {
        document.getElementById('composeTo').value = msg.from;
        document.getElementById('composeSubject').value = 'RE: ' + msg.subject;
    }
}

function forwardMessage(id) {
    showCompose();
    const msg = messagesData.find(m => m.id === id);
    if (msg) {
        document.getElementById('composeSubject').value = 'FW: ' + msg.subject;
        document.getElementById('composeBody').value = '\n\n--- Forwarded Message ---\nFrom: ' + msg.from + '\nSubject: ' + msg.subject + '\n\n' + msg.preview;
    }
}

function archiveMessage(id) {
    alert('Message archived! (Demo mode)');
}

// Close compose on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideCompose();
    }
});

// Close compose on background click
document.getElementById('composeModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideCompose();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

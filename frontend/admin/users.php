<?php
/**
 * Openspace EHR - Admin Users Management
 */
$page_title = 'User Management - Openspace EHR';

require_once '../includes/config.php';

// Ensure user is logged in and is admin
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'] ?? [];
$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'administrator']);

if (!$is_admin) {
    header('Location: ../home.php');
    exit;
}

// Demo users data
$all_users = [
    ['id' => 1, 'username' => 'admin', 'name' => 'System Administrator', 'email' => 'admin@hospital.org', 'role' => 'Administrator', 'status' => 'Active', 'last_login' => '2025-01-10 08:30:00'],
    ['id' => 2, 'username' => 'drsmith', 'name' => 'Dr. John Smith', 'email' => 'jsmith@hospital.org', 'role' => 'Physician', 'status' => 'Active', 'last_login' => '2025-01-10 07:15:00'],
    ['id' => 3, 'username' => 'nursejones', 'name' => 'Sarah Jones, RN', 'email' => 'sjones@hospital.org', 'role' => 'Nurse', 'status' => 'Active', 'last_login' => '2025-01-10 06:45:00'],
    ['id' => 4, 'username' => 'drsandhu', 'name' => 'Dr. Priya Sandhu', 'email' => 'psandhu@hospital.org', 'role' => 'Physician', 'status' => 'Active', 'last_login' => '2025-01-09 16:20:00'],
    ['id' => 5, 'username' => 'drwilson', 'name' => 'Dr. Sarah Wilson', 'email' => 'swilson@hospital.org', 'role' => 'Physician', 'status' => 'Active', 'last_login' => '2025-01-10 09:00:00'],
    ['id' => 6, 'username' => 'pharmthompson', 'name' => 'Mark Thompson, PharmD', 'email' => 'mthompson@hospital.org', 'role' => 'Pharmacist', 'status' => 'Active', 'last_login' => '2025-01-09 14:30:00'],
    ['id' => 7, 'username' => 'lab_tech1', 'name' => 'Jennifer Lee', 'email' => 'jlee@hospital.org', 'role' => 'Lab Tech', 'status' => 'Active', 'last_login' => '2025-01-10 05:00:00'],
    ['id' => 8, 'username' => 'radtech1', 'name' => 'Robert Martinez', 'email' => 'rmartinez@hospital.org', 'role' => 'Rad Tech', 'status' => 'Active', 'last_login' => '2025-01-08 11:45:00'],
    ['id' => 9, 'username' => 'regclerk', 'name' => 'Amanda White', 'email' => 'awhite@hospital.org', 'role' => 'Registration', 'status' => 'Inactive', 'last_login' => '2024-12-20 09:15:00'],
];

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Apply filters
$users = array_filter($all_users, function($u) use ($search, $role_filter, $status_filter) {
    // Search filter
    if ($search) {
        $search_lower = strtolower($search);
        $matches_search = 
            strpos(strtolower($u['name']), $search_lower) !== false ||
            strpos(strtolower($u['username']), $search_lower) !== false ||
            strpos(strtolower($u['email']), $search_lower) !== false;
        if (!$matches_search) return false;
    }
    
    // Role filter
    if ($role_filter && $u['role'] !== $role_filter) {
        return false;
    }
    
    // Status filter  
    if ($status_filter && $u['status'] !== $status_filter) {
        return false;
    }
    
    return true;
});

// Get unique roles for filter dropdown
$roles = array_unique(array_column($all_users, 'role'));
sort($roles);

// Include admin header
include 'includes/admin-header.php';
?>
<style>
    /* User Management specific styles */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .page-header h1 { font-size: 24px; color: #1a4a5e; display: flex; align-items: center; gap: 10px; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .filter-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .search-box { position: relative; }
    .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }
    .search-box input { padding: 10px 10px 10px 38px; border: 2px solid #d0d8e0; border-radius: 6px; width: 250px; }
    .filter-select { padding: 10px 15px; border: 2px solid #d0d8e0; border-radius: 6px; }
    .results-count { color: #666; font-size: 13px; }
    .users-table { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
    .users-table table { width: 100%; border-collapse: collapse; }
    .users-table th { background: #f5f8fa; text-align: left; padding: 15px; font-weight: 600; color: #1a4a5e; border-bottom: 2px solid #e0e0e0; }
    .users-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
    .users-table tr:hover { background: #fafbfc; }
    .user-info { display: flex; align-items: center; gap: 12px; }
    .user-avatar { width: 40px; height: 40px; background: #1a4a5e; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
    .user-name { font-weight: 500; }
    .user-username { font-size: 12px; color: #888; }
    .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .role-badge.admin { background: #fce4ec; color: #c2185b; }
    .role-badge.physician { background: #e3f2fd; color: #1565c0; }
    .role-badge.nurse { background: #e8f5e9; color: #2e7d32; }
    .role-badge.pharmacist { background: #fff3e0; color: #ef6c00; }
    .role-badge.default { background: #f5f5f5; color: #666; }
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-badge.active { background: #e8f5e9; color: #2e7d32; }
    .status-badge.inactive { background: #f5f5f5; color: #888; }
    .action-buttons { display: flex; gap: 8px; }
    .action-buttons button { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
    .btn-edit { background: #e3f2fd; color: #1565c0; }
    .btn-reset { background: #fff3e0; color: #ef6c00; }
    .btn-delete { background: #ffebee; color: #c62828; }
    .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; }
    .btn-primary { background: #1a4a5e; color: white; }
    .btn-secondary { background: #e0e0e0; color: #333; }
    .btn-sm { padding: 6px 12px; font-size: 12px; }
    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal.show { display: flex; }
    .modal-content { background: white; border-radius: 8px; width: 500px; max-width: 90%; max-height: 90vh; overflow: hidden; }
    .modal-header { background: linear-gradient(to bottom, #1a4a5e, #0d3545); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h2 { margin: 0; font-size: 16px; }
    .close-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }
    .modal-body { padding: 20px; max-height: 60vh; overflow-y: auto; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px; border: 2px solid #d0d8e0; border-radius: 4px; font-size: 14px; }
    .modal-footer { padding: 15px 20px; background: #f5f8fa; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px; }
    .no-results { text-align: center; padding: 60px 20px; color: #888; }
    .no-results i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
</style>

        <div class="page-header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <button class="btn btn-primary" onclick="openAddUserModal()">
                <i class="fas fa-user-plus"></i> Add New User
            </button>
        </div>
        
        <div class="toolbar">
            <form method="GET" class="filter-form" id="filterForm">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search users..." id="userSearch" 
                           value="<?php echo htmlspecialchars($search); ?>" onkeyup="debounceFilter()">
                </div>
                <select class="filter-select" name="role" id="roleFilter" onchange="submitFilter()">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $role_filter === $r ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select class="filter-select" name="status" id="statusFilter" onchange="submitFilter()">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <?php if ($search || $role_filter || $status_filter): ?>
                <a href="users.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
                <?php endif; ?>
            </form>
            <div class="results-count">
                Showing <?php echo count($users); ?> of <?php echo count($all_users); ?> users
            </div>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php foreach ($users as $u): ?>
                    <tr data-name="<?php echo strtolower($u['name']); ?>" data-role="<?php echo $u['role']; ?>" data-status="<?php echo $u['status']; ?>">
                        <td>
                            <div class="user-info">
                                <div class="user-avatar"><i class="fas fa-user"></i></div>
                                <div>
                                    <div class="user-name"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div class="user-username">@<?php echo htmlspecialchars($u['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="role-badge"><?php echo htmlspecialchars($u['role']); ?></span></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($u['status']); ?>">
                                <?php echo htmlspecialchars($u['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($u['last_login'])); ?></td>
                        <td>
                            <div class="actions">
                                <button class="action-btn edit" onclick="editUser(<?php echo $u['id']; ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn" onclick="resetPassword(<?php echo $u['id']; ?>)" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="action-btn delete" onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="userModalTitle">Add New User</h3>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" id="userFirstName" placeholder="First name">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" id="userLastName" placeholder="Last name">
                    </div>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="userUsername" placeholder="Username">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="userEmail" placeholder="Email address">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role</label>
                        <select id="userRole">
                            <option value="Nurse">Nurse</option>
                            <option value="Physician">Physician</option>
                            <option value="Pharmacist">Pharmacist</option>
                            <option value="Lab Tech">Lab Tech</option>
                            <option value="Rad Tech">Rad Tech</option>
                            <option value="Registration">Registration</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="userStatus">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label>Temporary Password</label>
                    <input type="password" id="userPassword" placeholder="Set temporary password">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveUser()">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>

    <script>
        let filterTimeout = null;
        
        function debounceFilter() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(submitFilter, 500);
        }
        
        function submitFilter() {
            document.getElementById('filterForm').submit();
        }
        
        function openAddUserModal() {
            document.getElementById('userModalTitle').textContent = 'Add New User';
            document.getElementById('passwordGroup').style.display = 'block';
            // Clear form
            document.getElementById('userFirstName').value = '';
            document.getElementById('userLastName').value = '';
            document.getElementById('userUsername').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userModal').classList.add('show');
        }
        
        function editUser(userId) {
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('userModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
        }
        
        function saveUser() {
            alert('User saved successfully! (Demo)');
            closeModal();
        }
        
        function resetPassword(userId) {
            if (confirm('Send password reset email to this user?')) {
                alert('Password reset email sent! (Demo)');
            }
        }
        
        function confirmDelete(userId, name) {
            if (confirm('Are you sure you want to delete user "' + name + '"? This cannot be undone.')) {
                alert('User deleted! (Demo)');
            }
        }
        
        // Handle enter key in search
        document.getElementById('userSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitFilter();
            }
        });
        
        // Close modal on background click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
<?php include 'includes/admin-footer.php'; ?>
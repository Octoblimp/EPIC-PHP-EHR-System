<?php
/**
 * Permission & Role-Based Access Control System
 * 
 * Provides granular screen-level and action-level permission checking.
 * Integrates with audit.php for logging access attempts.
 */

// Role permission definitions (default permissions for each role)
$ROLE_PERMISSIONS = [
    'administrator' => [
        // All screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => true,
        'screens.reports' => true,
        'screens.billing' => true,
        'screens.admin' => true,
        'screens.settings' => true,
        // All patient access
        'patients.view' => true,
        'patients.create' => true,
        'patients.edit' => true,
        'patients.merge' => true,
        // All clinical
        'notes.view' => true,
        'notes.create' => true,
        'notes.sign' => true,
        'notes.amend' => true,
        // All medications
        'medications.view' => true,
        'medications.prescribe' => true,
        'medications.administer' => true,
        'medications.controlled' => true,
        // All scheduling
        'appointments.view' => true,
        'appointments.create' => true,
        'appointments.edit' => true,
        'schedules.manage' => true,
        // All billing
        'billing.view' => true,
        'billing.create' => true,
        'billing.submit_claims' => true,
        'billing.post_payments' => true,
        // All admin
        'admin.access' => true,
        'admin.users' => true,
        'admin.roles' => true,
        'admin.audit_log' => true,
        'admin.system_config' => true,
        // All lab
        'lab.view_results' => true,
        'lab.order_tests' => true,
        'lab.enter_results' => true,
        'lab.manage_specimens' => true,
        // All reports
        'reports.clinical' => true,
        'reports.operational' => true,
        'reports.financial' => true,
        'reports.export' => true,
    ],
    
    'admin' => null, // Alias for administrator, will be resolved at runtime
    
    'physician' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => true,
        'screens.reports' => true,
        'screens.billing' => false,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access
        'patients.view' => true,
        'patients.create' => true,
        'patients.edit' => true,
        'patients.merge' => false,
        // Clinical
        'notes.view' => true,
        'notes.create' => true,
        'notes.sign' => true,
        'notes.amend' => true,
        // Medications - full prescribing
        'medications.view' => true,
        'medications.prescribe' => true,
        'medications.administer' => false,
        'medications.controlled' => true,
        // Scheduling
        'appointments.view' => true,
        'appointments.create' => true,
        'appointments.edit' => true,
        'schedules.manage' => false,
        // Billing - view only
        'billing.view' => true,
        'billing.create' => false,
        'billing.submit_claims' => false,
        'billing.post_payments' => false,
        // Admin - none
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab
        'lab.view_results' => true,
        'lab.order_tests' => true,
        'lab.enter_results' => false,
        'lab.manage_specimens' => false,
        // Reports
        'reports.clinical' => true,
        'reports.operational' => true,
        'reports.financial' => false,
        'reports.export' => true,
    ],
    
    'nurse' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => true,
        'screens.reports' => false,
        'screens.billing' => false,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access
        'patients.view' => true,
        'patients.create' => false,
        'patients.edit' => true,
        'patients.merge' => false,
        // Clinical
        'notes.view' => true,
        'notes.create' => true,
        'notes.sign' => true,
        'notes.amend' => false,
        // Medications - administer only
        'medications.view' => true,
        'medications.prescribe' => false,
        'medications.administer' => true,
        'medications.controlled' => false,
        // Scheduling
        'appointments.view' => true,
        'appointments.create' => true,
        'appointments.edit' => true,
        'schedules.manage' => false,
        // Billing - none
        'billing.view' => false,
        'billing.create' => false,
        'billing.submit_claims' => false,
        'billing.post_payments' => false,
        // Admin - none
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab
        'lab.view_results' => true,
        'lab.order_tests' => false,
        'lab.enter_results' => false,
        'lab.manage_specimens' => false,
        // Reports
        'reports.clinical' => true,
        'reports.operational' => false,
        'reports.financial' => false,
        'reports.export' => false,
    ],
    
    'medical_assistant' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => false,
        'screens.reports' => false,
        'screens.billing' => false,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access
        'patients.view' => true,
        'patients.create' => false,
        'patients.edit' => true,
        'patients.merge' => false,
        // Clinical - limited
        'notes.view' => true,
        'notes.create' => true,
        'notes.sign' => false,
        'notes.amend' => false,
        // Medications - view only
        'medications.view' => true,
        'medications.prescribe' => false,
        'medications.administer' => false,
        'medications.controlled' => false,
        // Scheduling
        'appointments.view' => true,
        'appointments.create' => true,
        'appointments.edit' => true,
        'schedules.manage' => false,
        // Billing/Admin - none
        'billing.view' => false,
        'billing.create' => false,
        'billing.submit_claims' => false,
        'billing.post_payments' => false,
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab
        'lab.view_results' => true,
        'lab.order_tests' => false,
        'lab.enter_results' => false,
        'lab.manage_specimens' => false,
        // Reports
        'reports.clinical' => false,
        'reports.operational' => false,
        'reports.financial' => false,
        'reports.export' => false,
    ],
    
    'front_desk' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => false,
        'screens.reports' => false,
        'screens.billing' => false,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access
        'patients.view' => true,
        'patients.create' => true,
        'patients.edit' => true,
        'patients.merge' => false,
        // Clinical - none
        'notes.view' => false,
        'notes.create' => false,
        'notes.sign' => false,
        'notes.amend' => false,
        // Medications - none
        'medications.view' => false,
        'medications.prescribe' => false,
        'medications.administer' => false,
        'medications.controlled' => false,
        // Scheduling - full
        'appointments.view' => true,
        'appointments.create' => true,
        'appointments.edit' => true,
        'schedules.manage' => false,
        // Billing - limited view
        'billing.view' => true,
        'billing.create' => false,
        'billing.submit_claims' => false,
        'billing.post_payments' => false,
        // Admin - none
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab
        'lab.view_results' => false,
        'lab.order_tests' => false,
        'lab.enter_results' => false,
        'lab.manage_specimens' => false,
        // Reports
        'reports.clinical' => false,
        'reports.operational' => true,
        'reports.financial' => false,
        'reports.export' => false,
    ],
    
    'billing' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => true,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => false,
        'screens.reports' => true,
        'screens.billing' => true,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access - limited
        'patients.view' => true,
        'patients.create' => false,
        'patients.edit' => true, // Insurance info
        'patients.merge' => false,
        // Clinical - limited view
        'notes.view' => true,
        'notes.create' => false,
        'notes.sign' => false,
        'notes.amend' => false,
        // Medications - none
        'medications.view' => false,
        'medications.prescribe' => false,
        'medications.administer' => false,
        'medications.controlled' => false,
        // Scheduling - view
        'appointments.view' => true,
        'appointments.create' => false,
        'appointments.edit' => false,
        'schedules.manage' => false,
        // Billing - full
        'billing.view' => true,
        'billing.create' => true,
        'billing.submit_claims' => true,
        'billing.post_payments' => true,
        // Admin - none
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab
        'lab.view_results' => false,
        'lab.order_tests' => false,
        'lab.enter_results' => false,
        'lab.manage_specimens' => false,
        // Reports
        'reports.clinical' => false,
        'reports.operational' => true,
        'reports.financial' => true,
        'reports.export' => true,
    ],
    
    'lab_tech' => [
        // Screens
        'screens.home' => true,
        'screens.schedule' => false,
        'screens.patient_search' => true,
        'screens.patient_chart' => true,
        'screens.inbox' => true,
        'screens.orders' => true,
        'screens.reports' => false,
        'screens.billing' => false,
        'screens.admin' => false,
        'screens.settings' => true,
        // Patient access - limited
        'patients.view' => true,
        'patients.create' => false,
        'patients.edit' => false,
        'patients.merge' => false,
        // Clinical - limited
        'notes.view' => true,
        'notes.create' => false,
        'notes.sign' => false,
        'notes.amend' => false,
        // Medications - none
        'medications.view' => false,
        'medications.prescribe' => false,
        'medications.administer' => false,
        'medications.controlled' => false,
        // Scheduling
        'appointments.view' => true,
        'appointments.create' => false,
        'appointments.edit' => false,
        'schedules.manage' => false,
        // Billing - none
        'billing.view' => false,
        'billing.create' => false,
        'billing.submit_claims' => false,
        'billing.post_payments' => false,
        // Admin - none
        'admin.access' => false,
        'admin.users' => false,
        'admin.roles' => false,
        'admin.audit_log' => false,
        'admin.system_config' => false,
        // Lab - full
        'lab.view_results' => true,
        'lab.order_tests' => false,
        'lab.enter_results' => true,
        'lab.manage_specimens' => true,
        // Reports
        'reports.clinical' => false,
        'reports.operational' => false,
        'reports.financial' => false,
        'reports.export' => false,
    ],
];

// Screen to permission mapping
$SCREEN_PERMISSIONS = [
    'home.php' => 'screens.home',
    'schedule.php' => 'screens.schedule',
    'patient-search.php' => 'screens.patient_search',
    'patient.php' => 'screens.patient_chart',
    'inbox.php' => 'screens.inbox',
    'orders.php' => 'screens.orders',
    'reports.php' => 'screens.reports',
    'billing.php' => 'screens.billing',
    'admin/index.php' => 'screens.admin',
    'admin/users.php' => 'screens.admin',
    'admin/roles.php' => 'screens.admin',
    'admin/audit-log.php' => 'screens.admin',
    'admin/db-updater.php' => 'screens.admin',
    'settings.php' => 'screens.settings',
    'profile.php' => 'screens.settings',
];

/**
 * Get the user's role, normalized to lowercase
 */
function getUserRole() {
    $user = $_SESSION['user'] ?? [];
    $role = strtolower($user['role'] ?? 'guest');
    
    // Handle role aliases
    if ($role === 'admin') {
        $role = 'administrator';
    }
    
    return $role;
}

/**
 * Get permissions for a role
 */
function getRolePermissions($role) {
    global $ROLE_PERMISSIONS;
    
    // Handle role aliases
    if ($role === 'admin') {
        $role = 'administrator';
    }
    
    return $ROLE_PERMISSIONS[$role] ?? [];
}

/**
 * Check if user has a specific permission
 * 
 * @param string $permission The permission code (e.g., 'screens.home', 'patients.view')
 * @param bool $logAttempt Whether to log the access attempt
 * @return bool
 */
function hasPermission($permission, $logAttempt = false) {
    $role = getUserRole();
    $permissions = getRolePermissions($role);
    
    // Check for custom user overrides in session
    $userOverrides = $_SESSION['user_permissions'] ?? [];
    if (isset($userOverrides[$permission])) {
        $hasAccess = $userOverrides[$permission];
    } else {
        $hasAccess = $permissions[$permission] ?? false;
    }
    
    // Log the access attempt if requested
    if ($logAttempt && function_exists('logAudit')) {
        logAudit('permission_check', [
            'permission' => $permission,
            'role' => $role,
            'granted' => $hasAccess,
        ]);
    }
    
    return $hasAccess;
}

/**
 * Check if user can access a specific screen
 * 
 * @param string $screenFile The PHP file name (e.g., 'home.php')
 * @return bool
 */
function canAccessScreen($screenFile) {
    global $SCREEN_PERMISSIONS;
    
    $permission = $SCREEN_PERMISSIONS[$screenFile] ?? null;
    
    if ($permission === null) {
        // Unknown screen, allow access by default (for backwards compatibility)
        return true;
    }
    
    return hasPermission($permission);
}

/**
 * Require a specific permission, redirect if not authorized
 * 
 * @param string $permission The required permission
 * @param string $redirectUrl Where to redirect if unauthorized
 */
function requirePermission($permission, $redirectUrl = '/home.php') {
    if (!hasPermission($permission, true)) {
        // Log the unauthorized access attempt
        if (function_exists('logAudit')) {
            logAudit('unauthorized_access', [
                'permission' => $permission,
                'url' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
        }
        
        // Set error message
        $_SESSION['error_message'] = 'You do not have permission to access this page.';
        
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Require screen access permission, redirect if not authorized
 * 
 * @param string $screenFile The PHP file name
 */
function requireScreenAccess($screenFile = null) {
    global $SCREEN_PERMISSIONS;
    
    // Auto-detect screen file if not provided
    if ($screenFile === null) {
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $screenFile = basename($scriptPath);
        
        // Check for admin subdirectory
        if (strpos($scriptPath, '/admin/') !== false) {
            $screenFile = 'admin/' . $screenFile;
        }
    }
    
    $permission = $SCREEN_PERMISSIONS[$screenFile] ?? null;
    
    if ($permission !== null) {
        requirePermission($permission);
    }
}

/**
 * Get list of screens user can access (for navigation filtering)
 * 
 * @return array Array of accessible screen files
 */
function getAccessibleScreens() {
    global $SCREEN_PERMISSIONS;
    
    $accessible = [];
    
    foreach ($SCREEN_PERMISSIONS as $screen => $permission) {
        if (hasPermission($permission)) {
            $accessible[] = $screen;
        }
    }
    
    return $accessible;
}

/**
 * Get all permissions for current user (for UI display)
 * 
 * @return array
 */
function getCurrentUserPermissions() {
    $role = getUserRole();
    $rolePermissions = getRolePermissions($role);
    $userOverrides = $_SESSION['user_permissions'] ?? [];
    
    return array_merge($rolePermissions, $userOverrides);
}

/**
 * Set a custom permission override for the current user
 * (typically loaded from database on login)
 * 
 * @param string $permission
 * @param bool $value
 */
function setUserPermissionOverride($permission, $value) {
    if (!isset($_SESSION['user_permissions'])) {
        $_SESSION['user_permissions'] = [];
    }
    $_SESSION['user_permissions'][$permission] = $value;
}

/**
 * Load user permission overrides from database
 * Call this after login to load custom permissions
 * 
 * @param int $userId
 */
function loadUserPermissionOverrides($userId) {
    // This would typically load from a user_permissions table
    // For now, use session storage as placeholder
    // 
    // Example database query:
    // SELECT permission, granted FROM user_permissions WHERE user_id = $userId
    //
    // foreach ($results as $row) {
    //     setUserPermissionOverride($row['permission'], $row['granted']);
    // }
}

/**
 * Render an "Access Denied" message (for AJAX or partial page loads)
 */
function renderAccessDenied() {
    http_response_code(403);
    echo '<div class="alert alert-danger" style="padding: 20px; margin: 20px; border-radius: 8px; background: #fee; border: 1px solid #c00; color: #900;">
        <h4><i class="bi bi-shield-x"></i> Access Denied</h4>
        <p>You do not have permission to access this resource. If you believe this is an error, please contact your system administrator.</p>
    </div>';
}

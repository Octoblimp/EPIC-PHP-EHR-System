"""
Admin Routes - User management, roles, departments, themes, audit logs
"""
from flask import Blueprint, request, jsonify
from models import db
from models.user import User
from models.patient import Patient
from models.theme import Organization, Department, Bed, Theme, SystemSetting
from models.role import Role, Permission, RolePermission, AuditLog, PatientList, DEFAULT_ROLES, DEFAULT_PERMISSIONS
from datetime import datetime
import json
import os

admin_bp = Blueprint('admin', __name__)


# ============== Setup / Demo Data ==============

@admin_bp.route('/setup/seed-demo-data', methods=['POST'])
def seed_demo_data():
    """Seed the database with demo/sample data.
    
    This endpoint is called by the frontend setup wizard when the user
    opts to install sample data. Requires a setup token for security.
    """
    data = request.get_json() or {}
    
    # Verify setup token (passed from setup.php)
    setup_token = data.get('setup_token')
    expected_token = os.environ.get('SETUP_TOKEN', '')
    
    # Allow if: valid token provided, OR no token set (first-time setup), OR from localhost
    is_localhost = request.remote_addr in ['127.0.0.1', '::1', 'localhost']
    token_valid = (setup_token and setup_token == expected_token) if expected_token else True
    
    if not (is_localhost or token_valid):
        return jsonify({
            'success': False,
            'error': 'Unauthorized. Setup can only be performed from localhost or with valid token.'
        }), 403
    
    # Check if data already exists
    if Patient.query.first() is not None:
        return jsonify({
            'success': False,
            'error': 'Database already contains data. Demo data can only be seeded into an empty database.',
            'has_data': True
        }), 400
    
    try:
        # Import and run the seed function from app.py
        from app import get_seed_function
        seed_database = get_seed_function()
        seed_database()
        
        return jsonify({
            'success': True,
            'message': 'Demo data seeded successfully!'
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({
            'success': False,
            'error': f'Failed to seed demo data: {str(e)}'
        }), 500


@admin_bp.route('/setup/check-status', methods=['GET'])
def check_setup_status():
    """Check if the system has been set up (has data)"""
    has_users = User.query.first() is not None
    has_patients = Patient.query.first() is not None
    
    return jsonify({
        'success': True,
        'is_initialized': has_users or has_patients,
        'has_users': has_users,
        'has_patients': has_patients
    })


# ============== User Management ==============

@admin_bp.route('/users', methods=['GET'])
def get_users():
    """Get all users with optional filters"""
    role_id = request.args.get('role_id')
    department_id = request.args.get('department_id')
    is_active = request.args.get('is_active', 'true').lower() == 'true'
    search = request.args.get('search', '')
    
    query = User.query
    
    if role_id:
        query = query.filter(User.role_id == role_id)
    if is_active is not None:
        query = query.filter(User.is_active == is_active)
    if search:
        query = query.filter(
            db.or_(
                User.username.ilike(f'%{search}%'),
                User.first_name.ilike(f'%{search}%'),
                User.last_name.ilike(f'%{search}%'),
                User.email.ilike(f'%{search}%')
            )
        )
    
    users = query.order_by(User.last_name, User.first_name).all()
    return jsonify([u.to_dict() for u in users])


@admin_bp.route('/users/<int:user_id>', methods=['GET'])
def get_user(user_id):
    """Get user by ID with full details"""
    user = User.query.get_or_404(user_id)
    user_dict = user.to_dict()
    
    # Add role details
    if user.role:
        user_dict['role'] = user.role.to_dict()
    
    return jsonify(user_dict)


@admin_bp.route('/users', methods=['POST'])
def create_user():
    """Create a new user"""
    data = request.get_json()
    
    # Check for existing username
    if User.query.filter_by(username=data['username']).first():
        return jsonify({'error': 'Username already exists'}), 400
    
    user = User(
        username=data['username'],
        email=data.get('email'),
        first_name=data.get('first_name'),
        last_name=data.get('last_name'),
        role_id=data.get('role_id'),
        title=data.get('title'),
        credentials=data.get('credentials'),
        npi=data.get('npi'),
        phone=data.get('phone'),
        pager=data.get('pager'),
        is_active=data.get('is_active', True)
    )
    
    # Set password (in production, use proper hashing)
    if data.get('password'):
        user.password_hash = data['password']  # Should hash in production
    
    db.session.add(user)
    db.session.commit()
    
    # Log action
    log_audit('create', 'user', user.id, f'Created user {user.username}')
    
    return jsonify(user.to_dict()), 201


@admin_bp.route('/users/<int:user_id>', methods=['PUT'])
def update_user(user_id):
    """Update user"""
    user = User.query.get_or_404(user_id)
    data = request.get_json()
    
    old_data = user.to_dict()
    
    # Update fields
    for field in ['email', 'first_name', 'last_name', 'role_id', 'title', 
                  'credentials', 'npi', 'phone', 'pager', 'is_active']:
        if field in data:
            setattr(user, field, data[field])
    
    if data.get('password'):
        user.password_hash = data['password']  # Should hash in production
    
    db.session.commit()
    
    # Log action
    log_audit('update', 'user', user.id, f'Updated user {user.username}',
              old_value=json.dumps(old_data), new_value=json.dumps(user.to_dict()))
    
    return jsonify(user.to_dict())


@admin_bp.route('/users/<int:user_id>', methods=['DELETE'])
def delete_user(user_id):
    """Deactivate user (soft delete)"""
    user = User.query.get_or_404(user_id)
    user.is_active = False
    db.session.commit()
    
    log_audit('delete', 'user', user.id, f'Deactivated user {user.username}')
    
    return jsonify({'message': 'User deactivated'})


@admin_bp.route('/users/<int:user_id>/reset-password', methods=['POST'])
def reset_user_password(user_id):
    """Reset user password"""
    user = User.query.get_or_404(user_id)
    data = request.get_json()
    
    # In production, send email with reset link or temporary password
    if data.get('new_password'):
        user.password_hash = data['new_password']  # Should hash
        db.session.commit()
    
    log_audit('update', 'user', user.id, f'Password reset for {user.username}')
    
    return jsonify({'message': 'Password reset successful'})


# ============== Role Management ==============

@admin_bp.route('/roles', methods=['GET'])
def get_roles():
    """Get all roles"""
    roles = Role.query.filter_by(is_active=True).order_by(Role.access_level.desc()).all()
    return jsonify([r.to_dict() for r in roles])


@admin_bp.route('/roles/<int:role_id>', methods=['GET'])
def get_role(role_id):
    """Get role with permissions"""
    role = Role.query.get_or_404(role_id)
    return jsonify(role.to_dict())


@admin_bp.route('/roles', methods=['POST'])
def create_role():
    """Create a new role"""
    data = request.get_json()
    
    role = Role(
        name=data['name'],
        display_name=data.get('display_name', data['name']),
        description=data.get('description'),
        role_type=data.get('role_type', 'clinical'),
        is_provider=data.get('is_provider', False),
        is_nurse=data.get('is_nurse', False),
        is_clinical_staff=data.get('is_clinical_staff', False),
        is_admin=data.get('is_admin', False),
        access_level=data.get('access_level', 1)
    )
    
    db.session.add(role)
    db.session.commit()
    
    # Add permissions if specified
    if data.get('permissions'):
        for perm_id in data['permissions']:
            rp = RolePermission(role_id=role.id, permission_id=perm_id)
            db.session.add(rp)
        db.session.commit()
    
    log_audit('create', 'role', role.id, f'Created role {role.name}')
    
    return jsonify(role.to_dict()), 201


@admin_bp.route('/roles/<int:role_id>', methods=['PUT'])
def update_role(role_id):
    """Update role"""
    role = Role.query.get_or_404(role_id)
    data = request.get_json()
    
    for field in ['name', 'display_name', 'description', 'role_type', 
                  'is_provider', 'is_nurse', 'is_clinical_staff', 'is_admin', 'access_level']:
        if field in data:
            setattr(role, field, data[field])
    
    # Update permissions
    if 'permissions' in data:
        # Remove existing
        RolePermission.query.filter_by(role_id=role.id).delete()
        # Add new
        for perm_id in data['permissions']:
            rp = RolePermission(role_id=role.id, permission_id=perm_id)
            db.session.add(rp)
    
    db.session.commit()
    
    log_audit('update', 'role', role.id, f'Updated role {role.name}')
    
    return jsonify(role.to_dict())


@admin_bp.route('/roles/<int:role_id>', methods=['DELETE'])
def delete_role(role_id):
    """Deactivate role"""
    role = Role.query.get_or_404(role_id)
    
    # Check if any users have this role
    user_count = User.query.filter_by(role_id=role_id).count()
    if user_count > 0:
        return jsonify({'error': f'Cannot delete role with {user_count} assigned users'}), 400
    
    role.is_active = False
    db.session.commit()
    
    log_audit('delete', 'role', role.id, f'Deactivated role {role.name}')
    
    return jsonify({'message': 'Role deactivated'})


# ============== Permission Management ==============

@admin_bp.route('/permissions', methods=['GET'])
def get_permissions():
    """Get all permissions"""
    category = request.args.get('category')
    
    query = Permission.query.filter_by(is_active=True)
    if category:
        query = query.filter_by(category=category)
    
    permissions = query.order_by(Permission.category, Permission.name).all()
    return jsonify([p.to_dict() for p in permissions])


@admin_bp.route('/permissions/categories', methods=['GET'])
def get_permission_categories():
    """Get unique permission categories"""
    categories = db.session.query(Permission.category).distinct().all()
    return jsonify([c[0] for c in categories if c[0]])


# ============== Organization Management ==============

@admin_bp.route('/organizations', methods=['GET'])
def get_organizations():
    """Get all organizations"""
    orgs = Organization.query.filter_by(is_active=True).all()
    return jsonify([o.to_dict() for o in orgs])


@admin_bp.route('/organizations/<int:org_id>', methods=['GET'])
def get_organization(org_id):
    """Get organization with theme"""
    org = Organization.query.get_or_404(org_id)
    org_dict = org.to_dict()
    if org.theme:
        org_dict['theme'] = org.theme.to_dict()
    return jsonify(org_dict)


@admin_bp.route('/organizations', methods=['POST'])
def create_organization():
    """Create organization"""
    data = request.get_json()
    
    org = Organization(
        name=data['name'],
        short_name=data.get('short_name'),
        org_type=data.get('org_type', 'hospital'),
        address=data.get('address'),
        city=data.get('city'),
        state=data.get('state'),
        zip_code=data.get('zip_code'),
        phone=data.get('phone')
    )
    
    db.session.add(org)
    db.session.commit()
    
    # Create default theme
    theme = Theme(organization_id=org.id, name=f'{org.name} Theme')
    db.session.add(theme)
    db.session.commit()
    
    log_audit('create', 'organization', org.id, f'Created organization {org.name}')
    
    return jsonify(org.to_dict()), 201


@admin_bp.route('/organizations/<int:org_id>', methods=['PUT'])
def update_organization(org_id):
    """Update organization"""
    org = Organization.query.get_or_404(org_id)
    data = request.get_json()
    
    for field in ['name', 'short_name', 'org_type', 'address', 'city', 
                  'state', 'zip_code', 'phone', 'fax', 'npi', 'tax_id']:
        if field in data:
            setattr(org, field, data[field])
    
    db.session.commit()
    
    log_audit('update', 'organization', org.id, f'Updated organization {org.name}')
    
    return jsonify(org.to_dict())


# ============== Department Management ==============

@admin_bp.route('/departments', methods=['GET'])
def get_departments():
    """Get all departments"""
    org_id = request.args.get('organization_id')
    dept_type = request.args.get('type')
    
    query = Department.query.filter_by(is_active=True)
    if org_id:
        query = query.filter_by(organization_id=org_id)
    if dept_type:
        query = query.filter_by(dept_type=dept_type)
    
    departments = query.order_by(Department.name).all()
    return jsonify([d.to_dict() for d in departments])


@admin_bp.route('/departments/<int:dept_id>', methods=['GET'])
def get_department(dept_id):
    """Get department with beds"""
    dept = Department.query.get_or_404(dept_id)
    dept_dict = dept.to_dict()
    dept_dict['beds'] = [b.to_dict() for b in dept.beds]
    return jsonify(dept_dict)


@admin_bp.route('/departments', methods=['POST'])
def create_department():
    """Create department"""
    data = request.get_json()
    
    dept = Department(
        organization_id=data['organization_id'],
        name=data['name'],
        short_name=data.get('short_name'),
        dept_type=data.get('dept_type', 'nursing_unit'),
        unit_code=data.get('unit_code'),
        floor=data.get('floor'),
        building=data.get('building'),
        bed_count=data.get('bed_count', 0),
        phone=data.get('phone'),
        specialty=data.get('specialty')
    )
    
    db.session.add(dept)
    db.session.commit()
    
    log_audit('create', 'department', dept.id, f'Created department {dept.name}')
    
    return jsonify(dept.to_dict()), 201


@admin_bp.route('/departments/<int:dept_id>', methods=['PUT'])
def update_department(dept_id):
    """Update department"""
    dept = Department.query.get_or_404(dept_id)
    data = request.get_json()
    
    for field in ['name', 'short_name', 'dept_type', 'unit_code', 'floor',
                  'building', 'bed_count', 'phone', 'specialty']:
        if field in data:
            setattr(dept, field, data[field])
    
    db.session.commit()
    
    log_audit('update', 'department', dept.id, f'Updated department {dept.name}')
    
    return jsonify(dept.to_dict())


@admin_bp.route('/departments/<int:dept_id>', methods=['DELETE'])
def delete_department(dept_id):
    """Deactivate department"""
    dept = Department.query.get_or_404(dept_id)
    dept.is_active = False
    db.session.commit()
    
    log_audit('delete', 'department', dept.id, f'Deactivated department {dept.name}')
    
    return jsonify({'message': 'Department deactivated'})


# ============== Bed Management ==============

@admin_bp.route('/departments/<int:dept_id>/beds', methods=['GET'])
def get_department_beds(dept_id):
    """Get beds for department"""
    beds = Bed.query.filter_by(department_id=dept_id, is_active=True).all()
    return jsonify([b.to_dict() for b in beds])


@admin_bp.route('/beds', methods=['POST'])
def create_bed():
    """Create bed"""
    data = request.get_json()
    
    bed = Bed(
        department_id=data['department_id'],
        room_number=data['room_number'],
        bed_letter=data.get('bed_letter'),
        bed_type=data.get('bed_type', 'standard'),
        status=data.get('status', 'available')
    )
    
    db.session.add(bed)
    db.session.commit()
    
    return jsonify(bed.to_dict()), 201


@admin_bp.route('/beds/<int:bed_id>', methods=['PUT'])
def update_bed(bed_id):
    """Update bed"""
    bed = Bed.query.get_or_404(bed_id)
    data = request.get_json()
    
    for field in ['room_number', 'bed_letter', 'bed_type', 'status']:
        if field in data:
            setattr(bed, field, data[field])
    
    db.session.commit()
    
    return jsonify(bed.to_dict())


# ============== Theme Management ==============

@admin_bp.route('/themes', methods=['GET'])
def get_themes():
    """Get all themes"""
    themes = Theme.query.filter_by(is_active=True).all()
    return jsonify([t.to_dict() for t in themes])


@admin_bp.route('/themes/<int:theme_id>', methods=['GET'])
def get_theme(theme_id):
    """Get theme"""
    theme = Theme.query.get_or_404(theme_id)
    return jsonify(theme.to_dict())


@admin_bp.route('/themes/organization/<int:org_id>', methods=['GET'])
def get_organization_theme(org_id):
    """Get theme for organization"""
    theme = Theme.query.filter_by(organization_id=org_id).first()
    if not theme:
        # Return default theme
        theme = Theme()
    return jsonify(theme.to_dict())


@admin_bp.route('/themes/organization/<int:org_id>/css', methods=['GET'])
def get_theme_css(org_id):
    """Get CSS variables for organization theme"""
    theme = Theme.query.filter_by(organization_id=org_id).first()
    if not theme:
        theme = Theme()
    return theme.to_css_variables(), 200, {'Content-Type': 'text/css'}


@admin_bp.route('/themes/<int:theme_id>', methods=['PUT'])
def update_theme(theme_id):
    """Update theme"""
    theme = Theme.query.get_or_404(theme_id)
    data = request.get_json()
    
    # Update all theme fields
    for field in ['name', 'primary_color', 'secondary_color', 'accent_color',
                  'header_bg_color', 'header_text_color', 'nav_bg_color', 
                  'nav_text_color', 'nav_active_bg', 'patient_header_bg',
                  'patient_header_text', 'button_primary_bg', 'button_primary_text',
                  'button_secondary_bg', 'button_danger_bg', 'status_critical_color',
                  'status_warning_color', 'status_success_color', 'status_info_color',
                  'font_family', 'font_size_base', 'logo_url', 'logo_width', 
                  'favicon_url', 'custom_css']:
        if field in data:
            setattr(theme, field, data[field])
    
    theme.updated_at = datetime.utcnow()
    db.session.commit()
    
    log_audit('update', 'theme', theme.id, f'Updated theme {theme.name}')
    
    return jsonify(theme.to_dict())


@admin_bp.route('/themes/presets', methods=['GET'])
def get_theme_presets():
    """Get preset theme options"""
    presets = [
        {
            'name': 'Epic Classic',
            'primary_color': '#c00',
            'secondary_color': '#0078d4',
            'header_bg_color': '#c00',
            'patient_header_bg': '#e8f4fd'
        },
        {
            'name': 'Modern Blue',
            'primary_color': '#1976d2',
            'secondary_color': '#0288d1',
            'header_bg_color': '#1976d2',
            'patient_header_bg': '#e3f2fd'
        },
        {
            'name': 'Forest Green',
            'primary_color': '#2e7d32',
            'secondary_color': '#388e3c',
            'header_bg_color': '#2e7d32',
            'patient_header_bg': '#e8f5e9'
        },
        {
            'name': 'Purple Health',
            'primary_color': '#7b1fa2',
            'secondary_color': '#8e24aa',
            'header_bg_color': '#7b1fa2',
            'patient_header_bg': '#f3e5f5'
        },
        {
            'name': 'Navy Medical',
            'primary_color': '#283593',
            'secondary_color': '#3949ab',
            'header_bg_color': '#283593',
            'patient_header_bg': '#e8eaf6'
        },
        {
            'name': 'Teal Care',
            'primary_color': '#00796b',
            'secondary_color': '#00897b',
            'header_bg_color': '#00796b',
            'patient_header_bg': '#e0f2f1'
        },
        {
            'name': 'Orange Vibrant',
            'primary_color': '#e65100',
            'secondary_color': '#f57c00',
            'header_bg_color': '#e65100',
            'patient_header_bg': '#fff3e0'
        },
        {
            'name': 'Dark Mode',
            'primary_color': '#424242',
            'secondary_color': '#616161',
            'header_bg_color': '#212121',
            'header_text_color': '#ffffff',
            'nav_bg_color': '#303030',
            'nav_text_color': '#ffffff',
            'patient_header_bg': '#424242',
            'patient_header_text': '#ffffff'
        }
    ]
    return jsonify(presets)


# ============== System Settings ==============

@admin_bp.route('/settings', methods=['GET'])
def get_settings():
    """Get system settings"""
    category = request.args.get('category')
    org_id = request.args.get('organization_id')
    
    query = SystemSetting.query
    if category:
        query = query.filter_by(category=category)
    if org_id:
        query = query.filter_by(organization_id=org_id)
    
    settings = query.all()
    return jsonify([s.to_dict() for s in settings])


@admin_bp.route('/settings/<string:key>', methods=['GET'])
def get_setting(key):
    """Get specific setting"""
    org_id = request.args.get('organization_id')
    setting = SystemSetting.query.filter_by(setting_key=key, organization_id=org_id).first()
    if not setting:
        return jsonify({'error': 'Setting not found'}), 404
    return jsonify(setting.to_dict())


@admin_bp.route('/settings', methods=['POST'])
def create_setting():
    """Create or update setting"""
    data = request.get_json()
    
    setting = SystemSetting.query.filter_by(
        setting_key=data['key'],
        organization_id=data.get('organization_id')
    ).first()
    
    if setting:
        setting.setting_value = str(data['value'])
    else:
        setting = SystemSetting(
            organization_id=data.get('organization_id'),
            category=data.get('category', 'general'),
            setting_key=data['key'],
            setting_value=str(data['value']),
            value_type=data.get('value_type', 'string'),
            description=data.get('description')
        )
        db.session.add(setting)
    
    db.session.commit()
    
    log_audit('update', 'setting', setting.id, f'Updated setting {setting.setting_key}')
    
    return jsonify(setting.to_dict())


# ============== Audit Logs ==============

@admin_bp.route('/audit-logs', methods=['GET'])
def get_audit_logs():
    """Get audit logs with filters"""
    user_id = request.args.get('user_id')
    action = request.args.get('action')
    resource_type = request.args.get('resource_type')
    patient_id = request.args.get('patient_id')
    start_date = request.args.get('start_date')
    end_date = request.args.get('end_date')
    limit = request.args.get('limit', 100, type=int)
    
    query = AuditLog.query
    
    if user_id:
        query = query.filter_by(user_id=user_id)
    if action:
        query = query.filter_by(action=action)
    if resource_type:
        query = query.filter_by(resource_type=resource_type)
    if patient_id:
        query = query.filter_by(patient_id=patient_id)
    if start_date:
        query = query.filter(AuditLog.created_at >= start_date)
    if end_date:
        query = query.filter(AuditLog.created_at <= end_date)
    
    logs = query.order_by(AuditLog.created_at.desc()).limit(limit).all()
    return jsonify([l.to_dict() for l in logs])


@admin_bp.route('/audit-logs/summary', methods=['GET'])
def get_audit_summary():
    """Get audit log summary/statistics"""
    # Actions by type
    action_counts = db.session.query(
        AuditLog.action, db.func.count(AuditLog.id)
    ).group_by(AuditLog.action).all()
    
    # Resources by type
    resource_counts = db.session.query(
        AuditLog.resource_type, db.func.count(AuditLog.id)
    ).group_by(AuditLog.resource_type).all()
    
    return jsonify({
        'by_action': {a[0]: a[1] for a in action_counts},
        'by_resource': {r[0]: r[1] for r in resource_counts if r[0]},
        'total': AuditLog.query.count()
    })


# ============== Patient Lists ==============

@admin_bp.route('/patient-lists', methods=['GET'])
def get_patient_lists():
    """Get patient lists"""
    user_id = request.args.get('user_id')
    dept_id = request.args.get('department_id')
    list_type = request.args.get('type')
    
    query = PatientList.query.filter_by(is_active=True)
    
    if user_id:
        query = query.filter_by(user_id=user_id)
    if dept_id:
        query = query.filter_by(department_id=dept_id)
    if list_type:
        query = query.filter_by(list_type=list_type)
    
    lists = query.order_by(PatientList.sort_order).all()
    return jsonify([l.to_dict() for l in lists])


@admin_bp.route('/patient-lists/<int:list_id>', methods=['GET'])
def get_patient_list(list_id):
    """Get patient list with patients"""
    plist = PatientList.query.get_or_404(list_id)
    result = plist.to_dict()
    result['patients'] = [e.to_dict() for e in plist.patients]
    return jsonify(result)


@admin_bp.route('/patient-lists', methods=['POST'])
def create_patient_list():
    """Create patient list"""
    data = request.get_json()
    
    plist = PatientList(
        name=data['name'],
        description=data.get('description'),
        user_id=data.get('user_id'),
        department_id=data.get('department_id'),
        list_type=data.get('list_type', 'personal'),
        sort_order=data.get('sort_order', 0)
    )
    
    db.session.add(plist)
    db.session.commit()
    
    return jsonify(plist.to_dict()), 201


# ============== Initialization ==============

@admin_bp.route('/initialize', methods=['POST'])
def initialize_admin_data():
    """Initialize roles, permissions, and default organization"""
    # Create default roles
    for role_data in DEFAULT_ROLES:
        if not Role.query.filter_by(name=role_data['name']).first():
            role = Role(**role_data)
            db.session.add(role)
    
    # Create default permissions
    for perm_data in DEFAULT_PERMISSIONS:
        if not Permission.query.filter_by(name=perm_data['name']).first():
            perm = Permission(**perm_data)
            db.session.add(perm)
    
    db.session.commit()
    
    # Create default organization if none exists
    if not Organization.query.first():
        org = Organization(
            name='General Hospital',
            short_name='GH',
            org_type='hospital'
        )
        db.session.add(org)
        db.session.commit()
        
        # Create default theme
        theme = Theme(organization_id=org.id, name='Default Theme')
        db.session.add(theme)
        db.session.commit()
    
    return jsonify({'message': 'Admin data initialized'})


# ============== Helper Functions ==============

def log_audit(action, resource_type, resource_id, description, 
              old_value=None, new_value=None, patient_id=None):
    """Log an audit entry"""
    log = AuditLog(
        action=action,
        resource_type=resource_type,
        resource_id=resource_id,
        description=description,
        old_value=old_value,
        new_value=new_value,
        patient_id=patient_id,
        ip_address=request.remote_addr,
        user_agent=request.user_agent.string if request.user_agent else None
    )
    db.session.add(log)
    db.session.commit()

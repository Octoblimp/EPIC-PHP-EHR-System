"""
Role-Based Access Control Models
Defines user roles, permissions, and access levels
"""
from . import db
from datetime import datetime


class Role(db.Model):
    """User roles with hierarchical permissions"""
    __tablename__ = 'roles'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)
    display_name = db.Column(db.String(100))
    description = db.Column(db.String(500))
    
    # Role type for categorization
    role_type = db.Column(db.String(50))  # clinical, administrative, technical, system
    
    # Clinical roles
    is_provider = db.Column(db.Boolean, default=False)  # MD, DO, NP, PA
    is_nurse = db.Column(db.Boolean, default=False)
    is_clinical_staff = db.Column(db.Boolean, default=False)
    
    # Administrative roles
    is_admin = db.Column(db.Boolean, default=False)
    is_super_admin = db.Column(db.Boolean, default=False)
    
    # Access levels (1-10)
    access_level = db.Column(db.Integer, default=1)
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    permissions = db.relationship('RolePermission', backref='role', lazy='dynamic', cascade='all, delete-orphan')
    users = db.relationship('User', backref='role', lazy='dynamic')
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'display_name': self.display_name,
            'description': self.description,
            'role_type': self.role_type,
            'is_provider': self.is_provider,
            'is_nurse': self.is_nurse,
            'is_clinical_staff': self.is_clinical_staff,
            'is_admin': self.is_admin,
            'is_super_admin': self.is_super_admin,
            'access_level': self.access_level,
            'is_active': self.is_active,
            'permissions': [p.to_dict() for p in self.permissions]
        }


class Permission(db.Model):
    """System permissions that can be assigned to roles"""
    __tablename__ = 'permissions'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)
    display_name = db.Column(db.String(100))
    description = db.Column(db.String(500))
    category = db.Column(db.String(50))  # patient, orders, medications, admin, reports, etc.
    
    # Permission type
    permission_type = db.Column(db.String(30))  # view, create, edit, delete, approve, sign
    
    # Module this permission applies to
    module = db.Column(db.String(50))
    
    is_active = db.Column(db.Boolean, default=True)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'display_name': self.display_name,
            'description': self.description,
            'category': self.category,
            'permission_type': self.permission_type,
            'module': self.module
        }


class RolePermission(db.Model):
    """Links roles to permissions with optional restrictions"""
    __tablename__ = 'role_permissions'
    
    id = db.Column(db.Integer, primary_key=True)
    role_id = db.Column(db.Integer, db.ForeignKey('roles.id'), nullable=False)
    permission_id = db.Column(db.Integer, db.ForeignKey('permissions.id'), nullable=False)
    
    # Restrictions
    department_restricted = db.Column(db.Boolean, default=False)  # Only for assigned department
    patient_restricted = db.Column(db.Boolean, default=False)  # Only for assigned patients
    time_restricted = db.Column(db.Boolean, default=False)  # Only during shift
    
    granted_at = db.Column(db.DateTime, default=datetime.utcnow)
    granted_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    permission = db.relationship('Permission')
    
    def to_dict(self):
        return {
            'id': self.id,
            'role_id': self.role_id,
            'permission': self.permission.to_dict() if self.permission else None,
            'department_restricted': self.department_restricted,
            'patient_restricted': self.patient_restricted,
            'time_restricted': self.time_restricted
        }


class UserDepartment(db.Model):
    """Links users to departments they can access"""
    __tablename__ = 'user_departments'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'), nullable=False)
    
    is_primary = db.Column(db.Boolean, default=False)  # User's primary/home department
    is_active = db.Column(db.Boolean, default=True)
    
    assigned_at = db.Column(db.DateTime, default=datetime.utcnow)
    assigned_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    department = db.relationship('Department')


class PatientList(db.Model):
    """Custom patient lists for users (My Patients, etc.)"""
    __tablename__ = 'patient_lists'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.String(500))
    
    # Owner
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # List type
    list_type = db.Column(db.String(50))  # personal, department, system, census
    
    # Auto-population rules (JSON)
    auto_rules = db.Column(db.Text)  # e.g., {"attending_id": 123} or {"department_id": 5}
    
    # Display settings
    sort_order = db.Column(db.Integer, default=0)
    is_default = db.Column(db.Boolean, default=False)
    
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    patients = db.relationship('PatientListEntry', backref='patient_list', lazy='dynamic')
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'description': self.description,
            'user_id': self.user_id,
            'department_id': self.department_id,
            'list_type': self.list_type,
            'is_default': self.is_default,
            'patient_count': self.patients.count()
        }


class PatientListEntry(db.Model):
    """Patients in a patient list"""
    __tablename__ = 'patient_list_entries'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_list_id = db.Column(db.Integer, db.ForeignKey('patient_lists.id'), nullable=False)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Assignment info
    assigned_at = db.Column(db.DateTime, default=datetime.utcnow)
    assigned_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    # Notes
    notes = db.Column(db.String(500))
    priority = db.Column(db.Integer, default=0)
    
    patient = db.relationship('Patient')
    encounter = db.relationship('Encounter')
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'encounter_id': self.encounter_id,
            'assigned_at': self.assigned_at.isoformat() if self.assigned_at else None,
            'notes': self.notes,
            'priority': self.priority,
            'patient': self.patient.to_dict() if self.patient else None
        }


class AuditLog(db.Model):
    """Audit trail for all system actions"""
    __tablename__ = 'audit_logs'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # Who
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    username = db.Column(db.String(100))
    user_role = db.Column(db.String(100))
    
    # What
    action = db.Column(db.String(50), nullable=False)  # view, create, update, delete, sign, print
    resource_type = db.Column(db.String(50))  # patient, order, medication, note, etc.
    resource_id = db.Column(db.Integer)
    
    # Details
    description = db.Column(db.Text)
    old_value = db.Column(db.Text)  # JSON of previous state
    new_value = db.Column(db.Text)  # JSON of new state
    
    # Patient context
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Technical details
    ip_address = db.Column(db.String(50))
    user_agent = db.Column(db.String(500))
    session_id = db.Column(db.String(100))
    
    # Timestamp
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'user_id': self.user_id,
            'username': self.username,
            'action': self.action,
            'resource_type': self.resource_type,
            'resource_id': self.resource_id,
            'description': self.description,
            'patient_id': self.patient_id,
            'ip_address': self.ip_address,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


# Default roles to seed
DEFAULT_ROLES = [
    {
        'name': 'super_admin',
        'display_name': 'Super Administrator',
        'description': 'Full system access',
        'role_type': 'system',
        'is_super_admin': True,
        'is_admin': True,
        'access_level': 10
    },
    {
        'name': 'admin',
        'display_name': 'Administrator',
        'description': 'System administration access',
        'role_type': 'administrative',
        'is_admin': True,
        'access_level': 9
    },
    {
        'name': 'physician',
        'display_name': 'Physician (MD/DO)',
        'description': 'Full clinical access for physicians',
        'role_type': 'clinical',
        'is_provider': True,
        'is_clinical_staff': True,
        'access_level': 8
    },
    {
        'name': 'nurse_practitioner',
        'display_name': 'Nurse Practitioner',
        'description': 'Advanced practice provider access',
        'role_type': 'clinical',
        'is_provider': True,
        'is_nurse': True,
        'is_clinical_staff': True,
        'access_level': 7
    },
    {
        'name': 'physician_assistant',
        'display_name': 'Physician Assistant',
        'description': 'Advanced practice provider access',
        'role_type': 'clinical',
        'is_provider': True,
        'is_clinical_staff': True,
        'access_level': 7
    },
    {
        'name': 'rn',
        'display_name': 'Registered Nurse',
        'description': 'Full nursing access',
        'role_type': 'clinical',
        'is_nurse': True,
        'is_clinical_staff': True,
        'access_level': 6
    },
    {
        'name': 'lpn',
        'display_name': 'Licensed Practical Nurse',
        'description': 'Limited nursing access',
        'role_type': 'clinical',
        'is_nurse': True,
        'is_clinical_staff': True,
        'access_level': 5
    },
    {
        'name': 'cna',
        'display_name': 'Certified Nursing Assistant',
        'description': 'Basic clinical documentation',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 4
    },
    {
        'name': 'unit_secretary',
        'display_name': 'Unit Secretary',
        'description': 'Unit clerical access',
        'role_type': 'administrative',
        'access_level': 3
    },
    {
        'name': 'pharmacist',
        'display_name': 'Pharmacist',
        'description': 'Pharmacy clinical access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 7
    },
    {
        'name': 'pharmacy_tech',
        'display_name': 'Pharmacy Technician',
        'description': 'Limited pharmacy access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 4
    },
    {
        'name': 'lab_tech',
        'display_name': 'Laboratory Technician',
        'description': 'Lab results and collection access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 4
    },
    {
        'name': 'radiology_tech',
        'display_name': 'Radiology Technician',
        'description': 'Radiology procedure access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 4
    },
    {
        'name': 'respiratory_therapist',
        'display_name': 'Respiratory Therapist',
        'description': 'RT clinical access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 5
    },
    {
        'name': 'physical_therapist',
        'display_name': 'Physical Therapist',
        'description': 'PT clinical access',
        'role_type': 'clinical',
        'is_clinical_staff': True,
        'access_level': 5
    },
    {
        'name': 'registration',
        'display_name': 'Registration Clerk',
        'description': 'Patient registration access',
        'role_type': 'administrative',
        'access_level': 2
    },
    {
        'name': 'billing',
        'display_name': 'Billing Specialist',
        'description': 'Billing and coding access',
        'role_type': 'administrative',
        'access_level': 3
    },
    {
        'name': 'medical_records',
        'display_name': 'Medical Records',
        'description': 'HIM/Medical records access',
        'role_type': 'administrative',
        'access_level': 4
    }
]

# Default permissions to seed
DEFAULT_PERMISSIONS = [
    # Patient permissions
    {'name': 'patient.view', 'display_name': 'View Patients', 'category': 'patient', 'permission_type': 'view', 'module': 'patients'},
    {'name': 'patient.create', 'display_name': 'Create Patients', 'category': 'patient', 'permission_type': 'create', 'module': 'patients'},
    {'name': 'patient.edit', 'display_name': 'Edit Patients', 'category': 'patient', 'permission_type': 'edit', 'module': 'patients'},
    {'name': 'patient.merge', 'display_name': 'Merge Patients', 'category': 'patient', 'permission_type': 'edit', 'module': 'patients'},
    
    # Orders permissions
    {'name': 'orders.view', 'display_name': 'View Orders', 'category': 'orders', 'permission_type': 'view', 'module': 'orders'},
    {'name': 'orders.create', 'display_name': 'Create Orders', 'category': 'orders', 'permission_type': 'create', 'module': 'orders'},
    {'name': 'orders.sign', 'display_name': 'Sign Orders', 'category': 'orders', 'permission_type': 'sign', 'module': 'orders'},
    {'name': 'orders.discontinue', 'display_name': 'Discontinue Orders', 'category': 'orders', 'permission_type': 'edit', 'module': 'orders'},
    
    # Medications permissions
    {'name': 'medications.view', 'display_name': 'View Medications', 'category': 'medications', 'permission_type': 'view', 'module': 'medications'},
    {'name': 'medications.administer', 'display_name': 'Administer Medications', 'category': 'medications', 'permission_type': 'create', 'module': 'mar'},
    {'name': 'medications.order', 'display_name': 'Order Medications', 'category': 'medications', 'permission_type': 'create', 'module': 'medications'},
    
    # Notes permissions
    {'name': 'notes.view', 'display_name': 'View Notes', 'category': 'notes', 'permission_type': 'view', 'module': 'notes'},
    {'name': 'notes.create', 'display_name': 'Create Notes', 'category': 'notes', 'permission_type': 'create', 'module': 'notes'},
    {'name': 'notes.sign', 'display_name': 'Sign Notes', 'category': 'notes', 'permission_type': 'sign', 'module': 'notes'},
    {'name': 'notes.cosign', 'display_name': 'Cosign Notes', 'category': 'notes', 'permission_type': 'sign', 'module': 'notes'},
    {'name': 'notes.addendum', 'display_name': 'Add Addendum', 'category': 'notes', 'permission_type': 'edit', 'module': 'notes'},
    
    # Flowsheets permissions
    {'name': 'flowsheets.view', 'display_name': 'View Flowsheets', 'category': 'flowsheets', 'permission_type': 'view', 'module': 'flowsheets'},
    {'name': 'flowsheets.document', 'display_name': 'Document Flowsheets', 'category': 'flowsheets', 'permission_type': 'create', 'module': 'flowsheets'},
    
    # Results permissions
    {'name': 'results.view', 'display_name': 'View Results', 'category': 'results', 'permission_type': 'view', 'module': 'results'},
    {'name': 'results.acknowledge', 'display_name': 'Acknowledge Results', 'category': 'results', 'permission_type': 'edit', 'module': 'results'},
    
    # Admin permissions
    {'name': 'admin.users', 'display_name': 'Manage Users', 'category': 'admin', 'permission_type': 'edit', 'module': 'admin'},
    {'name': 'admin.roles', 'display_name': 'Manage Roles', 'category': 'admin', 'permission_type': 'edit', 'module': 'admin'},
    {'name': 'admin.departments', 'display_name': 'Manage Departments', 'category': 'admin', 'permission_type': 'edit', 'module': 'admin'},
    {'name': 'admin.settings', 'display_name': 'System Settings', 'category': 'admin', 'permission_type': 'edit', 'module': 'admin'},
    {'name': 'admin.theme', 'display_name': 'Manage Theme', 'category': 'admin', 'permission_type': 'edit', 'module': 'admin'},
    {'name': 'admin.audit', 'display_name': 'View Audit Logs', 'category': 'admin', 'permission_type': 'view', 'module': 'admin'},
    
    # Reports permissions
    {'name': 'reports.view', 'display_name': 'View Reports', 'category': 'reports', 'permission_type': 'view', 'module': 'reports'},
    {'name': 'reports.create', 'display_name': 'Create Reports', 'category': 'reports', 'permission_type': 'create', 'module': 'reports'},
    {'name': 'reports.export', 'display_name': 'Export Reports', 'category': 'reports', 'permission_type': 'view', 'module': 'reports'},
]

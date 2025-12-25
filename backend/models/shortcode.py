"""
Shortcode Model
Defines navigation shortcodes for Go To quick access
"""
from . import db
from datetime import datetime


class Shortcode(db.Model):
    """Navigation shortcodes for quick access to patient chart sections"""
    __tablename__ = 'shortcodes'
    
    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(20), nullable=False, unique=True)  # e.g., 'vs', 'mar', 'lab'
    name = db.Column(db.String(100), nullable=False)  # Display name, e.g., 'Vitals'
    description = db.Column(db.String(255))  # Optional description
    
    # Navigation target
    tab = db.Column(db.String(50), nullable=False)  # Target tab in patient chart
    subtab = db.Column(db.String(50))  # Optional subtab/section
    url_params = db.Column(db.String(255))  # Additional URL parameters (JSON)
    
    # Display
    icon = db.Column(db.String(50), default='fa-file')  # FontAwesome icon class
    category = db.Column(db.String(50), default='General')  # For grouping in UI
    sort_order = db.Column(db.Integer, default=0)  # Display order within category
    
    # Access control
    required_permission = db.Column(db.String(100))  # Optional permission required
    roles_allowed = db.Column(db.Text)  # JSON array of role names, null = all
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    is_system = db.Column(db.Boolean, default=False)  # System shortcodes can't be deleted
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    updated_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        import json
        return {
            'id': self.id,
            'code': self.code,
            'name': self.name,
            'description': self.description,
            'tab': self.tab,
            'subtab': self.subtab,
            'url_params': json.loads(self.url_params) if self.url_params else None,
            'icon': self.icon,
            'category': self.category,
            'sort_order': self.sort_order,
            'required_permission': self.required_permission,
            'roles_allowed': json.loads(self.roles_allowed) if self.roles_allowed else None,
            'is_active': self.is_active,
            'is_system': self.is_system
        }
    
    @staticmethod
    def get_default_shortcodes():
        """Return list of default system shortcodes"""
        return [
            {'code': 'sum', 'name': 'Summary', 'tab': 'summary', 'icon': 'fa-clipboard', 'category': 'Main Views', 'is_system': True},
            {'code': 'cr', 'name': 'Chart Review', 'tab': 'chart-review', 'icon': 'fa-file-medical', 'category': 'Main Views', 'is_system': True},
            {'code': 'res', 'name': 'Results', 'tab': 'results', 'icon': 'fa-flask', 'category': 'Main Views', 'is_system': True},
            {'code': 'lab', 'name': 'Lab Results', 'tab': 'results', 'subtab': 'lab', 'icon': 'fa-vials', 'category': 'Results', 'is_system': True},
            {'code': 'wl', 'name': 'Work List', 'tab': 'work-list', 'icon': 'fa-tasks', 'category': 'Main Views', 'is_system': True},
            {'code': 'mar', 'name': 'MAR', 'tab': 'mar', 'icon': 'fa-pills', 'category': 'Medications', 'is_system': True},
            {'code': 'med', 'name': 'Medications', 'tab': 'mar', 'icon': 'fa-prescription-bottle', 'category': 'Medications', 'is_system': True},
            {'code': 'fs', 'name': 'Flowsheets', 'tab': 'flowsheets', 'icon': 'fa-chart-line', 'category': 'Documentation', 'is_system': True},
            {'code': 'vs', 'name': 'Vitals', 'tab': 'flowsheets', 'subtab': 'vitals', 'icon': 'fa-heartbeat', 'category': 'Documentation', 'is_system': True},
            {'code': 'io', 'name': 'Intake/Output', 'tab': 'intake-output', 'icon': 'fa-balance-scale', 'category': 'Documentation', 'is_system': True},
            {'code': 'not', 'name': 'Notes', 'tab': 'notes', 'icon': 'fa-sticky-note', 'category': 'Documentation', 'is_system': True},
            {'code': 'pn', 'name': 'Progress Notes', 'tab': 'notes', 'subtab': 'progress', 'icon': 'fa-file-alt', 'category': 'Documentation', 'is_system': True},
            {'code': 'edu', 'name': 'Education', 'tab': 'education', 'icon': 'fa-graduation-cap', 'category': 'Patient Info', 'is_system': True},
            {'code': 'cp', 'name': 'Care Plan', 'tab': 'care-plan', 'icon': 'fa-clipboard-list', 'category': 'Care Planning', 'is_system': True},
            {'code': 'ord', 'name': 'Orders', 'tab': 'orders', 'icon': 'fa-prescription', 'category': 'Orders', 'is_system': True},
            {'code': 'rx', 'name': 'Prescriptions', 'tab': 'orders', 'subtab': 'rx', 'icon': 'fa-capsules', 'category': 'Orders', 'is_system': True},
            {'code': 'img', 'name': 'Imaging', 'tab': 'results', 'subtab': 'imaging', 'icon': 'fa-x-ray', 'category': 'Results', 'is_system': True},
            {'code': 'dx', 'name': 'Diagnoses', 'tab': 'chart-review', 'subtab': 'diagnoses', 'icon': 'fa-diagnoses', 'category': 'Clinical', 'is_system': True},
            {'code': 'hx', 'name': 'History', 'tab': 'history', 'icon': 'fa-history', 'category': 'Clinical', 'is_system': True},
            {'code': 'all', 'name': 'Allergies', 'tab': 'summary', 'subtab': 'allergies', 'icon': 'fa-exclamation-triangle', 'category': 'Clinical', 'is_system': True},
            {'code': 'prob', 'name': 'Problem List', 'tab': 'chart-review', 'subtab': 'problems', 'icon': 'fa-list-ul', 'category': 'Clinical', 'is_system': True},
            {'code': 'imm', 'name': 'Immunizations', 'tab': 'chart-review', 'subtab': 'immunizations', 'icon': 'fa-syringe', 'category': 'Clinical', 'is_system': True},
            {'code': 'dem', 'name': 'Demographics', 'tab': 'demographics', 'icon': 'fa-id-card', 'category': 'Patient Info', 'is_system': True},
            {'code': 'ins', 'name': 'Insurance', 'tab': 'insurance', 'icon': 'fa-shield-alt', 'category': 'Patient Info', 'is_system': True},
        ]


class PageAccess(db.Model):
    """Explicit page-level access control per role"""
    __tablename__ = 'page_access'
    
    id = db.Column(db.Integer, primary_key=True)
    role_id = db.Column(db.Integer, db.ForeignKey('roles.id'), nullable=False)
    page_code = db.Column(db.String(100), nullable=False)  # e.g., 'admin.shortcodes', 'patient.chart'
    
    # Access level
    access_level = db.Column(db.String(20), default='none')  # none, view, edit, full
    
    # Explicit flags
    can_view = db.Column(db.Boolean, default=False)
    can_create = db.Column(db.Boolean, default=False)
    can_edit = db.Column(db.Boolean, default=False)
    can_delete = db.Column(db.Boolean, default=False)
    can_export = db.Column(db.Boolean, default=False)
    
    # Custom restrictions (JSON)
    restrictions = db.Column(db.Text)  # e.g., {"department_only": true, "own_patients_only": true}
    
    # Audit
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    updated_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    # Relationships
    role = db.relationship('Role', backref=db.backref('page_access', lazy='dynamic'))
    
    # Unique constraint
    __table_args__ = (
        db.UniqueConstraint('role_id', 'page_code', name='unique_role_page'),
    )
    
    def to_dict(self):
        import json
        return {
            'id': self.id,
            'role_id': self.role_id,
            'page_code': self.page_code,
            'access_level': self.access_level,
            'can_view': self.can_view,
            'can_create': self.can_create,
            'can_edit': self.can_edit,
            'can_delete': self.can_delete,
            'can_export': self.can_export,
            'restrictions': json.loads(self.restrictions) if self.restrictions else None
        }
    
    @staticmethod
    def get_page_definitions():
        """Return list of all pages that can have access control"""
        return [
            # Main screens
            {'code': 'home', 'name': 'Home Dashboard', 'category': 'Main', 'description': 'Main dashboard and patient list'},
            {'code': 'schedule', 'name': 'Schedule', 'category': 'Main', 'description': 'Scheduling and appointments'},
            {'code': 'inbox', 'name': 'Inbox', 'category': 'Main', 'description': 'Messages and notifications'},
            {'code': 'patient_search', 'name': 'Patient Search', 'category': 'Main', 'description': 'Search and find patients'},
            
            # Patient Chart tabs
            {'code': 'patient.summary', 'name': 'Patient Summary', 'category': 'Patient Chart', 'description': 'Patient summary view'},
            {'code': 'patient.chart_review', 'name': 'Chart Review', 'category': 'Patient Chart', 'description': 'Review patient chart history'},
            {'code': 'patient.results', 'name': 'Results', 'category': 'Patient Chart', 'description': 'Lab and test results'},
            {'code': 'patient.mar', 'name': 'MAR', 'category': 'Patient Chart', 'description': 'Medication Administration Record'},
            {'code': 'patient.flowsheets', 'name': 'Flowsheets', 'category': 'Patient Chart', 'description': 'Flowsheets and vital signs'},
            {'code': 'patient.notes', 'name': 'Notes', 'category': 'Patient Chart', 'description': 'Clinical notes'},
            {'code': 'patient.orders', 'name': 'Orders', 'category': 'Patient Chart', 'description': 'Orders management'},
            {'code': 'patient.care_plan', 'name': 'Care Plan', 'category': 'Patient Chart', 'description': 'Care planning'},
            {'code': 'patient.education', 'name': 'Education', 'category': 'Patient Chart', 'description': 'Patient education materials'},
            {'code': 'patient.demographics', 'name': 'Demographics', 'category': 'Patient Chart', 'description': 'Patient demographics'},
            {'code': 'patient.insurance', 'name': 'Insurance', 'category': 'Patient Chart', 'description': 'Insurance information'},
            {'code': 'patient.history', 'name': 'History', 'category': 'Patient Chart', 'description': 'Medical history'},
            
            # Admin pages
            {'code': 'admin.dashboard', 'name': 'Admin Dashboard', 'category': 'Admin', 'description': 'Administration dashboard'},
            {'code': 'admin.users', 'name': 'User Management', 'category': 'Admin', 'description': 'Manage users'},
            {'code': 'admin.roles', 'name': 'Role Management', 'category': 'Admin', 'description': 'Manage roles and permissions'},
            {'code': 'admin.shortcodes', 'name': 'Shortcode Management', 'category': 'Admin', 'description': 'Manage Go To shortcodes'},
            {'code': 'admin.page_access', 'name': 'Page Access', 'category': 'Admin', 'description': 'Manage page-level permissions'},
            {'code': 'admin.audit', 'name': 'Audit Log', 'category': 'Admin', 'description': 'View audit logs'},
            {'code': 'admin.database', 'name': 'Database Tools', 'category': 'Admin', 'description': 'Database management tools'},
            {'code': 'admin.settings', 'name': 'System Settings', 'category': 'Admin', 'description': 'System configuration'},
            
            # Reports
            {'code': 'reports.clinical', 'name': 'Clinical Reports', 'category': 'Reports', 'description': 'Clinical reports'},
            {'code': 'reports.operational', 'name': 'Operational Reports', 'category': 'Reports', 'description': 'Operational reports'},
            {'code': 'reports.financial', 'name': 'Financial Reports', 'category': 'Reports', 'description': 'Financial reports'},
            
            # Billing
            {'code': 'billing.charges', 'name': 'Charges', 'category': 'Billing', 'description': 'Charge entry'},
            {'code': 'billing.claims', 'name': 'Claims', 'category': 'Billing', 'description': 'Claims management'},
            {'code': 'billing.payments', 'name': 'Payments', 'category': 'Billing', 'description': 'Payment posting'},
        ]

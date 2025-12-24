"""
Authentication and Authorization Models
HIPAA-compliant user authentication with audit logging
"""
from datetime import datetime, timedelta
from werkzeug.security import generate_password_hash, check_password_hash
from flask_sqlalchemy import SQLAlchemy
import secrets
import hashlib
import pyotp
import uuid

db = SQLAlchemy()


class User(db.Model):
    """User account model with HIPAA-compliant security features"""
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    uuid = db.Column(db.String(36), unique=True, nullable=False, default=lambda: str(uuid.uuid4()))
    username = db.Column(db.String(50), unique=True, nullable=False, index=True)
    email = db.Column(db.String(255), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    
    # Personal Info
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    title = db.Column(db.String(50))  # MD, RN, NP, etc.
    npi = db.Column(db.String(10))  # National Provider Identifier
    employee_id = db.Column(db.String(50))
    phone = db.Column(db.String(20))
    
    # Role and Access
    role_id = db.Column(db.Integer, db.ForeignKey('roles.id'), nullable=False)
    department_id = db.Column(db.Integer, db.ForeignKey('departments.id'))
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    is_active = db.Column(db.Boolean, default=True)
    is_locked = db.Column(db.Boolean, default=False)
    
    # Security
    mfa_enabled = db.Column(db.Boolean, default=False)
    mfa_secret = db.Column(db.String(32))
    mfa_backup_codes = db.Column(db.Text)  # JSON array of backup codes
    password_expires_at = db.Column(db.DateTime)
    must_change_password = db.Column(db.Boolean, default=True)
    failed_login_attempts = db.Column(db.Integer, default=0)
    locked_until = db.Column(db.DateTime)
    last_password_change = db.Column(db.DateTime)
    password_history = db.Column(db.Text)  # JSON array of previous password hashes
    
    # Session Management
    last_login = db.Column(db.DateTime)
    last_activity = db.Column(db.DateTime)
    current_session_id = db.Column(db.String(64))
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    updated_by = db.Column(db.Integer)
    
    # Relationships
    role = db.relationship('Role', back_populates='users')
    department = db.relationship('Department', back_populates='users')
    facility = db.relationship('Facility', back_populates='users')
    sessions = db.relationship('UserSession', back_populates='user', cascade='all, delete-orphan')
    audit_logs = db.relationship('AuditLog', back_populates='user', foreign_keys='AuditLog.user_id')
    
    # Password policy constants
    PASSWORD_MIN_LENGTH = 12
    PASSWORD_EXPIRY_DAYS = 90
    PASSWORD_HISTORY_COUNT = 12
    MAX_FAILED_ATTEMPTS = 5
    LOCKOUT_DURATION_MINUTES = 30
    
    def set_password(self, password):
        """Set password with validation and history tracking"""
        # Validate password strength
        if len(password) < self.PASSWORD_MIN_LENGTH:
            raise ValueError(f"Password must be at least {self.PASSWORD_MIN_LENGTH} characters")
        
        if not any(c.isupper() for c in password):
            raise ValueError("Password must contain at least one uppercase letter")
        if not any(c.islower() for c in password):
            raise ValueError("Password must contain at least one lowercase letter")
        if not any(c.isdigit() for c in password):
            raise ValueError("Password must contain at least one digit")
        if not any(c in '!@#$%^&*()_+-=[]{}|;:,.<>?' for c in password):
            raise ValueError("Password must contain at least one special character")
        
        # Check password history
        if self.password_history:
            import json
            history = json.loads(self.password_history)
            for old_hash in history[-self.PASSWORD_HISTORY_COUNT:]:
                if check_password_hash(old_hash, password):
                    raise ValueError(f"Cannot reuse any of your last {self.PASSWORD_HISTORY_COUNT} passwords")
        
        # Store old password in history
        if self.password_hash:
            import json
            history = json.loads(self.password_history) if self.password_history else []
            history.append(self.password_hash)
            self.password_history = json.dumps(history[-self.PASSWORD_HISTORY_COUNT:])
        
        # Set new password
        self.password_hash = generate_password_hash(password, method='pbkdf2:sha256:600000')
        self.last_password_change = datetime.utcnow()
        self.password_expires_at = datetime.utcnow() + timedelta(days=self.PASSWORD_EXPIRY_DAYS)
        self.must_change_password = False
    
    def check_password(self, password):
        """Verify password and handle failed attempts"""
        if self.is_locked:
            if self.locked_until and datetime.utcnow() > self.locked_until:
                self.is_locked = False
                self.failed_login_attempts = 0
            else:
                return False
        
        if check_password_hash(self.password_hash, password):
            self.failed_login_attempts = 0
            return True
        else:
            self.failed_login_attempts += 1
            if self.failed_login_attempts >= self.MAX_FAILED_ATTEMPTS:
                self.is_locked = True
                self.locked_until = datetime.utcnow() + timedelta(minutes=self.LOCKOUT_DURATION_MINUTES)
            return False
    
    def is_password_expired(self):
        """Check if password needs to be changed"""
        if self.must_change_password:
            return True
        if self.password_expires_at and datetime.utcnow() > self.password_expires_at:
            return True
        return False
    
    def enable_mfa(self):
        """Generate MFA secret and backup codes"""
        self.mfa_secret = pyotp.random_base32()
        self.mfa_backup_codes = self._generate_backup_codes()
        self.mfa_enabled = True
        return self.mfa_secret
    
    def _generate_backup_codes(self, count=10):
        """Generate one-time backup codes"""
        import json
        codes = [secrets.token_hex(4).upper() for _ in range(count)]
        return json.dumps(codes)
    
    def verify_mfa(self, code):
        """Verify MFA code or backup code"""
        if not self.mfa_enabled:
            return True
        
        # Check TOTP
        totp = pyotp.TOTP(self.mfa_secret)
        if totp.verify(code, valid_window=1):
            return True
        
        # Check backup codes
        import json
        if self.mfa_backup_codes:
            codes = json.loads(self.mfa_backup_codes)
            if code.upper() in codes:
                codes.remove(code.upper())
                self.mfa_backup_codes = json.dumps(codes)
                return True
        
        return False
    
    def get_mfa_uri(self):
        """Get MFA provisioning URI for QR code"""
        if not self.mfa_secret:
            return None
        totp = pyotp.TOTP(self.mfa_secret)
        return totp.provisioning_uri(name=self.email, issuer_name="Epic EHR")
    
    @property
    def full_name(self):
        return f"{self.first_name} {self.last_name}"
    
    @property
    def display_name(self):
        if self.title:
            return f"{self.first_name} {self.last_name}, {self.title}"
        return self.full_name
    
    def has_permission(self, permission_code):
        """Check if user has a specific permission"""
        if not self.role:
            return False
        return self.role.has_permission(permission_code)
    
    def has_any_permission(self, permission_codes):
        """Check if user has any of the specified permissions"""
        return any(self.has_permission(code) for code in permission_codes)
    
    def has_all_permissions(self, permission_codes):
        """Check if user has all specified permissions"""
        return all(self.has_permission(code) for code in permission_codes)
    
    def can_access_patient(self, patient_id):
        """Check if user can access a specific patient (break-the-glass support)"""
        # System admin or superuser can access all
        if self.has_permission('SYSTEM_ADMIN'):
            return True
        
        # Check if patient is in user's care team
        # This would check assignments, encounters, orders, etc.
        return True  # Simplified - implement actual logic
    
    def to_dict(self, include_sensitive=False):
        """Serialize user to dictionary"""
        data = {
            'id': self.id,
            'uuid': self.uuid,
            'username': self.username,
            'email': self.email,
            'first_name': self.first_name,
            'last_name': self.last_name,
            'full_name': self.full_name,
            'display_name': self.display_name,
            'title': self.title,
            'npi': self.npi,
            'employee_id': self.employee_id,
            'role': self.role.to_dict() if self.role else None,
            'department_id': self.department_id,
            'facility_id': self.facility_id,
            'is_active': self.is_active,
            'mfa_enabled': self.mfa_enabled,
            'last_login': self.last_login.isoformat() if self.last_login else None,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }
        
        if include_sensitive:
            data.update({
                'is_locked': self.is_locked,
                'failed_login_attempts': self.failed_login_attempts,
                'password_expires_at': self.password_expires_at.isoformat() if self.password_expires_at else None,
                'must_change_password': self.must_change_password
            })
        
        return data


class Role(db.Model):
    """Role model with granular permissions"""
    __tablename__ = 'roles'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), unique=True, nullable=False)
    code = db.Column(db.String(50), unique=True, nullable=False)
    description = db.Column(db.Text)
    is_system_role = db.Column(db.Boolean, default=False)  # Cannot be deleted
    is_active = db.Column(db.Boolean, default=True)
    
    # Hierarchy
    parent_role_id = db.Column(db.Integer, db.ForeignKey('roles.id'))
    level = db.Column(db.Integer, default=0)  # For role hierarchy
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    users = db.relationship('User', back_populates='role')
    permissions = db.relationship('RolePermission', back_populates='role', cascade='all, delete-orphan')
    parent_role = db.relationship('Role', remote_side=[id], backref='child_roles')
    
    def has_permission(self, permission_code):
        """Check if role has a specific permission"""
        for rp in self.permissions:
            if rp.permission.code == permission_code and rp.is_granted:
                return True
        # Check parent role
        if self.parent_role:
            return self.parent_role.has_permission(permission_code)
        return False
    
    def get_all_permissions(self):
        """Get all permissions including inherited"""
        perms = set()
        for rp in self.permissions:
            if rp.is_granted:
                perms.add(rp.permission.code)
        if self.parent_role:
            perms.update(self.parent_role.get_all_permissions())
        return list(perms)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'code': self.code,
            'description': self.description,
            'is_system_role': self.is_system_role,
            'permissions': self.get_all_permissions(),
            'level': self.level
        }


class Permission(db.Model):
    """Granular permission definitions"""
    __tablename__ = 'permissions'
    
    id = db.Column(db.Integer, primary_key=True)
    code = db.Column(db.String(100), unique=True, nullable=False)
    name = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    category = db.Column(db.String(50))  # CLINICAL, ADMIN, BILLING, etc.
    module = db.Column(db.String(50))  # Patient, Orders, Notes, etc.
    is_phi_access = db.Column(db.Boolean, default=False)  # Requires PHI access logging
    requires_reason = db.Column(db.Boolean, default=False)  # Break-the-glass
    
    roles = db.relationship('RolePermission', back_populates='permission')
    
    def to_dict(self):
        return {
            'id': self.id,
            'code': self.code,
            'name': self.name,
            'description': self.description,
            'category': self.category,
            'module': self.module,
            'is_phi_access': self.is_phi_access
        }


class RolePermission(db.Model):
    """Many-to-many relationship between roles and permissions with conditions"""
    __tablename__ = 'role_permissions'
    
    id = db.Column(db.Integer, primary_key=True)
    role_id = db.Column(db.Integer, db.ForeignKey('roles.id'), nullable=False)
    permission_id = db.Column(db.Integer, db.ForeignKey('permissions.id'), nullable=False)
    is_granted = db.Column(db.Boolean, default=True)
    
    # Conditions (JSON for flexible restrictions)
    conditions = db.Column(db.Text)  # e.g., {"department_ids": [1,2], "patient_types": ["inpatient"]}
    
    role = db.relationship('Role', back_populates='permissions')
    permission = db.relationship('Permission', back_populates='roles')


class UserSession(db.Model):
    """Active user sessions for session management"""
    __tablename__ = 'user_sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    session_id = db.Column(db.String(64), unique=True, nullable=False, index=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    
    # Session Info
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.String(500))
    device_info = db.Column(db.Text)
    
    # Timestamps
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    last_activity = db.Column(db.DateTime, default=datetime.utcnow)
    expires_at = db.Column(db.DateTime)
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    terminated_at = db.Column(db.DateTime)
    termination_reason = db.Column(db.String(100))  # logout, timeout, forced, etc.
    
    user = db.relationship('User', back_populates='sessions')
    
    SESSION_TIMEOUT_MINUTES = 15  # HIPAA requirement
    SESSION_MAX_DURATION_HOURS = 12
    
    @classmethod
    def create_session(cls, user, ip_address=None, user_agent=None):
        """Create a new session"""
        session = cls(
            session_id=secrets.token_hex(32),
            user_id=user.id,
            ip_address=ip_address,
            user_agent=user_agent,
            expires_at=datetime.utcnow() + timedelta(hours=cls.SESSION_MAX_DURATION_HOURS)
        )
        user.current_session_id = session.session_id
        user.last_login = datetime.utcnow()
        return session
    
    def is_valid(self):
        """Check if session is still valid"""
        if not self.is_active:
            return False
        if datetime.utcnow() > self.expires_at:
            return False
        if datetime.utcnow() - self.last_activity > timedelta(minutes=self.SESSION_TIMEOUT_MINUTES):
            return False
        return True
    
    def refresh(self):
        """Update last activity time"""
        self.last_activity = datetime.utcnow()
    
    def terminate(self, reason='logout'):
        """End the session"""
        self.is_active = False
        self.terminated_at = datetime.utcnow()
        self.termination_reason = reason


class Facility(db.Model):
    """Healthcare facility model"""
    __tablename__ = 'facilities'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    code = db.Column(db.String(20), unique=True, nullable=False)
    facility_type = db.Column(db.String(50))  # Hospital, Clinic, Urgent Care, etc.
    
    # Address
    address_line1 = db.Column(db.String(200))
    address_line2 = db.Column(db.String(200))
    city = db.Column(db.String(100))
    state = db.Column(db.String(50))
    zip_code = db.Column(db.String(20))
    country = db.Column(db.String(100), default='USA')
    
    # Contact
    phone = db.Column(db.String(20))
    fax = db.Column(db.String(20))
    email = db.Column(db.String(255))
    
    # Identifiers
    npi = db.Column(db.String(10))
    tax_id = db.Column(db.String(20))
    
    # Settings
    is_active = db.Column(db.Boolean, default=True)
    timezone = db.Column(db.String(50), default='America/New_York')
    
    # Theme customization
    primary_color = db.Column(db.String(7), default='#0066cc')
    secondary_color = db.Column(db.String(7), default='#004d99')
    logo_url = db.Column(db.String(500))
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    departments = db.relationship('Department', back_populates='facility')
    users = db.relationship('User', back_populates='facility')
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'code': self.code,
            'facility_type': self.facility_type,
            'address': {
                'line1': self.address_line1,
                'line2': self.address_line2,
                'city': self.city,
                'state': self.state,
                'zip_code': self.zip_code,
                'country': self.country
            },
            'phone': self.phone,
            'email': self.email,
            'npi': self.npi,
            'is_active': self.is_active,
            'theme': {
                'primary_color': self.primary_color,
                'secondary_color': self.secondary_color,
                'logo_url': self.logo_url
            }
        }


class Department(db.Model):
    """Department/unit model"""
    __tablename__ = 'departments'
    
    id = db.Column(db.Integer, primary_key=True)
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'), nullable=False)
    name = db.Column(db.String(200), nullable=False)
    code = db.Column(db.String(20), nullable=False)
    department_type = db.Column(db.String(50))  # Inpatient, Outpatient, ED, OR, etc.
    
    # Capacity
    bed_count = db.Column(db.Integer, default=0)
    
    # Contact
    phone = db.Column(db.String(20))
    extension = db.Column(db.String(10))
    
    # Settings
    is_active = db.Column(db.Boolean, default=True)
    allows_admissions = db.Column(db.Boolean, default=True)
    
    # Relationships
    facility = db.relationship('Facility', back_populates='departments')
    users = db.relationship('User', back_populates='department')
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'facility_id': self.facility_id,
            'name': self.name,
            'code': self.code,
            'department_type': self.department_type,
            'bed_count': self.bed_count,
            'is_active': self.is_active
        }

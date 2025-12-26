"""
HIPAA Compliance - Audit Logging System
Comprehensive PHI access logging and security audit trail
SECURITY: Sensitive audit data is automatically encrypted at rest
"""
from datetime import datetime
from . import db
from utils.encryption import EncryptedString, EncryptedText
import json


class AuditLog(db.Model):
    """
    HIPAA-compliant audit log for all system activities
    Tracks: who, what, when, where, why
    Sensitive data is automatically encrypted
    """
    __tablename__ = 'audit_logs'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # WHO - partially encrypted
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    username = db.Column(db.String(50))  # Plain for queries
    user_role = db.Column(db.String(100))
    
    # WHEN
    timestamp = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    # WHERE - IP encrypted for privacy
    ip_address = db.Column(EncryptedString(45))
    user_agent = db.Column(EncryptedString(500))
    session_id = db.Column(db.String(64))
    facility_id = db.Column(db.Integer, db.ForeignKey('facilities.id'))
    
    # WHAT
    action = db.Column(db.String(50), nullable=False, index=True)  # CREATE, READ, UPDATE, DELETE, LOGIN, etc.
    resource_type = db.Column(db.String(50), nullable=False, index=True)  # Patient, Order, Note, etc.
    resource_id = db.Column(db.String(50))
    
    # PHI Access
    patient_id = db.Column(db.Integer, index=True)
    patient_mrn = db.Column(db.String(50))  # Plain for lookups
    is_phi_access = db.Column(db.Boolean, default=False, index=True)
    phi_type = db.Column(db.String(50))  # Demographics, Diagnosis, Medications, etc.
    
    # WHY (for break-the-glass) - ENCRYPTED
    access_reason = db.Column(EncryptedString(500))
    is_emergency_access = db.Column(db.Boolean, default=False)
    
    # Details - ENCRYPTED (may contain PHI)
    description = db.Column(EncryptedText())
    old_values = db.Column(EncryptedText())  # JSON of previous values for UPDATE
    new_values = db.Column(EncryptedText())  # JSON of new values for CREATE/UPDATE
    request_url = db.Column(db.String(500))
    request_method = db.Column(db.String(10))
    response_status = db.Column(db.Integer)
    
    # Status
    status = db.Column(db.String(20), default='SUCCESS')  # SUCCESS, FAILURE, DENIED
    error_message = db.Column(EncryptedText())
    
    # Relationships - using backref instead of back_populates to avoid requiring User model to define the relationship
    user = db.relationship('User', foreign_keys=[user_id], lazy='joined')
    
    # Action types
    ACTION_LOGIN = 'LOGIN'
    ACTION_LOGOUT = 'LOGOUT'
    ACTION_LOGIN_FAILED = 'LOGIN_FAILED'
    ACTION_CREATE = 'CREATE'
    ACTION_READ = 'READ'
    ACTION_UPDATE = 'UPDATE'
    ACTION_DELETE = 'DELETE'
    ACTION_PRINT = 'PRINT'
    ACTION_EXPORT = 'EXPORT'
    ACTION_SEARCH = 'SEARCH'
    ACTION_VIEW_LIST = 'VIEW_LIST'
    ACTION_BREAK_GLASS = 'BREAK_GLASS'
    ACTION_MFA_SETUP = 'MFA_SETUP'
    ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE'
    ACTION_PERMISSION_DENIED = 'PERMISSION_DENIED'
    
    @classmethod
    def log(cls, user=None, action=None, resource_type=None, resource_id=None,
            patient_id=None, description=None, old_values=None, new_values=None,
            ip_address=None, user_agent=None, session_id=None, facility_id=None,
            access_reason=None, is_emergency=False, status='SUCCESS', error=None,
            request_url=None, request_method=None, response_status=None):
        """Create an audit log entry"""
        
        # Determine if this is PHI access
        phi_resources = ['Patient', 'Encounter', 'Medication', 'Order', 'Vital',
                        'Lab', 'Note', 'Flowsheet', 'Allergy', 'Problem', 'Diagnosis']
        is_phi = resource_type in phi_resources or patient_id is not None
        
        log_entry = cls(
            user_id=user.id if user else None,
            username=user.username if user else None,
            user_role=user.role.name if user and user.role else None,
            action=action,
            resource_type=resource_type,
            resource_id=str(resource_id) if resource_id else None,
            patient_id=patient_id,
            is_phi_access=is_phi,
            phi_type=resource_type if is_phi else None,
            access_reason=access_reason,
            is_emergency_access=is_emergency,
            description=description,
            old_values=json.dumps(old_values) if old_values else None,
            new_values=json.dumps(new_values) if new_values else None,
            ip_address=ip_address,
            user_agent=user_agent,
            session_id=session_id,
            facility_id=facility_id,
            status=status,
            error_message=error,
            request_url=request_url,
            request_method=request_method,
            response_status=response_status
        )
        
        db.session.add(log_entry)
        db.session.commit()
        return log_entry
    
    def to_dict(self):
        return {
            'id': self.id,
            'timestamp': self.timestamp.isoformat(),
            'user': {
                'id': self.user_id,
                'username': self.username,
                'role': self.user_role
            },
            'action': self.action,
            'resource': {
                'type': self.resource_type,
                'id': self.resource_id
            },
            'patient_id': self.patient_id,
            'patient_mrn': self.patient_mrn,
            'is_phi_access': self.is_phi_access,
            'access_reason': self.access_reason,
            'is_emergency_access': self.is_emergency_access,
            'description': self.description,
            'ip_address': self.ip_address,
            'status': self.status,
            'error_message': self.error_message
        }


class SecurityAlert(db.Model):
    """Security alerts for suspicious activities"""
    __tablename__ = 'security_alerts'
    
    id = db.Column(db.Integer, primary_key=True)
    alert_type = db.Column(db.String(50), nullable=False)  # BRUTE_FORCE, UNUSUAL_ACCESS, etc.
    severity = db.Column(db.String(20), nullable=False)  # LOW, MEDIUM, HIGH, CRITICAL
    
    # Related entities
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    patient_id = db.Column(db.Integer)
    audit_log_id = db.Column(db.Integer, db.ForeignKey('audit_logs.id'))
    
    # Details
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    ip_address = db.Column(db.String(45))
    
    # Status
    status = db.Column(db.String(20), default='NEW')  # NEW, ACKNOWLEDGED, INVESTIGATING, RESOLVED, FALSE_POSITIVE
    acknowledged_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    acknowledged_at = db.Column(db.DateTime)
    resolved_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    resolved_at = db.Column(db.DateTime)
    resolution_notes = db.Column(db.Text)
    
    # Timestamps
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Alert types
    ALERT_BRUTE_FORCE = 'BRUTE_FORCE'
    ALERT_UNUSUAL_ACCESS = 'UNUSUAL_ACCESS'
    ALERT_AFTER_HOURS = 'AFTER_HOURS_ACCESS'
    ALERT_MASS_EXPORT = 'MASS_EXPORT'
    ALERT_BREAK_GLASS = 'BREAK_GLASS'
    ALERT_PRIVILEGE_ESCALATION = 'PRIVILEGE_ESCALATION'
    ALERT_CONCURRENT_SESSIONS = 'CONCURRENT_SESSIONS'
    
    @classmethod
    def create_alert(cls, alert_type, severity, title, description=None, 
                    user_id=None, patient_id=None, ip_address=None):
        """Create a security alert"""
        alert = cls(
            alert_type=alert_type,
            severity=severity,
            title=title,
            description=description,
            user_id=user_id,
            patient_id=patient_id,
            ip_address=ip_address
        )
        db.session.add(alert)
        db.session.commit()
        return alert
    
    def to_dict(self):
        return {
            'id': self.id,
            'alert_type': self.alert_type,
            'severity': self.severity,
            'title': self.title,
            'description': self.description,
            'status': self.status,
            'created_at': self.created_at.isoformat()
        }


class PHIAccessLog(db.Model):
    """Dedicated PHI access log for HIPAA compliance reporting"""
    __tablename__ = 'phi_access_logs'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # Access details
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    patient_id = db.Column(db.Integer, nullable=False, index=True)
    patient_mrn = db.Column(db.String(50))
    patient_name = db.Column(db.String(200))  # Denormalized for reporting
    
    # What was accessed
    access_type = db.Column(db.String(50), nullable=False)  # VIEW, EDIT, PRINT, EXPORT
    data_category = db.Column(db.String(50), nullable=False)  # Demographics, Medications, etc.
    specific_data = db.Column(db.Text)  # JSON description of specific data accessed
    
    # Context
    access_reason = db.Column(db.String(500))  # Treatment, Payment, Operations, Other
    relationship_to_patient = db.Column(db.String(100))  # Attending, Nurse, Consultant, etc.
    is_authorized = db.Column(db.Boolean, default=True)
    is_break_glass = db.Column(db.Boolean, default=False)
    
    # Location
    facility_id = db.Column(db.Integer)
    department_id = db.Column(db.Integer)
    workstation = db.Column(db.String(100))
    ip_address = db.Column(db.String(45))
    
    # Timestamp
    accessed_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    # Duration (for read access)
    duration_seconds = db.Column(db.Integer)
    
    def to_dict(self):
        return {
            'id': self.id,
            'user_id': self.user_id,
            'patient_id': self.patient_id,
            'patient_mrn': self.patient_mrn,
            'patient_name': self.patient_name,
            'access_type': self.access_type,
            'data_category': self.data_category,
            'access_reason': self.access_reason,
            'is_break_glass': self.is_break_glass,
            'accessed_at': self.accessed_at.isoformat()
        }


class DataEncryptionKey(db.Model):
    """Key management for data encryption"""
    __tablename__ = 'data_encryption_keys'
    
    id = db.Column(db.Integer, primary_key=True)
    key_id = db.Column(db.String(64), unique=True, nullable=False)
    key_type = db.Column(db.String(20), nullable=False)  # MASTER, DATA, BACKUP
    algorithm = db.Column(db.String(20), default='AES-256-GCM')
    
    # Key material (encrypted with master key)
    encrypted_key = db.Column(db.LargeBinary)
    key_version = db.Column(db.Integer, default=1)
    
    # Status
    status = db.Column(db.String(20), default='ACTIVE')  # ACTIVE, ROTATING, RETIRED
    
    # Lifecycle
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    activated_at = db.Column(db.DateTime)
    expires_at = db.Column(db.DateTime)
    retired_at = db.Column(db.DateTime)
    
    # Usage tracking
    last_used_at = db.Column(db.DateTime)
    usage_count = db.Column(db.Integer, default=0)


class ConsentRecord(db.Model):
    """Patient consent tracking for HIPAA"""
    __tablename__ = 'consent_records'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, nullable=False, index=True)
    
    # Consent type
    consent_type = db.Column(db.String(50), nullable=False)  # TREATMENT, DISCLOSURE, RESEARCH, etc.
    consent_scope = db.Column(db.String(100))  # Specific scope of consent
    
    # Status
    status = db.Column(db.String(20), default='ACTIVE')  # ACTIVE, REVOKED, EXPIRED
    
    # Details
    description = db.Column(db.Text)
    document_id = db.Column(db.String(100))  # Reference to signed document
    
    # Dates
    effective_date = db.Column(db.Date, nullable=False)
    expiration_date = db.Column(db.Date)
    revoked_date = db.Column(db.Date)
    
    # Signatures
    patient_signature = db.Column(db.Boolean, default=False)
    witness_signature = db.Column(db.Boolean, default=False)
    witness_name = db.Column(db.String(200))
    
    # Audit
    recorded_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    recorded_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'consent_type': self.consent_type,
            'status': self.status,
            'effective_date': self.effective_date.isoformat() if self.effective_date else None,
            'expiration_date': self.expiration_date.isoformat() if self.expiration_date else None
        }


class BusinessAssociateAgreement(db.Model):
    """BAA tracking for third-party integrations"""
    __tablename__ = 'business_associate_agreements'
    
    id = db.Column(db.Integer, primary_key=True)
    
    # Business Associate
    organization_name = db.Column(db.String(200), nullable=False)
    organization_type = db.Column(db.String(50))  # Clearinghouse, IT Vendor, etc.
    contact_name = db.Column(db.String(200))
    contact_email = db.Column(db.String(255))
    contact_phone = db.Column(db.String(20))
    
    # Agreement
    agreement_number = db.Column(db.String(50), unique=True)
    effective_date = db.Column(db.Date, nullable=False)
    expiration_date = db.Column(db.Date)
    renewal_date = db.Column(db.Date)
    
    # Status
    status = db.Column(db.String(20), default='ACTIVE')
    
    # Documents
    document_url = db.Column(db.String(500))
    
    # Scope
    data_types_shared = db.Column(db.Text)  # JSON array
    purposes = db.Column(db.Text)  # JSON array
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'organization_name': self.organization_name,
            'status': self.status,
            'effective_date': self.effective_date.isoformat() if self.effective_date else None,
            'expiration_date': self.expiration_date.isoformat() if self.expiration_date else None
        }

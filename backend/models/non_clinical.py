"""
Non-Clinical Features Models
Billing, Messaging, Documents, Reporting
"""
from datetime import datetime
from flask_sqlalchemy import SQLAlchemy
import json
import uuid

db = SQLAlchemy()


# ============================================================
# BILLING & CLAIMS
# ============================================================

class BillingAccount(db.Model):
    """Patient billing account"""
    __tablename__ = 'billing_accounts'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    account_number = db.Column(db.String(20), unique=True)
    
    # Balance
    current_balance = db.Column(db.Numeric(12, 2), default=0)
    insurance_balance = db.Column(db.Numeric(12, 2), default=0)
    patient_balance = db.Column(db.Numeric(12, 2), default=0)
    
    # Status
    status = db.Column(db.String(20), default='active')  # active, collections, closed
    is_self_pay = db.Column(db.Boolean, default=False)
    
    # Payment plan
    has_payment_plan = db.Column(db.Boolean, default=False)
    monthly_payment_amount = db.Column(db.Numeric(10, 2))
    
    # Collections
    sent_to_collections = db.Column(db.Boolean, default=False)
    collections_date = db.Column(db.Date)
    collections_agency = db.Column(db.String(100))
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'account_number': self.account_number,
            'current_balance': float(self.current_balance) if self.current_balance else 0,
            'insurance_balance': float(self.insurance_balance) if self.insurance_balance else 0,
            'patient_balance': float(self.patient_balance) if self.patient_balance else 0,
            'status': self.status
        }


class Charge(db.Model):
    """Individual charges/line items"""
    __tablename__ = 'charges'
    
    id = db.Column(db.Integer, primary_key=True)
    billing_account_id = db.Column(db.Integer, db.ForeignKey('billing_accounts.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Charge details
    service_date = db.Column(db.Date, nullable=False)
    procedure_code = db.Column(db.String(10), nullable=False)  # CPT code
    procedure_description = db.Column(db.String(500))
    
    # Diagnosis codes
    diagnosis_codes = db.Column(db.Text)  # JSON array of ICD-10 codes
    
    # Units and amounts
    units = db.Column(db.Integer, default=1)
    unit_charge = db.Column(db.Numeric(10, 2), nullable=False)
    total_charge = db.Column(db.Numeric(10, 2), nullable=False)
    
    # Modifiers
    modifier1 = db.Column(db.String(2))
    modifier2 = db.Column(db.String(2))
    modifier3 = db.Column(db.String(2))
    modifier4 = db.Column(db.String(2))
    
    # Place of service
    place_of_service = db.Column(db.String(2), default='11')  # Office
    
    # Provider
    rendering_provider_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    supervising_provider_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    # Status
    status = db.Column(db.String(20), default='pending')
    # pending, billed, paid, denied, adjusted, void
    
    # Posting
    posted_at = db.Column(db.DateTime)
    posted_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'service_date': self.service_date.isoformat() if self.service_date else None,
            'procedure_code': self.procedure_code,
            'procedure_description': self.procedure_description,
            'units': self.units,
            'unit_charge': float(self.unit_charge) if self.unit_charge else 0,
            'total_charge': float(self.total_charge) if self.total_charge else 0,
            'status': self.status
        }


class Claim(db.Model):
    """Insurance claims"""
    __tablename__ = 'claims'
    
    id = db.Column(db.Integer, primary_key=True)
    claim_number = db.Column(db.String(30), unique=True)
    billing_account_id = db.Column(db.Integer, db.ForeignKey('billing_accounts.id'), nullable=False)
    
    # Insurance
    insurance_id = db.Column(db.Integer, db.ForeignKey('patient_insurance.id'), nullable=False)
    payer_id = db.Column(db.String(20))
    payer_name = db.Column(db.String(200))
    
    # Dates
    service_date_from = db.Column(db.Date, nullable=False)
    service_date_to = db.Column(db.Date, nullable=False)
    submission_date = db.Column(db.Date)
    
    # Amounts
    total_charges = db.Column(db.Numeric(12, 2), nullable=False)
    allowed_amount = db.Column(db.Numeric(12, 2))
    paid_amount = db.Column(db.Numeric(12, 2))
    patient_responsibility = db.Column(db.Numeric(12, 2))
    adjustment_amount = db.Column(db.Numeric(12, 2))
    
    # Claim type
    claim_type = db.Column(db.String(20), default='professional')  # professional, institutional
    claim_frequency = db.Column(db.String(1), default='1')  # 1=original, 7=replacement, 8=void
    
    # Status
    status = db.Column(db.String(30), default='created')
    # created, validated, submitted, acknowledged, pending, paid, denied, appealed
    
    # Submission details
    submission_method = db.Column(db.String(20))  # electronic, paper
    clearinghouse_id = db.Column(db.String(50))
    payer_claim_number = db.Column(db.String(50))
    
    # Response
    adjudication_date = db.Column(db.Date)
    denial_reason_code = db.Column(db.String(10))
    denial_reason = db.Column(db.String(500))
    
    # ERA/EOB
    era_received_date = db.Column(db.Date)
    era_check_number = db.Column(db.String(30))
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'claim_number': self.claim_number,
            'payer_name': self.payer_name,
            'service_date_from': self.service_date_from.isoformat() if self.service_date_from else None,
            'total_charges': float(self.total_charges) if self.total_charges else 0,
            'paid_amount': float(self.paid_amount) if self.paid_amount else 0,
            'status': self.status
        }


class Payment(db.Model):
    """Payment transactions"""
    __tablename__ = 'payments'
    
    id = db.Column(db.Integer, primary_key=True)
    payment_number = db.Column(db.String(30), unique=True)
    billing_account_id = db.Column(db.Integer, db.ForeignKey('billing_accounts.id'), nullable=False)
    
    # Payment info
    payment_date = db.Column(db.Date, nullable=False)
    payment_amount = db.Column(db.Numeric(12, 2), nullable=False)
    
    # Source
    payment_source = db.Column(db.String(20))  # patient, insurance, adjustment
    payer_type = db.Column(db.String(20))  # primary, secondary, tertiary, patient
    
    # Method
    payment_method = db.Column(db.String(20))  # cash, check, credit, eft
    check_number = db.Column(db.String(30))
    reference_number = db.Column(db.String(50))
    
    # Claim link
    claim_id = db.Column(db.Integer, db.ForeignKey('claims.id'))
    
    # Status
    status = db.Column(db.String(20), default='posted')  # posted, reversed, refunded
    
    # Audit
    posted_at = db.Column(db.DateTime, default=datetime.utcnow)
    posted_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'payment_number': self.payment_number,
            'payment_date': self.payment_date.isoformat() if self.payment_date else None,
            'payment_amount': float(self.payment_amount) if self.payment_amount else 0,
            'payment_source': self.payment_source,
            'payment_method': self.payment_method,
            'status': self.status
        }


# ============================================================
# SECURE MESSAGING
# ============================================================

class MessageThread(db.Model):
    """Secure message threads"""
    __tablename__ = 'message_threads'
    
    id = db.Column(db.Integer, primary_key=True)
    thread_uuid = db.Column(db.String(36), unique=True, default=lambda: str(uuid.uuid4()))
    
    # Subject and type
    subject = db.Column(db.String(500), nullable=False)
    thread_type = db.Column(db.String(30), default='general')
    # general, clinical, refill_request, appointment, billing, referral
    
    # Priority
    priority = db.Column(db.String(10), default='normal')  # low, normal, high, urgent
    
    # Patient context (optional)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    
    # Status
    status = db.Column(db.String(20), default='open')  # open, closed, archived
    
    # Timestamps
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    last_message_at = db.Column(db.DateTime, default=datetime.utcnow)
    closed_at = db.Column(db.DateTime)
    
    # Creator
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    
    def to_dict(self):
        return {
            'id': self.id,
            'thread_uuid': self.thread_uuid,
            'subject': self.subject,
            'thread_type': self.thread_type,
            'priority': self.priority,
            'patient_id': self.patient_id,
            'status': self.status,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'last_message_at': self.last_message_at.isoformat() if self.last_message_at else None
        }


class Message(db.Model):
    """Individual messages"""
    __tablename__ = 'messages'
    
    id = db.Column(db.Integer, primary_key=True)
    message_uuid = db.Column(db.String(36), unique=True, default=lambda: str(uuid.uuid4()))
    thread_id = db.Column(db.Integer, db.ForeignKey('message_threads.id'), nullable=False)
    
    # Sender
    sender_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    sender_type = db.Column(db.String(20))  # staff, patient, system
    
    # Content (encrypted at rest)
    content = db.Column(db.Text, nullable=False)
    content_type = db.Column(db.String(20), default='text')  # text, html
    
    # Reply info
    reply_to_id = db.Column(db.Integer, db.ForeignKey('messages.id'))
    
    # Status
    is_draft = db.Column(db.Boolean, default=False)
    
    # Timestamps
    sent_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Attachments indicator
    has_attachments = db.Column(db.Boolean, default=False)
    
    def to_dict(self):
        return {
            'id': self.id,
            'message_uuid': self.message_uuid,
            'thread_id': self.thread_id,
            'sender_id': self.sender_id,
            'content': self.content,
            'sent_at': self.sent_at.isoformat() if self.sent_at else None,
            'has_attachments': self.has_attachments
        }


class MessageRecipient(db.Model):
    """Message recipients and read status"""
    __tablename__ = 'message_recipients'
    
    id = db.Column(db.Integer, primary_key=True)
    message_id = db.Column(db.Integer, db.ForeignKey('messages.id'), nullable=False)
    recipient_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    
    # Type
    recipient_type = db.Column(db.String(10), default='to')  # to, cc
    
    # Status
    is_read = db.Column(db.Boolean, default=False)
    read_at = db.Column(db.DateTime)
    
    # Actions
    is_deleted = db.Column(db.Boolean, default=False)
    deleted_at = db.Column(db.DateTime)
    is_flagged = db.Column(db.Boolean, default=False)
    
    def to_dict(self):
        return {
            'id': self.id,
            'message_id': self.message_id,
            'recipient_id': self.recipient_id,
            'recipient_type': self.recipient_type,
            'is_read': self.is_read,
            'read_at': self.read_at.isoformat() if self.read_at else None
        }


class MessageAttachment(db.Model):
    """Attachments to messages"""
    __tablename__ = 'message_attachments'
    
    id = db.Column(db.Integer, primary_key=True)
    message_id = db.Column(db.Integer, db.ForeignKey('messages.id'), nullable=False)
    document_id = db.Column(db.Integer, db.ForeignKey('documents.id'), nullable=False)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


# ============================================================
# DOCUMENT MANAGEMENT
# ============================================================

class Document(db.Model):
    """Document storage and management"""
    __tablename__ = 'documents'
    
    id = db.Column(db.Integer, primary_key=True)
    document_uuid = db.Column(db.String(36), unique=True, default=lambda: str(uuid.uuid4()))
    
    # Basic info
    title = db.Column(db.String(500), nullable=False)
    description = db.Column(db.Text)
    
    # Type and category
    document_type = db.Column(db.String(50), nullable=False)
    # clinical_note, lab_result, imaging, consent, insurance, correspondence, etc.
    category = db.Column(db.String(50))
    
    # Patient context
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # File info
    file_name = db.Column(db.String(255), nullable=False)
    file_type = db.Column(db.String(50))  # MIME type
    file_size = db.Column(db.Integer)
    file_path = db.Column(db.String(500))  # Encrypted storage path
    file_hash = db.Column(db.String(64))  # SHA-256 for integrity
    
    # Security
    is_encrypted = db.Column(db.Boolean, default=True)
    encryption_key_id = db.Column(db.Integer, db.ForeignKey('data_encryption_keys.id'))
    
    # Status
    status = db.Column(db.String(20), default='active')  # active, archived, deleted
    
    # Review status (for incoming documents)
    review_status = db.Column(db.String(20), default='pending')
    # pending, reviewed, signed, filed
    reviewed_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    reviewed_at = db.Column(db.DateTime)
    
    # Signature
    requires_signature = db.Column(db.Boolean, default=False)
    signed_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    signed_at = db.Column(db.DateTime)
    
    # Source
    source = db.Column(db.String(50))  # scan, fax, upload, system, interface
    source_reference = db.Column(db.String(100))
    
    # Dates
    document_date = db.Column(db.Date)  # Date of the document content
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'document_uuid': self.document_uuid,
            'title': self.title,
            'document_type': self.document_type,
            'category': self.category,
            'patient_id': self.patient_id,
            'file_name': self.file_name,
            'file_type': self.file_type,
            'file_size': self.file_size,
            'status': self.status,
            'review_status': self.review_status,
            'document_date': self.document_date.isoformat() if self.document_date else None,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class DocumentTemplate(db.Model):
    """Document templates"""
    __tablename__ = 'document_templates'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    code = db.Column(db.String(50), unique=True)
    
    # Type
    template_type = db.Column(db.String(50))  # letter, form, consent, etc.
    category = db.Column(db.String(50))
    
    # Content
    content = db.Column(db.Text)  # HTML template with placeholders
    
    # Settings
    is_active = db.Column(db.Boolean, default=True)
    requires_signature = db.Column(db.Boolean, default=False)
    
    # Department restrictions
    department_ids = db.Column(db.Text)  # JSON array
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'code': self.code,
            'template_type': self.template_type,
            'is_active': self.is_active
        }


# ============================================================
# REPORTING
# ============================================================

class ReportDefinition(db.Model):
    """Report definitions"""
    __tablename__ = 'report_definitions'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    code = db.Column(db.String(50), unique=True)
    description = db.Column(db.Text)
    
    # Type
    report_type = db.Column(db.String(50))
    # clinical, financial, operational, compliance, custom
    category = db.Column(db.String(50))
    
    # Query
    query_definition = db.Column(db.Text)  # SQL or JSON definition
    parameters = db.Column(db.Text)  # JSON array of parameter definitions
    
    # Output
    default_format = db.Column(db.String(20), default='pdf')  # pdf, excel, csv, html
    
    # Access
    is_public = db.Column(db.Boolean, default=False)
    required_permission = db.Column(db.String(100))
    
    # Settings
    is_active = db.Column(db.Boolean, default=True)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'code': self.code,
            'report_type': self.report_type,
            'category': self.category,
            'default_format': self.default_format,
            'is_active': self.is_active
        }


class ReportExecution(db.Model):
    """Report execution history"""
    __tablename__ = 'report_executions'
    
    id = db.Column(db.Integer, primary_key=True)
    report_id = db.Column(db.Integer, db.ForeignKey('report_definitions.id'), nullable=False)
    
    # Execution details
    parameters_used = db.Column(db.Text)  # JSON
    output_format = db.Column(db.String(20))
    
    # Status
    status = db.Column(db.String(20), default='queued')
    # queued, running, completed, failed
    
    # Results
    row_count = db.Column(db.Integer)
    file_path = db.Column(db.String(500))
    error_message = db.Column(db.Text)
    
    # Timing
    started_at = db.Column(db.DateTime)
    completed_at = db.Column(db.DateTime)
    execution_time_ms = db.Column(db.Integer)
    
    # User
    executed_by = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'report_id': self.report_id,
            'status': self.status,
            'row_count': self.row_count,
            'started_at': self.started_at.isoformat() if self.started_at else None,
            'completed_at': self.completed_at.isoformat() if self.completed_at else None,
            'execution_time_ms': self.execution_time_ms
        }


class ScheduledReport(db.Model):
    """Scheduled report jobs"""
    __tablename__ = 'scheduled_reports'
    
    id = db.Column(db.Integer, primary_key=True)
    report_id = db.Column(db.Integer, db.ForeignKey('report_definitions.id'), nullable=False)
    name = db.Column(db.String(200), nullable=False)
    
    # Schedule
    schedule_type = db.Column(db.String(20))  # daily, weekly, monthly
    schedule_time = db.Column(db.Time)
    schedule_day = db.Column(db.Integer)  # Day of week (0-6) or day of month (1-31)
    
    # Parameters
    parameters = db.Column(db.Text)  # JSON
    output_format = db.Column(db.String(20), default='pdf')
    
    # Distribution
    email_recipients = db.Column(db.Text)  # JSON array
    save_to_path = db.Column(db.String(500))
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    last_run_at = db.Column(db.DateTime)
    next_run_at = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'report_id': self.report_id,
            'name': self.name,
            'schedule_type': self.schedule_type,
            'is_active': self.is_active,
            'last_run_at': self.last_run_at.isoformat() if self.last_run_at else None,
            'next_run_at': self.next_run_at.isoformat() if self.next_run_at else None
        }


# ============================================================
# TASKS & WORKFLOW
# ============================================================

class Task(db.Model):
    """Tasks and to-do items"""
    __tablename__ = 'tasks'
    
    id = db.Column(db.Integer, primary_key=True)
    task_uuid = db.Column(db.String(36), unique=True, default=lambda: str(uuid.uuid4()))
    
    # Task info
    title = db.Column(db.String(500), nullable=False)
    description = db.Column(db.Text)
    task_type = db.Column(db.String(50))  # followup, callback, review, authorization, etc.
    
    # Priority
    priority = db.Column(db.String(10), default='normal')  # low, normal, high, urgent
    
    # Context
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Assignment
    assigned_to = db.Column(db.Integer, db.ForeignKey('users.id'))
    assigned_to_role = db.Column(db.String(50))  # Role-based assignment
    assigned_to_department = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # Dates
    due_date = db.Column(db.Date)
    due_time = db.Column(db.Time)
    
    # Status
    status = db.Column(db.String(20), default='open')
    # open, in_progress, completed, cancelled
    
    # Completion
    completed_at = db.Column(db.DateTime)
    completed_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    completion_notes = db.Column(db.Text)
    
    # Audit
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    
    def to_dict(self):
        return {
            'id': self.id,
            'task_uuid': self.task_uuid,
            'title': self.title,
            'task_type': self.task_type,
            'priority': self.priority,
            'patient_id': self.patient_id,
            'assigned_to': self.assigned_to,
            'due_date': self.due_date.isoformat() if self.due_date else None,
            'status': self.status,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class Alert(db.Model):
    """System alerts and notifications"""
    __tablename__ = 'alerts'
    
    id = db.Column(db.Integer, primary_key=True)
    alert_uuid = db.Column(db.String(36), unique=True, default=lambda: str(uuid.uuid4()))
    
    # Alert info
    title = db.Column(db.String(500), nullable=False)
    message = db.Column(db.Text)
    alert_type = db.Column(db.String(50))
    # clinical, result, medication, appointment, task, system
    severity = db.Column(db.String(20), default='info')
    # info, warning, urgent, critical
    
    # Context
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'))
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Target
    target_user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    target_role = db.Column(db.String(50))
    target_department = db.Column(db.Integer, db.ForeignKey('departments.id'))
    
    # Action
    action_url = db.Column(db.String(500))
    action_label = db.Column(db.String(100))
    
    # Status
    is_read = db.Column(db.Boolean, default=False)
    read_at = db.Column(db.DateTime)
    is_dismissed = db.Column(db.Boolean, default=False)
    dismissed_at = db.Column(db.DateTime)
    
    # Expiration
    expires_at = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'alert_uuid': self.alert_uuid,
            'title': self.title,
            'alert_type': self.alert_type,
            'severity': self.severity,
            'patient_id': self.patient_id,
            'is_read': self.is_read,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }

"""
Insurance Model - Patient insurance coverage and payer information
"""
from datetime import datetime
from models import db


class InsurancePayer(db.Model):
    """Insurance companies/payers master table"""
    __tablename__ = 'insurance_payers'
    
    id = db.Column(db.Integer, primary_key=True)
    payer_id = db.Column(db.String(50), unique=True, nullable=False)  # Internal payer ID
    name = db.Column(db.String(200), nullable=False)
    payer_type = db.Column(db.String(50))  # Commercial, Medicare, Medicaid, Workers Comp, etc.
    address = db.Column(db.String(255))
    city = db.Column(db.String(100))
    state = db.Column(db.String(2))
    zip_code = db.Column(db.String(10))
    phone = db.Column(db.String(20))
    fax = db.Column(db.String(20))
    claims_address = db.Column(db.String(255))
    electronic_payer_id = db.Column(db.String(50))  # EDI/clearinghouse ID
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    coverages = db.relationship('InsuranceCoverage', backref='payer', lazy=True)
    
    def to_dict(self):
        return {
            'id': self.id,
            'payer_id': self.payer_id,
            'name': self.name,
            'payer_type': self.payer_type,
            'address': self.address,
            'city': self.city,
            'state': self.state,
            'zip_code': self.zip_code,
            'phone': self.phone,
            'fax': self.fax,
            'is_active': self.is_active
        }


class InsuranceCoverage(db.Model):
    """Patient insurance coverage/policy information"""
    __tablename__ = 'insurance_coverages'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    payer_id = db.Column(db.Integer, db.ForeignKey('insurance_payers.id'), nullable=False)
    
    # Coverage Level
    coverage_level = db.Column(db.String(20), default='Primary')  # Primary, Secondary, Tertiary
    coverage_priority = db.Column(db.Integer, default=1)  # 1 = Primary, 2 = Secondary, etc.
    
    # Policy Information
    policy_number = db.Column(db.String(100), nullable=False)
    group_number = db.Column(db.String(100))
    group_name = db.Column(db.String(200))
    plan_name = db.Column(db.String(200))
    plan_type = db.Column(db.String(50))  # HMO, PPO, EPO, POS, etc.
    
    # Subscriber Information
    subscriber_id = db.Column(db.String(100))  # Can be different from policy number
    subscriber_first_name = db.Column(db.String(100))
    subscriber_last_name = db.Column(db.String(100))
    subscriber_dob = db.Column(db.Date)
    subscriber_relationship = db.Column(db.String(50))  # Self, Spouse, Child, Other
    subscriber_employer = db.Column(db.String(200))
    
    # Effective Dates
    effective_date = db.Column(db.Date)
    termination_date = db.Column(db.Date)
    
    # Benefits Info
    copay_primary_care = db.Column(db.Numeric(10, 2))
    copay_specialist = db.Column(db.Numeric(10, 2))
    copay_urgent_care = db.Column(db.Numeric(10, 2))
    copay_emergency = db.Column(db.Numeric(10, 2))
    deductible_individual = db.Column(db.Numeric(10, 2))
    deductible_family = db.Column(db.Numeric(10, 2))
    deductible_met = db.Column(db.Numeric(10, 2))
    out_of_pocket_max_individual = db.Column(db.Numeric(10, 2))
    out_of_pocket_max_family = db.Column(db.Numeric(10, 2))
    out_of_pocket_met = db.Column(db.Numeric(10, 2))
    
    # Authorization
    requires_referral = db.Column(db.Boolean, default=False)
    requires_preauthorization = db.Column(db.Boolean, default=False)
    preauth_phone = db.Column(db.String(20))
    
    # Status
    is_verified = db.Column(db.Boolean, default=False)
    verification_date = db.Column(db.DateTime)
    verified_by = db.Column(db.String(200))
    is_active = db.Column(db.Boolean, default=True)
    
    # Metadata
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    patient = db.relationship('Patient', backref=db.backref('insurance_coverages', lazy=True))
    authorizations = db.relationship('InsuranceAuthorization', backref='coverage', lazy=True)
    
    def is_coverage_active(self):
        """Check if coverage is currently active based on dates"""
        today = datetime.today().date()
        if not self.effective_date:
            return self.is_active
        if self.termination_date and self.termination_date < today:
            return False
        return self.effective_date <= today and self.is_active
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'payer_id': self.payer_id,
            'payer_name': self.payer.name if self.payer else None,
            'coverage_level': self.coverage_level,
            'coverage_priority': self.coverage_priority,
            'policy_number': self.policy_number,
            'group_number': self.group_number,
            'group_name': self.group_name,
            'plan_name': self.plan_name,
            'plan_type': self.plan_type,
            'subscriber_id': self.subscriber_id,
            'subscriber_name': f"{self.subscriber_first_name} {self.subscriber_last_name}" if self.subscriber_first_name else None,
            'subscriber_relationship': self.subscriber_relationship,
            'effective_date': self.effective_date.strftime('%m/%d/%Y') if self.effective_date else None,
            'termination_date': self.termination_date.strftime('%m/%d/%Y') if self.termination_date else None,
            'copay_primary_care': float(self.copay_primary_care) if self.copay_primary_care else None,
            'copay_specialist': float(self.copay_specialist) if self.copay_specialist else None,
            'copay_emergency': float(self.copay_emergency) if self.copay_emergency else None,
            'deductible_individual': float(self.deductible_individual) if self.deductible_individual else None,
            'deductible_met': float(self.deductible_met) if self.deductible_met else None,
            'out_of_pocket_max_individual': float(self.out_of_pocket_max_individual) if self.out_of_pocket_max_individual else None,
            'out_of_pocket_met': float(self.out_of_pocket_met) if self.out_of_pocket_met else None,
            'requires_referral': self.requires_referral,
            'requires_preauthorization': self.requires_preauthorization,
            'is_verified': self.is_verified,
            'verification_date': self.verification_date.strftime('%m/%d/%Y') if self.verification_date else None,
            'is_active': self.is_coverage_active()
        }


class InsuranceAuthorization(db.Model):
    """Prior authorizations and referrals"""
    __tablename__ = 'insurance_authorizations'
    
    id = db.Column(db.Integer, primary_key=True)
    coverage_id = db.Column(db.Integer, db.ForeignKey('insurance_coverages.id'), nullable=False)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    
    # Authorization Details
    auth_number = db.Column(db.String(100), nullable=False)
    auth_type = db.Column(db.String(50))  # Prior Auth, Referral, Certification
    service_type = db.Column(db.String(200))  # What's being authorized
    procedure_codes = db.Column(db.Text)  # JSON or comma-separated CPT codes
    diagnosis_codes = db.Column(db.Text)  # JSON or comma-separated ICD codes
    
    # Provider Info
    requesting_provider = db.Column(db.String(200))
    servicing_provider = db.Column(db.String(200))
    servicing_facility = db.Column(db.String(200))
    
    # Authorization Limits
    units_approved = db.Column(db.Integer)
    units_used = db.Column(db.Integer, default=0)
    visits_approved = db.Column(db.Integer)
    visits_used = db.Column(db.Integer, default=0)
    
    # Dates
    request_date = db.Column(db.Date)
    start_date = db.Column(db.Date)
    end_date = db.Column(db.Date)
    decision_date = db.Column(db.Date)
    
    # Status
    status = db.Column(db.String(50), default='Pending')  # Pending, Approved, Denied, Partial, Expired
    decision_reason = db.Column(db.Text)
    
    # Contact
    contact_name = db.Column(db.String(200))
    contact_phone = db.Column(db.String(20))
    reference_number = db.Column(db.String(100))  # Payer reference
    
    # Metadata
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    created_by = db.Column(db.String(200))
    
    def is_valid(self):
        """Check if authorization is currently valid"""
        today = datetime.today().date()
        if self.status != 'Approved':
            return False
        if self.end_date and self.end_date < today:
            return False
        if self.units_approved and self.units_used >= self.units_approved:
            return False
        if self.visits_approved and self.visits_used >= self.visits_approved:
            return False
        return True
    
    def to_dict(self):
        return {
            'id': self.id,
            'coverage_id': self.coverage_id,
            'patient_id': self.patient_id,
            'auth_number': self.auth_number,
            'auth_type': self.auth_type,
            'service_type': self.service_type,
            'procedure_codes': self.procedure_codes,
            'diagnosis_codes': self.diagnosis_codes,
            'requesting_provider': self.requesting_provider,
            'servicing_provider': self.servicing_provider,
            'units_approved': self.units_approved,
            'units_used': self.units_used,
            'visits_approved': self.visits_approved,
            'visits_used': self.visits_used,
            'start_date': self.start_date.strftime('%m/%d/%Y') if self.start_date else None,
            'end_date': self.end_date.strftime('%m/%d/%Y') if self.end_date else None,
            'status': self.status,
            'is_valid': self.is_valid(),
            'notes': self.notes
        }


class EligibilityCheck(db.Model):
    """Insurance eligibility verification history"""
    __tablename__ = 'eligibility_checks'
    
    id = db.Column(db.Integer, primary_key=True)
    coverage_id = db.Column(db.Integer, db.ForeignKey('insurance_coverages.id'), nullable=False)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    
    # Check Details
    check_date = db.Column(db.DateTime, default=datetime.utcnow)
    service_date = db.Column(db.Date)  # Date of service being checked
    check_type = db.Column(db.String(50))  # Real-time, Manual, Batch
    
    # Response
    is_eligible = db.Column(db.Boolean)
    coverage_status = db.Column(db.String(50))  # Active, Inactive, Pending
    response_code = db.Column(db.String(20))
    response_message = db.Column(db.Text)
    response_raw = db.Column(db.Text)  # Raw 271 response or API response
    
    # Benefits returned
    benefits_info = db.Column(db.Text)  # JSON with deductible, copay info
    
    # Metadata
    checked_by = db.Column(db.String(200))
    source = db.Column(db.String(100))  # Clearinghouse name, portal, etc.
    transaction_id = db.Column(db.String(100))  # EDI trace number
    
    def to_dict(self):
        return {
            'id': self.id,
            'coverage_id': self.coverage_id,
            'patient_id': self.patient_id,
            'check_date': self.check_date.strftime('%m/%d/%Y %H:%M') if self.check_date else None,
            'service_date': self.service_date.strftime('%m/%d/%Y') if self.service_date else None,
            'is_eligible': self.is_eligible,
            'coverage_status': self.coverage_status,
            'response_message': self.response_message,
            'checked_by': self.checked_by
        }

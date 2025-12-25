"""
Clearinghouse Integration Models
For insurance eligibility verification via EDI 270/271
"""
from datetime import datetime
from . import db
import json


class ClearinghouseConfig(db.Model):
    """Clearinghouse integration configuration"""
    __tablename__ = 'clearinghouse_configs'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)  # Display name
    provider = db.Column(db.String(50), nullable=False)  # availity, change_healthcare, waystar, trizetto, manual
    
    # API Configuration
    api_url = db.Column(db.String(500))
    api_username = db.Column(db.String(200))
    api_password = db.Column(db.String(200))  # Should be encrypted in production
    api_key = db.Column(db.String(500))
    submitter_id = db.Column(db.String(100))  # EDI Submitter ID
    
    # Availity-specific
    availity_client_id = db.Column(db.String(200))
    availity_client_secret = db.Column(db.String(500))
    availity_customer_id = db.Column(db.String(100))
    
    # Additional config as JSON
    extra_config = db.Column(db.Text)  # JSON for provider-specific settings
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    is_primary = db.Column(db.Boolean, default=False)  # Primary clearinghouse to use
    test_mode = db.Column(db.Boolean, default=True)  # Use test endpoints
    
    # Metadata
    last_test_date = db.Column(db.DateTime)
    last_test_result = db.Column(db.String(50))  # success, failed
    last_test_message = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    created_by = db.Column(db.String(200))
    
    def to_dict(self, include_secrets=False):
        result = {
            'id': self.id,
            'name': self.name,
            'provider': self.provider,
            'api_url': self.api_url,
            'submitter_id': self.submitter_id,
            'is_active': self.is_active,
            'is_primary': self.is_primary,
            'test_mode': self.test_mode,
            'last_test_date': self.last_test_date.strftime('%Y-%m-%d %H:%M') if self.last_test_date else None,
            'last_test_result': self.last_test_result,
            'last_test_message': self.last_test_message,
            'created_at': self.created_at.strftime('%Y-%m-%d %H:%M') if self.created_at else None
        }
        if include_secrets:
            result['api_username'] = self.api_username
            result['api_key'] = self.api_key
            result['availity_client_id'] = self.availity_client_id
            result['availity_customer_id'] = self.availity_customer_id
        return result


class EligibilityTransaction(db.Model):
    """Track all eligibility check transactions"""
    __tablename__ = 'eligibility_transactions'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    coverage_id = db.Column(db.Integer, db.ForeignKey('insurance_coverages.id'))
    clearinghouse_id = db.Column(db.Integer, db.ForeignKey('clearinghouse_configs.id'))
    
    # Request Info
    transaction_type = db.Column(db.String(20))  # 270_request, manual
    service_date = db.Column(db.Date)
    service_type_code = db.Column(db.String(10))  # 30=Health Benefit Plan Coverage
    
    # Payer Info
    payer_id = db.Column(db.String(50))
    payer_name = db.Column(db.String(200))
    electronic_payer_id = db.Column(db.String(50))  # EDI Payer ID
    
    # Member Info
    subscriber_id = db.Column(db.String(100))
    member_id = db.Column(db.String(100))
    member_dob = db.Column(db.Date)
    
    # Response Info
    response_status = db.Column(db.String(50))  # success, error, timeout, not_found
    is_eligible = db.Column(db.Boolean)
    coverage_status = db.Column(db.String(50))  # active, inactive, pending
    
    # Response Details (270/271)
    response_code = db.Column(db.String(20))
    response_message = db.Column(db.Text)
    raw_request = db.Column(db.Text)
    raw_response = db.Column(db.Text)
    
    # Benefits returned
    benefits_data = db.Column(db.Text)  # JSON with deductible, copay, OOP info
    
    # Manual entry fields
    manual_verified_by = db.Column(db.String(200))
    manual_notes = db.Column(db.Text)
    manual_reference = db.Column(db.String(100))  # Call reference number
    
    # Metadata
    trace_number = db.Column(db.String(100))  # EDI trace number
    transaction_date = db.Column(db.DateTime, default=datetime.utcnow)
    response_time_ms = db.Column(db.Integer)  # Response time in milliseconds
    created_by = db.Column(db.String(200))
    
    # Relationships
    patient = db.relationship('Patient', backref=db.backref('eligibility_transactions', lazy=True))
    clearinghouse = db.relationship('ClearinghouseConfig', backref=db.backref('transactions', lazy=True))
    
    def set_benefits(self, benefits_dict):
        """Store benefits data as JSON"""
        self.benefits_data = json.dumps(benefits_dict) if benefits_dict else None
    
    def get_benefits(self):
        """Retrieve benefits data as dict"""
        return json.loads(self.benefits_data) if self.benefits_data else {}
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'coverage_id': self.coverage_id,
            'transaction_type': self.transaction_type,
            'service_date': self.service_date.strftime('%m/%d/%Y') if self.service_date else None,
            'payer_name': self.payer_name,
            'subscriber_id': self.subscriber_id,
            'is_eligible': self.is_eligible,
            'coverage_status': self.coverage_status,
            'response_status': self.response_status,
            'response_message': self.response_message,
            'benefits': self.get_benefits(),
            'transaction_date': self.transaction_date.strftime('%m/%d/%Y %H:%M') if self.transaction_date else None,
            'manual_verified_by': self.manual_verified_by,
            'manual_notes': self.manual_notes,
            'manual_reference': self.manual_reference,
            'created_by': self.created_by
        }


# Pre-defined clearinghouse providers
CLEARINGHOUSE_PROVIDERS = {
    'availity': {
        'name': 'Availity',
        'description': 'Industry-leading health information network',
        'prod_url': 'https://api.availity.com',
        'test_url': 'https://api.availity.com/availity/test',
        'supports_270271': True,
        'is_free': False
    },
    'change_healthcare': {
        'name': 'Change Healthcare',
        'description': 'Comprehensive healthcare technology platform',
        'prod_url': 'https://api.changehealthcare.com',
        'test_url': 'https://sandbox.changehealthcare.com',
        'supports_270271': True,
        'is_free': False
    },
    'waystar': {
        'name': 'Waystar',
        'description': 'Cloud-based revenue cycle technology',
        'prod_url': 'https://api.waystar.com',
        'test_url': 'https://sandbox.waystar.com',
        'supports_270271': True,
        'is_free': False
    },
    'trizetto': {
        'name': 'Trizetto (Cognizant)',
        'description': 'Healthcare IT and services',
        'prod_url': 'https://gateway.trizetto.com',
        'test_url': 'https://gatewaytest.trizetto.com',
        'supports_270271': True,
        'is_free': False
    },
    'office_ally': {
        'name': 'Office Ally',
        'description': 'Free healthcare solutions provider',
        'prod_url': 'https://www.officeally.com/secure/api',
        'test_url': 'https://www.officeally.com/secure/api',
        'supports_270271': True,
        'is_free': True
    },
    'claims_md': {
        'name': 'Claims.MD',
        'description': 'Affordable clearinghouse services',
        'prod_url': 'https://api.claims.md',
        'test_url': 'https://sandbox.claims.md',
        'supports_270271': True,
        'is_free': False
    },
    'manual': {
        'name': 'Manual Verification',
        'description': 'Manual phone/portal verification without EDI integration',
        'prod_url': None,
        'test_url': None,
        'supports_270271': False,
        'is_free': True
    }
}

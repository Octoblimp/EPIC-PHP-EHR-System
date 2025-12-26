"""
Patient Model - Core patient demographics and information
SECURITY: All PHI/PII fields use automatic AES-256-GCM encryption
Data is encrypted on write, decrypted on read - completely transparent
"""
from datetime import datetime
from . import db
from utils.encryption import EncryptedString, EncryptedText

class Patient(db.Model):
    """Patient demographics and core information
    
    ENCRYPTION: All personal/medical data is automatically encrypted at rest.
    The encryption/decryption happens transparently via SQLAlchemy type decorators.
    """
    __tablename__ = 'patients'
    
    id = db.Column(db.Integer, primary_key=True)
    mrn = db.Column(db.String(20), unique=True, nullable=False)  # MRN stays plain for indexing
    csn = db.Column(db.String(20))  # Contact Serial Number (encounter)
    
    # Demographics - ALL ENCRYPTED
    first_name = db.Column(EncryptedString(100), nullable=False)
    last_name = db.Column(EncryptedString(100), nullable=False)
    middle_name = db.Column(EncryptedString(100))
    date_of_birth = db.Column(db.Date, nullable=False)  # Date type for calculations
    gender = db.Column(EncryptedString(20))
    ssn_last_four = db.Column(EncryptedString(4))
    
    # Contact Info - ALL ENCRYPTED
    address = db.Column(EncryptedString(255))
    city = db.Column(EncryptedString(100))
    state = db.Column(EncryptedString(2))
    zip_code = db.Column(EncryptedString(10))
    phone_home = db.Column(EncryptedString(20))
    phone_cell = db.Column(EncryptedString(20))
    email = db.Column(EncryptedString(255))
    
    # Medical Info - ALL ENCRYPTED
    blood_type = db.Column(EncryptedString(5))
    height_inches = db.Column(db.Float)  # Numeric for calculations
    weight_lbs = db.Column(db.Float)  # Numeric for calculations
    bmi = db.Column(db.Float)  # Numeric for calculations
    
    # Administrative - ENCRYPTED
    primary_care_provider = db.Column(EncryptedString(200))
    insurance_plan = db.Column(EncryptedString(200))
    preferred_language = db.Column(EncryptedString(50))
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    allergies = db.relationship('Allergy', backref='patient', lazy=True)
    encounters = db.relationship('Encounter', backref='patient', lazy=True)
    medications = db.relationship('Medication', backref='patient', lazy=True)
    orders = db.relationship('Order', backref='patient', lazy=True)
    vitals = db.relationship('Vital', backref='patient', lazy=True)
    flowsheet_entries = db.relationship('FlowsheetEntry', backref='patient', lazy=True)
    
    def get_age(self):
        """Calculate patient age"""
        today = datetime.today()
        return today.year - self.date_of_birth.year - (
            (today.month, today.day) < (self.date_of_birth.month, self.date_of_birth.day)
        )
    
    def get_full_name(self):
        """Return formatted full name"""
        return f"{self.last_name}, {self.first_name}"
    
    def to_dict(self):
        """Convert patient to dictionary"""
        return {
            'id': self.id,
            'mrn': self.mrn,
            'csn': self.csn,
            'first_name': self.first_name,
            'last_name': self.last_name,
            'middle_name': self.middle_name,
            'full_name': self.get_full_name(),
            'date_of_birth': self.date_of_birth.strftime('%m/%d/%Y') if self.date_of_birth else None,
            'age': self.get_age(),
            'gender': self.gender,
            'address': self.address,
            'city': self.city,
            'state': self.state,
            'zip_code': self.zip_code,
            'phone_home': self.phone_home,
            'phone_cell': self.phone_cell,
            'email': self.email,
            'blood_type': self.blood_type,
            'height_inches': self.height_inches,
            'weight_lbs': self.weight_lbs,
            'bmi': self.bmi,
            'primary_care_provider': self.primary_care_provider,
            'insurance_plan': self.insurance_plan,
            'preferred_language': self.preferred_language,
            'allergies': [a.to_dict() for a in self.allergies],
            'is_active': self.is_active
        }


class Allergy(db.Model):
    """Patient allergies - Clinical data is encrypted"""
    __tablename__ = 'allergies'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    allergen = db.Column(EncryptedString(200), nullable=False)
    reaction = db.Column(EncryptedString(200))
    severity = db.Column(EncryptedString(50))  # Mild, Moderate, Severe
    allergy_type = db.Column(EncryptedString(50))  # Drug, Food, Environmental
    onset_date = db.Column(db.Date)
    is_active = db.Column(db.Boolean, default=True)
    verified_by = db.Column(EncryptedString(200))
    verified_date = db.Column(db.DateTime)
    
    def to_dict(self):
        return {
            'id': self.id,
            'allergen': self.allergen,
            'reaction': self.reaction,
            'severity': self.severity,
            'allergy_type': self.allergy_type,
            'is_active': self.is_active
        }

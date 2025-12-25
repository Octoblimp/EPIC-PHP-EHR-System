"""
Patient Model - Core patient demographics and information
"""
from datetime import datetime
from . import db

class Patient(db.Model):
    """Patient demographics and core information"""
    __tablename__ = 'patients'
    
    id = db.Column(db.Integer, primary_key=True)
    mrn = db.Column(db.String(20), unique=True, nullable=False)  # Medical Record Number
    csn = db.Column(db.String(20))  # Contact Serial Number (encounter)
    
    # Demographics
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    middle_name = db.Column(db.String(100))
    date_of_birth = db.Column(db.Date, nullable=False)
    gender = db.Column(db.String(20))
    ssn_last_four = db.Column(db.String(4))
    
    # Contact Info
    address = db.Column(db.String(255))
    city = db.Column(db.String(100))
    state = db.Column(db.String(2))
    zip_code = db.Column(db.String(10))
    phone_home = db.Column(db.String(20))
    phone_cell = db.Column(db.String(20))
    email = db.Column(db.String(255))
    
    # Medical Info
    blood_type = db.Column(db.String(5))
    height_inches = db.Column(db.Float)
    weight_lbs = db.Column(db.Float)
    bmi = db.Column(db.Float)
    
    # Administrative
    primary_care_provider = db.Column(db.String(200))
    insurance_plan = db.Column(db.String(200))
    preferred_language = db.Column(db.String(50), default='English')
    
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
    """Patient allergies"""
    __tablename__ = 'allergies'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    allergen = db.Column(db.String(200), nullable=False)
    reaction = db.Column(db.String(200))
    severity = db.Column(db.String(50))  # Mild, Moderate, Severe
    allergy_type = db.Column(db.String(50))  # Drug, Food, Environmental
    onset_date = db.Column(db.Date)
    is_active = db.Column(db.Boolean, default=True)
    verified_by = db.Column(db.String(200))
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

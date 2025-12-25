"""
Medication Model - Patient medications and orders
"""
from datetime import datetime
from . import db

class Medication(db.Model):
    """Patient medications - both inpatient and outpatient"""
    __tablename__ = 'medications'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Medication Info
    name = db.Column(db.String(200), nullable=False)
    generic_name = db.Column(db.String(200))
    brand_name = db.Column(db.String(200))
    
    # Dosing
    dose = db.Column(db.String(100))
    dose_unit = db.Column(db.String(50))
    route = db.Column(db.String(50))  # PO, IV, IM, SubQ, etc.
    frequency = db.Column(db.String(100))  # BID, TID, PRN, etc.
    
    # Additional Info
    indication = db.Column(db.String(200))
    instructions = db.Column(db.Text)
    pharmacy_instructions = db.Column(db.Text)
    
    # Order Info
    ordering_provider = db.Column(db.String(200))
    order_date = db.Column(db.DateTime)
    start_date = db.Column(db.DateTime)
    end_date = db.Column(db.DateTime)
    
    # Status
    status = db.Column(db.String(50), default='Active')  # Active, Discontinued, Completed, On Hold
    med_type = db.Column(db.String(50))  # Scheduled, PRN, Continuous, One-time
    is_home_med = db.Column(db.Boolean, default=False)
    is_high_alert = db.Column(db.Boolean, default=False)
    
    # Administration
    last_given = db.Column(db.DateTime)
    next_due = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'name': self.name,
            'generic_name': self.generic_name,
            'brand_name': self.brand_name,
            'dose': self.dose,
            'dose_unit': self.dose_unit,
            'route': self.route,
            'frequency': self.frequency,
            'full_dose': f"{self.dose} {self.dose_unit or ''} {self.route or ''} {self.frequency or ''}".strip(),
            'indication': self.indication,
            'instructions': self.instructions,
            'ordering_provider': self.ordering_provider,
            'order_date': self.order_date.strftime('%m/%d/%Y %H:%M') if self.order_date else None,
            'start_date': self.start_date.strftime('%m/%d/%Y %H:%M') if self.start_date else None,
            'end_date': self.end_date.strftime('%m/%d/%Y %H:%M') if self.end_date else None,
            'status': self.status,
            'med_type': self.med_type,
            'is_home_med': self.is_home_med,
            'is_high_alert': self.is_high_alert,
            'last_given': self.last_given.strftime('%m/%d/%Y %H:%M') if self.last_given else None,
            'next_due': self.next_due.strftime('%m/%d/%Y %H:%M') if self.next_due else None
        }


class MedicationAdministration(db.Model):
    """Records of medication administration (MAR)"""
    __tablename__ = 'medication_administrations'
    
    id = db.Column(db.Integer, primary_key=True)
    medication_id = db.Column(db.Integer, db.ForeignKey('medications.id'), nullable=False)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    
    # Administration Details
    scheduled_time = db.Column(db.DateTime)
    administered_time = db.Column(db.DateTime)
    administered_by = db.Column(db.String(200))
    
    # Dose Given
    dose_given = db.Column(db.String(100))
    dose_unit = db.Column(db.String(50))
    route = db.Column(db.String(50))
    site = db.Column(db.String(100))  # For injections
    
    # Status
    status = db.Column(db.String(50))  # Given, Held, Refused, Not Given
    hold_reason = db.Column(db.String(200))
    notes = db.Column(db.Text)
    
    # Verification
    verified_by = db.Column(db.String(200))
    verified_time = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'medication_id': self.medication_id,
            'scheduled_time': self.scheduled_time.strftime('%m/%d/%Y %H:%M') if self.scheduled_time else None,
            'administered_time': self.administered_time.strftime('%m/%d/%Y %H:%M') if self.administered_time else None,
            'administered_by': self.administered_by,
            'dose_given': self.dose_given,
            'status': self.status,
            'notes': self.notes
        }

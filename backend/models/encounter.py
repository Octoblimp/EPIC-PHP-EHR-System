"""
Encounter Model - Patient visits and admissions
"""
from datetime import datetime
from models import db

class Encounter(db.Model):
    """Patient encounter/visit information"""
    __tablename__ = 'encounters'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    csn = db.Column(db.String(20), unique=True, nullable=False)  # Contact Serial Number
    
    # Encounter Details
    encounter_type = db.Column(db.String(50))  # Inpatient, Outpatient, ED, Observation
    patient_class = db.Column(db.String(50))  # Inpatient, Outpatient in a Bed, etc.
    admission_date = db.Column(db.DateTime, nullable=False)
    discharge_date = db.Column(db.DateTime)
    
    # Location
    facility = db.Column(db.String(200))
    department = db.Column(db.String(200))
    room = db.Column(db.String(20))
    bed = db.Column(db.String(20))
    unit = db.Column(db.String(100))
    
    # Care Team
    attending_provider = db.Column(db.String(200))
    admitting_provider = db.Column(db.String(200))
    primary_nurse = db.Column(db.String(200))
    treatment_team = db.Column(db.String(500))
    
    # Clinical
    chief_complaint = db.Column(db.Text)
    admission_diagnosis = db.Column(db.String(500))
    readmit_risk = db.Column(db.Integer)  # 0-100
    level_of_care = db.Column(db.String(50))
    code_status = db.Column(db.String(50))  # Full Code, DNR, DNI, etc.
    isolation_status = db.Column(db.String(100))
    
    # Administrative
    financial_class = db.Column(db.String(100))
    pop = db.Column(db.String(50))  # Point of Presentation
    status = db.Column(db.String(50), default='Active')  # Active, Discharged, Transferred
    
    # Dates
    ad_lwi_received = db.Column(db.DateTime)  # Advanced Directives received
    ad_poa_declined = db.Column(db.DateTime)  # Power of Attorney declined
    hclr = db.Column(db.String(100))  # Healthcare Legal Representative
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def get_location_string(self):
        """Get formatted location"""
        parts = []
        if self.facility:
            parts.append(self.facility)
        if self.unit:
            parts.append(self.unit)
        if self.room:
            parts.append(f"Room {self.room}")
        if self.bed:
            parts.append(f"Bed {self.bed}")
        return ' - '.join(parts) if parts else 'Not assigned'
    
    def to_dict(self):
        return {
            'id': self.id,
            'csn': self.csn,
            'patient_id': self.patient_id,
            'encounter_type': self.encounter_type,
            'patient_class': self.patient_class,
            'admission_date': self.admission_date.strftime('%m/%d/%Y %H:%M') if self.admission_date else None,
            'discharge_date': self.discharge_date.strftime('%m/%d/%Y %H:%M') if self.discharge_date else None,
            'facility': self.facility,
            'department': self.department,
            'room': self.room,
            'bed': self.bed,
            'unit': self.unit,
            'location': self.get_location_string(),
            'attending_provider': self.attending_provider,
            'admitting_provider': self.admitting_provider,
            'primary_nurse': self.primary_nurse,
            'treatment_team': self.treatment_team,
            'chief_complaint': self.chief_complaint,
            'admission_diagnosis': self.admission_diagnosis,
            'readmit_risk': self.readmit_risk,
            'level_of_care': self.level_of_care,
            'code_status': self.code_status,
            'isolation_status': self.isolation_status,
            'status': self.status,
            'ad_lwi_received': self.ad_lwi_received.strftime('%m/%d/%Y') if self.ad_lwi_received else None,
            'ad_poa_declined': self.ad_poa_declined.strftime('%m/%d/%Y') if self.ad_poa_declined else None,
            'hclr': self.hclr
        }

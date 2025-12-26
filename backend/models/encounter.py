"""
Encounter Model - Patient visits, admissions, and sticky notes
SECURITY: PHI/clinical data is automatically encrypted at rest
"""
from datetime import datetime
from . import db
from utils.encryption import EncryptedString, EncryptedText

class Encounter(db.Model):
    """Patient encounter/visit information - PHI encrypted"""
    __tablename__ = 'encounters'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    csn = db.Column(db.String(20), unique=True, nullable=False)  # Contact Serial Number - needed for lookups
    
    # Encounter Details
    encounter_type = db.Column(db.String(50))  # Inpatient, Outpatient, ED, Observation
    patient_class = db.Column(db.String(50))  # Inpatient, Outpatient in a Bed, etc.
    admission_date = db.Column(db.DateTime, nullable=False)
    discharge_date = db.Column(db.DateTime)
    expected_discharge_date = db.Column(db.DateTime)  # Expected D/C
    
    # Location - ENCRYPTED (PHI when combined with patient)
    facility = db.Column(EncryptedString(200))
    department = db.Column(EncryptedString(200))
    room = db.Column(EncryptedString(20))
    bed = db.Column(EncryptedString(20))
    unit = db.Column(EncryptedString(100))
    nursing_station = db.Column(EncryptedString(100))
    
    # Care Team - ENCRYPTED (provider names are sensitive)
    attending_provider = db.Column(EncryptedString(200))
    attending_provider_id = db.Column(db.Integer)
    admitting_provider = db.Column(EncryptedString(200))
    primary_nurse = db.Column(EncryptedString(200))
    primary_nurse_id = db.Column(db.Integer)
    treatment_team = db.Column(EncryptedString(500))
    consulting_providers = db.Column(EncryptedText())  # JSON array of consults
    
    # Clinical - ENCRYPTED (sensitive diagnoses)
    chief_complaint = db.Column(EncryptedText())
    admission_diagnosis = db.Column(EncryptedString(500))
    principal_diagnosis = db.Column(EncryptedString(500))
    secondary_diagnoses = db.Column(EncryptedText())  # JSON array
    readmit_risk = db.Column(db.Integer)  # 0-100
    level_of_care = db.Column(db.String(50))
    code_status = db.Column(EncryptedString(50))  # Full Code, DNR, DNI, etc. - sensitive
    isolation_status = db.Column(EncryptedString(100))
    isolation_type = db.Column(EncryptedString(100))  # Contact, Droplet, Airborne, etc.
    fall_risk = db.Column(db.Boolean, default=False)
    fall_risk_score = db.Column(db.Integer)
    suicide_risk = db.Column(db.Boolean, default=False)
    elopement_risk = db.Column(db.Boolean, default=False)
    pressure_ulcer_risk = db.Column(db.Boolean, default=False)
    
    # Patient Safety Alerts - ENCRYPTED
    allergy_alerts = db.Column(db.Boolean, default=False)
    critical_alerts = db.Column(EncryptedText())  # JSON array of critical alerts
    
    # Administrative
    financial_class = db.Column(db.String(100))
    pop = db.Column(db.String(50))  # Point of Presentation
    status = db.Column(db.String(50), default='Active')  # Active, Discharged, Transferred, Expired
    disposition = db.Column(EncryptedString(100))  # Discharge disposition
    
    # Transfer info - ENCRYPTED
    transferred_from = db.Column(EncryptedString(200))
    transferred_to = db.Column(EncryptedString(200))
    transfer_reason = db.Column(EncryptedText())
    
    # Dates
    ad_lwi_received = db.Column(db.DateTime)  # Advanced Directives received
    ad_poa_declined = db.Column(db.DateTime)  # Power of Attorney declined
    hclr = db.Column(EncryptedString(100))  # Healthcare Legal Representative - PII
    
    # Billing
    drg = db.Column(db.String(20))  # Diagnosis Related Group
    drg_weight = db.Column(db.Float)
    expected_reimbursement = db.Column(db.Numeric(12, 2))
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    sticky_notes = db.relationship('StickyNote', backref='encounter', lazy=True, 
                                   foreign_keys='StickyNote.encounter_id')
    
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
    
    def get_active_alerts(self):
        """Get list of active safety alerts"""
        alerts = []
        if self.code_status and self.code_status.upper() != 'FULL CODE':
            alerts.append({'type': 'code_status', 'value': self.code_status, 'color': 'red'})
        if self.fall_risk:
            alerts.append({'type': 'fall_risk', 'value': 'Fall Risk', 'color': 'yellow'})
        if self.isolation_status:
            alerts.append({'type': 'isolation', 'value': self.isolation_type or 'Isolation', 'color': 'purple'})
        if self.suicide_risk:
            alerts.append({'type': 'suicide', 'value': 'Suicide Precautions', 'color': 'orange'})
        if self.elopement_risk:
            alerts.append({'type': 'elopement', 'value': 'Elopement Risk', 'color': 'blue'})
        if self.pressure_ulcer_risk:
            alerts.append({'type': 'pressure_ulcer', 'value': 'Pressure Injury Risk', 'color': 'pink'})
        return alerts
    
    def to_dict(self):
        return {
            'id': self.id,
            'csn': self.csn,
            'patient_id': self.patient_id,
            'encounter_type': self.encounter_type,
            'patient_class': self.patient_class,
            'admission_date': self.admission_date.strftime('%m/%d/%Y %H:%M') if self.admission_date else None,
            'discharge_date': self.discharge_date.strftime('%m/%d/%Y %H:%M') if self.discharge_date else None,
            'expected_discharge_date': self.expected_discharge_date.strftime('%m/%d/%Y') if self.expected_discharge_date else None,
            'facility': self.facility,
            'department': self.department,
            'room': self.room,
            'bed': self.bed,
            'unit': self.unit,
            'nursing_station': self.nursing_station,
            'location': self.get_location_string(),
            'attending_provider': self.attending_provider,
            'admitting_provider': self.admitting_provider,
            'primary_nurse': self.primary_nurse,
            'treatment_team': self.treatment_team,
            'chief_complaint': self.chief_complaint,
            'admission_diagnosis': self.admission_diagnosis,
            'principal_diagnosis': self.principal_diagnosis,
            'readmit_risk': self.readmit_risk,
            'level_of_care': self.level_of_care,
            'code_status': self.code_status,
            'isolation_status': self.isolation_status,
            'isolation_type': self.isolation_type,
            'fall_risk': self.fall_risk,
            'fall_risk_score': self.fall_risk_score,
            'suicide_risk': self.suicide_risk,
            'elopement_risk': self.elopement_risk,
            'pressure_ulcer_risk': self.pressure_ulcer_risk,
            'alerts': self.get_active_alerts(),
            'status': self.status,
            'disposition': self.disposition,
            'ad_lwi_received': self.ad_lwi_received.strftime('%m/%d/%Y') if self.ad_lwi_received else None,
            'ad_poa_declined': self.ad_poa_declined.strftime('%m/%d/%Y') if self.ad_poa_declined else None,
            'hclr': self.hclr,
            'drg': self.drg,
            'sticky_notes': [note.to_dict() for note in self.sticky_notes if note.is_active]
        }


class StickyNote(db.Model):
    """Patient/encounter sticky notes - important alerts and reminders - ENCRYPTED"""
    __tablename__ = 'sticky_notes'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))  # Optional - can be patient-level
    
    # Note Content - ENCRYPTED (may contain PHI)
    title = db.Column(EncryptedString(200), nullable=False)
    content = db.Column(EncryptedText(), nullable=False)
    note_type = db.Column(db.String(50))  # Clinical, Administrative, Social, Safety, etc.
    priority = db.Column(db.String(20), default='Normal')  # Low, Normal, High, Critical
    color = db.Column(db.String(20), default='yellow')  # yellow, blue, green, red, purple, orange
    
    # Display Settings
    show_on_banner = db.Column(db.Boolean, default=True)  # Show in patient banner
    show_in_chart = db.Column(db.Boolean, default=True)   # Show in chart review
    show_popup = db.Column(db.Boolean, default=False)     # Show as popup when opening chart
    
    # Expiration
    expires_at = db.Column(db.DateTime)  # Auto-expire date
    expires_on_discharge = db.Column(db.Boolean, default=False)  # Expire when discharged
    
    # Status - ENCRYPTED acknowledgements
    is_active = db.Column(db.Boolean, default=True)
    acknowledged_by = db.Column(EncryptedString(200))
    acknowledged_at = db.Column(db.DateTime)
    
    # Audit - ENCRYPTED staff names
    created_by = db.Column(EncryptedString(200), nullable=False)
    created_by_id = db.Column(db.Integer)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_by = db.Column(EncryptedString(200))
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    patient = db.relationship('Patient', backref=db.backref('sticky_notes', lazy=True))
    
    def is_expired(self):
        """Check if note has expired"""
        if not self.is_active:
            return True
        if self.expires_at and datetime.utcnow() > self.expires_at:
            return True
        return False
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'encounter_id': self.encounter_id,
            'title': self.title,
            'content': self.content,
            'note_type': self.note_type,
            'priority': self.priority,
            'color': self.color,
            'show_on_banner': self.show_on_banner,
            'show_popup': self.show_popup,
            'expires_at': self.expires_at.strftime('%m/%d/%Y %H:%M') if self.expires_at else None,
            'is_active': self.is_active and not self.is_expired(),
            'created_by': self.created_by,
            'created_at': self.created_at.strftime('%m/%d/%Y %H:%M') if self.created_at else None,
            'updated_at': self.updated_at.strftime('%m/%d/%Y %H:%M') if self.updated_at else None
        }

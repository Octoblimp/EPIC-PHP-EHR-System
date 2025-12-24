"""
Vital Signs Model - Patient vital signs documentation
"""
from datetime import datetime
from models import db

class Vital(db.Model):
    """Patient vital signs"""
    __tablename__ = 'vitals'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Timestamp
    recorded_date = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    recorded_by = db.Column(db.String(200))
    
    # Core Vitals
    temperature = db.Column(db.Float)  # Fahrenheit
    temp_source = db.Column(db.String(50))  # Oral, Axillary, Rectal, Tympanic, Temporal
    heart_rate = db.Column(db.Integer)  # BPM
    respiratory_rate = db.Column(db.Integer)  # Breaths per minute
    
    # Blood Pressure
    bp_systolic = db.Column(db.Integer)
    bp_diastolic = db.Column(db.Integer)
    bp_position = db.Column(db.String(50))  # Sitting, Standing, Lying
    bp_location = db.Column(db.String(50))  # Left arm, Right arm
    
    # Oxygen
    spo2 = db.Column(db.Integer)  # Oxygen saturation %
    o2_device = db.Column(db.String(100))  # Room air, Nasal cannula, etc.
    o2_flow_rate = db.Column(db.Float)  # L/min
    fio2 = db.Column(db.Integer)  # % for ventilated patients
    
    # Pain
    pain_score = db.Column(db.Integer)  # 0-10
    pain_location = db.Column(db.String(200))
    
    # Additional
    weight_kg = db.Column(db.Float)
    height_cm = db.Column(db.Float)
    bmi = db.Column(db.Float)
    
    # Neuro (for specific units)
    gcs_eye = db.Column(db.Integer)
    gcs_verbal = db.Column(db.Integer)
    gcs_motor = db.Column(db.Integer)
    pupil_left = db.Column(db.String(50))
    pupil_right = db.Column(db.String(50))
    
    # OB specific
    fetal_heart_rate = db.Column(db.Integer)
    contractions = db.Column(db.String(100))
    
    # Notes
    notes = db.Column(db.Text)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def get_bp_string(self):
        """Format blood pressure"""
        if self.bp_systolic and self.bp_diastolic:
            return f"{self.bp_systolic}/{self.bp_diastolic}"
        return None
    
    def get_gcs_total(self):
        """Calculate GCS total"""
        if all([self.gcs_eye, self.gcs_verbal, self.gcs_motor]):
            return self.gcs_eye + self.gcs_verbal + self.gcs_motor
        return None
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'recorded_date': self.recorded_date.strftime('%m/%d/%Y %H:%M') if self.recorded_date else None,
            'recorded_time': self.recorded_date.strftime('%H:%M') if self.recorded_date else None,
            'recorded_by': self.recorded_by,
            'temperature': self.temperature,
            'temp_source': self.temp_source,
            'heart_rate': self.heart_rate,
            'respiratory_rate': self.respiratory_rate,
            'bp_systolic': self.bp_systolic,
            'bp_diastolic': self.bp_diastolic,
            'blood_pressure': self.get_bp_string(),
            'bp_position': self.bp_position,
            'spo2': self.spo2,
            'o2_device': self.o2_device,
            'o2_flow_rate': self.o2_flow_rate,
            'pain_score': self.pain_score,
            'pain_location': self.pain_location,
            'weight_kg': self.weight_kg,
            'height_cm': self.height_cm,
            'gcs_eye': self.gcs_eye,
            'gcs_verbal': self.gcs_verbal,
            'gcs_motor': self.gcs_motor,
            'gcs_total': self.get_gcs_total(),
            'notes': self.notes
        }

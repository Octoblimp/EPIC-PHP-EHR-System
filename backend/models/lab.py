"""
Lab Result Model - Laboratory results
"""
from datetime import datetime
from . import db

class LabResult(db.Model):
    """Laboratory results"""
    __tablename__ = 'lab_results'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    order_id = db.Column(db.Integer, db.ForeignKey('orders.id'))
    
    # Test Info
    test_name = db.Column(db.String(200), nullable=False)
    test_code = db.Column(db.String(50))
    panel_name = db.Column(db.String(200))  # If part of a panel (e.g., BMP, CBC)
    
    # Result
    value = db.Column(db.String(200))
    numeric_value = db.Column(db.Float)
    unit = db.Column(db.String(50))
    
    # Reference Range
    reference_low = db.Column(db.Float)
    reference_high = db.Column(db.Float)
    reference_text = db.Column(db.String(200))
    
    # Flags
    flag = db.Column(db.String(20))  # High, Low, Critical High, Critical Low, Abnormal
    is_critical = db.Column(db.Boolean, default=False)
    critical_acknowledged = db.Column(db.Boolean, default=False)
    critical_acknowledged_by = db.Column(db.String(200))
    
    # Timing
    collected_date = db.Column(db.DateTime)
    received_date = db.Column(db.DateTime)
    resulted_date = db.Column(db.DateTime)
    
    # Status
    status = db.Column(db.String(50), default='Final')  # Preliminary, Final, Corrected
    
    # Lab Info
    lab_name = db.Column(db.String(200))
    performing_tech = db.Column(db.String(200))
    
    # Comments
    comments = db.Column(db.Text)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def get_flag_class(self):
        """Return CSS class based on flag"""
        if self.is_critical:
            return 'critical'
        elif self.flag in ['High', 'Low', 'Abnormal']:
            return 'abnormal'
        return 'normal'
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'test_name': self.test_name,
            'test_code': self.test_code,
            'panel_name': self.panel_name,
            'value': self.value,
            'numeric_value': self.numeric_value,
            'unit': self.unit,
            'reference_low': self.reference_low,
            'reference_high': self.reference_high,
            'reference_text': self.reference_text or f"{self.reference_low}-{self.reference_high}" if self.reference_low else None,
            'flag': self.flag,
            'flag_class': self.get_flag_class(),
            'is_critical': self.is_critical,
            'collected_date': self.collected_date.strftime('%m/%d/%Y %H:%M') if self.collected_date else None,
            'resulted_date': self.resulted_date.strftime('%m/%d/%Y %H:%M') if self.resulted_date else None,
            'status': self.status,
            'comments': self.comments
        }

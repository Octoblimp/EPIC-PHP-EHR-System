"""
Order Model - Clinical orders (labs, imaging, procedures, etc.)
"""
from datetime import datetime
from models import db

class Order(db.Model):
    """Clinical orders"""
    __tablename__ = 'orders'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Order Details
    order_name = db.Column(db.String(300), nullable=False)
    order_type = db.Column(db.String(50))  # Lab, Imaging, Procedure, Consult, Nursing, Diet
    order_category = db.Column(db.String(100))
    
    # Priority
    priority = db.Column(db.String(50), default='Routine')  # Stat, Urgent, Routine, Timed
    
    # Ordering Info
    ordering_provider = db.Column(db.String(200))
    order_date = db.Column(db.DateTime, default=datetime.utcnow)
    order_time = db.Column(db.DateTime)
    
    # Scheduling
    scheduled_date = db.Column(db.DateTime)
    frequency = db.Column(db.String(100))
    duration = db.Column(db.String(100))
    
    # Status
    status = db.Column(db.String(50), default='Ordered')  # Ordered, In Progress, Completed, Cancelled, Pending
    acknowledgement_status = db.Column(db.String(50))  # Acknowledged, Pending, Not Required
    acknowledged_by = db.Column(db.String(200))
    acknowledged_date = db.Column(db.DateTime)
    
    # Clinical
    diagnosis = db.Column(db.String(500))
    clinical_indication = db.Column(db.Text)
    special_instructions = db.Column(db.Text)
    
    # Results
    result_status = db.Column(db.String(50))  # Pending, Preliminary, Final
    resulted_date = db.Column(db.DateTime)
    
    # Flags
    is_standing = db.Column(db.Boolean, default=False)
    requires_signature = db.Column(db.Boolean, default=False)
    is_signed = db.Column(db.Boolean, default=False)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'order_name': self.order_name,
            'order_type': self.order_type,
            'order_category': self.order_category,
            'priority': self.priority,
            'ordering_provider': self.ordering_provider,
            'order_date': self.order_date.strftime('%m/%d/%Y %H:%M') if self.order_date else None,
            'scheduled_date': self.scheduled_date.strftime('%m/%d/%Y %H:%M') if self.scheduled_date else None,
            'frequency': self.frequency,
            'status': self.status,
            'acknowledgement_status': self.acknowledgement_status,
            'diagnosis': self.diagnosis,
            'clinical_indication': self.clinical_indication,
            'special_instructions': self.special_instructions,
            'result_status': self.result_status,
            'is_standing': self.is_standing
        }


class OrderSet(db.Model):
    """Pre-defined order sets"""
    __tablename__ = 'order_sets'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text)
    category = db.Column(db.String(100))
    department = db.Column(db.String(100))
    orders_json = db.Column(db.Text)  # JSON array of order templates
    is_active = db.Column(db.Boolean, default=True)
    created_by = db.Column(db.String(200))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'description': self.description,
            'category': self.category,
            'department': self.department,
            'is_active': self.is_active
        }

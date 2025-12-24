"""
Routes package initialization
"""
from routes.patient_routes import patient_bp
from routes.medication_routes import medication_bp
from routes.order_routes import order_bp
from routes.vital_routes import vital_bp
from routes.flowsheet_routes import flowsheet_bp
from routes.lab_routes import lab_bp
from routes.note_routes import note_bp

__all__ = [
    'patient_bp',
    'medication_bp', 
    'order_bp',
    'vital_bp',
    'flowsheet_bp',
    'lab_bp',
    'note_bp'
]

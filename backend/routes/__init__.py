"""
Routes package initialization
Individual blueprints are imported in app.py to avoid circular imports
"""

__all__ = [
    'patient_bp',
    'medication_bp', 
    'order_bp',
    'vital_bp',
    'flowsheet_bp',
    'lab_bp',
    'note_bp',
    'shortcode_bp'
]

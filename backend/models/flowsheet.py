"""
Flowsheet Model - Documentation flowsheets and entries
"""
from datetime import datetime
from models import db

class FlowsheetTemplate(db.Model):
    """Flowsheet templates/definitions"""
    __tablename__ = 'flowsheet_templates'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    display_name = db.Column(db.String(200))
    category = db.Column(db.String(100))  # Vitals, Assessment, Intervention, etc.
    department = db.Column(db.String(100))
    description = db.Column(db.Text)
    
    # Configuration
    data_type = db.Column(db.String(50))  # numeric, text, select, multiselect, datetime
    unit = db.Column(db.String(50))
    options_json = db.Column(db.Text)  # JSON array for select options
    
    # Validation
    min_value = db.Column(db.Float)
    max_value = db.Column(db.Float)
    is_required = db.Column(db.Boolean, default=False)
    
    # Display
    display_order = db.Column(db.Integer, default=0)
    section = db.Column(db.String(100))
    subsection = db.Column(db.String(100))
    is_active = db.Column(db.Boolean, default=True)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        import json
        return {
            'id': self.id,
            'name': self.name,
            'display_name': self.display_name or self.name,
            'category': self.category,
            'department': self.department,
            'data_type': self.data_type,
            'unit': self.unit,
            'options': json.loads(self.options_json) if self.options_json else [],
            'min_value': self.min_value,
            'max_value': self.max_value,
            'is_required': self.is_required,
            'section': self.section,
            'subsection': self.subsection
        }


class FlowsheetEntry(db.Model):
    """Individual flowsheet documentation entries"""
    __tablename__ = 'flowsheet_entries'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    template_id = db.Column(db.Integer, db.ForeignKey('flowsheet_templates.id'))
    
    # Entry Data
    row_name = db.Column(db.String(200), nullable=False)
    value = db.Column(db.Text)
    numeric_value = db.Column(db.Float)
    
    # Context
    section = db.Column(db.String(100))
    subsection = db.Column(db.String(100))
    flowsheet_group = db.Column(db.String(100))  # e.g., "Pastoral Services", "Post Partum"
    
    # Timestamp
    entry_datetime = db.Column(db.DateTime, nullable=False, default=datetime.utcnow)
    documented_by = db.Column(db.String(200))
    
    # Comments
    comments = db.Column(db.Text)
    
    # Status
    status = db.Column(db.String(50), default='Active')  # Active, Deleted, Modified
    is_deleted = db.Column(db.Boolean, default=False)
    deleted_by = db.Column(db.String(200))
    deleted_date = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'encounter_id': self.encounter_id,
            'row_name': self.row_name,
            'value': self.value,
            'numeric_value': self.numeric_value,
            'section': self.section,
            'subsection': self.subsection,
            'flowsheet_group': self.flowsheet_group,
            'entry_datetime': self.entry_datetime.strftime('%m/%d/%Y %H:%M') if self.entry_datetime else None,
            'entry_date': self.entry_datetime.strftime('%m/%d/%Y') if self.entry_datetime else None,
            'entry_time': self.entry_datetime.strftime('%H%M') if self.entry_datetime else None,
            'documented_by': self.documented_by,
            'comments': self.comments,
            'status': self.status
        }


class FlowsheetSection(db.Model):
    """Flowsheet section definitions (like Pastoral Services, Post Partum Hemorrhage)"""
    __tablename__ = 'flowsheet_sections'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    display_name = db.Column(db.String(200))
    parent_section_id = db.Column(db.Integer, db.ForeignKey('flowsheet_sections.id'))
    
    description = db.Column(db.Text)
    department = db.Column(db.String(100))
    
    display_order = db.Column(db.Integer, default=0)
    is_active = db.Column(db.Boolean, default=True)
    
    # Configuration
    config_json = db.Column(db.Text)  # JSON configuration for section behavior
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Self-referential relationship for subsections
    subsections = db.relationship('FlowsheetSection', backref=db.backref('parent', remote_side=[id]))
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'display_name': self.display_name or self.name,
            'parent_section_id': self.parent_section_id,
            'description': self.description,
            'department': self.department,
            'display_order': self.display_order,
            'subsections': [s.to_dict() for s in self.subsections]
        }

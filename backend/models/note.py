"""
Note Model - Clinical documentation notes
"""
from datetime import datetime
from models import db

class Note(db.Model):
    """Clinical notes"""
    __tablename__ = 'notes'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Note Details
    note_type = db.Column(db.String(100), nullable=False)  # Progress Note, H&P, Consult, Discharge Summary
    note_title = db.Column(db.String(300))
    service = db.Column(db.String(100))  # Medicine, Surgery, Nursing, etc.
    
    # Content
    content = db.Column(db.Text)
    content_html = db.Column(db.Text)
    
    # Author Info
    author = db.Column(db.String(200), nullable=False)
    author_role = db.Column(db.String(100))  # Physician, NP, PA, RN, etc.
    cosigner = db.Column(db.String(200))
    
    # Dates
    note_date = db.Column(db.DateTime, default=datetime.utcnow)
    filed_date = db.Column(db.DateTime)
    signed_date = db.Column(db.DateTime)
    cosigned_date = db.Column(db.DateTime)
    
    # Status
    status = db.Column(db.String(50), default='In Progress')  # In Progress, Signed, Cosigned, Addended
    is_addendum = db.Column(db.Boolean, default=False)
    parent_note_id = db.Column(db.Integer, db.ForeignKey('notes.id'))
    
    # Flags
    is_sensitive = db.Column(db.Boolean, default=False)
    requires_cosign = db.Column(db.Boolean, default=False)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Self-referential for addenda
    addenda = db.relationship('Note', backref=db.backref('parent_note', remote_side=[id]))
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'note_type': self.note_type,
            'note_title': self.note_title,
            'service': self.service,
            'content': self.content,
            'author': self.author,
            'author_role': self.author_role,
            'cosigner': self.cosigner,
            'note_date': self.note_date.strftime('%m/%d/%Y %H:%M') if self.note_date else None,
            'signed_date': self.signed_date.strftime('%m/%d/%Y %H:%M') if self.signed_date else None,
            'status': self.status,
            'is_addendum': self.is_addendum,
            'requires_cosign': self.requires_cosign
        }


class SmartPhrase(db.Model):
    """SmartPhrase/SmartText templates"""
    __tablename__ = 'smart_phrases'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    abbreviation = db.Column(db.String(50), unique=True)
    content = db.Column(db.Text, nullable=False)
    
    category = db.Column(db.String(100))
    department = db.Column(db.String(100))
    owner = db.Column(db.String(200))
    
    is_shared = db.Column(db.Boolean, default=False)
    is_active = db.Column(db.Boolean, default=True)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'abbreviation': self.abbreviation,
            'content': self.content,
            'category': self.category,
            'is_shared': self.is_shared
        }

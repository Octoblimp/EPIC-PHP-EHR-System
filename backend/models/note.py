"""
Note Model - Clinical documentation notes and addenda
SECURITY: All clinical content is automatically encrypted at rest
"""
from datetime import datetime
from . import db
from utils.encryption import EncryptedString, EncryptedText

class Note(db.Model):
    """Clinical notes - Content is automatically encrypted"""
    __tablename__ = 'notes'
    
    id = db.Column(db.Integer, primary_key=True)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    encounter_id = db.Column(db.Integer, db.ForeignKey('encounters.id'))
    
    # Note Details
    note_type = db.Column(db.String(100), nullable=False)  # Plain for filtering
    note_title = db.Column(EncryptedString(300))
    service = db.Column(db.String(100))  # Plain for filtering
    specialty = db.Column(db.String(100))  # Plain for filtering
    
    # Content - ENCRYPTED (PHI)
    content = db.Column(EncryptedText())
    content_html = db.Column(EncryptedText())
    
    # Author Info - ENCRYPTED
    author = db.Column(EncryptedString(200), nullable=False)
    author_id = db.Column(db.Integer)
    author_role = db.Column(db.String(100))  # Plain for filtering
    cosigner = db.Column(EncryptedString(200))
    cosigner_id = db.Column(db.Integer)
    
    # Dates
    note_date = db.Column(db.DateTime, default=datetime.utcnow)
    filed_date = db.Column(db.DateTime)
    signed_date = db.Column(db.DateTime)
    cosigned_date = db.Column(db.DateTime)
    
    # Status
    status = db.Column(db.String(50), default='In Progress')  # In Progress, Pended, Signed, Cosigned, Addended, Amended
    is_addendum = db.Column(db.Boolean, default=False)
    is_amended = db.Column(db.Boolean, default=False)
    parent_note_id = db.Column(db.Integer, db.ForeignKey('notes.id'))
    amendment_reason = db.Column(db.Text)
    
    # Flags
    is_sensitive = db.Column(db.Boolean, default=False)
    requires_cosign = db.Column(db.Boolean, default=False)
    is_late_entry = db.Column(db.Boolean, default=False)  # Late documentation flag
    
    # Attestation (for residents/students)
    attestation_statement = db.Column(db.Text)
    attested_by = db.Column(db.String(200))
    attested_date = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Self-referential for addenda
    addenda = db.relationship('Note', backref=db.backref('parent_note', remote_side=[id]))
    
    # Relationship to formal addenda
    formal_addenda = db.relationship('NoteAddendum', backref='note', lazy=True)
    
    def can_be_addended(self):
        """Check if note can have addenda added"""
        return self.status in ['Signed', 'Cosigned', 'Addended', 'Amended']
    
    def get_full_history(self):
        """Get note with all addenda in chronological order"""
        history = [self.to_dict()]
        for addendum in sorted(self.formal_addenda, key=lambda x: x.created_at):
            history.append(addendum.to_dict())
        return history
    
    def to_dict(self):
        return {
            'id': self.id,
            'patient_id': self.patient_id,
            'encounter_id': self.encounter_id,
            'note_type': self.note_type,
            'note_title': self.note_title,
            'service': self.service,
            'specialty': self.specialty,
            'content': self.content,
            'content_html': self.content_html,
            'author': self.author,
            'author_id': self.author_id,
            'author_role': self.author_role,
            'cosigner': self.cosigner,
            'note_date': self.note_date.strftime('%m/%d/%Y %H:%M') if self.note_date else None,
            'filed_date': self.filed_date.strftime('%m/%d/%Y %H:%M') if self.filed_date else None,
            'signed_date': self.signed_date.strftime('%m/%d/%Y %H:%M') if self.signed_date else None,
            'cosigned_date': self.cosigned_date.strftime('%m/%d/%Y %H:%M') if self.cosigned_date else None,
            'status': self.status,
            'is_addendum': self.is_addendum,
            'is_amended': self.is_amended,
            'requires_cosign': self.requires_cosign,
            'is_late_entry': self.is_late_entry,
            'is_sensitive': self.is_sensitive,
            'can_be_addended': self.can_be_addended(),
            'addenda_count': len(self.formal_addenda),
            'addenda': [a.to_dict() for a in self.formal_addenda] if self.formal_addenda else []
        }


class NoteAddendum(db.Model):
    """Addenda to signed clinical notes - formal additions after signing"""
    __tablename__ = 'note_addenda'
    
    id = db.Column(db.Integer, primary_key=True)
    note_id = db.Column(db.Integer, db.ForeignKey('notes.id'), nullable=False)
    patient_id = db.Column(db.Integer, db.ForeignKey('patients.id'), nullable=False)
    
    # Addendum Type
    addendum_type = db.Column(db.String(50), default='Addendum')  # Addendum, Amendment, Correction, Late Entry
    
    # Content
    title = db.Column(db.String(300))
    content = db.Column(db.Text, nullable=False)
    content_html = db.Column(db.Text)
    reason = db.Column(db.Text)  # Reason for addendum/amendment
    
    # What was changed (for amendments)
    original_text = db.Column(db.Text)  # For amendments - what was originally written
    corrected_text = db.Column(db.Text)  # For amendments - what it's being corrected to
    
    # Author Info
    author = db.Column(db.String(200), nullable=False)
    author_id = db.Column(db.Integer)
    author_role = db.Column(db.String(100))
    
    # Dates
    addendum_date = db.Column(db.DateTime, default=datetime.utcnow)  # Date addendum refers to
    signed_date = db.Column(db.DateTime)
    
    # Status
    status = db.Column(db.String(50), default='In Progress')  # In Progress, Signed
    
    # If co-signature required
    requires_cosign = db.Column(db.Boolean, default=False)
    cosigner = db.Column(db.String(200))
    cosigned_date = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'note_id': self.note_id,
            'patient_id': self.patient_id,
            'addendum_type': self.addendum_type,
            'title': self.title,
            'content': self.content,
            'content_html': self.content_html,
            'reason': self.reason,
            'original_text': self.original_text,
            'corrected_text': self.corrected_text,
            'author': self.author,
            'author_role': self.author_role,
            'addendum_date': self.addendum_date.strftime('%m/%d/%Y %H:%M') if self.addendum_date else None,
            'signed_date': self.signed_date.strftime('%m/%d/%Y %H:%M') if self.signed_date else None,
            'status': self.status,
            'requires_cosign': self.requires_cosign,
            'cosigner': self.cosigner,
            'created_at': self.created_at.strftime('%m/%d/%Y %H:%M') if self.created_at else None
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

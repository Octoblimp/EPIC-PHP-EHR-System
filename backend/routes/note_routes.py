"""
Notes API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime, timedelta
from models import db
from models.note import Note, SmartPhrase

note_bp = Blueprint('notes', __name__)

@note_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_notes(patient_id):
    """Get notes for a patient"""
    note_type = request.args.get('type')
    days = request.args.get('days', 90, type=int)
    
    cutoff = datetime.now() - timedelta(days=days)
    
    query = Note.query.filter(
        Note.patient_id == patient_id,
        Note.note_date >= cutoff
    )
    
    if note_type:
        query = query.filter_by(note_type=note_type)
    
    notes = query.order_by(Note.note_date.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [n.to_dict() for n in notes],
        'count': len(notes)
    })

@note_bp.route('/<int:note_id>', methods=['GET'])
def get_note(note_id):
    """Get a specific note"""
    note = Note.query.get_or_404(note_id)
    return jsonify({
        'success': True,
        'data': note.to_dict()
    })

@note_bp.route('/', methods=['POST'])
def create_note():
    """Create a new note"""
    data = request.get_json()
    
    note = Note(
        patient_id=data['patient_id'],
        encounter_id=data.get('encounter_id'),
        note_type=data['note_type'],
        note_title=data.get('note_title'),
        service=data.get('service'),
        content=data['content'],
        author=data['author'],
        author_role=data.get('author_role')
    )
    
    db.session.add(note)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': note.to_dict(),
        'message': 'Note created successfully'
    }), 201

@note_bp.route('/<int:note_id>/sign', methods=['POST'])
def sign_note(note_id):
    """Sign a note"""
    data = request.get_json()
    note = Note.query.get_or_404(note_id)
    
    note.status = 'Signed'
    note.signed_date = datetime.now()
    note.filed_date = datetime.now()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': note.to_dict(),
        'message': 'Note signed successfully'
    })

@note_bp.route('/smart-phrases', methods=['GET'])
def get_smart_phrases():
    """Get available SmartPhrases"""
    category = request.args.get('category')
    
    query = SmartPhrase.query.filter_by(is_active=True)
    
    if category:
        query = query.filter_by(category=category)
    
    phrases = query.order_by(SmartPhrase.name).all()
    
    return jsonify({
        'success': True,
        'data': [p.to_dict() for p in phrases]
    })

@note_bp.route('/smart-phrases/<abbreviation>', methods=['GET'])
def get_smart_phrase(abbreviation):
    """Get a SmartPhrase by abbreviation"""
    phrase = SmartPhrase.query.filter_by(abbreviation=abbreviation, is_active=True).first_or_404()
    return jsonify({
        'success': True,
        'data': phrase.to_dict()
    })

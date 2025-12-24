"""
Patient API Routes
"""
from flask import Blueprint, jsonify, request
from models import db
from models.patient import Patient, Allergy
from models.encounter import Encounter

patient_bp = Blueprint('patients', __name__)

@patient_bp.route('/', methods=['GET'])
def get_patients():
    """Get all patients with optional filters"""
    patients = Patient.query.filter_by(is_active=True).all()
    return jsonify({
        'success': True,
        'data': [p.to_dict() for p in patients],
        'count': len(patients)
    })

@patient_bp.route('/<int:patient_id>', methods=['GET'])
def get_patient(patient_id):
    """Get a specific patient by ID"""
    patient = Patient.query.get_or_404(patient_id)
    return jsonify({
        'success': True,
        'data': patient.to_dict()
    })

@patient_bp.route('/mrn/<mrn>', methods=['GET'])
def get_patient_by_mrn(mrn):
    """Get a patient by MRN"""
    patient = Patient.query.filter_by(mrn=mrn).first_or_404()
    return jsonify({
        'success': True,
        'data': patient.to_dict()
    })

@patient_bp.route('/search', methods=['GET'])
def search_patients():
    """Search patients by name or MRN"""
    query = request.args.get('q', '')
    if len(query) < 2:
        return jsonify({'success': False, 'message': 'Search query too short'}), 400
    
    patients = Patient.query.filter(
        db.or_(
            Patient.last_name.ilike(f'%{query}%'),
            Patient.first_name.ilike(f'%{query}%'),
            Patient.mrn.ilike(f'%{query}%')
        )
    ).filter_by(is_active=True).limit(50).all()
    
    return jsonify({
        'success': True,
        'data': [p.to_dict() for p in patients],
        'count': len(patients)
    })

@patient_bp.route('/<int:patient_id>/encounters', methods=['GET'])
def get_patient_encounters(patient_id):
    """Get all encounters for a patient"""
    encounters = Encounter.query.filter_by(patient_id=patient_id).order_by(Encounter.admission_date.desc()).all()
    return jsonify({
        'success': True,
        'data': [e.to_dict() for e in encounters],
        'count': len(encounters)
    })

@patient_bp.route('/<int:patient_id>/current-encounter', methods=['GET'])
def get_current_encounter(patient_id):
    """Get the current active encounter for a patient"""
    encounter = Encounter.query.filter_by(
        patient_id=patient_id, 
        status='Active'
    ).order_by(Encounter.admission_date.desc()).first()
    
    if not encounter:
        return jsonify({'success': False, 'message': 'No active encounter found'}), 404
    
    return jsonify({
        'success': True,
        'data': encounter.to_dict()
    })

@patient_bp.route('/<int:patient_id>/allergies', methods=['GET'])
def get_patient_allergies(patient_id):
    """Get patient allergies"""
    allergies = Allergy.query.filter_by(patient_id=patient_id, is_active=True).all()
    return jsonify({
        'success': True,
        'data': [a.to_dict() for a in allergies],
        'count': len(allergies)
    })

@patient_bp.route('/<int:patient_id>/header', methods=['GET'])
def get_patient_header(patient_id):
    """Get combined patient header data (patient info + current encounter + allergies)"""
    patient = Patient.query.get_or_404(patient_id)
    encounter = Encounter.query.filter_by(
        patient_id=patient_id, 
        status='Active'
    ).order_by(Encounter.admission_date.desc()).first()
    
    allergies = Allergy.query.filter_by(patient_id=patient_id, is_active=True).all()
    
    return jsonify({
        'success': True,
        'data': {
            'patient': patient.to_dict(),
            'encounter': encounter.to_dict() if encounter else None,
            'allergies': [a.to_dict() for a in allergies],
            'allergy_status': 'Unknown: Not on File' if not allergies else ', '.join([a.allergen for a in allergies])
        }
    })

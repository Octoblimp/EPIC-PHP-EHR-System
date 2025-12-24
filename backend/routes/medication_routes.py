"""
Medication API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime
from models import db
from models.medication import Medication, MedicationAdministration

medication_bp = Blueprint('medications', __name__)

@medication_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_medications(patient_id):
    """Get all medications for a patient"""
    status = request.args.get('status', 'Active')
    
    query = Medication.query.filter_by(patient_id=patient_id)
    if status != 'all':
        query = query.filter_by(status=status)
    
    medications = query.order_by(Medication.name).all()
    
    return jsonify({
        'success': True,
        'data': [m.to_dict() for m in medications],
        'count': len(medications)
    })

@medication_bp.route('/patient/<int:patient_id>/categorized', methods=['GET'])
def get_medications_categorized(patient_id):
    """Get medications categorized by type"""
    medications = Medication.query.filter_by(patient_id=patient_id, status='Active').all()
    
    categorized = {
        'scheduled': [],
        'prn': [],
        'continuous': [],
        'home_meds': []
    }
    
    for med in medications:
        med_dict = med.to_dict()
        if med.is_home_med:
            categorized['home_meds'].append(med_dict)
        elif med.med_type == 'PRN':
            categorized['prn'].append(med_dict)
        elif med.med_type == 'Continuous':
            categorized['continuous'].append(med_dict)
        else:
            categorized['scheduled'].append(med_dict)
    
    return jsonify({
        'success': True,
        'data': categorized
    })

@medication_bp.route('/<int:med_id>', methods=['GET'])
def get_medication(med_id):
    """Get a specific medication"""
    medication = Medication.query.get_or_404(med_id)
    return jsonify({
        'success': True,
        'data': medication.to_dict()
    })

@medication_bp.route('/patient/<int:patient_id>/mar', methods=['GET'])
def get_mar(patient_id):
    """Get Medication Administration Record"""
    date_str = request.args.get('date')
    if date_str:
        try:
            target_date = datetime.strptime(date_str, '%Y-%m-%d').date()
        except ValueError:
            target_date = datetime.today().date()
    else:
        target_date = datetime.today().date()
    
    # Get all active medications
    medications = Medication.query.filter_by(patient_id=patient_id, status='Active').all()
    
    mar_data = []
    for med in medications:
        # Get administrations for this date
        admins = MedicationAdministration.query.filter(
            MedicationAdministration.medication_id == med.id,
            db.func.date(MedicationAdministration.scheduled_time) == target_date
        ).order_by(MedicationAdministration.scheduled_time).all()
        
        mar_data.append({
            'medication': med.to_dict(),
            'administrations': [a.to_dict() for a in admins]
        })
    
    return jsonify({
        'success': True,
        'data': mar_data,
        'date': target_date.strftime('%m/%d/%Y')
    })

@medication_bp.route('/administration', methods=['POST'])
def record_administration():
    """Record a medication administration"""
    data = request.get_json()
    
    admin = MedicationAdministration(
        medication_id=data['medication_id'],
        patient_id=data['patient_id'],
        scheduled_time=datetime.strptime(data['scheduled_time'], '%Y-%m-%d %H:%M') if data.get('scheduled_time') else None,
        administered_time=datetime.strptime(data['administered_time'], '%Y-%m-%d %H:%M') if data.get('administered_time') else datetime.now(),
        administered_by=data['administered_by'],
        dose_given=data.get('dose_given'),
        status=data.get('status', 'Given'),
        notes=data.get('notes')
    )
    
    db.session.add(admin)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': admin.to_dict(),
        'message': 'Administration recorded successfully'
    })

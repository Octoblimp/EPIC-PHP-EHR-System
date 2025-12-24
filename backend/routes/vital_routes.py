"""
Vital Signs API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime, timedelta
from models import db
from models.vital import Vital

vital_bp = Blueprint('vitals', __name__)

@vital_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_vitals(patient_id):
    """Get vitals for a patient"""
    # Default to last 24 hours
    hours = request.args.get('hours', 24, type=int)
    limit = request.args.get('limit', 50, type=int)
    
    cutoff = datetime.now() - timedelta(hours=hours)
    
    vitals = Vital.query.filter(
        Vital.patient_id == patient_id,
        Vital.recorded_date >= cutoff
    ).order_by(Vital.recorded_date.desc()).limit(limit).all()
    
    return jsonify({
        'success': True,
        'data': [v.to_dict() for v in vitals],
        'count': len(vitals)
    })

@vital_bp.route('/patient/<int:patient_id>/latest', methods=['GET'])
def get_latest_vitals(patient_id):
    """Get the most recent vital signs"""
    vital = Vital.query.filter_by(patient_id=patient_id).order_by(Vital.recorded_date.desc()).first()
    
    if not vital:
        return jsonify({'success': False, 'message': 'No vitals found'}), 404
    
    return jsonify({
        'success': True,
        'data': vital.to_dict()
    })

@vital_bp.route('/patient/<int:patient_id>/trends', methods=['GET'])
def get_vital_trends(patient_id):
    """Get vital sign trends for charting"""
    hours = request.args.get('hours', 72, type=int)
    vital_type = request.args.get('type', 'all')
    
    cutoff = datetime.now() - timedelta(hours=hours)
    
    vitals = Vital.query.filter(
        Vital.patient_id == patient_id,
        Vital.recorded_date >= cutoff
    ).order_by(Vital.recorded_date.asc()).all()
    
    # Format for charting
    trends = {
        'timestamps': [],
        'temperature': [],
        'heart_rate': [],
        'respiratory_rate': [],
        'bp_systolic': [],
        'bp_diastolic': [],
        'spo2': [],
        'pain_score': []
    }
    
    for v in vitals:
        trends['timestamps'].append(v.recorded_date.strftime('%m/%d %H:%M'))
        trends['temperature'].append(v.temperature)
        trends['heart_rate'].append(v.heart_rate)
        trends['respiratory_rate'].append(v.respiratory_rate)
        trends['bp_systolic'].append(v.bp_systolic)
        trends['bp_diastolic'].append(v.bp_diastolic)
        trends['spo2'].append(v.spo2)
        trends['pain_score'].append(v.pain_score)
    
    return jsonify({
        'success': True,
        'data': trends
    })

@vital_bp.route('/', methods=['POST'])
def record_vitals():
    """Record new vital signs"""
    data = request.get_json()
    
    vital = Vital(
        patient_id=data['patient_id'],
        encounter_id=data.get('encounter_id'),
        recorded_date=datetime.strptime(data['recorded_date'], '%Y-%m-%d %H:%M') if data.get('recorded_date') else datetime.now(),
        recorded_by=data['recorded_by'],
        temperature=data.get('temperature'),
        temp_source=data.get('temp_source'),
        heart_rate=data.get('heart_rate'),
        respiratory_rate=data.get('respiratory_rate'),
        bp_systolic=data.get('bp_systolic'),
        bp_diastolic=data.get('bp_diastolic'),
        bp_position=data.get('bp_position'),
        spo2=data.get('spo2'),
        o2_device=data.get('o2_device'),
        o2_flow_rate=data.get('o2_flow_rate'),
        pain_score=data.get('pain_score'),
        pain_location=data.get('pain_location'),
        weight_kg=data.get('weight_kg'),
        height_cm=data.get('height_cm'),
        notes=data.get('notes')
    )
    
    db.session.add(vital)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': vital.to_dict(),
        'message': 'Vitals recorded successfully'
    }), 201

@vital_bp.route('/<int:vital_id>', methods=['PUT'])
def update_vitals(vital_id):
    """Update vital signs"""
    data = request.get_json()
    vital = Vital.query.get_or_404(vital_id)
    
    for key, value in data.items():
        if hasattr(vital, key) and key not in ['id', 'patient_id', 'created_at']:
            setattr(vital, key, value)
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': vital.to_dict(),
        'message': 'Vitals updated successfully'
    })

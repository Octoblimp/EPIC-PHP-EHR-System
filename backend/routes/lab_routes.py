"""
Lab Results API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime, timedelta
from models import db
from models.lab import LabResult

lab_bp = Blueprint('labs', __name__)

@lab_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_labs(patient_id):
    """Get lab results for a patient"""
    days = request.args.get('days', 30, type=int)
    panel = request.args.get('panel')
    
    cutoff = datetime.now() - timedelta(days=days)
    
    query = LabResult.query.filter(
        LabResult.patient_id == patient_id,
        LabResult.resulted_date >= cutoff
    )
    
    if panel:
        query = query.filter_by(panel_name=panel)
    
    results = query.order_by(LabResult.resulted_date.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [r.to_dict() for r in results],
        'count': len(results)
    })

@lab_bp.route('/patient/<int:patient_id>/latest', methods=['GET'])
def get_latest_labs(patient_id):
    """Get latest result for each test"""
    # Get distinct test names
    subquery = db.session.query(
        LabResult.test_name,
        db.func.max(LabResult.resulted_date).label('max_date')
    ).filter_by(patient_id=patient_id).group_by(LabResult.test_name).subquery()
    
    results = LabResult.query.join(
        subquery,
        db.and_(
            LabResult.test_name == subquery.c.test_name,
            LabResult.resulted_date == subquery.c.max_date
        )
    ).filter_by(patient_id=patient_id).all()
    
    return jsonify({
        'success': True,
        'data': [r.to_dict() for r in results]
    })

@lab_bp.route('/patient/<int:patient_id>/critical', methods=['GET'])
def get_critical_labs(patient_id):
    """Get critical lab values"""
    results = LabResult.query.filter_by(
        patient_id=patient_id,
        is_critical=True,
        critical_acknowledged=False
    ).order_by(LabResult.resulted_date.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [r.to_dict() for r in results],
        'count': len(results)
    })

@lab_bp.route('/patient/<int:patient_id>/by-panel', methods=['GET'])
def get_labs_by_panel(patient_id):
    """Get labs grouped by panel"""
    days = request.args.get('days', 7, type=int)
    cutoff = datetime.now() - timedelta(days=days)
    
    results = LabResult.query.filter(
        LabResult.patient_id == patient_id,
        LabResult.resulted_date >= cutoff
    ).order_by(LabResult.resulted_date.desc()).all()
    
    by_panel = {}
    for result in results:
        panel = result.panel_name or 'Individual Tests'
        if panel not in by_panel:
            by_panel[panel] = []
        by_panel[panel].append(result.to_dict())
    
    return jsonify({
        'success': True,
        'data': by_panel
    })

@lab_bp.route('/patient/<int:patient_id>/trends/<test_name>', methods=['GET'])
def get_lab_trends(patient_id, test_name):
    """Get trending data for a specific test"""
    days = request.args.get('days', 30, type=int)
    cutoff = datetime.now() - timedelta(days=days)
    
    results = LabResult.query.filter(
        LabResult.patient_id == patient_id,
        LabResult.test_name == test_name,
        LabResult.resulted_date >= cutoff
    ).order_by(LabResult.resulted_date.asc()).all()
    
    trend_data = {
        'test_name': test_name,
        'timestamps': [r.resulted_date.strftime('%m/%d/%Y %H:%M') for r in results],
        'values': [r.numeric_value for r in results],
        'reference_low': results[0].reference_low if results else None,
        'reference_high': results[0].reference_high if results else None,
        'unit': results[0].unit if results else None
    }
    
    return jsonify({
        'success': True,
        'data': trend_data
    })

@lab_bp.route('/<int:result_id>/acknowledge', methods=['POST'])
def acknowledge_critical(result_id):
    """Acknowledge a critical lab value"""
    data = request.get_json()
    result = LabResult.query.get_or_404(result_id)
    
    result.critical_acknowledged = True
    result.critical_acknowledged_by = data['acknowledged_by']
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': result.to_dict(),
        'message': 'Critical value acknowledged'
    })

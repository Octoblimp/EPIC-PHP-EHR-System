"""
Flowsheet API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime, timedelta
from models import db
from models.flowsheet import FlowsheetTemplate, FlowsheetEntry, FlowsheetSection

flowsheet_bp = Blueprint('flowsheets', __name__)

@flowsheet_bp.route('/sections', methods=['GET'])
def get_sections():
    """Get all flowsheet sections"""
    department = request.args.get('department')
    
    query = FlowsheetSection.query.filter_by(is_active=True, parent_section_id=None)
    
    if department:
        query = query.filter_by(department=department)
    
    sections = query.order_by(FlowsheetSection.display_order).all()
    
    return jsonify({
        'success': True,
        'data': [s.to_dict() for s in sections]
    })

@flowsheet_bp.route('/templates', methods=['GET'])
def get_templates():
    """Get flowsheet templates/row definitions"""
    section = request.args.get('section')
    category = request.args.get('category')
    
    query = FlowsheetTemplate.query.filter_by(is_active=True)
    
    if section:
        query = query.filter_by(section=section)
    if category:
        query = query.filter_by(category=category)
    
    templates = query.order_by(FlowsheetTemplate.display_order).all()
    
    return jsonify({
        'success': True,
        'data': [t.to_dict() for t in templates]
    })

@flowsheet_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_flowsheet(patient_id):
    """Get flowsheet entries for a patient"""
    group = request.args.get('group')  # e.g., "Pastoral Services", "Post Partum Hemorrhage"
    section = request.args.get('section')
    hours = request.args.get('hours', 72, type=int)
    
    cutoff = datetime.now() - timedelta(hours=hours)
    
    query = FlowsheetEntry.query.filter(
        FlowsheetEntry.patient_id == patient_id,
        FlowsheetEntry.is_deleted == False,
        FlowsheetEntry.entry_datetime >= cutoff
    )
    
    if group:
        query = query.filter_by(flowsheet_group=group)
    if section:
        query = query.filter_by(section=section)
    
    entries = query.order_by(FlowsheetEntry.entry_datetime.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [e.to_dict() for e in entries],
        'count': len(entries)
    })

@flowsheet_bp.route('/patient/<int:patient_id>/grouped', methods=['GET'])
def get_flowsheet_grouped(patient_id):
    """Get flowsheet entries grouped by section/row"""
    group = request.args.get('group')
    hours = request.args.get('hours', 72, type=int)
    
    cutoff = datetime.now() - timedelta(hours=hours)
    
    query = FlowsheetEntry.query.filter(
        FlowsheetEntry.patient_id == patient_id,
        FlowsheetEntry.is_deleted == False,
        FlowsheetEntry.entry_datetime >= cutoff
    )
    
    if group:
        query = query.filter_by(flowsheet_group=group)
    
    entries = query.order_by(FlowsheetEntry.section, FlowsheetEntry.row_name, FlowsheetEntry.entry_datetime).all()
    
    # Group by section and row
    grouped = {}
    for entry in entries:
        section = entry.section or 'General'
        row = entry.row_name
        
        if section not in grouped:
            grouped[section] = {}
        if row not in grouped[section]:
            grouped[section][row] = []
        
        grouped[section][row].append(entry.to_dict())
    
    return jsonify({
        'success': True,
        'data': grouped
    })

@flowsheet_bp.route('/patient/<int:patient_id>/column-view', methods=['GET'])
def get_flowsheet_column_view(patient_id):
    """Get flowsheet data in column format (like Epic's time-based columns)"""
    group = request.args.get('group')
    hours = request.args.get('hours', 24, type=int)
    
    cutoff = datetime.now() - timedelta(hours=hours)
    
    query = FlowsheetEntry.query.filter(
        FlowsheetEntry.patient_id == patient_id,
        FlowsheetEntry.is_deleted == False,
        FlowsheetEntry.entry_datetime >= cutoff
    )
    
    if group:
        query = query.filter_by(flowsheet_group=group)
    
    entries = query.order_by(FlowsheetEntry.entry_datetime).all()
    
    # Get unique timestamps and rows
    timestamps = sorted(set([e.entry_datetime.strftime('%Y-%m-%d %H:%M') for e in entries]))
    rows = {}
    
    for entry in entries:
        row_key = f"{entry.section}|{entry.row_name}"
        if row_key not in rows:
            rows[row_key] = {
                'section': entry.section,
                'row_name': entry.row_name,
                'values': {}
            }
        timestamp_key = entry.entry_datetime.strftime('%Y-%m-%d %H:%M')
        rows[row_key]['values'][timestamp_key] = {
            'value': entry.value,
            'id': entry.id,
            'documented_by': entry.documented_by
        }
    
    return jsonify({
        'success': True,
        'data': {
            'timestamps': timestamps,
            'rows': list(rows.values())
        }
    })

@flowsheet_bp.route('/entry', methods=['POST'])
def create_entry():
    """Create a new flowsheet entry"""
    data = request.get_json()
    
    entry = FlowsheetEntry(
        patient_id=data['patient_id'],
        encounter_id=data.get('encounter_id'),
        template_id=data.get('template_id'),
        row_name=data['row_name'],
        value=data['value'],
        numeric_value=data.get('numeric_value'),
        section=data.get('section'),
        subsection=data.get('subsection'),
        flowsheet_group=data.get('flowsheet_group'),
        entry_datetime=datetime.strptime(data['entry_datetime'], '%Y-%m-%d %H:%M') if data.get('entry_datetime') else datetime.now(),
        documented_by=data['documented_by'],
        comments=data.get('comments')
    )
    
    db.session.add(entry)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': entry.to_dict(),
        'message': 'Entry created successfully'
    }), 201

@flowsheet_bp.route('/entry/<int:entry_id>', methods=['PUT'])
def update_entry(entry_id):
    """Update a flowsheet entry"""
    data = request.get_json()
    entry = FlowsheetEntry.query.get_or_404(entry_id)
    
    entry.value = data.get('value', entry.value)
    entry.numeric_value = data.get('numeric_value', entry.numeric_value)
    entry.comments = data.get('comments', entry.comments)
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': entry.to_dict(),
        'message': 'Entry updated successfully'
    })

@flowsheet_bp.route('/entry/<int:entry_id>', methods=['DELETE'])
def delete_entry(entry_id):
    """Soft delete a flowsheet entry"""
    data = request.get_json() or {}
    entry = FlowsheetEntry.query.get_or_404(entry_id)
    
    entry.is_deleted = True
    entry.deleted_by = data.get('deleted_by')
    entry.deleted_date = datetime.now()
    entry.status = 'Deleted'
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'message': 'Entry deleted successfully'
    })

# Predefined flowsheet groups (like Pastoral Services, Post Partum Hemorrhage)
@flowsheet_bp.route('/groups', methods=['GET'])
def get_flowsheet_groups():
    """Get available flowsheet groups"""
    groups = [
        {
            'id': 'pastoral_services',
            'name': 'Pastoral Services',
            'sections': [
                'Reason For Visit',
                'Religious Affiliation',
                'Visit Information',
                'Sacraments',
                'Spiritual/Cultural Care Plan',
                'Coping (Adult) Care Plan',
                'Coping (Pediatric) Care Plan',
                'Coping (Obstetric) Care Plan'
            ]
        },
        {
            'id': 'post_partum_hemorrhage',
            'name': 'Post Partum Hemorrhage',
            'sections': [
                'Vitals',
                'Events',
                'Documentation',
                'Bleeding Assessment Summary',
                'Interventions'
            ]
        },
        {
            'id': 'intake_output',
            'name': 'Intake/Output',
            'sections': [
                'Intake',
                'Output',
                'Balance'
            ]
        },
        {
            'id': 'nursing_assessment',
            'name': 'Nursing Assessment',
            'sections': [
                'Neurological',
                'Cardiovascular',
                'Respiratory',
                'Gastrointestinal',
                'Genitourinary',
                'Musculoskeletal',
                'Skin',
                'Psychosocial'
            ]
        }
    ]
    
    return jsonify({
        'success': True,
        'data': groups
    })

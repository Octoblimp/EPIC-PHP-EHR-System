"""
Shortcode Routes
API endpoints for managing navigation shortcodes
"""
from flask import Blueprint, request, jsonify
from models import db, Shortcode, PageAccess, Role
from datetime import datetime
import json

shortcode_bp = Blueprint('shortcodes', __name__)


@shortcode_bp.route('/shortcodes', methods=['GET'])
def get_shortcodes():
    """Get all active shortcodes, optionally filtered by role"""
    role = request.args.get('role')
    category = request.args.get('category')
    
    query = Shortcode.query.filter_by(is_active=True)
    
    if category:
        query = query.filter_by(category=category)
    
    shortcodes = query.order_by(Shortcode.category, Shortcode.sort_order).all()
    
    # Filter by role if provided
    if role:
        filtered = []
        for sc in shortcodes:
            if sc.roles_allowed:
                allowed = json.loads(sc.roles_allowed)
                if role.lower() in [r.lower() for r in allowed]:
                    filtered.append(sc)
            else:
                filtered.append(sc)
        shortcodes = filtered
    
    return jsonify({
        'success': True,
        'shortcodes': [sc.to_dict() for sc in shortcodes]
    })


@shortcode_bp.route('/shortcodes', methods=['POST'])
def create_shortcode():
    """Create a new shortcode"""
    data = request.get_json()
    
    if not data.get('code') or not data.get('name') or not data.get('tab'):
        return jsonify({'success': False, 'error': 'Code, name, and tab are required'}), 400
    
    # Check for duplicate code
    existing = Shortcode.query.filter_by(code=data['code'].lower()).first()
    if existing:
        return jsonify({'success': False, 'error': f'Shortcode "{data["code"]}" already exists'}), 409
    
    shortcode = Shortcode(
        code=data['code'].lower(),
        name=data['name'],
        description=data.get('description'),
        tab=data['tab'],
        subtab=data.get('subtab'),
        icon=data.get('icon', 'fa-file'),
        category=data.get('category', 'Custom'),
        sort_order=data.get('sort_order', 0),
        required_permission=data.get('required_permission'),
        roles_allowed=json.dumps(data['roles_allowed']) if data.get('roles_allowed') else None,
        is_active=True,
        is_system=False
    )
    
    db.session.add(shortcode)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'shortcode': shortcode.to_dict()
    })


@shortcode_bp.route('/shortcodes/<int:id>', methods=['PUT'])
def update_shortcode(id):
    """Update an existing shortcode"""
    shortcode = Shortcode.query.get_or_404(id)
    data = request.get_json()
    
    # Cannot change code of system shortcodes
    if shortcode.is_system and data.get('code') and data['code'] != shortcode.code:
        return jsonify({'success': False, 'error': 'Cannot change code of system shortcode'}), 400
    
    if data.get('name'):
        shortcode.name = data['name']
    if data.get('description') is not None:
        shortcode.description = data['description']
    if data.get('tab'):
        shortcode.tab = data['tab']
    if data.get('subtab') is not None:
        shortcode.subtab = data['subtab']
    if data.get('icon'):
        shortcode.icon = data['icon']
    if data.get('category'):
        shortcode.category = data['category']
    if data.get('sort_order') is not None:
        shortcode.sort_order = data['sort_order']
    if data.get('required_permission') is not None:
        shortcode.required_permission = data['required_permission']
    if data.get('roles_allowed') is not None:
        shortcode.roles_allowed = json.dumps(data['roles_allowed']) if data['roles_allowed'] else None
    if data.get('is_active') is not None and not shortcode.is_system:
        shortcode.is_active = data['is_active']
    
    shortcode.updated_at = datetime.utcnow()
    db.session.commit()
    
    return jsonify({
        'success': True,
        'shortcode': shortcode.to_dict()
    })


@shortcode_bp.route('/shortcodes/<int:id>', methods=['DELETE'])
def delete_shortcode(id):
    """Delete a shortcode (not allowed for system shortcodes)"""
    shortcode = Shortcode.query.get_or_404(id)
    
    if shortcode.is_system:
        return jsonify({'success': False, 'error': 'Cannot delete system shortcode'}), 400
    
    db.session.delete(shortcode)
    db.session.commit()
    
    return jsonify({'success': True})


@shortcode_bp.route('/shortcodes/reset', methods=['POST'])
def reset_shortcodes():
    """Reset shortcodes to defaults (removes custom, restores system)"""
    # Remove all custom shortcodes
    Shortcode.query.filter_by(is_system=False).delete()
    
    # Restore system shortcodes
    defaults = Shortcode.get_default_shortcodes()
    for default in defaults:
        existing = Shortcode.query.filter_by(code=default['code']).first()
        if not existing:
            shortcode = Shortcode(**default)
            db.session.add(shortcode)
        else:
            existing.is_active = True
    
    db.session.commit()
    
    return jsonify({'success': True, 'message': 'Shortcodes reset to defaults'})


# Page Access Routes
@shortcode_bp.route('/page-access', methods=['GET'])
def get_page_access():
    """Get all page access configurations"""
    role_id = request.args.get('role_id', type=int)
    
    query = PageAccess.query
    if role_id:
        query = query.filter_by(role_id=role_id)
    
    access_list = query.all()
    
    # Also return page definitions
    page_definitions = PageAccess.get_page_definitions()
    
    return jsonify({
        'success': True,
        'page_access': [pa.to_dict() for pa in access_list],
        'page_definitions': page_definitions
    })


@shortcode_bp.route('/page-access', methods=['POST'])
def set_page_access():
    """Set page access for a role"""
    data = request.get_json()
    
    role_id = data.get('role_id')
    page_code = data.get('page_code')
    
    if not role_id or not page_code:
        return jsonify({'success': False, 'error': 'role_id and page_code are required'}), 400
    
    # Find or create
    access = PageAccess.query.filter_by(role_id=role_id, page_code=page_code).first()
    if not access:
        access = PageAccess(role_id=role_id, page_code=page_code)
        db.session.add(access)
    
    # Update fields
    if data.get('access_level'):
        access.access_level = data['access_level']
    if data.get('can_view') is not None:
        access.can_view = data['can_view']
    if data.get('can_create') is not None:
        access.can_create = data['can_create']
    if data.get('can_edit') is not None:
        access.can_edit = data['can_edit']
    if data.get('can_delete') is not None:
        access.can_delete = data['can_delete']
    if data.get('can_export') is not None:
        access.can_export = data['can_export']
    if data.get('restrictions') is not None:
        access.restrictions = json.dumps(data['restrictions']) if data['restrictions'] else None
    
    access.updated_at = datetime.utcnow()
    db.session.commit()
    
    return jsonify({
        'success': True,
        'page_access': access.to_dict()
    })


@shortcode_bp.route('/page-access/bulk', methods=['POST'])
def bulk_set_page_access():
    """Set multiple page access entries at once"""
    data = request.get_json()
    
    role_id = data.get('role_id')
    access_entries = data.get('entries', [])
    
    if not role_id:
        return jsonify({'success': False, 'error': 'role_id is required'}), 400
    
    for entry in access_entries:
        page_code = entry.get('page_code')
        if not page_code:
            continue
        
        access = PageAccess.query.filter_by(role_id=role_id, page_code=page_code).first()
        if not access:
            access = PageAccess(role_id=role_id, page_code=page_code)
            db.session.add(access)
        
        access.access_level = entry.get('access_level', 'none')
        access.can_view = entry.get('can_view', False)
        access.can_create = entry.get('can_create', False)
        access.can_edit = entry.get('can_edit', False)
        access.can_delete = entry.get('can_delete', False)
        access.can_export = entry.get('can_export', False)
        access.updated_at = datetime.utcnow()
    
    db.session.commit()
    
    return jsonify({'success': True, 'message': f'Updated {len(access_entries)} page access entries'})


@shortcode_bp.route('/page-access/role/<int:role_id>', methods=['DELETE'])
def clear_role_page_access(role_id):
    """Clear all page access entries for a role"""
    PageAccess.query.filter_by(role_id=role_id).delete()
    db.session.commit()
    
    return jsonify({'success': True})

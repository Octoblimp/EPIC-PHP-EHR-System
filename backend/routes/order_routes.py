"""
Order API Routes
"""
from flask import Blueprint, jsonify, request
from datetime import datetime
from models import db
from models.order import Order, OrderSet

order_bp = Blueprint('orders', __name__)

@order_bp.route('/patient/<int:patient_id>', methods=['GET'])
def get_patient_orders(patient_id):
    """Get all orders for a patient"""
    status = request.args.get('status')
    order_type = request.args.get('type')
    
    query = Order.query.filter_by(patient_id=patient_id)
    
    if status:
        query = query.filter_by(status=status)
    if order_type:
        query = query.filter_by(order_type=order_type)
    
    orders = query.order_by(Order.order_date.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [o.to_dict() for o in orders],
        'count': len(orders)
    })

@order_bp.route('/patient/<int:patient_id>/pending', methods=['GET'])
def get_pending_orders(patient_id):
    """Get pending orders requiring acknowledgement"""
    orders = Order.query.filter_by(
        patient_id=patient_id,
        acknowledgement_status='Pending'
    ).order_by(Order.order_date.desc()).all()
    
    return jsonify({
        'success': True,
        'data': [o.to_dict() for o in orders],
        'count': len(orders)
    })

@order_bp.route('/patient/<int:patient_id>/to-complete', methods=['GET'])
def get_orders_to_complete(patient_id):
    """Get orders that need to be completed"""
    orders = Order.query.filter(
        Order.patient_id == patient_id,
        Order.status.in_(['Ordered', 'In Progress'])
    ).order_by(Order.priority.desc(), Order.order_date).all()
    
    return jsonify({
        'success': True,
        'data': [o.to_dict() for o in orders],
        'count': len(orders)
    })

@order_bp.route('/patient/<int:patient_id>/by-type', methods=['GET'])
def get_orders_by_type(patient_id):
    """Get orders grouped by type"""
    orders = Order.query.filter_by(patient_id=patient_id).order_by(Order.order_date.desc()).all()
    
    by_type = {}
    for order in orders:
        order_type = order.order_type or 'Other'
        if order_type not in by_type:
            by_type[order_type] = []
        by_type[order_type].append(order.to_dict())
    
    return jsonify({
        'success': True,
        'data': by_type
    })

@order_bp.route('/<int:order_id>', methods=['GET'])
def get_order(order_id):
    """Get a specific order"""
    order = Order.query.get_or_404(order_id)
    return jsonify({
        'success': True,
        'data': order.to_dict()
    })

@order_bp.route('/', methods=['POST'])
def create_order():
    """Create a new order"""
    data = request.get_json()
    
    order = Order(
        patient_id=data['patient_id'],
        encounter_id=data.get('encounter_id'),
        order_name=data['order_name'],
        order_type=data.get('order_type'),
        order_category=data.get('order_category'),
        priority=data.get('priority', 'Routine'),
        ordering_provider=data['ordering_provider'],
        clinical_indication=data.get('clinical_indication'),
        special_instructions=data.get('special_instructions')
    )
    
    db.session.add(order)
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': order.to_dict(),
        'message': 'Order created successfully'
    }), 201

@order_bp.route('/<int:order_id>/acknowledge', methods=['POST'])
def acknowledge_order(order_id):
    """Acknowledge an order"""
    data = request.get_json()
    order = Order.query.get_or_404(order_id)
    
    order.acknowledgement_status = 'Acknowledged'
    order.acknowledged_by = data['acknowledged_by']
    order.acknowledged_date = datetime.now()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': order.to_dict(),
        'message': 'Order acknowledged'
    })

@order_bp.route('/<int:order_id>/status', methods=['PUT'])
def update_order_status(order_id):
    """Update order status"""
    data = request.get_json()
    order = Order.query.get_or_404(order_id)
    
    order.status = data['status']
    if data['status'] == 'Completed':
        order.resulted_date = datetime.now()
    
    db.session.commit()
    
    return jsonify({
        'success': True,
        'data': order.to_dict(),
        'message': f'Order status updated to {data["status"]}'
    })

@order_bp.route('/sets', methods=['GET'])
def get_order_sets():
    """Get available order sets"""
    category = request.args.get('category')
    department = request.args.get('department')
    
    query = OrderSet.query.filter_by(is_active=True)
    
    if category:
        query = query.filter_by(category=category)
    if department:
        query = query.filter_by(department=department)
    
    order_sets = query.order_by(OrderSet.name).all()
    
    return jsonify({
        'success': True,
        'data': [os.to_dict() for os in order_sets]
    })

"""
Authentication Routes
Simple authentication for EHR system
"""
from flask import Blueprint, request, jsonify, session, g
from functools import wraps
from datetime import datetime, timedelta
import secrets

auth_bp = Blueprint('auth', __name__, url_prefix='/api/auth')


def get_client_ip():
    """Get client IP address"""
    if request.headers.get('X-Forwarded-For'):
        return request.headers.get('X-Forwarded-For').split(',')[0].strip()
    return request.remote_addr


def login_required(f):
    """Decorator to require authentication"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        from models.user import User
        from models.auth import UserSession
        
        # Check for session token
        token = request.headers.get('Authorization', '').replace('Bearer ', '')
        if not token:
            token = session.get('session_id')
        
        if not token:
            return jsonify({
                'success': False,
                'error': 'Authentication required',
                'code': 'AUTH_REQUIRED'
            }), 401
        
        # Validate session
        user_session = UserSession.query.filter_by(session_id=token).first()
        if not user_session or not user_session.is_valid():
            return jsonify({
                'success': False,
                'error': 'Session expired or invalid',
                'code': 'SESSION_INVALID'
            }), 401
        
        # Refresh session activity
        user_session.refresh()
        
        # Get user
        user = User.query.get(user_session.user_id)
        if not user or not user.is_active:
            return jsonify({
                'success': False,
                'error': 'User account is disabled',
                'code': 'ACCOUNT_DISABLED'
            }), 401
        
        # Set user in request context
        g.current_user = user
        g.session = user_session
        
        return f(*args, **kwargs)
    return decorated_function


@auth_bp.route('/login', methods=['POST'])
def login():
    """Authenticate user and create session"""
    from models import db
    from models.user import User
    from models.auth import UserSession
    
    data = request.get_json()
    
    if not data:
        return jsonify({
            'success': False,
            'error': 'No JSON data provided'
        }), 400
    
    username = data.get('username', '').strip()
    password = data.get('password', '')
    
    ip_address = get_client_ip()
    user_agent = request.headers.get('User-Agent', '')[:500]
    
    # Validate input
    if not username or not password:
        return jsonify({
            'success': False,
            'error': 'Username and password are required'
        }), 400
    
    # Find user
    user = User.query.filter_by(username=username).first()
    
    if not user:
        return jsonify({
            'success': False,
            'error': 'Invalid username or password'
        }), 401
    
    # Check password
    if not user.check_password(password):
        return jsonify({
            'success': False,
            'error': 'Invalid username or password'
        }), 401
    
    # Check if account is active
    if not user.is_active:
        return jsonify({
            'success': False,
            'error': 'Account is disabled. Contact administrator.',
            'code': 'ACCOUNT_DISABLED'
        }), 401
    
    # Invalidate any existing sessions (single session policy)
    existing_sessions = UserSession.query.filter_by(
        user_id=user.id,
        is_active=True
    ).all()
    
    for old_session in existing_sessions:
        old_session.terminate('new_login')
    
    # Create new session
    new_session = UserSession.create_session(user, ip_address, user_agent)
    db.session.add(new_session)
    
    # Update last login
    user.last_login = datetime.utcnow()
    db.session.commit()
    
    # Build response
    response_data = {
        'success': True,
        'token': new_session.session_id,
        'expires_at': new_session.expires_at.isoformat(),
        'user': user.to_dict()
    }
    
    return jsonify(response_data)


@auth_bp.route('/logout', methods=['POST'])
@login_required
def logout():
    """End user session"""
    from models import db
    
    user_session = g.session
    
    # Terminate session
    user_session.terminate('logout')
    db.session.commit()
    
    return jsonify({
        'success': True,
        'message': 'Logged out successfully'
    })


@auth_bp.route('/me', methods=['GET'])
@login_required
def get_current_user():
    """Get current user info"""
    user = g.current_user
    return jsonify({
        'success': True,
        'user': user.to_dict()
    })


@auth_bp.route('/sessions', methods=['GET'])
@login_required
def get_user_sessions():
    """Get all active sessions for current user"""
    from models.auth import UserSession
    
    user = g.current_user
    sessions = UserSession.query.filter_by(
        user_id=user.id,
        is_active=True
    ).order_by(UserSession.last_activity.desc()).all()
    
    return jsonify({
        'success': True,
        'sessions': [{
            'id': s.id,
            'ip_address': s.ip_address,
            'user_agent': s.user_agent,
            'created_at': s.created_at.isoformat(),
            'last_activity': s.last_activity.isoformat(),
            'is_current': s.session_id == g.session.session_id
        } for s in sessions]
    })


@auth_bp.route('/sessions/<int:session_id>/terminate', methods=['POST'])
@login_required
def terminate_session(session_id):
    """Terminate a specific session"""
    from models import db
    from models.auth import UserSession
    
    user = g.current_user
    target_session = UserSession.query.filter_by(
        id=session_id,
        user_id=user.id
    ).first()
    
    if not target_session:
        return jsonify({
            'success': False,
            'error': 'Session not found'
        }), 404
    
    target_session.terminate('user_terminated')
    db.session.commit()
    
    return jsonify({
        'success': True,
        'message': 'Session terminated'
    })


@auth_bp.route('/refresh', methods=['POST'])
@login_required
def refresh_session():
    """Refresh session token"""
    from models import db
    
    user_session = g.session
    user_session.refresh()
    db.session.commit()
    
    return jsonify({
        'success': True,
        'expires_at': user_session.expires_at.isoformat(),
        'timeout_warning_at': (
            datetime.utcnow() + 
            timedelta(minutes=user_session.SESSION_TIMEOUT_MINUTES - 2)
        ).isoformat()
    })

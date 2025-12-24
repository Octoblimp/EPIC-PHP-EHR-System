"""
Authentication Routes
HIPAA-compliant authentication with MFA support
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
        from models.auth import User, UserSession
        from models.audit import AuditLog
        
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
        
        # Check password expiry
        if user.is_password_expired():
            return jsonify({
                'success': False,
                'error': 'Password expired. Please change your password.',
                'code': 'PASSWORD_EXPIRED',
                'must_change_password': True
            }), 403
        
        # Set user in request context
        g.current_user = user
        g.session = user_session
        
        return f(*args, **kwargs)
    return decorated_function


def permission_required(*permissions):
    """Decorator to require specific permissions"""
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            from models.audit import AuditLog
            
            user = g.get('current_user')
            if not user:
                return jsonify({
                    'success': False,
                    'error': 'Authentication required'
                }), 401
            
            # Check if user has any of the required permissions
            if not user.has_any_permission(permissions):
                # Log permission denied
                AuditLog.log(
                    user=user,
                    action=AuditLog.ACTION_PERMISSION_DENIED,
                    resource_type='Permission',
                    description=f'Permission denied: {", ".join(permissions)}',
                    ip_address=get_client_ip(),
                    status='DENIED'
                )
                
                return jsonify({
                    'success': False,
                    'error': 'Permission denied',
                    'required_permissions': list(permissions)
                }), 403
            
            return f(*args, **kwargs)
        return decorated_function
    return decorator


@auth_bp.route('/login', methods=['POST'])
def login():
    """Authenticate user and create session"""
    from models.auth import User, UserSession, db
    from models.audit import AuditLog, SecurityAlert
    
    data = request.get_json()
    username = data.get('username', '').strip()
    password = data.get('password', '')
    mfa_code = data.get('mfa_code')
    
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
        # Log failed attempt (don't reveal if user exists)
        AuditLog.log(
            action=AuditLog.ACTION_LOGIN_FAILED,
            resource_type='Authentication',
            description=f'Failed login attempt for username: {username}',
            ip_address=ip_address,
            user_agent=user_agent,
            status='FAILURE',
            error='Invalid credentials'
        )
        return jsonify({
            'success': False,
            'error': 'Invalid username or password'
        }), 401
    
    # Check if account is locked
    if user.is_locked:
        if user.locked_until and datetime.utcnow() < user.locked_until:
            minutes_remaining = int((user.locked_until - datetime.utcnow()).total_seconds() / 60)
            return jsonify({
                'success': False,
                'error': f'Account locked. Try again in {minutes_remaining} minutes.',
                'code': 'ACCOUNT_LOCKED'
            }), 401
        else:
            user.is_locked = False
            user.failed_login_attempts = 0
    
    # Check password
    if not user.check_password(password):
        db.session.commit()  # Save failed attempt count
        
        # Log failed attempt
        AuditLog.log(
            user=user,
            action=AuditLog.ACTION_LOGIN_FAILED,
            resource_type='Authentication',
            description=f'Failed login attempt ({user.failed_login_attempts}/{user.MAX_FAILED_ATTEMPTS})',
            ip_address=ip_address,
            user_agent=user_agent,
            status='FAILURE',
            error='Invalid password'
        )
        
        # Create security alert if too many failures
        if user.failed_login_attempts >= 3:
            SecurityAlert.create_alert(
                alert_type=SecurityAlert.ALERT_BRUTE_FORCE,
                severity='MEDIUM' if user.failed_login_attempts < user.MAX_FAILED_ATTEMPTS else 'HIGH',
                title=f'Multiple failed login attempts for user {username}',
                description=f'{user.failed_login_attempts} failed attempts from IP {ip_address}',
                user_id=user.id,
                ip_address=ip_address
            )
        
        return jsonify({
            'success': False,
            'error': 'Invalid username or password',
            'attempts_remaining': max(0, user.MAX_FAILED_ATTEMPTS - user.failed_login_attempts)
        }), 401
    
    # Check if account is active
    if not user.is_active:
        return jsonify({
            'success': False,
            'error': 'Account is disabled. Contact administrator.',
            'code': 'ACCOUNT_DISABLED'
        }), 401
    
    # Check MFA if enabled
    if user.mfa_enabled:
        if not mfa_code:
            return jsonify({
                'success': False,
                'mfa_required': True,
                'message': 'MFA code required'
            }), 200  # 200 because credentials are valid
        
        if not user.verify_mfa(mfa_code):
            AuditLog.log(
                user=user,
                action=AuditLog.ACTION_LOGIN_FAILED,
                resource_type='Authentication',
                description='Invalid MFA code',
                ip_address=ip_address,
                status='FAILURE',
                error='Invalid MFA code'
            )
            return jsonify({
                'success': False,
                'error': 'Invalid MFA code'
            }), 401
    
    # Invalidate any existing sessions (single session policy)
    existing_sessions = UserSession.query.filter_by(
        user_id=user.id,
        is_active=True
    ).all()
    
    for old_session in existing_sessions:
        old_session.terminate('new_login')
        
        # Alert on concurrent session attempt
        if len(existing_sessions) > 0:
            SecurityAlert.create_alert(
                alert_type=SecurityAlert.ALERT_CONCURRENT_SESSIONS,
                severity='LOW',
                title=f'Concurrent session terminated for {username}',
                description=f'New login from {ip_address}, old session terminated',
                user_id=user.id,
                ip_address=ip_address
            )
    
    # Create new session
    new_session = UserSession.create_session(user, ip_address, user_agent)
    db.session.add(new_session)
    db.session.commit()
    
    # Log successful login
    AuditLog.log(
        user=user,
        action=AuditLog.ACTION_LOGIN,
        resource_type='Authentication',
        description='Successful login',
        ip_address=ip_address,
        user_agent=user_agent,
        session_id=new_session.session_id,
        facility_id=user.facility_id,
        status='SUCCESS'
    )
    
    # Build response
    response_data = {
        'success': True,
        'token': new_session.session_id,
        'expires_at': new_session.expires_at.isoformat(),
        'user': user.to_dict(),
        'must_change_password': user.is_password_expired(),
        'password_expires_in_days': (
            (user.password_expires_at - datetime.utcnow()).days 
            if user.password_expires_at else None
        )
    }
    
    return jsonify(response_data)


@auth_bp.route('/logout', methods=['POST'])
@login_required
def logout():
    """End user session"""
    from models.audit import AuditLog, db
    
    user = g.current_user
    user_session = g.session
    
    # Terminate session
    user_session.terminate('logout')
    db.session.commit()
    
    # Log logout
    AuditLog.log(
        user=user,
        action=AuditLog.ACTION_LOGOUT,
        resource_type='Authentication',
        description='User logged out',
        ip_address=get_client_ip(),
        session_id=user_session.session_id,
        status='SUCCESS'
    )
    
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


@auth_bp.route('/change-password', methods=['POST'])
@login_required
def change_password():
    """Change user password"""
    from models.auth import db
    from models.audit import AuditLog
    
    user = g.current_user
    data = request.get_json()
    
    current_password = data.get('current_password', '')
    new_password = data.get('new_password', '')
    confirm_password = data.get('confirm_password', '')
    
    # Validate current password
    if not user.check_password(current_password):
        return jsonify({
            'success': False,
            'error': 'Current password is incorrect'
        }), 400
    
    # Validate new password match
    if new_password != confirm_password:
        return jsonify({
            'success': False,
            'error': 'New passwords do not match'
        }), 400
    
    # Set new password (includes validation)
    try:
        user.set_password(new_password)
        db.session.commit()
        
        # Log password change
        AuditLog.log(
            user=user,
            action=AuditLog.ACTION_PASSWORD_CHANGE,
            resource_type='User',
            resource_id=user.id,
            description='Password changed successfully',
            ip_address=get_client_ip(),
            status='SUCCESS'
        )
        
        return jsonify({
            'success': True,
            'message': 'Password changed successfully',
            'password_expires_at': user.password_expires_at.isoformat()
        })
        
    except ValueError as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400


@auth_bp.route('/mfa/setup', methods=['POST'])
@login_required
def setup_mfa():
    """Enable MFA for user"""
    from models.auth import db
    from models.audit import AuditLog
    import qrcode
    import io
    import base64
    
    user = g.current_user
    
    # Generate MFA secret
    secret = user.enable_mfa()
    db.session.commit()
    
    # Generate QR code
    uri = user.get_mfa_uri()
    qr = qrcode.QRCode(version=1, box_size=10, border=5)
    qr.add_data(uri)
    qr.make(fit=True)
    
    img = qr.make_image(fill_color="black", back_color="white")
    buffer = io.BytesIO()
    img.save(buffer, format='PNG')
    qr_base64 = base64.b64encode(buffer.getvalue()).decode()
    
    # Get backup codes
    import json
    backup_codes = json.loads(user.mfa_backup_codes)
    
    # Log MFA setup
    AuditLog.log(
        user=user,
        action=AuditLog.ACTION_MFA_SETUP,
        resource_type='User',
        resource_id=user.id,
        description='MFA enabled',
        ip_address=get_client_ip(),
        status='SUCCESS'
    )
    
    return jsonify({
        'success': True,
        'secret': secret,
        'qr_code': f'data:image/png;base64,{qr_base64}',
        'backup_codes': backup_codes,
        'message': 'MFA enabled. Please save your backup codes securely.'
    })


@auth_bp.route('/mfa/verify', methods=['POST'])
@login_required
def verify_mfa():
    """Verify MFA code during setup"""
    from models.auth import db
    
    user = g.current_user
    data = request.get_json()
    code = data.get('code', '')
    
    if user.verify_mfa(code):
        db.session.commit()
        return jsonify({
            'success': True,
            'message': 'MFA verified successfully'
        })
    else:
        return jsonify({
            'success': False,
            'error': 'Invalid MFA code'
        }), 400


@auth_bp.route('/mfa/disable', methods=['POST'])
@login_required
def disable_mfa():
    """Disable MFA (requires password confirmation)"""
    from models.auth import db
    from models.audit import AuditLog
    
    user = g.current_user
    data = request.get_json()
    password = data.get('password', '')
    
    # Verify password
    if not user.check_password(password):
        return jsonify({
            'success': False,
            'error': 'Invalid password'
        }), 400
    
    # Disable MFA
    user.mfa_enabled = False
    user.mfa_secret = None
    user.mfa_backup_codes = None
    db.session.commit()
    
    # Log
    AuditLog.log(
        user=user,
        action='MFA_DISABLED',
        resource_type='User',
        resource_id=user.id,
        description='MFA disabled',
        ip_address=get_client_ip(),
        status='SUCCESS'
    )
    
    return jsonify({
        'success': True,
        'message': 'MFA disabled'
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
    from models.auth import UserSession, db
    
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
    from models.auth import db
    
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

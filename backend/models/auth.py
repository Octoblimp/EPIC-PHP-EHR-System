"""
Authentication and Authorization Models
Session management for user authentication
"""
from datetime import datetime, timedelta
from . import db
import secrets

# Import the User model from user.py
from .user import User


class UserSession(db.Model):
    """Active user sessions for session management"""
    __tablename__ = 'user_sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    session_id = db.Column(db.String(64), unique=True, nullable=False, index=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    
    # Session Info
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.String(500))
    device_info = db.Column(db.Text)
    
    # Timestamps
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    last_activity = db.Column(db.DateTime, default=datetime.utcnow)
    expires_at = db.Column(db.DateTime)
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    terminated_at = db.Column(db.DateTime)
    termination_reason = db.Column(db.String(100))  # logout, timeout, forced, etc.
    
    # Relationship to User
    user = db.relationship('User', backref='sessions')
    
    SESSION_TIMEOUT_MINUTES = 15  # HIPAA requirement
    SESSION_MAX_DURATION_HOURS = 12
    
    @classmethod
    def create_session(cls, user, ip_address=None, user_agent=None):
        """Create a new session"""
        session = cls(
            session_id=secrets.token_hex(32),
            user_id=user.id,
            ip_address=ip_address,
            user_agent=user_agent,
            expires_at=datetime.utcnow() + timedelta(hours=cls.SESSION_MAX_DURATION_HOURS)
        )
        user.last_login = datetime.utcnow()
        return session
    
    def is_valid(self):
        """Check if session is still valid"""
        if not self.is_active:
            return False
        if datetime.utcnow() > self.expires_at:
            return False
        if datetime.utcnow() - self.last_activity > timedelta(minutes=self.SESSION_TIMEOUT_MINUTES):
            return False
        return True
    
    def refresh(self):
        """Update last activity time"""
        self.last_activity = datetime.utcnow()
    
    def terminate(self, reason='logout'):
        """End the session"""
        self.is_active = False
        self.terminated_at = datetime.utcnow()
        self.termination_reason = reason

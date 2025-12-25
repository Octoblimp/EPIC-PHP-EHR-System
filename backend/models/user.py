"""
User Model - System users and authentication
SECURITY: Uses Argon2id for password hashing (HIPAA-compliant)
"""
from datetime import datetime
from . import db
import argon2
from argon2 import PasswordHasher, exceptions as argon2_exceptions

# Configure Argon2id hasher with secure parameters
ph = PasswordHasher(
    time_cost=4,        # Number of iterations
    memory_cost=65536,  # 64 MB memory
    parallelism=3,      # 3 parallel threads
    hash_len=32,        # Output hash length
    salt_len=16,        # Salt length
    type=argon2.Type.ID # Argon2id (hybrid of Argon2i and Argon2d)
)

class User(db.Model):
    """System users"""
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(100), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    
    # Profile
    first_name = db.Column(db.String(100), nullable=False)
    last_name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(255), unique=True)
    
    # Role & Department
    role = db.Column(db.String(50), nullable=False)  # Physician, Nurse, Admin, Tech, etc.
    department = db.Column(db.String(100))
    specialty = db.Column(db.String(100))
    title = db.Column(db.String(100))  # MD, DO, RN, NP, PA, etc.
    
    # Provider Info
    npi = db.Column(db.String(20))  # National Provider Identifier
    provider_id = db.Column(db.String(50))
    
    # Status
    is_active = db.Column(db.Boolean, default=True)
    is_provider = db.Column(db.Boolean, default=False)
    can_order = db.Column(db.Boolean, default=False)
    can_prescribe = db.Column(db.Boolean, default=False)
    
    # Last Activity
    last_login = db.Column(db.DateTime)
    last_activity = db.Column(db.DateTime)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def set_password(self, password):
        """Hash password using Argon2id"""
        self.password_hash = ph.hash(password)
    
    def check_password(self, password):
        """Verify password against Argon2id hash"""
        try:
            ph.verify(self.password_hash, password)
            
            # Check if rehash is needed (parameters changed)
            if ph.check_needs_rehash(self.password_hash):
                self.password_hash = ph.hash(password)
                # Note: Caller should commit the session to save the new hash
            
            return True
        except argon2_exceptions.VerifyMismatchError:
            return False
        except argon2_exceptions.InvalidHash:
            # Handle legacy bcrypt hashes during migration
            from werkzeug.security import check_password_hash
            if check_password_hash(self.password_hash, password):
                # Upgrade to Argon2id
                self.password_hash = ph.hash(password)
                return True
            return False
    
    def get_full_name(self):
        title = f", {self.title}" if self.title else ""
        return f"{self.last_name}, {self.first_name}{title}"
    
    def get_display_name(self):
        title = f"{self.title} " if self.title else ""
        return f"{title}{self.first_name} {self.last_name}"
    
    def to_dict(self):
        return {
            'id': self.id,
            'username': self.username,
            'first_name': self.first_name,
            'last_name': self.last_name,
            'full_name': self.get_full_name(),
            'display_name': self.get_display_name(),
            'email': self.email,
            'role': self.role,
            'department': self.department,
            'specialty': self.specialty,
            'title': self.title,
            'is_provider': self.is_provider,
            'can_order': self.can_order,
            'can_prescribe': self.can_prescribe,
            'is_active': self.is_active
        }

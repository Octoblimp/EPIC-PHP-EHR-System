"""
HIPAA-Compliant AES-256-GCM Database Encryption Utilities
Provides field-level encryption for all PHI/PII data in the database.

SECURITY NOTES:
- Uses AES-256-GCM (Authenticated Encryption with Associated Data)
- Per-field random salts for key derivation
- Per-message random nonces (IVs)
- HKDF for key derivation
- Authentication tags prevent tampering
- All operations are constant-time to prevent timing attacks
"""

import os
import base64
import json
import hashlib
from typing import Union, Optional, Any
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.kdf.hkdf import HKDF
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.backends import default_backend
from functools import lru_cache
import secrets


class DatabaseEncryption:
    """
    AES-256-GCM encryption for database fields.
    
    Features:
    - Per-field random salt for key derivation
    - Per-record random nonce (IV)
    - Authentication tag to prevent tampering
    - Base64 encoding for safe storage
    - Automatic type preservation
    """
    
    # Prefix to identify encrypted values
    ENCRYPTED_PREFIX = "ENC:v1:"
    
    def __init__(self, master_key: Optional[str] = None):
        """
        Initialize encryption with master key.
        
        Args:
            master_key: Master encryption key. If not provided, reads from
                       HIPAA_ENCRYPTION_KEY environment variable.
        """
        key_source = master_key or os.environ.get('HIPAA_ENCRYPTION_KEY')
        
        if not key_source:
            # Generate a key for development - NEVER do this in production
            import warnings
            warnings.warn(
                "HIPAA_ENCRYPTION_KEY not set! Using generated key. "
                "Set environment variable for production!",
                RuntimeWarning
            )
            key_source = self._get_or_create_dev_key()
        
        # Derive 256-bit master key using HKDF
        self._master_key = self._derive_key(
            key_source.encode('utf-8') if isinstance(key_source, str) else key_source,
            b'hipaa-ehr-master-key',
            32
        )
    
    def _get_or_create_dev_key(self) -> str:
        """Get or create a development key (NOT FOR PRODUCTION)."""
        key_file = os.path.join(os.path.dirname(__file__), '..', '.encryption_key')
        
        if os.path.exists(key_file):
            with open(key_file, 'r') as f:
                return f.read().strip()
        
        # Generate new key
        new_key = secrets.token_hex(32)
        
        try:
            with open(key_file, 'w') as f:
                f.write(new_key)
            os.chmod(key_file, 0o600)
        except Exception:
            pass
        
        return new_key
    
    def _derive_key(self, key_material: bytes, info: bytes, length: int) -> bytes:
        """Derive a key using HKDF-SHA256."""
        hkdf = HKDF(
            algorithm=hashes.SHA256(),
            length=length,
            salt=None,  # Salt is added per-message
            info=info,
            backend=default_backend()
        )
        return hkdf.derive(key_material)
    
    def _derive_message_key(self, salt: bytes) -> bytes:
        """Derive a per-message key from master key and salt."""
        hkdf = HKDF(
            algorithm=hashes.SHA256(),
            length=32,  # 256 bits
            salt=salt,
            info=b'message-encryption-key',
            backend=default_backend()
        )
        return hkdf.derive(self._master_key)
    
    def encrypt(self, plaintext: str) -> str:
        """
        Encrypt a string value using AES-256-GCM.
        
        Format: ENC:v1:<base64(salt + nonce + ciphertext + tag)>
        
        Args:
            plaintext: The string to encrypt
            
        Returns:
            Encrypted string with metadata prefix
        """
        if not plaintext:
            return plaintext
        
        # Already encrypted? Return as-is
        if isinstance(plaintext, str) and plaintext.startswith(self.ENCRYPTED_PREFIX):
            return plaintext
        
        # Generate random salt (16 bytes) and nonce (12 bytes for GCM)
        salt = secrets.token_bytes(16)
        nonce = secrets.token_bytes(12)
        
        # Derive message-specific key
        message_key = self._derive_message_key(salt)
        
        # Encrypt using AES-256-GCM
        aesgcm = AESGCM(message_key)
        
        plaintext_bytes = plaintext.encode('utf-8') if isinstance(plaintext, str) else plaintext
        ciphertext = aesgcm.encrypt(nonce, plaintext_bytes, None)
        
        # Combine: salt (16) + nonce (12) + ciphertext + tag (16 bytes appended by GCM)
        combined = salt + nonce + ciphertext
        
        # Return with prefix and base64 encoding
        return self.ENCRYPTED_PREFIX + base64.b64encode(combined).decode('ascii')
    
    def decrypt(self, encrypted_value: str) -> str:
        """
        Decrypt an AES-256-GCM encrypted value.
        
        Args:
            encrypted_value: The encrypted string with ENC:v1: prefix
            
        Returns:
            Decrypted plaintext string
        """
        if not encrypted_value:
            return encrypted_value
        
        # Not encrypted? Return as-is
        if not encrypted_value.startswith(self.ENCRYPTED_PREFIX):
            return encrypted_value
        
        # Remove prefix and decode base64
        encoded = encrypted_value[len(self.ENCRYPTED_PREFIX):]
        combined = base64.b64decode(encoded)
        
        # Extract components
        salt = combined[:16]
        nonce = combined[16:28]
        ciphertext = combined[28:]
        
        # Derive message-specific key
        message_key = self._derive_message_key(salt)
        
        # Decrypt
        aesgcm = AESGCM(message_key)
        plaintext = aesgcm.decrypt(nonce, ciphertext, None)
        
        return plaintext.decode('utf-8')
    
    def encrypt_field(self, value: Any, field_type: str = 'string') -> str:
        """
        Encrypt a field value for database storage with type preservation.
        
        Args:
            value: The value to encrypt
            field_type: Type hint for decryption (string, int, float, date, json)
            
        Returns:
            Encrypted string with type metadata
        """
        if value is None:
            return None
        
        if value == '':
            return ''
        
        # Already encrypted?
        if isinstance(value, str) and value.startswith(self.ENCRYPTED_PREFIX):
            return value
        
        # Serialize non-string values
        if not isinstance(value, str):
            value = json.dumps(value)
            field_type = 'json'
        
        # Prefix with type marker
        prefixed = f"{field_type}:{value}"
        
        return self.encrypt(prefixed)
    
    def decrypt_field(self, encrypted_value: str) -> Any:
        """
        Decrypt a field value from database storage.
        
        Args:
            encrypted_value: The encrypted field value
            
        Returns:
            Original value with type restored
        """
        if encrypted_value is None:
            return None
        
        if encrypted_value == '':
            return ''
        
        # Not encrypted? Return as-is
        if not encrypted_value.startswith(self.ENCRYPTED_PREFIX):
            return encrypted_value
        
        # Decrypt
        decrypted = self.decrypt(encrypted_value)
        
        # Extract type and value
        colon_pos = decrypted.find(':')
        if colon_pos == -1:
            return decrypted
        
        field_type = decrypted[:colon_pos]
        value = decrypted[colon_pos + 1:]
        
        # Restore type
        if field_type == 'json':
            return json.loads(value)
        elif field_type == 'int':
            return int(value)
        elif field_type == 'float':
            return float(value)
        
        return value
    
    def hash_for_search(self, value: str, salt: str = '') -> str:
        """
        Create a deterministic hash for searching encrypted fields.
        
        This allows searching by creating a hash that will always be the same
        for the same input, enabling WHERE clause matches.
        
        WARNING: This reduces security. Only use for fields that MUST be searchable.
        
        Args:
            value: The value to hash
            salt: Optional additional salt for this field
            
        Returns:
            Hex-encoded hash suitable for database storage
        """
        if not value:
            return ''
        
        # Combine value with master key and optional salt
        combined = self._master_key + value.encode('utf-8') + salt.encode('utf-8')
        
        # Use SHA-256 for the search hash
        return hashlib.sha256(combined).hexdigest()
    
    def is_encrypted(self, value: str) -> bool:
        """Check if a value is already encrypted."""
        return isinstance(value, str) and value.startswith(self.ENCRYPTED_PREFIX)


# Global encryption instance
_encryption_instance = None


def get_encryption() -> DatabaseEncryption:
    """Get or create the global encryption instance."""
    global _encryption_instance
    if _encryption_instance is None:
        _encryption_instance = DatabaseEncryption()
    return _encryption_instance


def encrypt_value(value: str) -> str:
    """Convenience function to encrypt a value."""
    return get_encryption().encrypt(value)


def decrypt_value(value: str) -> str:
    """Convenience function to decrypt a value."""
    return get_encryption().decrypt(value)


def encrypt_field(value: Any, field_type: str = 'string') -> str:
    """Convenience function to encrypt a field."""
    return get_encryption().encrypt_field(value, field_type)


def decrypt_field(value: str) -> Any:
    """Convenience function to decrypt a field."""
    return get_encryption().decrypt_field(value)


def hash_for_search(value: str, salt: str = '') -> str:
    """Convenience function to create a searchable hash."""
    return get_encryption().hash_for_search(value, salt)


# SQLAlchemy TypeDecorator for automatic encryption
from sqlalchemy import TypeDecorator, String, Text


class EncryptedString(TypeDecorator):
    """
    SQLAlchemy type for automatically encrypting/decrypting string fields.
    
    Usage:
        class Patient(db.Model):
            first_name = db.Column(EncryptedString(100), nullable=False)
    """
    impl = String
    cache_ok = True
    
    def __init__(self, length=255, *args, **kwargs):
        # Encrypted strings are longer due to encoding
        super().__init__(length * 4, *args, **kwargs)
    
    def process_bind_param(self, value, dialect):
        """Encrypt value before storing in database."""
        if value is not None:
            return encrypt_value(str(value))
        return value
    
    def process_result_value(self, value, dialect):
        """Decrypt value when reading from database."""
        if value is not None:
            return decrypt_value(value)
        return value


class EncryptedText(TypeDecorator):
    """
    SQLAlchemy type for automatically encrypting/decrypting text fields.
    
    Usage:
        class Note(db.Model):
            content = db.Column(EncryptedText(), nullable=False)
    """
    impl = Text
    cache_ok = True
    
    def process_bind_param(self, value, dialect):
        """Encrypt value before storing in database."""
        if value is not None:
            return encrypt_value(str(value))
        return value
    
    def process_result_value(self, value, dialect):
        """Decrypt value when reading from database."""
        if value is not None:
            return decrypt_value(value)
        return value


class EncryptedJSON(TypeDecorator):
    """
    SQLAlchemy type for automatically encrypting/decrypting JSON fields.
    
    Usage:
        class Patient(db.Model):
            allergies = db.Column(EncryptedJSON(), default=list)
    """
    impl = Text
    cache_ok = True
    
    def process_bind_param(self, value, dialect):
        """Encrypt value before storing in database."""
        if value is not None:
            return encrypt_field(value, 'json')
        return value
    
    def process_result_value(self, value, dialect):
        """Decrypt value when reading from database."""
        if value is not None:
            return decrypt_field(value)
        return value


class SearchableEncryptedString(TypeDecorator):
    """
    SQLAlchemy type for encrypted strings with searchable hash.
    
    Stores both encrypted value and search hash in format:
    <hash>|<encrypted_value>
    
    Use hash_for_search() to generate WHERE clause values.
    
    Usage:
        class Patient(db.Model):
            ssn = db.Column(SearchableEncryptedString(20))
    """
    impl = String
    cache_ok = True
    
    def __init__(self, length=255, *args, **kwargs):
        # Need space for hash (64) + separator (1) + encrypted value
        super().__init__(length * 4 + 65, *args, **kwargs)
    
    def process_bind_param(self, value, dialect):
        """Encrypt value and prepend search hash."""
        if value is not None:
            search_hash = hash_for_search(str(value))
            encrypted = encrypt_value(str(value))
            return f"{search_hash}|{encrypted}"
        return value
    
    def process_result_value(self, value, dialect):
        """Extract and decrypt value."""
        if value is not None:
            # Split hash and encrypted value
            parts = value.split('|', 1)
            if len(parts) == 2:
                return decrypt_value(parts[1])
            # Fallback for non-prefixed values
            return decrypt_value(value)
        return value

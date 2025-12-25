# Openspace EHR Security Documentation

## HIPAA-Compliant Security Implementation

This document outlines the security measures implemented in Openspace EHR to ensure HIPAA compliance and protect patient data.

---

## 🔐 Password Security

### Argon2id Hashing
All passwords are hashed using **Argon2id**, the winner of the Password Hashing Competition and recommended by OWASP:

```
Algorithm: Argon2id (hybrid of Argon2i and Argon2d)
Memory Cost: 64 MB
Time Cost: 4 iterations
Parallelism: 3 threads
Hash Length: 32 bytes
Salt Length: 16 bytes
```

**Files Modified:**
- `backend/models/user.py` - Python Argon2id implementation
- `frontend/includes/security.php` - PHP Argon2id implementation
- `frontend/login.php` - Updated authentication with Argon2id

**Migration:** Legacy bcrypt hashes are automatically upgraded to Argon2id on successful login.

---

## 🛡️ Data Encryption (AES-256-GCM)

### Protected Health Information (PHI) Encryption
All PII/PHI is encrypted at rest using **AES-256-GCM** with per-message salting:

```
Cipher: AES-256-GCM (authenticated encryption)
Key Derivation: HKDF-SHA256
Salt: 16 bytes random per message
Nonce: 12 bytes random per message
Tag: 16 bytes authentication tag
```

### Encrypted Fields
| Table | Fields |
|-------|--------|
| patients | first_name, last_name, email, phone, address, ssn |
| users | first_name, last_name, email, phone, npi |
| clinical_notes | content, addendum |
| messages | subject, body |

### Key Management
1. **Production:** Set `HIPAA_ENCRYPTION_KEY` environment variable
2. **Development:** Auto-generated key stored in `.encryption_key` (git-ignored)

**CRITICAL:** Never commit encryption keys to version control!

---

## 🍪 Cookie & Session Security

### Session Configuration
```php
Session Name: OSSID
HttpOnly: true       // Not accessible via JavaScript
Secure: true         // Only sent over HTTPS
SameSite: Strict     // Prevents CSRF attacks
Lifetime: Session    // Expires when browser closes
```

### Session Protection Features
1. **Session Fingerprinting** - Binds session to browser characteristics
2. **Session Regeneration** - New session ID on login
3. **Idle Timeout** - Configurable session timeout (default 30 min)
4. **Secure Session ID** - 64-character cryptographically random

### CSRF Protection
All forms include CSRF tokens:
```php
$token = getCSRFToken();
// In form: <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
// Validation: validateCSRF($_POST['csrf_token'])
```

---

## 🔒 Transport Security

### Security Headers
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: [configured per page]
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### HTTPS Enforcement
- HSTS header forces HTTPS for 1 year
- Secure cookie flag prevents transmission over HTTP

---

## 🛑 Input Validation

### Sanitization Functions
```php
InputValidator::sanitizeString($input)    // HTML entity encoding
InputValidator::validateInt($input)       // Integer validation with range
InputValidator::validatePatientId($id)    // Patient ID validation
InputValidator::validateEmail($email)     // Email format validation
InputValidator::validateDate($date)       // Date format validation
InputValidator::validateMRN($mrn)         // MRN format validation
```

### SQL Injection Prevention
- All database queries use **prepared statements** with parameterized values
- Backend uses **SQLAlchemy ORM** (Python)
- Frontend uses **PDO prepared statements** (PHP)

**SECURITY NOTE FOR DEVELOPERS:**
> NEVER concatenate user input into SQL queries. Always use parameterized queries.
> This is enforced through code review and automated scanning.

---

## ⏱️ Rate Limiting

### Brute Force Protection
```
Login Attempts: 5 per 5 minutes
API Requests: Configurable per endpoint
```

### Implementation
```php
if (RateLimiter::isLimited($key, $maxAttempts, $windowSeconds)) {
    // Block request
}
RateLimiter::recordAttempt($key);
```

---

## 📋 Audit Logging

All access to PHI is logged:
```
- User ID and username
- Action performed
- Resource accessed
- Patient ID (if applicable)
- IP address
- User agent
- Timestamp
```

---

## 🔑 Demo Login Credentials

For testing (when backend is unavailable):
| Username | Password | Role |
|----------|----------|------|
| admin | demo123 | Administrator |
| drsmith | demo123 | Physician |
| nurse1 | demo123 | Nurse |
| demo | demo123 | Demo User |

---

## 📁 Security Files

| File | Purpose |
|------|---------|
| `frontend/includes/security.php` | Core security classes |
| `frontend/admin/encrypt-pii.php` | PII encryption tool |
| `frontend/admin/db-updater.php` | Database migrations |
| `backend/models/user.py` | User authentication |

---

## ⚠️ Security Checklist for Developers

- [ ] Use parameterized queries (NEVER string concatenation)
- [ ] Validate ALL user input
- [ ] Use `InputValidator` for all `$_GET`, `$_POST` data
- [ ] Include CSRF tokens in all forms
- [ ] Encrypt any new PII fields using `encryptPII()`
- [ ] Log all PHI access using audit functions
- [ ] Never expose encryption keys or sensitive config to frontend
- [ ] All decryption must happen server-side only
- [ ] Test authentication with both API and demo fallback

---

## 🚨 Security Incident Response

1. **Detection:** Monitor audit logs for suspicious activity
2. **Containment:** Disable affected accounts immediately
3. **Investigation:** Review audit trail
4. **Notification:** Follow HIPAA breach notification requirements
5. **Remediation:** Patch vulnerabilities and update credentials

---

*Last Updated: December 25, 2024*
*Security Version: 2.5.0*

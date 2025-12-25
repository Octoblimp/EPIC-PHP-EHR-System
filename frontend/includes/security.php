<?php
/**
 * Openspace EHR - Security Module
 * HIPAA-compliant security functions
 * 
 * SECURITY NOTES FOR DEVELOPERS:
 * =============================
 * 1. NEVER concatenate user input directly into SQL queries
 * 2. ALWAYS use prepared statements with parameterized queries
 * 3. ALWAYS validate and sanitize ALL user input
 * 4. NEVER expose encryption keys to the client
 * 5. ALL decryption MUST happen server-side only
 * 6. Session tokens must be cryptographically random
 * 7. All cookies must have HttpOnly, Secure, and SameSite flags
 */

// HIPAA-compliant AES-256-GCM encryption class
class HIPAAEncryption {
    private $key;
    private $cipher = 'aes-256-gcm';
    
    /**
     * Initialize with encryption key from environment
     * Key should be stored in environment variable, NOT in code
     */
    public function __construct() {
        // Get key from environment or generate a warning
        $envKey = getenv('HIPAA_ENCRYPTION_KEY');
        
        if (!$envKey) {
            // For development only - in production, this MUST be set via environment
            // Generate a deterministic key from a config value if needed
            $envKey = $this->deriveKeyFromConfig();
        }
        
        // Derive a proper 256-bit key using HKDF
        $this->key = hash_hkdf('sha256', $envKey, 32, 'hipaa-ehr-encryption');
    }
    
    /**
     * Derive encryption key from config (development fallback)
     */
    private function deriveKeyFromConfig(): string {
        // In production, this should come from environment variable
        // This is a development fallback ONLY
        $configPath = __DIR__ . '/../.encryption_key';
        
        if (file_exists($configPath)) {
            return trim(file_get_contents($configPath));
        }
        
        // Generate and save a new key (first run)
        $newKey = bin2hex(random_bytes(32));
        
        // Try to save for persistence (may fail in some environments)
        @file_put_contents($configPath, $newKey);
        @chmod($configPath, 0600); // Restrict permissions
        
        error_log('HIPAA_ENCRYPTION_KEY not set - using generated key. Set environment variable for production!');
        
        return $newKey;
    }
    
    /**
     * Encrypt data with AES-256-GCM
     * Returns base64-encoded string containing: salt + nonce + ciphertext + tag
     */
    public function encrypt(string $plaintext): string {
        if (empty($plaintext)) {
            return '';
        }
        
        // Generate random salt (for key derivation per-message)
        $salt = random_bytes(16);
        
        // Derive message-specific key using salt
        $messageKey = hash_hkdf('sha256', $this->key, 32, 'message-key', $salt);
        
        // Generate random nonce (IV) for GCM
        $nonce = random_bytes(12); // GCM recommends 12-byte nonce
        
        // Encrypt with authentication tag
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $messageKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '', // Additional authenticated data (AAD)
            16  // Tag length
        );
        
        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }
        
        // Combine: salt (16) + nonce (12) + ciphertext + tag (16)
        $combined = $salt . $nonce . $ciphertext . $tag;
        
        return base64_encode($combined);
    }
    
    /**
     * Decrypt data encrypted with encrypt()
     */
    public function decrypt(string $encryptedData): string {
        if (empty($encryptedData)) {
            return '';
        }
        
        $combined = base64_decode($encryptedData);
        
        if ($combined === false || strlen($combined) < 44) { // 16 + 12 + min 0 + 16
            throw new Exception('Invalid encrypted data format');
        }
        
        // Extract components
        $salt = substr($combined, 0, 16);
        $nonce = substr($combined, 16, 12);
        $tag = substr($combined, -16);
        $ciphertext = substr($combined, 28, -16);
        
        // Derive message-specific key using salt
        $messageKey = hash_hkdf('sha256', $this->key, 32, 'message-key', $salt);
        
        // Decrypt and verify authentication tag
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $messageKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );
        
        if ($plaintext === false) {
            throw new Exception('Decryption failed - data may have been tampered with');
        }
        
        return $plaintext;
    }
    
    /**
     * Encrypt for database storage (with type prefix for identification)
     */
    public function encryptForDB($value, string $fieldType = 'string'): string {
        if ($value === null || $value === '') {
            return '';
        }
        
        // Serialize non-string values
        if (!is_string($value)) {
            $value = json_encode($value);
            $fieldType = 'json';
        }
        
        // Prefix with type marker for proper decryption
        $prefixed = $fieldType . ':' . $value;
        
        return 'ENC:' . $this->encrypt($prefixed);
    }
    
    /**
     * Decrypt from database storage
     */
    public function decryptFromDB(string $encryptedValue) {
        if (empty($encryptedValue)) {
            return '';
        }
        
        // Check if value is encrypted
        if (strpos($encryptedValue, 'ENC:') !== 0) {
            // Not encrypted - return as-is (for migration purposes)
            return $encryptedValue;
        }
        
        $decrypted = $this->decrypt(substr($encryptedValue, 4));
        
        // Extract type prefix
        $colonPos = strpos($decrypted, ':');
        if ($colonPos === false) {
            return $decrypted;
        }
        
        $type = substr($decrypted, 0, $colonPos);
        $value = substr($decrypted, $colonPos + 1);
        
        // Deserialize if needed
        if ($type === 'json') {
            return json_decode($value, true);
        }
        
        return $value;
    }
}

/**
 * Password hashing using Argon2id (strongest algorithm available)
 */
class PasswordHasher {
    /**
     * Hash a password using Argon2id
     */
    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,  // 64 MB
            'time_cost' => 4,        // 4 iterations
            'threads' => 3           // 3 parallel threads
        ]);
    }
    
    /**
     * Verify a password against an Argon2id hash
     */
    public static function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing (e.g., algorithm upgrade)
     */
    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
}

/**
 * Secure session management
 */
class SecureSession {
    private static $initialized = false;
    
    /**
     * Initialize secure session with all protections
     */
    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        
        // Prevent session fixation
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        
        // Strong session ID
        ini_set('session.sid_length', '64');
        ini_set('session.sid_bits_per_character', '6');
        
        // Secure cookie settings
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $cookieParams = [
            'lifetime' => 0,           // Session cookie
            'path' => '/',
            'domain' => '',
            'secure' => $secure,       // Only send over HTTPS
            'httponly' => true,        // Not accessible via JavaScript
            'samesite' => 'Strict'     // Prevent CSRF
        ];
        
        session_set_cookie_params($cookieParams);
        
        // Use secure session name
        session_name('OSSID');
        
        self::$initialized = true;
    }
    
    /**
     * Start session securely
     */
    public static function start(): void {
        self::init();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate fingerprint for session binding
        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = self::generateFingerprint();
        }
        
        // Validate fingerprint
        if ($_SESSION['_fingerprint'] !== self::generateFingerprint()) {
            // Potential session hijacking - destroy session
            self::destroy();
            throw new Exception('Session validation failed');
        }
        
        // Check for session timeout
        self::checkTimeout();
    }
    
    /**
     * Generate session fingerprint for binding
     */
    private static function generateFingerprint(): string {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            // Don't include IP as it can change on mobile networks
            // Instead use other browser characteristics
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
        ];
        
        return hash('sha256', implode('|', $data));
    }
    
    /**
     * Check session timeout
     */
    private static function checkTimeout(): void {
        $timeout = $_SESSION['system_settings']['session_timeout'] ?? 30;
        $timeout = $timeout * 60; // Convert to seconds
        
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $timeout) {
                self::destroy();
                header('Location: login.php?timeout=1');
                exit;
            }
        }
        
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Regenerate session ID (call after login or privilege change)
     */
    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    /**
     * Destroy session securely
     */
    public static function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear all session data
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
        }
    }
    
    /**
     * Get CSRF token (create if not exists)
     */
    public static function getCSRFToken(): string {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token): bool {
        if (!isset($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

/**
 * Input validation and sanitization
 */
class InputValidator {
    /**
     * Sanitize string input
     */
    public static function sanitizeString(?string $input): string {
        if ($input === null) {
            return '';
        }
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validate integer input
     */
    public static function validateInt($input, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int {
        $filtered = filter_var($input, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max]
        ]);
        
        return $filtered === false ? null : $filtered;
    }
    
    /**
     * Validate and sanitize patient ID
     */
    public static function validatePatientId($id): ?int {
        return self::validateInt($id, 1);
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail(?string $email): ?string {
        if ($email === null) {
            return null;
        }
        $filtered = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        return $filtered === false ? null : $filtered;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function validateDate(?string $date): ?string {
        if ($date === null) {
            return null;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return $date;
        }
        return null;
    }
    
    /**
     * Validate MRN format
     */
    public static function validateMRN(?string $mrn): ?string {
        if ($mrn === null) {
            return null;
        }
        
        // Allow alphanumeric MRNs
        if (preg_match('/^[A-Z0-9]{3,20}$/i', $mrn)) {
            return strtoupper($mrn);
        }
        return null;
    }
}

/**
 * Rate limiting for brute force protection
 */
class RateLimiter {
    private static $storage = [];
    
    /**
     * Check if action is rate limited
     */
    public static function isLimited(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool {
        $now = time();
        
        // Use session-based storage (in production, use Redis/Memcached)
        if (!isset($_SESSION['_rate_limits'])) {
            $_SESSION['_rate_limits'] = [];
        }
        
        // Clean old entries
        self::cleanup($key, $windowSeconds);
        
        // Count attempts
        $attempts = $_SESSION['_rate_limits'][$key] ?? [];
        $recentAttempts = array_filter($attempts, fn($t) => $t > $now - $windowSeconds);
        
        return count($recentAttempts) >= $maxAttempts;
    }
    
    /**
     * Record an attempt
     */
    public static function recordAttempt(string $key): void {
        if (!isset($_SESSION['_rate_limits'])) {
            $_SESSION['_rate_limits'] = [];
        }
        if (!isset($_SESSION['_rate_limits'][$key])) {
            $_SESSION['_rate_limits'][$key] = [];
        }
        $_SESSION['_rate_limits'][$key][] = time();
    }
    
    /**
     * Clear rate limit for key (e.g., after successful login)
     */
    public static function clear(string $key): void {
        if (isset($_SESSION['_rate_limits'][$key])) {
            unset($_SESSION['_rate_limits'][$key]);
        }
    }
    
    /**
     * Cleanup old entries
     */
    private static function cleanup(string $key, int $windowSeconds): void {
        if (!isset($_SESSION['_rate_limits'][$key])) {
            return;
        }
        
        $now = time();
        $_SESSION['_rate_limits'][$key] = array_filter(
            $_SESSION['_rate_limits'][$key],
            fn($t) => $t > $now - $windowSeconds
        );
    }
}

/**
 * Security headers helper
 */
class SecurityHeaders {
    /**
     * Set all recommended security headers
     */
    public static function send(): void {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: blob:; connect-src 'self'");
        
        // HSTS (only if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Permissions policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }
}

// Global encryption instance
$hipaaEncryption = new HIPAAEncryption();

/**
 * Quick helper functions
 */
function encryptPII($value): string {
    global $hipaaEncryption;
    return $hipaaEncryption->encryptForDB($value);
}

function decryptPII($value) {
    global $hipaaEncryption;
    return $hipaaEncryption->decryptFromDB($value);
}

function hashPassword(string $password): string {
    return PasswordHasher::hash($password);
}

function verifyPassword(string $password, string $hash): bool {
    return PasswordHasher::verify($password, $hash);
}

function validatePatientId($id): ?int {
    return InputValidator::validatePatientId($id);
}

function getCSRFToken(): string {
    return SecureSession::getCSRFToken();
}

function validateCSRF(string $token): bool {
    return SecureSession::validateCSRFToken($token);
}

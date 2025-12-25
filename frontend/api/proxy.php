<?php
/**
 * API Proxy
 * Forwards requests from PHP frontend to Python backend
 * This allows the PHP frontend to communicate with the Python API
 */

// List of local PHP API files that should NOT be proxied
$localApiFiles = [
    'close-patient-tab.php',
    'add-patient-tab.php',
    'toggle-sidebar-favorite.php',
    'update-sidebar-favorites.php'
];

// Check if request is for a local PHP file
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$filename = basename($path);

// If it's a local PHP API file, let it be handled directly (don't proxy)
if (in_array($filename, $localApiFiles)) {
    // This file should not handle this request - it should go to the actual PHP file
    // Return 404 to indicate this proxy shouldn't handle it
    // The web server should route .php files directly
    return;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Python backend URL
$backendUrl = 'http://localhost:5000/api';

// Get the request path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove the /api prefix to get the actual endpoint
$endpoint = preg_replace('/^\/api/', '', $path);

// Build the full backend URL
$url = $backendUrl . $endpoint;

// Add query string if present
if (!empty($_SERVER['QUERY_STRING'])) {
    $url .= '?' . $_SERVER['QUERY_STRING'];
}

// Initialize cURL
$ch = curl_init();

// Set common options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        $input = file_get_contents('php://input');
        if ($input) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        }
        break;
        
    case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $input = file_get_contents('php://input');
        if ($input) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        }
        break;
        
    case 'DELETE':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Handle errors
if ($error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Backend connection failed: ' . $error,
        'message' => 'Make sure the Python backend server is running on port 5000'
    ]);
    exit();
}

// Forward the response
http_response_code($httpCode);
echo $response;

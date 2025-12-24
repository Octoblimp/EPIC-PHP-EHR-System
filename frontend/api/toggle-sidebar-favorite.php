<?php
/**
 * API: Toggle Sidebar Favorite
 * Adds or removes a navigation item from the sidebar favorites
 */

session_start();

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$key = $input['key'] ?? null;

if (!$key) {
    echo json_encode(['success' => false, 'error' => 'No key provided']);
    exit;
}

// Initialize user settings if not exists
if (!isset($_SESSION['user_settings'])) {
    $_SESSION['user_settings'] = [];
}

// Initialize sidebar favorites if not exists
if (!isset($_SESSION['user_settings']['sidebar_favorites'])) {
    $_SESSION['user_settings']['sidebar_favorites'] = ['home', 'patients', 'schedule', 'inbox', 'reports'];
}

$favorites = &$_SESSION['user_settings']['sidebar_favorites'];

// Toggle the favorite
if (in_array($key, $favorites)) {
    // Remove from favorites
    $favorites = array_values(array_filter($favorites, function($f) use ($key) {
        return $f !== $key;
    }));
    $action = 'removed';
} else {
    // Add to favorites
    $favorites[] = $key;
    $action = 'added';
}

echo json_encode([
    'success' => true, 
    'action' => $action,
    'favorites' => $favorites
]);

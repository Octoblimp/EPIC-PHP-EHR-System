<?php
/**
 * API: Update Sidebar Favorites
 * Updates the entire list of sidebar favorites for patient chart navigation
 */

session_start();

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$favorites = $input['favorites'] ?? null;

if (!is_array($favorites)) {
    echo json_encode(['success' => false, 'error' => 'Invalid favorites array']);
    exit;
}

// Validate items against allowed values
$allowed_items = [
    'summary', 'chart-review', 'results', 'work-list', 'mar', 'flowsheets',
    'intake-output', 'notes', 'education', 'care-plan', 'orders', 'demographics', 'history'
];

$validated = array_filter($favorites, function($item) use ($allowed_items) {
    return in_array($item, $allowed_items);
});

// Ensure at least some items are selected
if (empty($validated)) {
    $validated = ['summary', 'flowsheets', 'notes', 'mar', 'orders', 'results'];
}

// Initialize user settings if not exists
if (!isset($_SESSION['user_settings'])) {
    $_SESSION['user_settings'] = [];
}

// Update sidebar favorites
$_SESSION['user_settings']['sidebar_favorites'] = array_values($validated);

echo json_encode([
    'success' => true, 
    'favorites' => $_SESSION['user_settings']['sidebar_favorites']
]);

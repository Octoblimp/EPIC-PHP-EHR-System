<?php
/**
 * Sample Extension for Openspace EHR
 * 
 * This extension demonstrates how to use the extension API to:
 * - Register action hooks
 * - Add filters
 * - Create shortcodes
 * - Add menu items
 * - Register widgets
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    die('Direct access not allowed');
}

/**
 * Initialize the extension
 */
add_action('init', function() {
    // Extension initialization code
    error_log('Sample Extension initialized');
});

/**
 * Hook into patient loaded event
 */
add_action('patient_loaded', function($patient) {
    // Do something when a patient is loaded
    // Example: Log access, trigger integrations, etc.
});

/**
 * Filter patient name display
 */
add_filter('patient_name_display', function($name, $patient) {
    // Example: Add a prefix or format the name differently
    return $name;
}, 10);

/**
 * Filter vital signs before display
 */
add_filter('vital_signs_display', function($vitals) {
    // Example: Add calculated values like BMI
    if (isset($vitals['weight']) && isset($vitals['height'])) {
        $heightM = $vitals['height'] / 100; // cm to m
        $weightKg = $vitals['weight'];
        $vitals['bmi'] = round($weightKg / ($heightM * $heightM), 1);
    }
    return $vitals;
}, 10);

/**
 * Register a shortcode for displaying patient alerts
 * Usage: [patient_alerts type="allergy" limit="5"]
 */
add_shortcode('patient_alerts', function($attrs, $content) {
    $type = $attrs['type'] ?? 'all';
    $limit = intval($attrs['limit'] ?? 10);
    
    // Demo output
    $html = '<div class="patient-alerts-widget">';
    $html .= '<h4>Patient Alerts</h4>';
    $html .= '<ul>';
    $html .= '<li class="alert-allergy">Penicillin - Severe</li>';
    $html .= '<li class="alert-flag">Fall Risk</li>';
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
});

/**
 * Register a shortcode for quick actions
 * Usage: [quick_action type="order" action="lab"]Order Labs[/quick_action]
 */
add_shortcode('quick_action', function($attrs, $content) {
    $type = $attrs['type'] ?? 'action';
    $action = $attrs['action'] ?? '';
    
    return '<button class="quick-action-btn" data-type="' . htmlspecialchars($type) . '" data-action="' . htmlspecialchars($action) . '">' . htmlspecialchars($content) . '</button>';
});

/**
 * Add menu items to sidebar
 */
add_menu_item('sidebar', [
    'id' => 'sample-page',
    'title' => 'Sample Page',
    'icon' => 'fas fa-puzzle-piece',
    'url' => '/extensions/sample-extension/page.php',
    'position' => 100,
]);

/**
 * Add items to patient chart navigation
 */
add_menu_item('patient_chart', [
    'id' => 'custom-activity',
    'title' => 'Custom Activity',
    'icon' => 'fas fa-star',
    'url' => '#custom-activity',
    'position' => 50,
]);

/**
 * Register a dashboard widget
 */
register_widget('sample_widget', [
    'title' => 'Sample Widget',
    'description' => 'A sample dashboard widget',
    'icon' => 'fas fa-puzzle-piece',
    'size' => 'small', // small, medium, large
    'render' => function() {
        return '<div class="sample-widget">
            <h4>Sample Widget</h4>
            <p>This is a sample widget created by an extension.</p>
            <ul>
                <li>Today\'s Tasks: 5</li>
                <li>Pending Items: 3</li>
            </ul>
        </div>';
    },
]);

/**
 * Add custom CSS
 */
add_action('before_footer', function() {
    echo '<style>
        .patient-alerts-widget {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 10px;
        }
        .patient-alerts-widget h4 {
            margin: 0 0 10px;
            color: #856404;
        }
        .alert-allergy { color: #dc3545; }
        .alert-flag { color: #fd7e14; }
        .quick-action-btn {
            background: linear-gradient(to bottom, #1a4a5e, #0d3545);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .sample-widget {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
    </style>';
});

/**
 * Add custom JavaScript
 */
add_action('before_footer', function() {
    echo '<script>
        // Sample extension JavaScript
        document.querySelectorAll(".quick-action-btn").forEach(btn => {
            btn.addEventListener("click", function() {
                const type = this.dataset.type;
                const action = this.dataset.action;
                console.log("Quick action:", type, action);
            });
        });
    </script>';
});

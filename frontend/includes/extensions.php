<?php
/**
 * Openspace EHR - Extension Development Framework
 * Hooks, filters, and extension management system
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    die('Direct access not allowed');
}

/**
 * Extension Manager Class
 * Handles loading, registering, and managing extensions
 */
class ExtensionManager {
    private static $instance = null;
    private $extensions = [];
    private $hooks = [];
    private $filters = [];
    private $shortcodes = [];
    private $menus = [];
    private $widgets = [];
    private $loaded = false;
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initDefaultHooks();
    }
    
    /**
     * Initialize default system hooks
     */
    private function initDefaultHooks() {
        // System hooks that extensions can tap into
        $this->hooks = [
            'init' => [],
            'before_header' => [],
            'after_header' => [],
            'before_content' => [],
            'after_content' => [],
            'before_footer' => [],
            'after_footer' => [],
            'patient_loaded' => [],
            'patient_saved' => [],
            'encounter_created' => [],
            'encounter_closed' => [],
            'order_placed' => [],
            'result_received' => [],
            'medication_ordered' => [],
            'note_signed' => [],
            'user_login' => [],
            'user_logout' => [],
            'api_request' => [],
            'api_response' => [],
        ];
    }
    
    /**
     * Load all extensions from the extensions directory
     */
    public function loadExtensions() {
        if ($this->loaded) return;
        
        $extensionsDir = dirname(__DIR__) . '/extensions';
        if (!is_dir($extensionsDir)) {
            mkdir($extensionsDir, 0755, true);
            return;
        }
        
        $dirs = scandir($extensionsDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            
            $extensionPath = $extensionsDir . '/' . $dir;
            $mainFile = $extensionPath . '/extension.php';
            $configFile = $extensionPath . '/extension.json';
            
            if (is_dir($extensionPath) && file_exists($mainFile)) {
                $this->loadExtension($dir, $extensionPath, $configFile, $mainFile);
            }
        }
        
        $this->loaded = true;
        $this->doAction('init');
    }
    
    /**
     * Load a single extension
     */
    private function loadExtension($id, $path, $configFile, $mainFile) {
        $config = [];
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        // Check if extension is enabled
        $enabled = $this->isExtensionEnabled($id);
        if (!$enabled) return;
        
        $this->extensions[$id] = [
            'id' => $id,
            'path' => $path,
            'name' => $config['name'] ?? $id,
            'version' => $config['version'] ?? '1.0.0',
            'author' => $config['author'] ?? 'Unknown',
            'description' => $config['description'] ?? '',
            'requires' => $config['requires'] ?? [],
            'enabled' => true,
        ];
        
        // Include the main extension file
        require_once $mainFile;
    }
    
    /**
     * Check if an extension is enabled
     */
    public function isExtensionEnabled($id) {
        // Check database or config for enabled status
        // For now, all extensions are enabled by default
        return true;
    }
    
    /**
     * Register an action hook
     */
    public function addAction($hook, $callback, $priority = 10) {
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }
        
        $this->hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        
        // Sort by priority
        usort($this->hooks[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Execute an action hook
     */
    public function doAction($hook, ...$args) {
        if (!isset($this->hooks[$hook])) return;
        
        foreach ($this->hooks[$hook] as $action) {
            call_user_func_array($action['callback'], $args);
        }
    }
    
    /**
     * Register a filter
     */
    public function addFilter($filter, $callback, $priority = 10) {
        if (!isset($this->filters[$filter])) {
            $this->filters[$filter] = [];
        }
        
        $this->filters[$filter][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        
        usort($this->filters[$filter], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Apply filters to a value
     */
    public function applyFilters($filter, $value, ...$args) {
        if (!isset($this->filters[$filter])) return $value;
        
        foreach ($this->filters[$filter] as $filterItem) {
            $value = call_user_func_array($filterItem['callback'], array_merge([$value], $args));
        }
        
        return $value;
    }
    
    /**
     * Register a shortcode
     */
    public function addShortcode($tag, $callback) {
        $this->shortcodes[$tag] = $callback;
    }
    
    /**
     * Process shortcodes in content
     */
    public function processShortcodes($content) {
        if (empty($this->shortcodes)) return $content;
        
        $pattern = '/\[(\w+)(?:\s+([^\]]*))?\](?:([^\[]*)\[\/\1\])?/';
        
        return preg_replace_callback($pattern, function($matches) {
            $tag = $matches[1];
            $attrs = isset($matches[2]) ? $this->parseShortcodeAttrs($matches[2]) : [];
            $content = $matches[3] ?? '';
            
            if (isset($this->shortcodes[$tag])) {
                return call_user_func($this->shortcodes[$tag], $attrs, $content);
            }
            
            return $matches[0];
        }, $content);
    }
    
    /**
     * Parse shortcode attributes
     */
    private function parseShortcodeAttrs($str) {
        $attrs = [];
        $pattern = '/(\w+)=["\']([^"\']*)["\']|(\w+)=(\S+)/';
        
        preg_match_all($pattern, $str, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $attrs[$match[1]] = $match[2];
            } else {
                $attrs[$match[3]] = $match[4];
            }
        }
        
        return $attrs;
    }
    
    /**
     * Register a menu item
     */
    public function addMenuItem($location, $item) {
        if (!isset($this->menus[$location])) {
            $this->menus[$location] = [];
        }
        
        $this->menus[$location][] = $item;
    }
    
    /**
     * Get menu items for a location
     */
    public function getMenuItems($location) {
        return $this->menus[$location] ?? [];
    }
    
    /**
     * Register a widget
     */
    public function addWidget($id, $config) {
        $this->widgets[$id] = $config;
    }
    
    /**
     * Get registered widgets
     */
    public function getWidgets() {
        return $this->widgets;
    }
    
    /**
     * Render a widget
     */
    public function renderWidget($id) {
        if (!isset($this->widgets[$id])) return '';
        
        $widget = $this->widgets[$id];
        if (isset($widget['render']) && is_callable($widget['render'])) {
            return call_user_func($widget['render']);
        }
        
        return '';
    }
    
    /**
     * Get all loaded extensions
     */
    public function getExtensions() {
        return $this->extensions;
    }
    
    /**
     * Get extension info
     */
    public function getExtension($id) {
        return $this->extensions[$id] ?? null;
    }
    
    /**
     * Enable an extension
     */
    public function enableExtension($id) {
        // Store in database or config
        return true;
    }
    
    /**
     * Disable an extension
     */
    public function disableExtension($id) {
        // Store in database or config
        return true;
    }
}

// Global helper functions for extension developers

/**
 * Add an action hook
 */
function add_action($hook, $callback, $priority = 10) {
    ExtensionManager::getInstance()->addAction($hook, $callback, $priority);
}

/**
 * Execute an action hook
 */
function do_action($hook, ...$args) {
    ExtensionManager::getInstance()->doAction($hook, ...$args);
}

/**
 * Add a filter
 */
function add_filter($filter, $callback, $priority = 10) {
    ExtensionManager::getInstance()->addFilter($filter, $callback, $priority);
}

/**
 * Apply filters
 */
function apply_filters($filter, $value, ...$args) {
    return ExtensionManager::getInstance()->applyFilters($filter, $value, ...$args);
}

/**
 * Add a shortcode
 */
function add_shortcode($tag, $callback) {
    ExtensionManager::getInstance()->addShortcode($tag, $callback);
}

/**
 * Process shortcodes
 */
function do_shortcode($content) {
    return ExtensionManager::getInstance()->processShortcodes($content);
}

/**
 * Add a menu item
 */
function add_menu_item($location, $item) {
    ExtensionManager::getInstance()->addMenuItem($location, $item);
}

/**
 * Get menu items
 */
function get_menu_items($location) {
    return ExtensionManager::getInstance()->getMenuItems($location);
}

/**
 * Register a widget
 */
function register_widget($id, $config) {
    ExtensionManager::getInstance()->addWidget($id, $config);
}

/**
 * Render a widget
 */
function render_widget($id) {
    return ExtensionManager::getInstance()->renderWidget($id);
}

/**
 * Check if extension is active
 */
function is_extension_active($id) {
    $ext = ExtensionManager::getInstance()->getExtension($id);
    return $ext && $ext['enabled'];
}

/**
 * Get extension URL
 */
function get_extension_url($id) {
    return '/extensions/' . $id . '/';
}

/**
 * Get extension path
 */
function get_extension_path($id) {
    return dirname(__DIR__) . '/extensions/' . $id . '/';
}

<?php
/**
 * Plugin Name: Plans
 * Description: CPT "Plan" with [plans] shortcode that renders Monthly | Annual tabs and plan cards. No jQuery dependency.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: Kostiantyn Makarovskiy for Softoria Test Task
 * License: GPLv2 or later
 * Text Domain: plans
 */

if (!defined('ABSPATH')) {
    exit;
}

// PSR-4-like simple autoloader for this plugin only.
spl_autoload_register(function ($class) {
    if (strpos($class, 'Plans_') !== 0) {
        return;
    }
    $path = plugin_dir_path(__FILE__) . 'includes/' . 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

define('PLANS_PLUGIN_FILE', __FILE__);
define('PLANS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLANS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLANS_CACHE_KEY', 'plans_shortcode_v1');

final class Plans_Plugin {
    public function __construct() {
        // Core components.
        new Plans_CPT();
        new Plans_Meta();
        new Plans_Admin();
        new Plans_Shortcode();
        new Plans_Cache();

        // Flush rewrite on activation in case something changes in future.
        register_activation_hook(PLANS_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(PLANS_PLUGIN_FILE, [$this, 'deactivate']);
    }

    public function activate() {
        // Ensure CPT registered before flush.
        (new Plans_CPT())->register_cpt();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
        // Optional: clear transients on deactivate
        delete_transient(PLANS_CACHE_KEY);
    }
}
new Plans_Plugin();
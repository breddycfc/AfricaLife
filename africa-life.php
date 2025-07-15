<?php
/**
 * Plugin Name: Africa Life
 * Plugin URI: https://example.com/africa-life
 * Description: WordPress plugin for managing funeral cover applications with agent and admin interfaces
 * Version: 1.0.0
 * Author: Africa Life
 * License: GPL v2 or later
 * Text Domain: africa-life
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AFRICA_LIFE_VERSION', '1.0.0');
define('AFRICA_LIFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFRICA_LIFE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFRICA_LIFE_PLUGIN_BASENAME', plugin_basename(__FILE__));

class AfricaLife {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    private function load_dependencies() {
        $required_files = array(
            'includes/class-database.php',
            'includes/class-roles.php',
            'includes/class-shortcodes.php',
            'includes/class-ajax-handler.php',
            'includes/class-pdf-generator.php',
            'includes/class-email-handler.php',
            'admin/class-admin-dashboard.php',
            'public/class-agent-interface.php'
        );
        
        foreach ($required_files as $file) {
            $full_path = AFRICA_LIFE_PLUGIN_DIR . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
            } else {
                wp_die('Africa Life Plugin Error: Required file missing - ' . $file);
            }
        }
    }
    
    private function define_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function activate() {
        AfricaLife_Database::create_tables();
        AfricaLife_Roles::create_roles();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        AfricaLife_Roles::remove_roles();
        flush_rewrite_rules();
    }
    
    public function init() {
        AfricaLife_Shortcodes::init();
        AfricaLife_Ajax_Handler::init();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('tailwindcss', 'https://cdn.tailwindcss.com', array(), null);
        wp_enqueue_style('africa-life-public', AFRICA_LIFE_PLUGIN_URL . 'assets/css/public.css', array(), AFRICA_LIFE_VERSION);
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        wp_enqueue_script('africa-life-public', AFRICA_LIFE_PLUGIN_URL . 'assets/js/public.js', array('jquery'), AFRICA_LIFE_VERSION, true);
        
        wp_localize_script('africa-life-public', 'africa_life_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('africa_life_nonce')
        ));
        
        // Also localize for global use
        wp_localize_script('jquery', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('africa_life_admin')
        ));
    }
    
    public function enqueue_admin_scripts() {
        wp_enqueue_style('africa-life-admin', AFRICA_LIFE_PLUGIN_URL . 'assets/css/admin.css', array(), AFRICA_LIFE_VERSION);
        wp_enqueue_script('jquery');
        wp_enqueue_script('africa-life-admin', AFRICA_LIFE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AFRICA_LIFE_VERSION, true);
        
        // Localize script for admin AJAX calls
        wp_localize_script('africa-life-admin', 'africa_life_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('africa_life_admin')
        ));
    }
}

AfricaLife::get_instance();
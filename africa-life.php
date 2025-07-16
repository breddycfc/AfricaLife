<?php
/**
 * Plugin Name: Africa Life
 * Description: Funeral cover application management system with agent and admin interfaces
 * Version: 2.0.0
 * Author: Africa Life
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AFRICA_LIFE_VERSION', '2.0.0');
define('AFRICA_LIFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFRICA_LIFE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-database.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-roles.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-pdf-generator.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'includes/class-email-handler.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'admin/class-admin-dashboard.php';
require_once AFRICA_LIFE_PLUGIN_DIR . 'public/class-agent-interface.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'africa_life_activate');
function africa_life_activate() {
	require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
    AfricaLife_Database::create_tables();
    AfricaLife_Database::update_tables_for_pdf();
    AfricaLife_Roles::create_roles();
    flush_rewrite_rules();
}

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'africa_life_deactivate');
function africa_life_deactivate() {
    flush_rewrite_rules();
}

// Make sure admin styles are enqueued with higher priority
function africa_life_admin_styles() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'africa-life') !== false) {
        wp_enqueue_style('africa-life-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '1.0.1', 'all');
    }
}
add_action('admin_enqueue_scripts', 'africa_life_admin_styles', 999);

// Plugin uninstall hook
register_uninstall_hook(__FILE__, 'africa_life_uninstall');
function africa_life_uninstall() {
    // Only remove data if explicitly set
    if (get_option('africa_life_remove_data_on_uninstall')) {
        global $wpdb;
        
        // Drop custom tables
        $tables = array(
            $wpdb->prefix . 'africa_life_submissions',
            $wpdb->prefix . 'africa_life_dependents',
            $wpdb->prefix . 'africa_life_plans',
            $wpdb->prefix . 'africa_life_templates'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Remove roles
        AfricaLife_Roles::remove_roles();
        
        // Delete options
        delete_option('africa_life_version');
        delete_option('africa_life_admin_email');
        delete_option('africa_life_remove_data_on_uninstall');
    }
}

add_action('admin_init', 'africa_life_ensure_roles');
function africa_life_ensure_roles() {
    if (!get_role('africa_life_qa')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-roles.php';
        AfricaLife_Roles::create_roles();
    }
}

// Initialize plugin
add_action('init', 'africa_life_init');
function africa_life_init() {
    // Initialize shortcodes
    AfricaLife_Shortcodes::init();
    
    // Initialize AJAX handlers
    AfricaLife_Ajax_Handler::init();
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'africa_life_admin_scripts');
function africa_life_admin_scripts($hook) {
    if (strpos($hook, 'africa-life') !== false) {
        wp_enqueue_style('africa-life-admin', AFRICA_LIFE_PLUGIN_URL . 'assets/css/admin.css', array(), AFRICA_LIFE_VERSION);
        wp_enqueue_script('africa-life-admin', AFRICA_LIFE_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), AFRICA_LIFE_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_localize_script('africa-life-admin', 'africa_life_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('africa_life_admin')
        ));
    }
}

// Enqueue public scripts and styles
add_action('wp_enqueue_scripts', 'africa_life_public_scripts');
function africa_life_public_scripts() {
    // Enqueue Tailwind CSS
    wp_enqueue_style('tailwindcss', 'https://cdn.tailwindcss.com', array(), null);
    
    // Enqueue plugin styles
    wp_enqueue_style('africa-life-public', AFRICA_LIFE_PLUGIN_URL . 'assets/css/public.css', array(), AFRICA_LIFE_VERSION);
    
    // Enqueue plugin scripts
    wp_enqueue_script('africa-life-public', AFRICA_LIFE_PLUGIN_URL . 'assets/js/public.js', array('jquery'), AFRICA_LIFE_VERSION, true);
    
    // Check if admin dashboard shortcode is used and provide admin nonce
    global $post;
    $nonce_key = 'africa_life_public';
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'africa_life_admin_dashboard')) {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        $nonce_key = 'africa_life_admin'; // Use admin nonce for admin dashboard
    }
    
    // Localize script
    wp_localize_script('africa-life-public', 'africa_life_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce($nonce_key)
    ));
}

// Add custom Tailwind config script
add_action('wp_head', 'africa_life_tailwind_config');
function africa_life_tailwind_config() {
    ?>
    <script>
    if (typeof tailwind !== 'undefined') {
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'africa-green': '#2e7d32',
                        'africa-yellow': '#fbbf24',
                    }
                }
            }
        }
    }
    </script>
    <?php
}

// Add this temporarily to force table creation
add_action('admin_init', 'africa_life_check_qa_table');
function africa_life_check_qa_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'africa_life_qa_assignments';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if (!$exists) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
        AfricaLife_Database::create_tables();
    }
}

// Add admin menu
add_action('admin_menu', 'africa_life_admin_menu');
function africa_life_admin_menu() {
    add_menu_page(
        'Africa Life',
        'Africa Life',
        'manage_options',
        'africa-life',
        'africa_life_admin_page',
        'dashicons-heart',
        30
    );
    
    add_submenu_page(
        'africa-life',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'africa-life',
        'africa_life_admin_page'
    );
    
    add_submenu_page(
        'africa-life',
        'Submissions',
        'Submissions',
        'manage_options',
        'africa-life-submissions',
        'africa_life_submissions_page'
    );
    
    add_submenu_page(
        'africa-life',
        'Plans',
        'Plans',
        'manage_options',
        'africa-life-plans',
        'africa_life_plans_page'
    );
    
    add_submenu_page(
        'africa-life',
        'Agents',
        'Agents',
        'manage_options',
        'africa-life-agents',
        'africa_life_agents_page'
    );
    
    add_submenu_page(
        'africa-life',
        'Settings',
        'Settings',
        'manage_options',
        'africa-life-settings',
        'africa_life_settings_page'
    );
}

// Admin page callbacks
function africa_life_admin_page() {
    echo '<div class="wrap"><h1>Africa Life Dashboard</h1>';
    echo '<p>Please use the shortcode [africa_life_admin_dashboard] on a page to access the full dashboard.</p>';
    echo '</div>';
}

function africa_life_submissions_page() {
    echo '<div class="wrap"><h1>Submissions</h1>';
    echo '<p>Please use the shortcode [africa_life_admin_dashboard] and navigate to the Submissions tab.</p>';
    echo '</div>';
}

function africa_life_plans_page() {
    echo '<div class="wrap"><h1>Plans</h1>';
    echo '<p>Please use the shortcode [africa_life_admin_dashboard] and navigate to the Plans tab.</p>';
    echo '</div>';
}

function africa_life_agents_page() {
    echo '<div class="wrap"><h1>Agents</h1>';
    echo '<p>Please use the shortcode [africa_life_admin_dashboard] and navigate to the Agents tab.</p>';
    echo '</div>';
}

function africa_life_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>Africa Life Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('africa_life_settings');
    do_settings_sections('africa_life_settings');
    submit_button();
    echo '</form>';
    echo '</div>';
}

// Register settings
add_action('admin_init', 'africa_life_register_settings');
function africa_life_register_settings() {
    register_setting('africa_life_settings', 'africa_life_admin_email');
    register_setting('africa_life_settings', 'africa_life_remove_data_on_uninstall');
    
    add_settings_section(
        'africa_life_general_settings',
        'General Settings',
        'africa_life_general_settings_callback',
        'africa_life_settings'
    );
    
    add_settings_field(
        'africa_life_admin_email',
        'Admin Email',
        'africa_life_admin_email_callback',
        'africa_life_settings',
        'africa_life_general_settings'
    );
    
    add_settings_field(
        'africa_life_remove_data_on_uninstall',
        'Remove Data on Uninstall',
        'africa_life_remove_data_callback',
        'africa_life_settings',
        'africa_life_general_settings'
    );
}

function africa_life_general_settings_callback() {
    echo '<p>Configure general settings for the Africa Life plugin.</p>';
}

function africa_life_admin_email_callback() {
    $value = get_option('africa_life_admin_email', get_option('admin_email'));
    echo '<input type="email" name="africa_life_admin_email" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Email address where admin notifications will be sent.</p>';
}

function africa_life_remove_data_callback() {
    $value = get_option('africa_life_remove_data_on_uninstall', false);
    echo '<label><input type="checkbox" name="africa_life_remove_data_on_uninstall" value="1" ' . checked($value, true, false) . ' /> ';
    echo 'Remove all plugin data when uninstalling</label>';
    echo '<p class="description">Warning: This will permanently delete all submissions, plans, and agent data.</p>';
}

// Check and update database on version change
add_action('plugins_loaded', 'africa_life_check_version');
function africa_life_check_version() {
    $current_version = get_option('africa_life_version', '0');
    
    if (version_compare($current_version, AFRICA_LIFE_VERSION, '<')) {
        // Run database updates
        AfricaLife_Database::create_tables();
        AfricaLife_Database::update_tables_for_pdf();
        
        // Update version
        update_option('africa_life_version', AFRICA_LIFE_VERSION);
    }
}
// Add this temporarily to force table creation
add_action('admin_init', 'africa_life_check_tables');
function africa_life_check_tables() {
    $db_version = get_option('africa_life_db_version');
    if ($db_version != '1.0') {
        require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
        AfricaLife_Database::create_tables();
    }
}

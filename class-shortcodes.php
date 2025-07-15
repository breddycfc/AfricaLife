<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Shortcodes {
    
    public static function init() {
        add_shortcode('africa_life_agent_form', array(__CLASS__, 'agent_form_shortcode'));
        add_shortcode('africa_life_admin_dashboard', array(__CLASS__, 'admin_dashboard_shortcode'));
        add_shortcode('africa_life_admin_login', array(__CLASS__, 'admin_login_shortcode'));
        add_shortcode('africa_life_agent_login', array(__CLASS__, 'agent_login_shortcode'));
        add_shortcode('africa_life_script', array(__CLASS__, 'script_shortcode'));
    }
    
    public static function agent_form_shortcode($atts) {
        if (!is_user_logged_in() || !AfricaLife_Roles::user_has_agent_access()) {
            return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Access denied. You must be logged in as an agent to access this form.</div>';
        }
        
        $agent_interface = new AfricaLife_Agent_Interface();
        return $agent_interface->render_form();
    }
    
    public static function admin_dashboard_shortcode($atts) {
        if (!is_user_logged_in() || !AfricaLife_Roles::user_has_admin_access()) {
            return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Access denied. Please login as an administrator.</div>';
        }
        
        $admin_dashboard = new AfricaLife_Admin_Dashboard();
        return $admin_dashboard->render_dashboard_only();
    }
    
    public static function admin_login_shortcode($atts) {
        if (is_user_logged_in() && AfricaLife_Roles::user_has_admin_access()) {
            return '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">You are already logged in. <a href="' . home_url('/admin-dashboard/') . '">Go to Dashboard</a></div>';
        }
        
        $admin_dashboard = new AfricaLife_Admin_Dashboard();
        return $admin_dashboard->render_login_form();
    }
    
    public static function agent_login_shortcode($atts) {
        if (is_user_logged_in() && AfricaLife_Roles::user_has_agent_access()) {
            return '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">You are already logged in as an agent. <a href="' . home_url('/agent-dashboard/') . '">Go to Agent Dashboard</a></div>';
        }
        
        return self::render_agent_login_form();
    }
    
    private static function render_agent_login_form() {
        ob_start();
        ?>
        <div class="africa-life-agent-login min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);">
            <div class="bg-gray-900 p-8 rounded-xl shadow-2xl border border-yellow-400 w-full max-w-md">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-yellow-400 mb-2">Africa Life</h1>
                    <p class="text-gray-300">Agent Login Portal</p>
                </div>
                
                <form id="agent-login-form" class="space-y-6">
                    <?php wp_nonce_field('africa_life_agent_login', 'nonce'); ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
                    </div>
                    
                    <button type="submit" id="agent-login-btn" 
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105">
                        Login as Agent
                    </button>
                    
                    <div id="agent-login-message" class="hidden text-center text-red-400 text-sm bg-red-900 p-3 rounded-lg"></div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Define AJAX URL for agent login
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            
            $('#agent-login-form').submit(function(e) {
                e.preventDefault();
                
                var loginBtn = $('#agent-login-btn');
                var message = $('#agent-login-message');
                var originalText = loginBtn.text();
                
                loginBtn.prop('disabled', true).html('<span class="inline-block animate-spin mr-2">⟳</span>Logging in...');
                message.hide();
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'africa_life_agent_login',
                        username: $('input[name="username"]').val(),
                        password: $('input[name="password"]').val(),
                        nonce: $('input[name="nonce"]').val()
                    },
                    success: function(response) {
                        console.log('Agent login response:', response);
                        
                        if (response.success) {
                            message.removeClass('text-red-400 bg-red-900').addClass('text-green-400 bg-green-900').text('Login successful! Redirecting...').show();
                            setTimeout(function() {
                                window.location.href = '<?php echo home_url('/agent-dashboard/'); ?>';
                            }, 1000);
                        } else {
                            message.removeClass('text-green-400 bg-green-900').addClass('text-red-400 bg-red-900').text(response.data).show();
                            loginBtn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Agent login error:', error);
                        message.removeClass('text-green-400 bg-green-900').addClass('text-red-400 bg-red-900').text('An error occurred. Please try again.').show();
                        loginBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public static function script_shortcode($atts) {
        if (!is_user_logged_in() || !AfricaLife_Roles::user_has_agent_access()) {
            return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Access denied.</div>';
        }
        
        $script_template = AfricaLife_Database::get_template('script');
        $script_content = $script_template ? $script_template->template_content['content'] : 'Agent script not configured.';
        
        ob_start();
        ?>
        <div class="africa-life-script-widget bg-gray-900 p-6 rounded-lg shadow-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-yellow-400">Agent Script</h2>
                <button id="toggle-script" class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors duration-200">
                    Minimize
                </button>
            </div>
            
            <div id="script-content" class="bg-gray-800 p-4 rounded-lg text-gray-300 transition-all duration-300">
                <?php echo wp_kses_post($script_content); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#toggle-script').click(function() {
                var button = $(this);
                var content = $('#script-content');
                
                content.slideToggle(300, function() {
                    var isVisible = content.is(':visible');
                    button.text(isVisible ? 'Minimize' : 'Maximize');
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
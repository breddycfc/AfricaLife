<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Admin_Dashboard {
    
    public function render() {
    if (!is_user_logged_in() || !AfricaLife_Roles::user_has_dashboard_access()) {
        return $this->render_login_form();
    }
    
    return $this->render_dashboard_content();
}
    
    public function render_dashboard_only() {
        return $this->render_dashboard_content();
    }
    
    public function render_login_form() {
        ob_start();
        ?>
        <div class="africa-life-admin-login min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);">
            <div class="bg-gray-900 p-8 rounded-xl shadow-2xl w-full max-w-md border border-gray-700">
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-yellow-400 mb-2">Africa Life</h1>
                    <p class="text-gray-300">Admin Dashboard Login</p>
                </div>
                
                <form id="admin-login-form" class="space-y-6">
                    
                    <?php wp_nonce_field('africa_life_admin_login', 'nonce'); ?>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-white">Username</label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                               placeholder="Enter your username">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-white">Password</label>
                        <input type="password" name="password" required 
                               class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent"
                               placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" id="login-btn" 
                            class="w-full bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black font-bold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                        Login to Dashboard
                    </button>
                    
                    <div id="login-message" class="hidden text-center text-red-400 text-sm bg-red-900 p-3 rounded-lg"></div>
                </form>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#admin-login-form').submit(function(e) {
                e.preventDefault();
                
                var loginBtn = $('#login-btn');
                var message = $('#login-message');
                var originalText = loginBtn.text();
                
                loginBtn.prop('disabled', true).html('<span class="inline-block animate-spin mr-2">⟳</span>Logging in...');
                message.addClass('hidden');
                
                $.ajax({
                    url: africa_life_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'africa_life_admin_login',
                        username: $('input[name="username"]').val(),
                        password: $('input[name="password"]').val(),
                        nonce: $('input[name="nonce"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            message.removeClass('hidden').removeClass('text-red-400 bg-red-900').addClass('text-green-400 bg-green-900').text('Login successful! Redirecting...');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            message.text(response.data || 'Login failed').removeClass('hidden');
                        }
                    },
                    error: function() {
                        message.text('An error occurred. Please try again.').removeClass('hidden');
                    },
                    complete: function() {
                        loginBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_dashboard_content() {
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'stats';
    $is_super_admin = AfricaLife_Roles::user_has_admin_access();
    $is_qa = AfricaLife_Roles::user_has_qa_access();
    
    ob_start();
    ?>
    <!-- Add inline critical CSS -->
    <style>
        .africa-life-admin-dashboard {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif !important;
            color: #ffffff !important;
            background: #0f0f0f !important;
            min-height: 100vh !important;
        }
        
        .africa-life-admin-dashboard * {
            box-sizing: border-box;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid #2a2a2a;
        }
        
        .admin-nav-container {
            background: #1a1a1a;
            border-radius: 16px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            border: 1px solid #2a2a2a;
        }
        
        .admin-nav-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .admin-nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            color: #a0a0a0;
            transition: all 0.3s ease;
        }
        
        .admin-nav-tab:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }
        
        .admin-nav-tab.active {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #000;
            font-weight: 600;
        }
        
        .admin-content {
            background: #1a1a1a;
            border-radius: 20px;
            border: 1px solid #2a2a2a;
            overflow: hidden;
        }
        
        .admin-content-inner {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1f1f1f 0%, #2a2a2a 100%);
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            padding: 1.75rem;
            transition: all 0.3s ease;
        }
        
        .table-container {
            background: #0f0f0f;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #2a2a2a;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            background: #1f1f1f;
            color: #a0a0a0;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .admin-table td {
            padding: 1.25rem 1.5rem;
            color: #ffffff;
            border-bottom: 1px solid rgba(42, 42, 42, 0.5);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #000;
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid #2a2a2a;
        }
    </style>
    
    <div class="africa-life-admin-dashboard">
        <div class="admin-container">
            <!-- Header -->
            <div class="admin-header">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
                    <div>
                        <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 0.5rem 0;">
                            AFRICA LIFE
                        </h1>
                        <p style="color: #a0a0a0; font-size: 1rem; margin: 0;">
                            <?php echo $is_super_admin ? 'Administration Dashboard' : 'Quality Assurance Dashboard'; ?>
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div style="text-align: right;">
                            <p style="color: #a0a0a0; font-size: 0.875rem; margin: 0 0 0.25rem 0;">Welcome back,</p>
                            <p style="font-size: 1.125rem; font-weight: 600; color: #ffffff; margin: 0;">
                                <?php echo esc_html(wp_get_current_user()->display_name); ?>
                                <span style="display: inline-block; padding: 0.25rem 0.75rem; background: rgba(251, 191, 36, 0.2); color: #fbbf24; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin-left: 0.5rem;">
                                    <?php echo $is_super_admin ? 'Super Admin' : 'QA'; ?>
                                </span>
                            </p>
                        </div>
                        <a href="<?php echo wp_logout_url(get_permalink()); ?>" 
                           style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Rest of the content continues... -->
            
            <!-- Navigation -->
            <div class="admin-nav-container">
                <nav class="admin-nav-tabs">
                    <?php
                    if ($is_super_admin) {
                        $tabs = array(
                            'stats' => array('label' => 'Dashboard', 'icon' => '📊'),
                            'submissions' => array('label' => 'Applications', 'icon' => '📄'),
                            'plans' => array('label' => 'Plans', 'icon' => '📋'),
                            'agents' => array('label' => 'Agents', 'icon' => '👥'),
                            'qa' => array('label' => 'QA Users', 'icon' => '👔'),
                            'templates' => array('label' => 'Templates', 'icon' => '📝'),
                            'settings' => array('label' => 'Settings', 'icon' => '⚙️')
                        );
                    } else {
                        $tabs = array(
                            'stats' => array('label' => 'Dashboard', 'icon' => '📊'),
                            'submissions' => array('label' => 'Applications', 'icon' => '📄')
                        );
                    }
                    
                    foreach ($tabs as $tab_key => $tab_data) {
                        $active = $current_tab === $tab_key;
                        $active_class = $active ? 'active' : '';
                        
                        echo '<a href="?tab=' . $tab_key . '" class="admin-nav-tab ' . $active_class . '">';
                        echo '<span class="tab-icon">' . $tab_data['icon'] . '</span>';
                        echo '<span>' . $tab_data['label'] . '</span>';
                        echo '</a>';
                    }
                    ?>
                </nav>
            </div>
            
            <!-- Content -->
            <div class="admin-content fade-in">
                <div class="admin-content-inner">
                    <?php
                    switch ($current_tab) {
                        case 'stats':
                            echo $this->render_stats_tab();
                            break;
                        case 'submissions':
                            echo $this->render_submissions_tab();
                            break;
                        case 'plans':
                            if ($is_super_admin) {
                                echo $this->render_plans_tab();
                            }
                            break;
                        case 'agents':
                            if ($is_super_admin) {
                                echo $this->render_agents_tab();
                            }
                            break;
                        case 'qa':
                            if ($is_super_admin) {
                                echo $this->render_qa_tab();
                            }
                            break;
                        case 'templates':
                            if ($is_super_admin) {
                                echo $this->render_templates_tab();
                            }
                            break;
                        case 'settings':
                            if ($is_super_admin) {
                                echo $this->render_settings_tab();
                            }
                            break;
                        default:
                            echo $this->render_stats_tab();
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Global Modal Container -->
    <div id="modal-container"></div>
    
    <script>
    // Global functions
    function showModal(title, content, actions) {
        var modal = jQuery('<div class="admin-modal fade-in">' +
            '<div class="admin-modal-content">' +
                '<div class="admin-modal-header">' +
                    '<h3 class="admin-modal-title">' + title + '</h3>' +
                    '<button class="admin-modal-close modal-close">&times;</button>' +
                '</div>' +
                '<div class="admin-modal-body">' + content + '</div>' +
                '<div class="admin-modal-footer">' + (actions || '') + '</div>' +
            '</div>' +
        '</div>');
        
        jQuery('#modal-container').html(modal);
        
        modal.find('.modal-close').click(function() {
            closeModal();
        });
        
        modal.click(function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        return modal;
    }
    
    function closeModal() {
        jQuery('#modal-container').fadeOut(300, function() {
            jQuery(this).empty();
        });
    }
    
    function showNotification(message, type) {
        var bgColor = type === 'success' ? '#10b981' : '#ef4444';
        var icon = type === 'success' ? '✓' : '✗';
        
        var notification = jQuery('<div class="notification fade-in" style="position: fixed; top: 2rem; right: 2rem; z-index: 10000; padding: 1rem 1.5rem; border-radius: 12px; background: ' + bgColor + '; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 1rem; font-weight: 500;">' +
            '<span style="font-size: 1.25rem;">' + icon + '</span>' +
            '<span>' + message + '</span>' +
            '<button style="margin-left: 1rem; background: none; border: none; color: white; font-size: 1.25rem; cursor: pointer; opacity: 0.7;" onclick="jQuery(this).parent().fadeOut(300, function(){ jQuery(this).remove(); })">&times;</button>' +
        '</div>');
        
        jQuery('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                notification.remove();
            });
        }, 5000);
    }
    </script>
    <?php
    return ob_get_clean();
}
    
    private function render_stats_tab() {
    $stats = $this->get_dashboard_stats();
    
    ob_start();
    ?>
    <div>
        <div class="section-header">
            <div class="section-title">
                <div class="section-title-icon">📊</div>
                <span>Dashboard Overview</span>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-header">
                        <div>
                            <p class="stat-label">Total Applications</p>
                            <p class="stat-value"><?php echo number_format($stats['total']); ?></p>
                            <p class="stat-change positive">↑ 12% from last month</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            📋
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-header">
                        <div>
                            <p class="stat-label">Pending Review</p>
                            <p class="stat-value" style="color: var(--warning);"><?php echo number_format($stats['pending']); ?></p>
                            <p class="stat-change negative">↓ 5% from last week</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning);">
                            ⏳
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-header">
                        <div>
                            <p class="stat-label">Approved</p>
                            <p class="stat-value" style="color: var(--success);"><?php echo number_format($stats['approved']); ?></p>
                            <p class="stat-change positive">↑ 18% from last month</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                            ✅
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-header">
                        <div>
                            <p class="stat-label">Declined</p>
                            <p class="stat-value" style="color: var(--danger);"><?php echo number_format($stats['declined']); ?></p>
                            <p class="stat-change positive">↓ 22% improvement</p>
                        </div>
                        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                            ❌
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Premium Card -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">💰</div>
                <div>
                    <h3 class="info-card-title">Monthly Premium Collection</h3>
                    <p class="info-card-subtitle">Expected revenue for <?php echo date('F Y'); ?></p>
                </div>
            </div>
            <p class="info-card-value">R <?php echo number_format($stats['monthly_premium'], 2); ?></p>
        </div>
        
        <!-- Recent Applications -->
        <div style="margin-top: 2rem;">
            <div class="section-header">
                <h3 style="font-size: 1.25rem; font-weight: 600;">Recent Applications</h3>
                <a href="?tab=submissions" class="btn btn-secondary btn-sm">View All →</a>
            </div>
            
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Application #</th>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Premium</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stats['recent_applications'])): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem;">
                                    <div class="empty-state-icon">📄</div>
                                    <p style="color: var(--text-secondary);">No applications found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stats['recent_applications'] as $app): ?>
                                <tr>
                                    <td style="font-weight: 600; color: var(--primary-gold);">
                                        <?php echo esc_html($app->application_number); ?>
                                    </td>
                                    <td><?php echo esc_html($app->main_full_names . ' ' . $app->main_surname); ?></td>
                                    <td><?php echo esc_html($app->plan_name); ?></td>
                                    <td style="font-weight: 600;">R <?php echo number_format($app->total_premium, 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($app->submission_date)); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($app->status); ?>">
                                            <?php echo esc_html($app->status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
    
    private function get_dashboard_stats() {
    $is_qa = AfricaLife_Roles::user_has_qa_access();
    
    if ($is_qa) {
        return AfricaLife_Database::get_dashboard_stats_for_qa(get_current_user_id());
    }
    
    // Original stats for super admin
    global $wpdb;
    
    $submissions_table = $wpdb->prefix . 'africa_life_submissions';
    $plans_table = $wpdb->prefix . 'africa_life_plans';
    
    // Basic counts
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table");
    $approved = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'Approved'");
    $declined = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'Declined'");
    $pending = $wpdb->get_var("SELECT COUNT(*) FROM $submissions_table WHERE status = 'Pending'");
    
    // Monthly premium
    $monthly_premium = $wpdb->get_var(
        "SELECT SUM(total_premium) FROM $submissions_table 
        WHERE status = 'Approved' 
        AND MONTH(submission_date) = MONTH(CURRENT_DATE())
        AND YEAR(submission_date) = YEAR(CURRENT_DATE())"
    );
    
    // Recent applications
    $recent_applications = $wpdb->get_results(
        "SELECT s.*, p.plan_name 
        FROM $submissions_table s
        LEFT JOIN $plans_table p ON s.plan_id = p.id
        ORDER BY s.submission_date DESC 
        LIMIT 10"
    );
    
    return array(
        'total' => intval($total),
        'approved' => intval($approved),
        'declined' => intval($declined),
        'pending' => intval($pending),
        'monthly_premium' => floatval($monthly_premium),
        'recent_applications' => $recent_applications
    );
}
    
    private function render_submissions_tab() {
    $is_qa = AfricaLife_Roles::user_has_qa_access();
    
    if ($is_qa) {
        $submissions = AfricaLife_Database::get_submissions_for_qa(get_current_user_id());
    } else {
        $submissions = AfricaLife_Database::get_submissions();
    }
    
    ob_start();
    ?>
    <div class="p-8">
        <h2 class="text-2xl font-semibold mb-8 text-yellow-400">Applications Management</h2>
        
        <?php if (empty($submissions)): ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📄</div>
                <p class="text-gray-400 text-lg">No applications found.</p>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-white admin-table">
                        <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                            <tr>
                                <th class="px-6 py-4">Application #</th>
                                <th class="px-6 py-4">Member #</th>
                                <th class="px-6 py-4">Customer Name</th>
                                <th class="px-6 py-4">ID Number</th>
                                <th class="px-6 py-4">Agent</th>
                                <th class="px-6 py-4">Plan</th>
                                <th class="px-6 py-4">Premium</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-750 transition-colors duration-200">
                                    <td class="px-6 py-4 font-medium text-yellow-400">
                                        <?php echo esc_html($submission->application_number); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($submission->member_number); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($submission->main_full_names . ' ' . $submission->main_surname); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($submission->main_id_number); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php
                                        $agent = get_userdata($submission->agent_id);
                                        echo esc_html($agent ? $agent->display_name : 'Unknown');
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($this->get_plan_name($submission->plan_id)); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        R <?php echo number_format($submission->total_premium, 2); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html(date('Y-m-d', strtotime($submission->submission_date))); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <select class="status-select bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                                data-submission-id="<?php echo $submission->id; ?>">
                                            <option value="Pending" <?php selected($submission->status, 'Pending'); ?>>Pending</option>
                                            <option value="Approved" <?php selected($submission->status, 'Approved'); ?>>Approved</option>
                                            <option value="Declined" <?php selected($submission->status, 'Declined'); ?>>Declined</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button class="view-details-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                                                    data-submission-id="<?php echo $submission->id; ?>">
                                                View
                                            </button>
                                            <?php if ($submission->pdf_file): ?>
                                                <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/africa-life/' . $submission->pdf_file); ?>" 
                                                   target="_blank" class="bg-yellow-600 hover:bg-yellow-700 text-black px-3 py-1 rounded text-xs font-medium transition-colors duration-200">
                                                    PDF
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Status update
        $('.status-select').change(function() {
            var submissionId = $(this).data('submission-id');
            var newStatus = $(this).val();
            var selectElement = $(this);
            var originalValue = selectElement.data('original-value') || selectElement.val();
            
            if (!selectElement.data('original-value')) {
                selectElement.data('original-value', originalValue);
            }
            
            selectElement.prop('disabled', true).addClass('opacity-50');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'africa_life_update_status',
                    submission_id: submissionId,
                    status: newStatus,
                    nonce: africa_life_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        selectElement.data('original-value', newStatus);
                        showNotification('Status updated successfully', 'success');
                    } else {
                        selectElement.val(originalValue);
                        showNotification('Error updating status: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    selectElement.val(originalValue);
                    showNotification('An error occurred while updating the status.', 'error');
                },
                complete: function() {
                    selectElement.prop('disabled', false).removeClass('opacity-50');
                }
            });
        });
        
        // View details
        $('.view-details-btn').click(function() {
            var submissionId = $(this).data('submission-id');
            // TODO: Implement view details modal
            showNotification('View details functionality coming soon', 'info');
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
    
    private function get_plan_name($plan_id) {
        $plan = AfricaLife_Database::get_plan($plan_id);
        return $plan ? $plan->plan_name : 'Unknown Plan';
    }
	
	
	private function render_qa_tab() {
    $qa_users = AfricaLife_Roles::get_qa_users();
    $all_agents = AfricaLife_Roles::get_agents();
    
    ob_start();
    ?>
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-semibold text-yellow-400">QA Management</h2>
            <button id="add-qa-btn" class="btn btn-primary">
                <span>👔</span> Add New QA User
            </button>
        </div>
        
        <?php if (empty($qa_users)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">👔</div>
                <p class="empty-state-text">No QA users found.</p>
                <button onclick="jQuery('#add-qa-btn').click()" class="btn btn-primary">
                    Create Your First QA User
                </button>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-white admin-table">
                        <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                            <tr>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Assigned Agents</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qa_users as $qa): ?>
                                <?php $assigned_agents = AfricaLife_Roles::get_qa_assigned_agents($qa->ID); ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-750 transition-colors duration-200">
                                    <td class="px-6 py-4 font-medium text-yellow-400">
                                        <?php echo esc_html(trim($qa->first_name . ' ' . $qa->last_name) ?: $qa->display_name); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($qa->user_login); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($qa->user_email); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <?php if (empty($assigned_agents)): ?>
                                                <span class="text-gray-500 text-xs">No agents assigned</span>
                                            <?php else: ?>
                                                <?php foreach ($assigned_agents as $agent): ?>
                                                    <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">
                                                        <?php echo esc_html($agent->display_name); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="button-group">
                                            <button class="manage-assignments-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm" 
                                                    data-qa-id="<?php echo $qa->ID; ?>"
                                                    data-qa-name="<?php echo esc_attr($qa->display_name); ?>">
                                                Manage Agents
                                            </button>
                                            <button class="delete-qa-btn button-danger" 
                                                    data-qa-id="<?php echo $qa->ID; ?>">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
jQuery(document).ready(function($) {
    console.log('QA tab JavaScript loaded');
    
    // Add QA button click handler
    $('#add-qa-btn').off('click').on('click', function(e) {
        e.preventDefault();
        console.log('Add QA button clicked');
        showQAModal();
    });
    
    // Manage assignments button click handler - use event delegation
    $(document).on('click', '.manage-assignments-btn', function(e) {
        e.preventDefault();
        var qaId = $(this).data('qa-id');
        var qaName = $(this).data('qa-name');
        showAssignmentsModal(qaId, qaName);
    });
    
    // Delete QA button click handler - use event delegation
    $(document).on('click', '.delete-qa-btn', function(e) {
        e.preventDefault();
        var qaId = $(this).data('qa-id');
        if (confirm('Are you sure you want to delete this QA user? This action cannot be undone.')) {
            deleteQA(qaId);
        }
    });
    
    window.showQAModal = function() {
        console.log('showQAModal called');
        
        var content = '<form id="qa-form" class="space-y-6">' +
            '<div class="grid grid-cols-2 gap-4">' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">First Name *</label>' +
                    '<input type="text" name="first_name" id="qa_first_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '</div>' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Last Name *</label>' +
                    '<input type="text" name="last_name" id="qa_last_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '</div>' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm font-medium text-yellow-400 mb-2">Username *</label>' +
                '<input type="text" name="username" id="qa_username" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '<p class="text-xs text-gray-400 mt-1">This will be used for login</p>' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm font-medium text-yellow-400 mb-2">Email *</label>' +
                '<input type="email" name="email" id="qa_email" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
            '</div>' +
            '<div>' +
                '<label class="block text-sm font-medium text-yellow-400 mb-2">Password *</label>' +
                '<input type="password" name="password" id="qa_password" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '<p class="text-xs text-gray-400 mt-1">Minimum 6 characters</p>' +
            '</div>' +
        '</form>';
        
        var actions = '<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>' +
                     '<button type="button" id="save-qa-btn" class="btn btn-primary">Create QA User</button>';
        
        showModal('Add New QA User', content, actions);
        
        // Handle form submission
        $('#save-qa-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Create QA clicked');
            
            // Validate form
            var isValid = true;
            $('#qa-form input[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('border-red-500');
                    isValid = false;
                } else {
                    $(this).removeClass('border-red-500');
                }
            });
            
            if (!isValid) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }
            
            // Validate email
            var email = $('#qa_email').val();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showNotification('Please enter a valid email address', 'error');
                $('#qa_email').addClass('border-red-500');
                return;
            }
            
            // Validate password length
            var password = $('#qa_password').val();
            if (password.length < 6) {
                showNotification('Password must be at least 6 characters long', 'error');
                $('#qa_password').addClass('border-red-500');
                return;
            }
            
            // All validation passed, create QA
            createQA();
        });
    };
    
    window.createQA = function() {
        console.log('createQA called');
        
        var data = {
            action: 'africa_life_create_qa',
            nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>',
            first_name: $('#qa_first_name').val(),
            last_name: $('#qa_last_name').val(),
            username: $('#qa_username').val(),
            email: $('#qa_email').val(),
            password: $('#qa_password').val()
        };
        
        console.log('Sending QA data:', data);
        
        // Disable button to prevent double submission
        $('#save-qa-btn').prop('disabled', true).text('Creating...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('QA create response:', response);
                if (response.success) {
                    showNotification('QA user created successfully', 'success');
                    closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                    $('#save-qa-btn').prop('disabled', false).text('Create QA User');
                }
            },
            error: function(xhr, status, error) {
                console.error('QA create error:', error);
                console.error('Response:', xhr.responseText);
                showNotification('An error occurred while creating the QA user.', 'error');
                $('#save-qa-btn').prop('disabled', false).text('Create QA User');
            }
        });
    };
    
    window.showAssignmentsModal = function(qaId, qaName) {
        console.log('showAssignmentsModal called', qaId, qaName);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'africa_life_get_qa_assignments',
                qa_id: qaId,
                nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>'
            },
            success: function(response) {
                console.log('Get assignments response:', response);
                if (response.success) {
                    var content = '<h4 class="mb-4 text-gray-300">Select agents to assign to ' + qaName + '</h4>' +
                        '<form id="assignments-form" class="space-y-4">' +
                        '<input type="hidden" name="qa_id" value="' + qaId + '">';
                    
                    $.each(response.data.agents, function(index, agent) {
                        var checked = response.data.assigned.includes(agent.ID) ? 'checked' : '';
                        content += '<label class="flex items-center space-x-3 p-3 bg-gray-800 rounded hover:bg-gray-700 cursor-pointer">' +
                            '<input type="checkbox" name="agent_ids[]" value="' + agent.ID + '" ' + checked + ' class="form-checkbox h-5 w-5 text-yellow-400">' +
                            '<span class="text-white">' + agent.display_name + '</span>' +
                        '</label>';
                    });
                    
                    content += '</form>';
                    
                    var actions = '<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>' +
                                 '<button type="button" id="save-assignments-btn" class="btn btn-primary">Save Assignments</button>';
                    
                    showModal('Manage Agent Assignments', content, actions);
                    
                    // Bind save button
                    $('#save-assignments-btn').off('click').on('click', function() {
                        saveAssignments();
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Get assignments error:', error);
                showNotification('Error loading assignments', 'error');
            }
        });
    };
    
    window.saveAssignments = function() {
        console.log('saveAssignments called');
        
        var formData = $('#assignments-form').serialize();
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData + '&action=africa_life_save_qa_assignments&nonce=<?php echo wp_create_nonce('africa_life_admin'); ?>',
            success: function(response) {
                console.log('Save assignments response:', response);
                if (response.success) {
                    showNotification('Assignments saved successfully', 'success');
                    closeModal();
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save assignments error:', error);
                showNotification('An error occurred while saving assignments.', 'error');
            }
        });
    };
    
    window.deleteQA = function(qaId) {
        console.log('deleteQA called', qaId);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'africa_life_delete_qa',
                user_id: qaId,
                nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>'
            },
            success: function(response) {
                console.log('Delete QA response:', response);
                if (response.success) {
                    showNotification('QA user deleted successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete QA error:', error);
                showNotification('An error occurred while deleting the QA user.', 'error');
            }
        });
    };
});
</script>
    <?php
    return ob_get_clean();
}
	
    
    // Continue with other tab methods...
private function render_plans_tab() {
    $plans = AfricaLife_Database::get_plans();
    
    ob_start();
    ?>
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
    <h2 class="text-2xl font-semibold text-white">Plans Management</h2>
    <button id="add-plan-btn" class="btn btn-primary">
        <span>➕</span> Add New Plan
    </button>
</div>
        
        <?php if (empty($plans)): ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📋</div>
                <p class="text-gray-400 text-lg mb-4">No plans found.</p>
                <button onclick="jQuery('#add-plan-btn').click()" class="bg-yellow-600 hover:bg-yellow-700 text-black px-6 py-3 rounded-lg font-semibold">
                    Create Your First Plan
                </button>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($plans as $plan): ?>
                    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-yellow-400 transition-colors duration-300">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-yellow-300"><?php echo esc_html($plan->plan_name); ?></h3>
                                <p class="text-sm text-gray-400">Code: <?php echo esc_html($plan->plan_code); ?></p>
                            </div>
                            <div class="flex space-x-2">
                                <button class="edit-plan-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200" 
                                        data-plan-id="<?php echo $plan->id; ?>"
                                        data-plan-name="<?php echo esc_attr($plan->plan_name); ?>"
                                        data-categories='<?php echo esc_attr(json_encode($plan->categories)); ?>'>
                                    Edit
                                </button>
                                <button class="delete-plan-btn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200" 
                                        data-plan-id="<?php echo $plan->id; ?>">
                                    Delete
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <?php
                            $categories = is_array($plan->categories) ? $plan->categories : json_decode($plan->categories, true);
                            $total_premium = 0;
                            if ($categories):
                                foreach ($categories as $category):
                                    $total_premium += floatval($category['rate']);
                            ?>
                                <div class="bg-gray-700 p-4 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-medium text-white"><?php echo esc_html($category['name']); ?></span>
                                        <span class="text-gray-400 text-sm"><?php echo esc_html($category['age_range']); ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-300">
                                        <span>Premium: <span class="text-yellow-400 font-semibold">R<?php echo number_format($category['rate'], 2); ?></span></span>
                                        <span>Cover: <span class="text-green-400 font-semibold">R<?php echo number_format($category['cover_amount'], 2); ?></span></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="border-t border-gray-600 pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold">Total Premium:</span>
                                    <span class="text-xl font-bold text-yellow-400">R<?php echo number_format($total_premium, 2); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('Plans tab JavaScript loaded');
        
        // Add plan button click handler
        $('#add-plan-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Add plan button clicked');
            showPlanModal();
        });
        
        // Edit plan button click handler - use event delegation
        $(document).on('click', '.edit-plan-btn', function(e) {
            e.preventDefault();
            console.log('Edit plan button clicked');
            var planId = $(this).data('plan-id');
            var planName = $(this).data('plan-name');
            var categories = $(this).data('categories');
            showPlanModal(planId, planName, categories);
        });
        
        // Delete plan button click handler - use event delegation
        $(document).on('click', '.delete-plan-btn', function(e) {
            e.preventDefault();
            console.log('Delete plan button clicked');
            var planId = $(this).data('plan-id');
            if (confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
                deletePlan(planId);
            }
        });
        
        var categoryIndex = 0;
        
        window.showPlanModal = function(planId, planName, categories) {
            console.log('showPlanModal called', planId, planName, categories);
            
            var title = planId ? 'Edit Plan' : 'Add New Funeral Cover Plan';
            categoryIndex = 0;
            
            var content = '<form id="plan-form" class="space-y-6">' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Plan Name *</label>' +
                    '<input type="text" name="plan_name" id="plan_name_input" required placeholder="e.g. Family Funeral Cover Plan" ' +
                           'value="' + (planName || '') + '" ' +
                           'class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '</div>' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Coverage Categories</label>' +
                    '<div id="categories-container" class="space-y-4"></div>' +
                    '<button type="button" id="add-category-btn" class="mt-4 text-yellow-400 hover:text-yellow-300 text-sm font-medium inline-flex items-center">' +
                        '<span class="mr-1">➕</span> Add Another Category' +
                    '</button>' +
                '</div>' +
            '</form>';
            
            var actions = '<button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg mr-3 transition-colors duration-200" onclick="closeModal()">Cancel</button>' +
                         '<button type="button" id="save-plan-btn" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black px-6 py-2 rounded-lg font-semibold transition-all duration-200">Save Plan</button>';
            
            showModal(title, content, actions);
            
            // Add existing categories or default category
            if (categories && categories.length > 0) {
                $.each(categories, function(index, category) {
                    addCategoryField(category);
                });
            } else {
                addCategoryField();
            }
            
            // Add category button handler
            $('#add-category-btn').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Add category clicked');
                addCategoryField();
            });
            
            // Save plan button handler
            $('#save-plan-btn').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Save plan clicked');
                
                // Validate form
                var planNameInput = $('#plan_name_input');
                if (!planNameInput.val().trim()) {
                    showNotification('Please enter a plan name', 'error');
                    planNameInput.focus();
                    return;
                }
                
                savePlan(planId);
            });
        };
        
        window.addCategoryField = function(category) {
            console.log('Adding category field', categoryIndex, category);
            
            var html = '<div class="category-item bg-gray-800 p-4 rounded-lg border border-gray-600" data-index="' + categoryIndex + '">' +
                '<div class="flex justify-between items-center mb-3">' +
                    '<span class="text-sm text-yellow-400 font-medium">Category ' + (categoryIndex + 1) + '</span>' +
                    (categoryIndex > 0 ? '<button type="button" class="remove-category text-red-400 hover:text-red-300 text-sm font-medium">Remove</button>' : '') +
                '</div>' +
                '<div class="grid grid-cols-2 gap-3">' +
                    '<div>' +
                        '<label class="block text-xs font-medium text-gray-300 mb-1">Category Name *</label>' +
                        '<input type="text" name="cat_name_' + categoryIndex + '" required ' +
                               'value="' + (category ? category.name : '') + '" ' +
                               'placeholder="e.g. Principal Member" ' +
                               'class="cat-name w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-xs font-medium text-gray-300 mb-1">Age Range *</label>' +
                        '<input type="text" name="cat_age_' + categoryIndex + '" required ' +
                               'value="' + (category ? category.age_range : '') + '" ' +
                               'placeholder="e.g. 18-64 years" ' +
                               'class="cat-age w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-xs font-medium text-gray-300 mb-1">Monthly Premium (R) *</label>' +
                        '<input type="number" step="0.01" min="0" name="cat_rate_' + categoryIndex + '" required ' +
                               'value="' + (category ? category.rate : '') + '" ' +
                               'placeholder="150.00" ' +
                               'class="cat-rate w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-xs font-medium text-gray-300 mb-1">Cover Amount (R) *</label>' +
                        '<input type="number" step="0.01" min="0" name="cat_cover_' + categoryIndex + '" required ' +
                               'value="' + (category ? category.cover_amount : '') + '" ' +
                               'placeholder="25000.00" ' +
                               'class="cat-cover w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                    '</div>' +
                '</div>' +
                '<div class="mt-3">' +
                    '<label class="block text-xs font-medium text-gray-300 mb-1">Terms</label>' +
                    '<input type="text" name="cat_terms_' + categoryIndex + '" ' +
                           'value="' + (category ? (category.terms || '') : '') + '" ' +
                           'placeholder="e.g. 6 months waiting period" ' +
                           'class="cat-terms w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                '</div>' +
            '</div>';
            
            $('#categories-container').append(html);
            categoryIndex++;
            
            // Bind remove handler to newly added remove buttons
            $('.remove-category').off('click').on('click', function(e) {
                e.preventDefault();
                $(this).closest('.category-item').remove();
            });
        };
        
        window.savePlan = function(planId) {
            console.log('savePlan called', planId);
            
            var categories = [];
            
            // Collect all categories
            $('#categories-container .category-item').each(function() {
                var item = $(this);
                var category = {
                    name: item.find('.cat-name').val(),
                    age_range: item.find('.cat-age').val(),
                    rate: item.find('.cat-rate').val(),
                    cover_amount: item.find('.cat-cover').val(),
                    terms: item.find('.cat-terms').val() || ''
                };
                
                console.log('Category collected:', category);
                
                if (category.name && category.rate && category.cover_amount) {
                    categories.push(category);
                }
            });
            
            if (categories.length === 0) {
                showNotification('Please add at least one category', 'error');
                return;
            }
            
            var data = {
                action: 'africa_life_save_plan',
                nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>',
                plan_name: $('#plan_name_input').val(),
                categories: JSON.stringify(categories)
            };
            
            if (planId) {
                data.plan_id = planId;
            }
            
            console.log('Sending plan data:', data);
            
            // Disable button to prevent double submission
            $('#save-plan-btn').prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('Plan save response:', response);
                    if (response.success) {
                        showNotification('Plan saved successfully', 'success');
                        closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        $('#save-plan-btn').prop('disabled', false).text('Save Plan');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Plan save error:', error);
                    console.error('Response:', xhr.responseText);
                    showNotification('An error occurred while saving the plan.', 'error');
                    $('#save-plan-btn').prop('disabled', false).text('Save Plan');
                }
            });
        };
        
        window.deletePlan = function(planId) {
            console.log('deletePlan called', planId);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'africa_life_delete_plan',
                    plan_id: planId,
                    nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>'
                },
                success: function(response) {
                    console.log('Plan delete response:', response);
                    if (response.success) {
                        showNotification('Plan deleted successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Plan delete error:', error);
                    showNotification('An error occurred while deleting the plan.', 'error');
                }
            });
        };
    });
    </script>
    <?php
    return ob_get_clean();
}
    private function render_agents_tab() {
    $agents = AfricaLife_Roles::get_agents();
    
    ob_start();
    ?>
    <div class="p-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-semibold text-yellow-400">Agents Management</h2>
            <button id="add-agent-btn" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 inline-flex items-center">
                <span class="mr-2">👤</span> Add New Agent
            </button>
        </div>
        
        <?php if (empty($agents)): ?>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">👥</div>
                <p class="text-gray-400 text-lg mb-4">No agents found.</p>
                <button onclick="jQuery('#add-agent-btn').click()" class="bg-yellow-600 hover:bg-yellow-700 text-black px-6 py-3 rounded-lg font-semibold">
                    Create Your First Agent
                </button>
            </div>
        <?php else: ?>
            <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-white">
                        <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                            <tr>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Email</th>
                                <th class="px-6 py-4">Registered</th>
                                <th class="px-6 py-4">Submissions</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agents as $agent): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-750 transition-colors duration-200">
                                    <td class="px-6 py-4 font-medium text-yellow-400">
                                        <?php echo esc_html(trim($agent->first_name . ' ' . $agent->last_name) ?: $agent->display_name); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($agent->user_login); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html($agent->user_email); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300">
                                        <?php echo esc_html(date('Y-m-d', strtotime($agent->user_registered))); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                            <?php echo $this->get_agent_submission_count($agent->ID); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="delete-agent-btn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors duration-200" 
                                                data-agent-id="<?php echo $agent->ID; ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('Agents tab JavaScript loaded');
        
        // Add agent button click handler
        $('#add-agent-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('Add agent button clicked');
            showAgentModal();
        });
        
        // Delete agent button click handler - use event delegation
        $(document).on('click', '.delete-agent-btn', function(e) {
            e.preventDefault();
            console.log('Delete agent button clicked');
            var agentId = $(this).data('agent-id');
            if (confirm('Are you sure you want to delete this agent? This action cannot be undone.')) {
                deleteAgent(agentId);
            }
        });
        
        window.showAgentModal = function() {
            console.log('showAgentModal called');
            
            var content = '<form id="agent-form" class="space-y-6">' +
                '<div class="grid grid-cols-2 gap-4">' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">First Name *</label>' +
                        '<input type="text" name="first_name" id="agent_first_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">Last Name *</label>' +
                        '<input type="text" name="last_name" id="agent_last_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                    '</div>' +
                '</div>' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Username *</label>' +
                    '<input type="text" name="username" id="agent_username" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                    '<p class="text-xs text-gray-400 mt-1">This will be used for login</p>' +
                '</div>' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Email *</label>' +
                    '<input type="email" name="email" id="agent_email" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                '</div>' +
                '<div>' +
                    '<label class="block text-sm font-medium text-yellow-400 mb-2">Password *</label>' +
                    '<input type="password" name="password" id="agent_password" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                    '<p class="text-xs text-gray-400 mt-1">Minimum 6 characters</p>' +
                '</div>' +
            '</form>';
            
            var actions = '<button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg mr-3 transition-colors duration-200" onclick="closeModal()">Cancel</button>' +
                         '<button type="button" id="create-agent-btn" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black px-6 py-2 rounded-lg font-semibold transition-all duration-200">Create Agent</button>';
            
            showModal('Add New Agent', content, actions);
            
            // Create agent button handler
            $('#create-agent-btn').off('click').on('click', function(e) {
                e.preventDefault();
                console.log('Create agent clicked');
                
                // Validate form
                var isValid = true;
                $('#agent-form input[required]').each(function() {
                    if (!$(this).val().trim()) {
                        $(this).addClass('border-red-500');
                        isValid = false;
                    } else {
                        $(this).removeClass('border-red-500');
                    }
                });
                
                if (!isValid) {
                    showNotification('Please fill in all required fields', 'error');
                    return;
                }
                
                // Validate email
                var email = $('#agent_email').val();
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    showNotification('Please enter a valid email address', 'error');
                    $('#agent_email').addClass('border-red-500');
                    return;
                }
                
                // Validate password length
                var password = $('#agent_password').val();
                if (password.length < 6) {
                    showNotification('Password must be at least 6 characters long', 'error');
                    $('#agent_password').addClass('border-red-500');
                    return;
                }
                
                createAgent();
            });
        };
        
        window.createAgent = function() {
            console.log('createAgent called');
            
            var data = {
                action: 'africa_life_create_agent',
                nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>',
                first_name: $('#agent_first_name').val(),
                last_name: $('#agent_last_name').val(),
                username: $('#agent_username').val(),
                email: $('#agent_email').val(),
                password: $('#agent_password').val()
            };
            
            console.log('Sending agent data:', data);
            
            // Disable button to prevent double submission
            $('#create-agent-btn').prop('disabled', true).text('Creating...');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('Agent create response:', response);
                    if (response.success) {
                        showNotification('Agent created successfully', 'success');
                        closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        $('#create-agent-btn').prop('disabled', false).text('Create Agent');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Agent create error:', error);
                    console.error('Response:', xhr.responseText);
                    showNotification('An error occurred while creating the agent.', 'error');
                    $('#create-agent-btn').prop('disabled', false).text('Create Agent');
                }
            });
        };
        
        window.deleteAgent = function(agentId) {
            console.log('deleteAgent called', agentId);
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'africa_life_delete_agent',
                    user_id: agentId,
                    nonce: '<?php echo wp_create_nonce('africa_life_admin'); ?>'
                },
                success: function(response) {
                    console.log('Agent delete response:', response);
                    if (response.success) {
                        showNotification('Agent deleted successfully', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Agent delete error:', error);
                    showNotification('An error occurred while deleting the agent.', 'error');
                }
            });
        };
    });
    </script>
    <?php
    return ob_get_clean();
}
    
    private function get_agent_submission_count($agent_id) {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'africa_life_submissions';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $submissions_table WHERE agent_id = %d",
            $agent_id
        ));
        
        return intval($count);
    }
    
    private function render_templates_tab() {
        ob_start();
        ?>
        <div class="p-8">
            <h2 class="text-2xl font-semibold mb-8 text-yellow-400">Templates Management</h2>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📝</div>
                <p class="text-gray-400 text-lg">Templates management coming soon.</p>
                <p class="text-gray-500 text-sm mt-2">Email and PDF templates will be customizable here.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_settings_tab() {
        ob_start();
        ?>
        <div class="p-8">
            <h2 class="text-2xl font-semibold mb-8 text-yellow-400">Settings</h2>
            <div class="text-center py-12">
                <div class="text-6xl mb-4">⚙️</div>
                <p class="text-gray-400 text-lg">Settings management coming soon.</p>
                <p class="text-gray-500 text-sm mt-2">System settings and configurations will be available here.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

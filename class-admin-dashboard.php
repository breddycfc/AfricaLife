<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Admin_Dashboard {
    
    public function render() {
        if (!is_user_logged_in() || !AfricaLife_Roles::user_has_admin_access()) {
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
        
        ob_start();
        ?>
        <div class="africa-life-admin-dashboard min-h-screen text-white" style="background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 bg-gray-900 p-6 rounded-xl border border-gray-700">
                    <div>
                        <h1 class="text-4xl font-bold text-yellow-400 mb-2">Africa Life</h1>
                        <p class="text-gray-300">Admin Dashboard</p>
                    </div>
                    <div class="flex flex-col md:flex-row items-start md:items-center space-y-2 md:space-y-0 md:space-x-4 mt-4 md:mt-0">
                        <span class="text-gray-300">Welcome, <span class="text-yellow-400 font-semibold"><?php echo esc_html(wp_get_current_user()->display_name); ?></span></span>
                        <a href="<?php echo wp_logout_url(get_permalink()); ?>" 
                           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-300 inline-flex items-center">
                            <span class="mr-2">🚪</span> Logout
                        </a>
                    </div>
                </div>
                
                <!-- Navigation Tabs -->
                <div class="mb-8">
                    <div class="bg-gray-900 rounded-xl p-2 border border-gray-700">
                        <nav class="flex flex-wrap gap-2">
                            <?php
                            $tabs = array(
                                'stats' => array('label' => 'Statistics', 'icon' => '📊'),
                                'submissions' => array('label' => 'Submissions', 'icon' => '📄'),
                                'plans' => array('label' => 'Plans', 'icon' => '📋'),
                                'agents' => array('label' => 'Agents', 'icon' => '👥'),
                                'templates' => array('label' => 'Templates', 'icon' => '📝'),
                                'settings' => array('label' => 'Settings', 'icon' => '⚙️')
                            );
                            
                            foreach ($tabs as $tab_key => $tab_data) {
                                $active = $current_tab === $tab_key;
                                $active_class = $active ? 
                                    'bg-gradient-to-r from-yellow-500 to-yellow-600 text-black font-semibold' : 
                                    'text-gray-300 hover:text-white hover:bg-gray-700';
                                
                                echo '<a href="?tab=' . $tab_key . '" class="px-6 py-3 rounded-lg transition-all duration-300 ' . $active_class . ' inline-flex items-center space-x-2">';
                                echo '<span>' . $tab_data['icon'] . '</span>';
                                echo '<span>' . $tab_data['label'] . '</span>';
                                echo '</a>';
                            }
                            ?>
                        </nav>
                    </div>
                </div>
                
                <!-- Tab Content -->
                <div class="bg-gray-900 rounded-xl border border-gray-700 overflow-hidden">
                    <?php
                    switch ($current_tab) {
                        case 'stats':
                            echo $this->render_stats_tab();
                            break;
                        case 'submissions':
                            echo $this->render_submissions_tab();
                            break;
                        case 'plans':
                            echo $this->render_plans_tab();
                            break;
                        case 'agents':
                            echo $this->render_agents_tab();
                            break;
                        case 'templates':
                            echo $this->render_templates_tab();
                            break;
                        case 'settings':
                            echo $this->render_settings_tab();
                            break;
                        default:
                            echo $this->render_stats_tab();
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Global Modal Container -->
        <div id="modal-container"></div>
        
        <script>
        // Define global ajaxUrl variable
        var ajaxUrl = africa_life_ajax.ajax_url;
        
        // Global modal functions
        function showModal(title, content, actions) {
            var modal = jQuery('<div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">' +
                '<div class="bg-gray-900 max-w-4xl w-full max-h-screen overflow-y-auto rounded-xl border border-gray-700">' +
                    '<div class="p-6">' +
                        '<div class="flex justify-between items-center mb-6">' +
                            '<h3 class="text-xl font-semibold text-yellow-400">' + title + '</h3>' +
                            '<button class="modal-close text-gray-400 hover:text-white text-2xl">&times;</button>' +
                        '</div>' +
                        '<div class="modal-content mb-6 text-white">' + content + '</div>' +
                        '<div class="modal-actions flex justify-end space-x-3">' + (actions || '') + '</div>' +
                    '</div>' +
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
            
            modal.hide().fadeIn(300);
            return modal;
        }
        
        function closeModal() {
            jQuery('#modal-container').fadeOut(300, function() {
                jQuery(this).empty();
            });
        }
        
        function showNotification(message, type) {
            var bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            var icon = type === 'success' ? '✓' : '✗';
            
            var notification = jQuery('<div class="fixed top-4 right-4 z-50 p-4 rounded-lg text-white ' + bgColor + ' shadow-lg transform translate-x-full transition-transform duration-300 max-w-md">' +
                '<div class="flex items-center">' +
                    '<span class="text-lg mr-3">' + icon + '</span>' +
                    '<span class="flex-1">' + message + '</span>' +
                    '<button class="ml-4 text-xl leading-none opacity-70 hover:opacity-100" onclick="jQuery(this).parent().parent().fadeOut(300, function(){ jQuery(this).remove(); })">&times;</button>' +
                '</div>' +
            '</div>');
            
            jQuery('body').append(notification);
            
            setTimeout(function() {
                notification.removeClass('translate-x-full');
            }, 100);
            
            setTimeout(function() {
                notification.addClass('translate-x-full');
                setTimeout(function() {
                    notification.remove();
                }, 300);
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
        <div class="p-8">
            <h2 class="text-2xl font-semibold mb-8 text-yellow-400">Dashboard Statistics</h2>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-yellow-400 transition-colors duration-300">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-300 mb-2">Total Applications</h3>
                            <p class="text-3xl font-bold text-yellow-400"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="ml-4">
                            <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-2xl">📋</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-yellow-400 transition-colors duration-300">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-300 mb-2">Pending</h3>
                            <p class="text-3xl font-bold text-orange-400"><?php echo $stats['pending']; ?></p>
                        </div>
                        <div class="ml-4">
                            <div class="w-16 h-16 bg-orange-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-2xl">⏳</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-green-400 transition-colors duration-300">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-300 mb-2">Approved</h3>
                            <p class="text-3xl font-bold text-green-400"><?php echo $stats['approved']; ?></p>
                        </div>
                        <div class="ml-4">
                            <div class="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-2xl">✓</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-red-400 transition-colors duration-300">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="text-lg font-medium text-gray-300 mb-2">Declined</h3>
                            <p class="text-3xl font-bold text-red-400"><?php echo $stats['declined']; ?></p>
                        </div>
                        <div class="ml-4">
                            <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-2xl">✗</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Premium Card -->
            <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 mb-8">
                <h3 class="text-lg font-medium mb-4 text-yellow-400">Monthly Premium Collection</h3>
                <p class="text-4xl font-bold text-green-400">R <?php echo number_format($stats['monthly_premium'], 2); ?></p>
                <p class="text-gray-400 mt-2">Total expected premium for <?php echo date('F Y'); ?></p>
            </div>
            
            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
                    <h3 class="text-lg font-medium mb-4 text-yellow-400">Application Status Distribution</h3>
                    <canvas id="statusChart" width="400" height="300"></canvas>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-xl border border-gray-700">
                    <h3 class="text-lg font-medium mb-4 text-yellow-400">Monthly Applications</h3>
                    <canvas id="monthlyChart" width="400" height="300"></canvas>
                </div>
            </div>
            
            <!-- Recent Applications -->
            <div class="mt-8 bg-gray-800 p-6 rounded-xl border border-gray-700">
                <h3 class="text-lg font-medium mb-4 text-yellow-400">Recent Applications</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-white">
                        <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                            <tr>
                                <th class="px-6 py-3">Application #</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Plan</th>
                                <th class="px-6 py-3">Premium</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_applications'] as $app): ?>
                                <tr class="border-b border-gray-700 hover:bg-gray-750 transition-colors duration-200">
                                    <td class="px-6 py-4"><?php echo esc_html($app->application_number); ?></td>
                                    <td class="px-6 py-4"><?php echo esc_html($app->main_full_names . ' ' . $app->main_surname); ?></td>
                                    <td class="px-6 py-4"><?php echo esc_html($app->plan_name); ?></td>
                                    <td class="px-6 py-4">R <?php echo number_format($app->total_premium, 2); ?></td>
                                    <td class="px-6 py-4"><?php echo date('Y-m-d', strtotime($app->submission_date)); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-xs rounded-full <?php 
                                            echo $app->status === 'Approved' ? 'bg-green-900 text-green-300' : 
                                                ($app->status === 'Declined' ? 'bg-red-900 text-red-300' : 'bg-yellow-900 text-yellow-300'); 
                                        ?>">
                                            <?php echo esc_html($app->status); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Status Distribution Chart
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Declined'],
                    datasets: [{
                        data: [<?php echo $stats['pending']; ?>, <?php echo $stats['approved']; ?>, <?php echo $stats['declined']; ?>],
                        backgroundColor: ['#fbbf24', '#10b981', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: '#d1d5db',
                                font: {
                                    size: 14
                                }
                            }
                        }
                    }
                }
            });
            
            // Monthly Submissions Chart
            var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($stats['monthly_labels']); ?>,
                    datasets: [{
                        label: 'Applications',
                        data: <?php echo json_encode($stats['monthly_data']); ?>,
                        borderColor: '#fbbf24',
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#d1d5db'
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.3)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#d1d5db'
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.3)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#d1d5db'
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function get_dashboard_stats() {
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
        
        // Monthly data for the last 6 months
        $monthly_data = array();
        $monthly_labels = array();
        
        for ($i = 5; $i >= 0; $i--) {
            $month_start = date('Y-m-01', strtotime("-$i months"));
            $month_end = date('Y-m-t', strtotime("-$i months"));
            
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $submissions_table WHERE submission_date BETWEEN %s AND %s",
                $month_start,
                $month_end . ' 23:59:59'
            ));
            
            $monthly_data[] = intval($count);
            $monthly_labels[] = date('M Y', strtotime($month_start));
        }
        
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
            'monthly_data' => $monthly_data,
            'monthly_labels' => $monthly_labels,
            'recent_applications' => $recent_applications
        );
    }
    
    private function render_submissions_tab() {
        $submissions = AfricaLife_Database::get_submissions();
        
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
                        <table class="w-full text-sm text-left text-white">
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
    
    // Continue with other tab methods...
    private function render_plans_tab() {
        $plans = AfricaLife_Database::get_plans();
        
        ob_start();
        ?>
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-semibold text-yellow-400">Plans Management</h2>
                <button id="add-plan-btn" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 inline-flex items-center">
                    <span class="mr-2">➕</span> Add New Plan
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
            // Add plan
            $('#add-plan-btn').click(function() {
                showPlanModal();
            });
            
            // Edit plan
            $('.edit-plan-btn').click(function() {
                var planId = $(this).data('plan-id');
                var planName = $(this).data('plan-name');
                var categories = $(this).data('categories');
                showPlanModal(planId, planName, categories);
            });
            
            // Delete plan
            $('.delete-plan-btn').click(function() {
                var planId = $(this).data('plan-id');
                if (confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
                    deletePlan(planId);
                }
            });
            
            function showPlanModal(planId, planName, categories) {
                var title = planId ? 'Edit Plan' : 'Add New Funeral Cover Plan';
                var categoryIndex = 0;
                
                var content = '<form id="plan-form" class="space-y-6">' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">Plan Name *</label>' +
                        '<input type="text" name="plan_name" required placeholder="e.g. Family Funeral Cover Plan" ' +
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
                             '<button type="submit" form="plan-form" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black px-6 py-2 rounded-lg font-semibold transition-all duration-200">Save Plan</button>';
                
                showModal(title, content, actions);
                
                // Add existing categories or default category
                if (categories && categories.length > 0) {
                    $.each(categories, function(index, category) {
                        addCategoryField(category);
                    });
                } else {
                    addCategoryField();
                }
                
                // Add category handler
                $('#add-category-btn').click(function() {
                    addCategoryField();
                });
                
                // Form submit handler
                $('#plan-form').submit(function(e) {
                    e.preventDefault();
                    savePlan(planId);
                });
                
                function addCategoryField(category) {
                    var html = '<div class="category-item bg-gray-800 p-4 rounded-lg border border-gray-600">' +
                        '<div class="flex justify-between items-center mb-3">' +
                            '<span class="text-sm text-yellow-400 font-medium">Category ' + (categoryIndex + 1) + '</span>' +
                            (categoryIndex > 0 ? '<button type="button" class="remove-category text-red-400 hover:text-red-300 text-sm font-medium">Remove</button>' : '') +
                        '</div>' +
                        '<div class="grid grid-cols-2 gap-3">' +
                            '<div>' +
                                '<label class="block text-xs font-medium text-gray-300 mb-1">Category Name *</label>' +
                                '<input type="text" name="categories[' + categoryIndex + '][name]" required ' +
                                       'value="' + (category ? category.name : '') + '" ' +
                                       'placeholder="e.g. Principal Member" ' +
                                       'class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                            '</div>' +
                            '<div>' +
                                '<label class="block text-xs font-medium text-gray-300 mb-1">Age Range *</label>' +
                                '<input type="text" name="categories[' + categoryIndex + '][age_range]" required ' +
                                       'value="' + (category ? category.age_range : '') + '" ' +
                                       'placeholder="e.g. 18-64 years" ' +
                                       'class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                            '</div>' +
                            '<div>' +
                                '<label class="block text-xs font-medium text-gray-300 mb-1">Monthly Premium (R) *</label>' +
                                '<input type="number" step="0.01" min="0" name="categories[' + categoryIndex + '][rate]" required ' +
                                       'value="' + (category ? category.rate : '') + '" ' +
                                       'placeholder="150.00" ' +
                                       'class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                            '</div>' +
                            '<div>' +
                                '<label class="block text-xs font-medium text-gray-300 mb-1">Cover Amount (R) *</label>' +
                                '<input type="number" step="0.01" min="0" name="categories[' + categoryIndex + '][cover_amount]" required ' +
                                       'value="' + (category ? category.cover_amount : '') + '" ' +
                                       'placeholder="25000.00" ' +
                                       'class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-3">' +
                            '<label class="block text-xs font-medium text-gray-300 mb-1">Terms</label>' +
                            '<input type="text" name="categories[' + categoryIndex + '][terms]" ' +
                                   'value="' + (category ? (category.terms || '') : '') + '" ' +
                                   'placeholder="e.g. 6 months waiting period" ' +
                                   'class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white text-sm focus:outline-none focus:ring-1 focus:ring-yellow-400">' +
                        '</div>' +
                    '</div>';
                    
                    $('#categories-container').append(html);
                    categoryIndex++;
                }
                
                // Remove category handler
                $(document).on('click', '.remove-category', function() {
                    $(this).closest('.category-item').remove();
                });
            }
            
            function savePlan(planId) {
                var formData = $('#plan-form').serializeArray();
                var data = {
                    action: 'africa_life_save_plan',
                    nonce: africa_life_ajax.nonce,
                    plan_name: '',
                    categories: []
                };
                
                if (planId) {
                    data.plan_id = planId;
                }
                
                // Parse form data
                $.each(formData, function(index, field) {
                    if (field.name === 'plan_name') {
                        data.plan_name = field.value;
                    } else if (field.name.includes('categories')) {
                        var matches = field.name.match(/categories\[(\d+)\]\[(\w+)\]/);
                        if (matches) {
                            var catIndex = parseInt(matches[1]);
                            var fieldName = matches[2];
                            
                            if (!data.categories[catIndex]) {
                                data.categories[catIndex] = {};
                            }
                            
                            data.categories[catIndex][fieldName] = field.value;
                        }
                    }
                });
                
                // Clean up categories array
                data.categories = data.categories.filter(function(cat) {
                    return cat && cat.name && cat.rate && cat.cover_amount;
                });
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            showNotification('Plan saved successfully', 'success');
                            closeModal();
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred while saving the plan.', 'error');
                    }
                });
            }
            
            function deletePlan(planId) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'africa_life_delete_plan',
                        plan_id: planId,
                        nonce: africa_life_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Plan deleted successfully', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred while deleting the plan.', 'error');
                    }
                });
            }
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
            $('#add-agent-btn').click(function() {
                showAgentModal();
            });
            
            $('.delete-agent-btn').click(function() {
                var agentId = $(this).data('agent-id');
                if (confirm('Are you sure you want to delete this agent? This action cannot be undone.')) {
                    deleteAgent(agentId);
                }
            });
            
            function showAgentModal() {
                var content = '<form id="agent-form" class="space-y-6">' +
                    '<div class="grid grid-cols-2 gap-4">' +
                        '<div>' +
                            '<label class="block text-sm font-medium text-yellow-400 mb-2">First Name *</label>' +
                            '<input type="text" name="first_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                        '</div>' +
                        '<div>' +
                            '<label class="block text-sm font-medium text-yellow-400 mb-2">Last Name *</label>' +
                            '<input type="text" name="last_name" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                        '</div>' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">Username *</label>' +
                        '<input type="text" name="username" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                        '<p class="text-xs text-gray-400 mt-1">This will be used for login</p>' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">Email *</label>' +
                        '<input type="email" name="email" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                    '</div>' +
                    '<div>' +
                        '<label class="block text-sm font-medium text-yellow-400 mb-2">Password *</label>' +
                        '<input type="password" name="password" required class="w-full px-4 py-3 bg-gray-800 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">' +
                        '<p class="text-xs text-gray-400 mt-1">Minimum 6 characters</p>' +
                    '</div>' +
                '</form>';
                
                var actions = '<button type="button" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg mr-3 transition-colors duration-200" onclick="closeModal()">Cancel</button>' +
                             '<button type="submit" form="agent-form" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-black px-6 py-2 rounded-lg font-semibold transition-all duration-200">Create Agent</button>';
                
                showModal('Add New Agent', content, actions);
                
                $('#agent-form').submit(function(e) {
                    e.preventDefault();
                    createAgent();
                });
            }
            
            function createAgent() {
                var formData = $('#agent-form').serialize();
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: formData + '&action=africa_life_create_agent&nonce=' + africa_life_ajax.nonce,
                    success: function(response) {
                        if (response.success) {
                            showNotification('Agent created successfully', 'success');
                            closeModal();
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred while creating the agent.', 'error');
                    }
                });
            }
            
            function deleteAgent(agentId) {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'africa_life_delete_agent',
                        user_id: agentId,
                        nonce: africa_life_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('Agent deleted successfully', 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                        }
                    },
                    error: function() {
                        showNotification('An error occurred while deleting the agent.', 'error');
                    }
                });
            }
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
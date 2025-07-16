<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Ajax_Handler {
    
    public static function init() {
    // Public AJAX actions
    add_action('wp_ajax_africa_life_submit_form', array(__CLASS__, 'handle_form_submission'));
    add_action('wp_ajax_africa_life_admin_login', array(__CLASS__, 'handle_admin_login'));
    add_action('wp_ajax_nopriv_africa_life_admin_login', array(__CLASS__, 'handle_admin_login'));
    add_action('wp_ajax_africa_life_agent_login', array(__CLASS__, 'handle_agent_login'));
    add_action('wp_ajax_nopriv_africa_life_agent_login', array(__CLASS__, 'handle_agent_login'));
    
    // Admin AJAX actions
    add_action('wp_ajax_africa_life_update_status', array(__CLASS__, 'handle_status_update'));
    add_action('wp_ajax_africa_life_save_template', array(__CLASS__, 'handle_save_template'));
    add_action('wp_ajax_africa_life_save_plan', array(__CLASS__, 'handle_save_plan'));
    add_action('wp_ajax_africa_life_delete_plan', array(__CLASS__, 'handle_delete_plan'));
    add_action('wp_ajax_africa_life_create_agent', array(__CLASS__, 'handle_create_agent'));
    add_action('wp_ajax_africa_life_delete_agent', array(__CLASS__, 'handle_delete_agent'));
    
    // QA AJAX actions - ADD THESE
    add_action('wp_ajax_africa_life_create_qa', array(__CLASS__, 'handle_create_qa'));
    add_action('wp_ajax_africa_life_delete_qa', array(__CLASS__, 'handle_delete_qa'));
    add_action('wp_ajax_africa_life_get_qa_assignments', array(__CLASS__, 'handle_get_qa_assignments'));
    add_action('wp_ajax_africa_life_save_qa_assignments', array(__CLASS__, 'handle_save_qa_assignments'));
}
	
	
	
	
	
	public static function handle_create_qa() {
    error_log('Africa Life: handle_create_qa called');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'africa_life_admin')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
        return;
    }
    
    if (!AfricaLife_Roles::user_has_admin_access()) {
        wp_send_json_error('Access denied - admin access required');
        return;
    }
    
    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    
    if (empty($username) || empty($email) || empty($password)) {
        wp_send_json_error('Username, email, and password are required');
        return;
    }
    
    if (strlen($password) < 6) {
        wp_send_json_error('Password must be at least 6 characters long');
        return;
    }
    
    if (!is_email($email)) {
        wp_send_json_error('Please provide a valid email address');
        return;
    }
    
    if (username_exists($username)) {
        wp_send_json_error('Username already exists');
        return;
    }
    
    if (email_exists($email)) {
        wp_send_json_error('Email already exists');
        return;
    }
    
    $result = AfricaLife_Roles::create_qa($username, $email, $password, $first_name, $last_name);
    
    if (is_wp_error($result)) {
        error_log('QA creation error: ' . $result->get_error_message());
        wp_send_json_error($result->get_error_message());
        return;
    }
    
    wp_send_json_success('QA user created successfully');
}
public static function handle_delete_qa() {
    if (!wp_verify_nonce($_POST['nonce'], 'africa_life_admin')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!AfricaLife_Roles::user_has_admin_access()) {
        wp_send_json_error('Access denied');
    }
    
    $user_id = intval($_POST['user_id']);
    
    $result = AfricaLife_Roles::delete_qa($user_id);
    
    if (!$result) {
        wp_send_json_error('Failed to delete QA user');
    }
    
    wp_send_json_success('QA user deleted successfully');
}

public static function handle_get_qa_assignments() {
    if (!wp_verify_nonce($_POST['nonce'], 'africa_life_admin')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!AfricaLife_Roles::user_has_admin_access()) {
        wp_send_json_error('Access denied');
    }
    
    $qa_id = intval($_POST['qa_id']);
    
    $all_agents = AfricaLife_Roles::get_agents();
    $assigned_agents = AfricaLife_Roles::get_qa_assigned_agents($qa_id);
    
    $assigned_ids = array();
    foreach ($assigned_agents as $agent) {
        $assigned_ids[] = $agent->ID;
    }
    
    $agents_data = array();
    foreach ($all_agents as $agent) {
        $agents_data[] = array(
            'ID' => $agent->ID,
            'display_name' => $agent->display_name
        );
    }
    
    wp_send_json_success(array(
        'agents' => $agents_data,
        'assigned' => $assigned_ids
    ));
}

public static function handle_save_qa_assignments() {
    if (!wp_verify_nonce($_POST['nonce'], 'africa_life_admin')) {
        wp_send_json_error('Security check failed');
    }
    
    if (!AfricaLife_Roles::user_has_admin_access()) {
        wp_send_json_error('Access denied');
    }
    
    $qa_id = intval($_POST['qa_id']);
    $agent_ids = isset($_POST['agent_ids']) ? array_map('intval', $_POST['agent_ids']) : array();
    
    global $wpdb;
    $table = $wpdb->prefix . 'africa_life_qa_assignments';
    
    // Remove all existing assignments for this QA
    $wpdb->delete($table, array('qa_user_id' => $qa_id));
    
    // Add new assignments
    foreach ($agent_ids as $agent_id) {
        AfricaLife_Roles::assign_agent_to_qa($qa_id, $agent_id, get_current_user_id());
    }
    
    wp_send_json_success('Assignments saved successfully');
}
	
    
    public static function handle_form_submission() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['africa_life_nonce'], 'africa_life_submit')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check if user is logged in and has agent access
    if (!is_user_logged_in() || !AfricaLife_Roles::user_has_agent_access()) {
        wp_send_json_error('Access denied. Please log in as an agent.');
    }
    
    // Validate required fields
    $required_fields = array(
        // Contract details
        'plan_id', 'entry_date', 'cover_date',
        // Main member details
        'main_surname', 'main_full_names', 'main_title', 'main_id_number',
        'main_date_of_birth', 'main_address', 'main_postal_code', 'main_email',
        'main_contact_numbers', 'main_preferred_language',
        // Beneficiary details
        'beneficiary_name_surname', 'beneficiary_relationship', 
        'beneficiary_id_number', 'beneficiary_telephone',
        // Bank details
        'bank_account_holder', 'bank_address', 'bank_name', 
        'bank_branch_code', 'bank_account_number', 'bank_account_type',
        'bank_contact_number', 'bank_abbreviated_name',
        // Declaration
        'declaration_accepted', 'verbal_consent'
    );
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error('Required field missing: ' . $field);
        }
    }
    
    // Validate ID numbers
    if (!self::validate_sa_id_number($_POST['main_id_number'])) {
        wp_send_json_error('Invalid main member ID number');
    }
    
    if (!self::validate_sa_id_number($_POST['beneficiary_id_number'])) {
        wp_send_json_error('Invalid beneficiary ID number');
    }
    
    // Validate email
    if (!is_email($_POST['main_email'])) {
        wp_send_json_error('Invalid email address');
    }
    
    // Get plan details
    $plan = AfricaLife_Database::get_plan(intval($_POST['plan_id']));
    if (!$plan) {
        wp_send_json_error('Invalid plan selected');
    }
    
    // Calculate premiums
    $total_premium = floatval($_POST['total_premium']);
    $risk_premium = $total_premium * 0.85;
    $marketing_admin_fee = $total_premium * 0.15;
    
    // Prepare submission data
    $submission_data = array(
        'agent_id' => get_current_user_id(),
        'plan_id' => intval($_POST['plan_id']),
        'category' => sanitize_text_field($_POST['category']),
        'cover_amount' => floatval($_POST['cover_amount']),
        'entry_date' => sanitize_text_field($_POST['entry_date']),
        'cover_date' => sanitize_text_field($_POST['cover_date']),
        'total_premium' => $total_premium,
        'risk_premium' => $risk_premium,
        'marketing_admin_fee' => $marketing_admin_fee,
        // Main member details
        'main_surname' => sanitize_text_field($_POST['main_surname']),
        'main_full_names' => sanitize_text_field($_POST['main_full_names']),
        'main_title' => sanitize_text_field($_POST['main_title']),
        'main_id_number' => sanitize_text_field($_POST['main_id_number']),
        'main_date_of_birth' => sanitize_text_field($_POST['main_date_of_birth']),
        'main_address' => sanitize_textarea_field($_POST['main_address']),
        'main_postal_code' => sanitize_text_field($_POST['main_postal_code']),
        'main_email' => sanitize_email($_POST['main_email']),
        'main_contact_numbers' => sanitize_text_field($_POST['main_contact_numbers']),
        'main_preferred_language' => sanitize_text_field($_POST['main_preferred_language']),
        // Beneficiary details
        'beneficiary_name_surname' => sanitize_text_field($_POST['beneficiary_name_surname']),
        'beneficiary_relationship' => sanitize_text_field($_POST['beneficiary_relationship']),
        'beneficiary_id_number' => sanitize_text_field($_POST['beneficiary_id_number']),
        'beneficiary_telephone' => sanitize_text_field($_POST['beneficiary_telephone']),
        // Bank details
        'bank_account_holder' => sanitize_text_field($_POST['bank_account_holder']),
        'bank_address' => sanitize_textarea_field($_POST['bank_address']),
        'bank_name' => sanitize_text_field($_POST['bank_name']),
        'bank_branch_code' => sanitize_text_field($_POST['bank_branch_code']),
        'bank_account_number' => sanitize_text_field($_POST['bank_account_number']),
        'bank_account_type' => sanitize_text_field($_POST['bank_account_type']),
        'bank_contact_number' => sanitize_text_field($_POST['bank_contact_number']),
        'bank_abbreviated_name' => sanitize_text_field($_POST['bank_abbreviated_name']),
        // Declaration
        'declaration_accepted' => 1,
        'declaration_date' => current_time('mysql'),
        'verbal_consent' => 1
    );
    
    // Add spouse details if provided
    if (!empty($_POST['spouse_surname'])) {
        $submission_data['spouse_surname'] = sanitize_text_field($_POST['spouse_surname']);
        $submission_data['spouse_full_names'] = sanitize_text_field($_POST['spouse_full_names']);
        $submission_data['spouse_id_number'] = sanitize_text_field($_POST['spouse_id_number']);
        $submission_data['spouse_date_of_birth'] = sanitize_text_field($_POST['spouse_date_of_birth']);
        $submission_data['spouse_relationship'] = sanitize_text_field($_POST['spouse_relationship']);
        
        // Validate spouse ID if provided
        if (!empty($_POST['spouse_id_number']) && !self::validate_sa_id_number($_POST['spouse_id_number'])) {
            wp_send_json_error('Invalid spouse ID number');
        }
    }
    
    // Create submission
    $submission_id = AfricaLife_Database::create_submission($submission_data);
    
    if (!$submission_id) {
        wp_send_json_error('Failed to save submission');
    }
    
    // Add children/dependents
    if (!empty($_POST['child_surname']) && is_array($_POST['child_surname'])) {
        for ($i = 0; $i < count($_POST['child_surname']); $i++) {
            if (!empty($_POST['child_surname'][$i])) {
                $child_data = array(
                    'dependent_type' => 'child',
                    'surname' => sanitize_text_field($_POST['child_surname'][$i]),
                    'full_names' => sanitize_text_field($_POST['child_full_names'][$i] ?? ''),
                    'relationship' => sanitize_text_field($_POST['child_relationship'][$i] ?? ''),
                    'id_number' => sanitize_text_field($_POST['child_id_number'][$i] ?? '')
                );
                
                // Validate child ID if provided
                if (!empty($child_data['id_number']) && !self::validate_sa_id_number($child_data['id_number'])) {
                    // Skip invalid ID numbers for children (they might not have IDs yet)
                    $child_data['id_number'] = '';
                }
                
                AfricaLife_Database::add_dependent($submission_id, $child_data);
            }
        }
    }
    
    // Add extended family
    if (!empty($_POST['extended_surname']) && is_array($_POST['extended_surname'])) {
        for ($i = 0; $i < count($_POST['extended_surname']); $i++) {
            if (!empty($_POST['extended_surname'][$i])) {
                $extended_data = array(
                    'dependent_type' => 'extended_family',
                    'surname' => sanitize_text_field($_POST['extended_surname'][$i]),
                    'full_names' => sanitize_text_field($_POST['extended_full_names'][$i] ?? ''),
                    'relationship' => sanitize_text_field($_POST['extended_relationship'][$i] ?? ''),
                    'id_number' => sanitize_text_field($_POST['extended_id_number'][$i] ?? '')
                );
                
                // Validate extended family ID if provided
                if (!empty($extended_data['id_number']) && !self::validate_sa_id_number($extended_data['id_number'])) {
                    $extended_data['id_number'] = '';
                }
                
                AfricaLife_Database::add_dependent($submission_id, $extended_data);
            }
        }
    }
    
    // Generate PDF
    $pdf_generator = new AfricaLife_PDF_Generator();
    $pdf_file = $pdf_generator->generate_pdf($submission_id);
    
    if ($pdf_file) {
        AfricaLife_Database::update_submission_pdf($submission_id, $pdf_file);
    }
    
    // NOTE: NO EMAILS SENT HERE - Will be sent upon approval only
    
    wp_send_json_success(array(
        'message' => 'Application submitted successfully! It will be reviewed by our team.',
        'submission_id' => $submission_id,
        'application_number' => AfricaLife_Database::get_submission($submission_id)->application_number
    ));

}
    
    /**
     * Validate South African ID number
     */
    private static function validate_sa_id_number($id_number) {
        // Remove spaces
        $id_number = str_replace(' ', '', $id_number);
        
        // Check length
        if (strlen($id_number) != 13) {
            return false;
        }
        
        // Check if all digits
        if (!ctype_digit($id_number)) {
            return false;
        }
        
        // Extract date components
        $year = substr($id_number, 0, 2);
        $month = substr($id_number, 2, 2);
        $day = substr($id_number, 4, 2);
        
        // Validate month
        if ($month < 1 || $month > 12) {
            return false;
        }
        
        // Validate day
        if ($day < 1 || $day > 31) {
            return false;
        }
        
        // Luhn checksum validation
        $checksum = 0;
        $temp_total = 0;
        $multiplier = 1;
        
        for ($i = 0; $i < 13; $i++) {
            $temp_total = $multiplier * intval($id_number[$i]);
            if ($temp_total > 9) {
                $temp_total = intval($temp_total / 10) + ($temp_total % 10);
            }
            $checksum += $temp_total;
            $multiplier = ($multiplier == 1) ? 2 : 1;
        }
        
        return ($checksum % 10) == 0;
    }
    
    public static function handle_admin_login() {
        if (!wp_verify_nonce($_POST['nonce'], 'africa_life_admin_login')) {
            wp_send_json_error('Security check failed');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error('Invalid credentials');
        }
        
        if (!AfricaLife_Roles::user_has_admin_access($user->ID)) {
            wp_send_json_error('Access denied. Administrator role required.');
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success('Login successful');
    }
    
    public static function handle_agent_login() {
        if (!wp_verify_nonce($_POST['nonce'], 'africa_life_agent_login')) {
            wp_send_json_error('Security check failed');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error('Invalid credentials');
        }
        
        if (!AfricaLife_Roles::user_has_agent_access($user->ID)) {
            wp_send_json_error('Access denied. Agent role required.');
        }
        
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success('Login successful');
    }
    
   public static function handle_status_update() {
    error_log('Africa Life: handle_status_update called');
    error_log('POST data: ' . print_r($_POST, true));
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    // Check if user has admin or QA access
    if (!AfricaLife_Roles::user_has_dashboard_access()) {
        wp_send_json_error('Access denied');
    }
    
    $submission_id = intval($_POST['submission_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    if (!in_array($new_status, array('Pending', 'Approved', 'Declined'))) {
        wp_send_json_error('Invalid status');
    }
    
    // Get current submission data
    $submission = AfricaLife_Database::get_submission($submission_id);
    if (!$submission) {
        wp_send_json_error('Submission not found');
    }
    
    $old_status = $submission->status;
    
    // If QA, verify they have access to this submission
    if (AfricaLife_Roles::user_has_qa_access() && !AfricaLife_Roles::user_has_admin_access()) {
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'africa_life_submissions';
        $assignments_table = $wpdb->prefix . 'africa_life_qa_assignments';
        
        $has_access = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $submissions_table s 
            INNER JOIN $assignments_table a ON s.agent_id = a.agent_id 
            WHERE s.id = %d AND a.qa_user_id = %d",
            $submission_id,
            get_current_user_id()
        ));
        
        if (!$has_access) {
            wp_send_json_error('You do not have permission to update this submission');
        }
    }
    
    global $wpdb;
    
    $submissions_table = $wpdb->prefix . 'africa_life_submissions';
    
    // Update status
    $result = $wpdb->update($submissions_table,
        array('status' => $new_status),
        array('id' => $submission_id)
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to update status');
    }
    
    // If status changed to Approved and wasn't Approved before, send emails
    if ($new_status === 'Approved' && $old_status !== 'Approved') {
        error_log('Status changed to Approved - sending emails');
        
        // Send full submission emails with PDF
        $email_handler = new AfricaLife_Email_Handler();
        $email_sent = $email_handler->send_submission_emails($submission_id);
        
        if ($email_sent) {
            error_log('Approval emails sent successfully');
        } else {
            error_log('Failed to send approval emails');
        }
    }
    
    // Always send status change notification to agent
    $email_handler = new AfricaLife_Email_Handler();
    $email_handler->send_status_notification($submission_id, $new_status);
    
    wp_send_json_success('Status updated successfully');
}
    
    public static function handle_save_template() {
        error_log('Africa Life: handle_save_template called');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        if (!AfricaLife_Roles::user_has_admin_access()) {
            wp_send_json_error('Access denied');
        }
        
        $template_type = sanitize_text_field($_POST['template_type']);
        $template_content = $_POST['template_content'];
        
        if (!in_array($template_type, array('email', 'pdf', 'script'))) {
            wp_send_json_error('Invalid template type');
        }
        
        global $wpdb;
        
        $templates_table = $wpdb->prefix . 'africa_life_templates';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $templates_table WHERE template_type = %s",
            $template_type
        ));
        
        $data = array(
            'template_type' => $template_type,
            'template_content' => json_encode($template_content),
            'updated_by' => get_current_user_id()
        );
        
        if ($existing) {
            $result = $wpdb->update($templates_table, $data, array('template_type' => $template_type));
        } else {
            $result = $wpdb->insert($templates_table, $data);
        }
        
        if ($result === false) {
            wp_send_json_error('Failed to save template');
        }
        
        wp_send_json_success('Template saved successfully');
    }
    
    public static function handle_save_plan() {
    error_log('Africa Life: handle_save_plan called');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Add nonce verification
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'africa_life_admin')) {
        wp_send_json_error('Security check failed. Please refresh the page and try again.');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
        return;
    }
    
    if (!AfricaLife_Roles::user_has_admin_access()) {
        wp_send_json_error('Access denied - admin access required');
        return;
    }
    
    $plan_name = sanitize_text_field($_POST['plan_name']);
    
    if (empty($plan_name)) {
        wp_send_json_error('Plan name is required');
        return;
    }
    
    // Handle categories - they might come as JSON string
    $categories = $_POST['categories'];
    if (is_string($categories)) {
        $categories = json_decode(stripslashes($categories), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid categories data format');
            return;
        }
    }
    
    if (empty($categories) || !is_array($categories)) {
        wp_send_json_error('At least one category is required');
        return;
    }
    
    // Validate and sanitize categories
    $sanitized_categories = array();
    foreach ($categories as $category) {
        if (empty($category['name']) || empty($category['rate']) || empty($category['cover_amount'])) {
            continue; // Skip incomplete categories
        }
        
        $rate = floatval($category['rate']);
        $cover_amount = floatval($category['cover_amount']);
        
        if ($rate <= 0 || $cover_amount <= 0) {
            wp_send_json_error('Category rates and cover amounts must be positive numbers');
            return;
        }
        
        $sanitized_categories[] = array(
            'name' => sanitize_text_field($category['name']),
            'age_range' => sanitize_text_field($category['age_range']),
            'rate' => $rate,
            'cover_amount' => $cover_amount,
            'terms' => sanitize_text_field(isset($category['terms']) ? $category['terms'] : '')
        );
    }
    
    if (empty($sanitized_categories)) {
        wp_send_json_error('No valid categories provided');
        return;
    }
    
    global $wpdb;
    
    // Use the full table name
    $table_name = $wpdb->prefix . 'africa_life_plans';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        // Try to create the table
        AfricaLife_Database::create_tables();
        
        // Check again
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist. Please deactivate and reactivate the plugin.');
            return;
        }
    }
    
    // Generate plan code
    $plan_code = 'AFL-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $plan_name), 0, 6)) . '-' . sprintf('%03d', rand(1, 999));
    
    $data = array(
        'plan_name' => $plan_name,
        'plan_code' => $plan_code,
        'categories' => json_encode($sanitized_categories),
        'created_by' => get_current_user_id()
    );
    
    if (!empty($_POST['plan_id'])) {
        $plan_id = intval($_POST['plan_id']);
        // Don't overwrite plan_code on update
        unset($data['plan_code']);
        unset($data['created_by']);
        $result = $wpdb->update($table_name, $data, array('id' => $plan_id));
    } else {
        $result = $wpdb->insert($table_name, $data);
    }
    
    if ($result === false) {
        error_log('Database error: ' . $wpdb->last_error);
        error_log('Table name: ' . $table_name);
        wp_send_json_error('Failed to save plan: ' . $wpdb->last_error);
        return;
    }
    
    wp_send_json_success('Plan saved successfully');
}
    
    public static function handle_delete_plan() {
        error_log('Africa Life: handle_delete_plan called');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        if (!AfricaLife_Roles::user_has_admin_access()) {
            wp_send_json_error('Access denied');
        }
        
        $plan_id = intval($_POST['plan_id']);
        
        global $wpdb;
        
        $plans_table = $wpdb->prefix . 'africa_life_plans';
        
        $result = $wpdb->delete($plans_table, array('id' => $plan_id));
        
        if ($result === false) {
            wp_send_json_error('Failed to delete plan');
        }
        
        wp_send_json_success('Plan deleted successfully');
    }
    
    public static function handle_create_agent() {
        error_log('Africa Life: handle_create_agent called');
        error_log('POST data: ' . print_r($_POST, true));
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        if (!AfricaLife_Roles::user_has_admin_access()) {
            wp_send_json_error('Access denied - admin access required');
        }
        
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        
        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error('Username, email, and password are required');
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error('Password must be at least 6 characters long');
        }
        
        // Check if username or email already exists
        if (username_exists($username)) {
            wp_send_json_error('Username already exists');
        }
        
        if (email_exists($email)) {
            wp_send_json_error('Email already exists');
        }
        
        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error('Please provide a valid email address');
        }
        
        $result = AfricaLife_Roles::create_agent($username, $email, $password, $first_name, $last_name);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Agent created successfully');
    }
    
    public static function handle_delete_agent() {
        error_log('Africa Life: handle_delete_agent called');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        if (!AfricaLife_Roles::user_has_admin_access()) {
            wp_send_json_error('Access denied');
        }
        
        $user_id = intval($_POST['user_id']);
        
        $result = AfricaLife_Roles::delete_agent($user_id);
        
        if (!$result) {
            wp_send_json_error('Failed to delete agent');
        }
        
        wp_send_json_success('Agent deleted successfully');
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Database {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Main submissions table with all PDF form fields
        $submissions_table = $wpdb->prefix . 'africa_life_submissions';
        $sql = "CREATE TABLE IF NOT EXISTS $submissions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            agent_id int(11) NOT NULL,
            application_number varchar(50) NOT NULL,
            
            -- Contract Details
            member_number varchar(50) NOT NULL,
            category varchar(100),
            cover_amount decimal(10,2),
            entry_date date,
            cover_date date,
            total_premium decimal(10,2),
            risk_premium decimal(10,2),
            marketing_admin_fee decimal(10,2),
            plan_id int(11) NOT NULL,
            
            -- Main Member Details
            main_surname varchar(255) NOT NULL,
            main_full_names varchar(255) NOT NULL,
            main_title varchar(20) NOT NULL,
            main_id_number varchar(13) NOT NULL,
            main_date_of_birth date NOT NULL,
            main_address text NOT NULL,
            main_postal_code varchar(10) NOT NULL,
            main_email varchar(255) NOT NULL,
            main_contact_numbers varchar(255) NOT NULL,
            main_preferred_language enum('English','Afrikaans','Xhosa') DEFAULT 'English',
            
            -- Spouse Details
            spouse_surname varchar(255),
            spouse_full_names varchar(255),
            spouse_id_number varchar(13),
            spouse_date_of_birth date,
            spouse_relationship enum('Married','Living Together',''),
            
            -- Beneficiary Details
            beneficiary_name_surname varchar(255) NOT NULL,
            beneficiary_relationship varchar(100) NOT NULL,
            beneficiary_id_number varchar(13) NOT NULL,
            beneficiary_telephone varchar(50) NOT NULL,
            
            -- Bank Details
            bank_account_holder varchar(255) NOT NULL,
            bank_address text NOT NULL,
            bank_name varchar(100) NOT NULL,
            bank_branch_code varchar(10) NOT NULL,
            bank_account_number varchar(50) NOT NULL,
            bank_account_type enum('Current','Savings','Transmission') NOT NULL,
            bank_contact_number varchar(50) NOT NULL,
            bank_abbreviated_name varchar(255) NOT NULL,
            
            -- Declaration and Status
            declaration_accepted tinyint(1) DEFAULT 1,
            declaration_date datetime,
            verbal_consent tinyint(1) DEFAULT 1,
            
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'Pending',
            pdf_file varchar(500),
            
            PRIMARY KEY (id),
            UNIQUE KEY application_number (application_number),
            KEY agent_id (agent_id),
            KEY plan_id (plan_id),
            KEY status (status),
            KEY main_id_number (main_id_number),
            KEY submission_date (submission_date)
        ) $charset_collate;";
        
        // Children/Dependents table
        $dependents_table = $wpdb->prefix . 'africa_life_dependents';
        $sql .= "CREATE TABLE IF NOT EXISTS $dependents_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            submission_id int(11) NOT NULL,
            dependent_type enum('child','extended_family') NOT NULL,
            surname varchar(255) NOT NULL,
            full_names varchar(255) NOT NULL,
            relationship varchar(100) NOT NULL,
            id_number varchar(13) NOT NULL,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY dependent_type (dependent_type)
        ) $charset_collate;";
        
        // Plans table
        $plans_table = $wpdb->prefix . 'africa_life_plans';
        $sql .= "CREATE TABLE IF NOT EXISTS $plans_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            plan_name varchar(255) NOT NULL,
            plan_code varchar(50) NOT NULL,
            categories longtext NOT NULL,
            created_by int(11) NOT NULL,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY plan_code (plan_code)
        ) $charset_collate;";
        
        // Templates table
        $templates_table = $wpdb->prefix . 'africa_life_templates';
        $sql .= "CREATE TABLE IF NOT EXISTS $templates_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            template_content longtext NOT NULL,
            logo_path varchar(500),
            updated_by int(11) NOT NULL,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        self::insert_default_templates();
        self::insert_default_plans();
    }
    
    // New method to update existing tables for PDF form fields
    public static function update_tables_for_pdf() {
        global $wpdb;
        
        $submissions_table = $wpdb->prefix . 'africa_life_submissions';
        
        // Check if we need to add new columns
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $submissions_table");
        
        $new_columns = array(
            'application_number' => "varchar(50) NOT NULL DEFAULT ''",
            'member_number' => "varchar(50) NOT NULL DEFAULT ''",
            'category' => "varchar(100)",
            'cover_amount' => "decimal(10,2)",
            'entry_date' => "date",
            'cover_date' => "date",
            'total_premium' => "decimal(10,2)",
            'risk_premium' => "decimal(10,2)",
            'marketing_admin_fee' => "decimal(10,2)",
            'main_surname' => "varchar(255)",
            'main_full_names' => "varchar(255)",
            'main_title' => "varchar(20)",
            'main_id_number' => "varchar(13)",
            'main_date_of_birth' => "date",
            'main_address' => "text",
            'main_postal_code' => "varchar(10)",
            'main_email' => "varchar(255)",
            'main_contact_numbers' => "varchar(255)",
            'main_preferred_language' => "enum('English','Afrikaans','Xhosa') DEFAULT 'English'",
            'spouse_surname' => "varchar(255)",
            'spouse_full_names' => "varchar(255)",
            'spouse_id_number' => "varchar(13)",
            'spouse_date_of_birth' => "date",
            'spouse_relationship' => "enum('Married','Living Together','')",
            'beneficiary_name_surname' => "varchar(255)",
            'beneficiary_relationship' => "varchar(100)",
            'beneficiary_id_number' => "varchar(13)",
            'beneficiary_telephone' => "varchar(50)",
            'bank_account_holder' => "varchar(255)",
            'bank_address' => "text",
            'bank_name' => "varchar(100)",
            'bank_branch_code' => "varchar(10)",
            'bank_account_number' => "varchar(50)",
            'bank_account_type' => "enum('Current','Savings','Transmission')",
            'bank_contact_number' => "varchar(50)",
            'bank_abbreviated_name' => "varchar(255)",
            'declaration_accepted' => "tinyint(1) DEFAULT 1",
            'declaration_date' => "datetime",
            'verbal_consent' => "tinyint(1) DEFAULT 1"
        );
        
        foreach ($new_columns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $wpdb->query("ALTER TABLE $submissions_table ADD COLUMN $column $definition");
            }
        }
        
        // Create dependents table if it doesn't exist
        self::create_tables();
    }
    
    private static function insert_default_templates() {
        global $wpdb;
        
        $templates_table = $wpdb->prefix . 'africa_life_templates';
        
        $email_template = array(
            'customer_subject' => 'Your Africa Life Funeral Cover Application - {application_number}',
            'customer_body' => 'Dear {customer_name},

Thank you for your Africa Life funeral cover application. Please find attached your application form.

Application Details:
Application Number: {application_number}
Member Number: {member_number}
Plan: {plan_name}
Total Premium: R{total_premium}

Your selected coverage includes:
{plan_details}

Important Information:
- Entry Date: {entry_date}
- Cover Date: {cover_date}
- Debit Order Date: Monthly on the date you selected
- Bank Account: ****{account_last4}

If you have any questions, please contact us at 031 035 0625.

Best regards,
Africa Life Team',
            'admin_subject' => 'New Funeral Cover Application - {customer_name} - {application_number}',
            'admin_body' => 'A new funeral cover application has been submitted.

Application Details:
Application Number: {application_number}
Member Number: {member_number}

Customer Details:
Name: {customer_name}
ID Number: {id_number}
Email: {customer_email}
Phone: {contact_number}

Plan: {plan_name}
Total Premium: R{total_premium}
Agent: {agent_name}
Date: {submission_date}

Please review the attached application form.'
        );
        
        $pdf_template = array(
            'header' => 'AFRICA LIFE APPLICATION FORM',
            'footer' => 'Africa Life - Funeral Cover Application',
            'declaration' => 'I declare that neither I nor my dependents suffer from any pre-existing health conditions that could lead to an early death. I understand and accept waiting periods, premiums and other conditions in the master policy as explained to me by the Intermediary.',
            'debit_order_declaration' => 'I authorize Africa Life to issue and deliver payment instructions to my Banker for collection against my Bank account on condition that the sum of such payment instruction will never exceed my obligations as agreed in your contract/agreement.',
            'font_family' => 'helvetica',
            'font_size' => 10
        );
        
        $script_template = array(
            'content' => '<h3>Africa Life Agent Script</h3>
<p><strong>Opening:</strong></p>
<p>"Good day, my name is [Agent Name] from Africa Life Funeral Cover. How are you today?"</p>

<p><strong>Introduction:</strong></p>
<p>"I\'m calling to tell you about our funeral cover plans that can protect your family from financial burden during difficult times."</p>

<p><strong>Benefits:</strong></p>
<ul>
<li>Cover amounts from R10,000 to R50,000</li>
<li>Affordable monthly premiums</li>
<li>Cover for you, your spouse, children, and extended family</li>
<li>24/7 assistance when you need it most</li>
<li>Quick claim payouts</li>
</ul>

<p><strong>Closing:</strong></p>
<p>"Would you like to secure this protection for your family today?"</p>

<p><strong>Important Notes:</strong></p>
<ul>
<li>Always confirm verbal consent before proceeding</li>
<li>Ensure all information is accurate</li>
<li>Explain waiting periods clearly</li>
<li>Confirm bank details twice</li>
</ul>'
        );
        
        // Insert or update templates
        $existing_email = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $templates_table WHERE template_type = %s",
            'email'
        ));
        
        if (!$existing_email) {
            $wpdb->insert($templates_table, array(
                'template_type' => 'email',
                'template_content' => json_encode($email_template),
                'updated_by' => get_current_user_id() ?: 1
            ));
        }
        
        $existing_pdf = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $templates_table WHERE template_type = %s",
            'pdf'
        ));
        
        if (!$existing_pdf) {
            $wpdb->insert($templates_table, array(
                'template_type' => 'pdf',
                'template_content' => json_encode($pdf_template),
                'updated_by' => get_current_user_id() ?: 1
            ));
        }
        
        $existing_script = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $templates_table WHERE template_type = %s",
            'script'
        ));
        
        if (!$existing_script) {
            $wpdb->insert($templates_table, array(
                'template_type' => 'script',
                'template_content' => json_encode($script_template),
                'updated_by' => get_current_user_id() ?: 1
            ));
        }
    }
    
    public static function insert_default_plans() {
        global $wpdb;
        
        $plans_table = $wpdb->prefix . 'africa_life_plans';
        
        // Check if plans already exist
        $existing_plans = $wpdb->get_var("SELECT COUNT(*) FROM $plans_table");
        
        if ($existing_plans == 0) {
            $default_plans = array(
                array(
                    'plan_name' => 'Basic Individual Cover',
                    'plan_code' => 'AFL-BASIC-001',
                    'categories' => json_encode(array(
                        array(
                            'name' => 'Principal Member',
                            'age_range' => '18-64 years',
                            'rate' => 89.00,
                            'cover_amount' => 10000.00,
                            'terms' => '6 months waiting period for natural causes'
                        )
                    ))
                ),
                array(
                    'plan_name' => 'Family Cover Plan',
                    'plan_code' => 'AFL-FAMILY-001',
                    'categories' => json_encode(array(
                        array(
                            'name' => 'Principal Member',
                            'age_range' => '18-64 years',
                            'rate' => 150.00,
                            'cover_amount' => 20000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Spouse',
                            'age_range' => '18-64 years',
                            'rate' => 120.00,
                            'cover_amount' => 20000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Children (14-21 years)',
                            'age_range' => '14-21 years',
                            'rate' => 35.00,
                            'cover_amount' => 10000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Children (6-13 years)',
                            'age_range' => '6-13 years',
                            'rate' => 25.00,
                            'cover_amount' => 5000.00,
                            'terms' => '6 months waiting period'
                        )
                    ))
                ),
                array(
                    'plan_name' => 'Extended Family Cover',
                    'plan_code' => 'AFL-EXTENDED-001',
                    'categories' => json_encode(array(
                        array(
                            'name' => 'Principal Member',
                            'age_range' => '18-64 years',
                            'rate' => 200.00,
                            'cover_amount' => 30000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Spouse',
                            'age_range' => '18-64 years',
                            'rate' => 150.00,
                            'cover_amount' => 25000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Children (0-21 years)',
                            'age_range' => '0-21 years',
                            'rate' => 50.00,
                            'cover_amount' => 15000.00,
                            'terms' => '6 months waiting period'
                        ),
                        array(
                            'name' => 'Extended Family Member',
                            'age_range' => '0-64 years',
                            'rate' => 75.00,
                            'cover_amount' => 15000.00,
                            'terms' => '12 months waiting period'
                        )
                    ))
                )
            );
            
            foreach ($default_plans as $plan) {
                $wpdb->insert($plans_table, array(
                    'plan_name' => $plan['plan_name'],
                    'plan_code' => $plan['plan_code'],
                    'categories' => $plan['categories'],
                    'created_by' => get_current_user_id() ?: 1
                ));
            }
        }
    }
    
    public static function get_submissions($agent_id = null, $status = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_submissions';
        $query = "SELECT * FROM $table WHERE 1=1";
        $params = array();
        
        if ($agent_id) {
            $query .= " AND agent_id = %d";
            $params[] = $agent_id;
        }
        
        if ($status) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY submission_date DESC";
        
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        
        return $wpdb->get_results($query);
    }
    
    public static function get_submission($submission_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_submissions';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $submission_id
        ));
    }
    
    public static function get_submission_dependents($submission_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_dependents';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE submission_id = %d ORDER BY dependent_type, id",
            $submission_id
        ));
    }
    
    public static function get_plans() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_plans';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY plan_name ASC");
    }
    
    public static function get_plan($plan_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_plans';
        $plan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $plan_id
        ));
        
        if ($plan && !empty($plan->categories)) {
            $plan->categories = json_decode($plan->categories, true);
        }
        
        return $plan;
    }
    
    public static function get_template($type) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_templates';
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_type = %s",
            $type
        ));
        
        if ($result) {
            $result->template_content = json_decode($result->template_content, true);
        }
        
        return $result;
    }
    
    public static function generate_application_number() {
        return 'AFL' . date('Ymd') . sprintf('%04d', rand(1, 9999));
    }
    
    public static function generate_member_number() {
        return 'EMP' . date('y') . sprintf('%06d', rand(1, 999999));
    }
    
    public static function create_submission($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_submissions';
        
        // Generate unique numbers
        $data['application_number'] = self::generate_application_number();
        $data['member_number'] = self::generate_member_number();
        
        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function add_dependent($submission_id, $dependent_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_dependents';
        
        $dependent_data['submission_id'] = $submission_id;
        
        return $wpdb->insert($table, $dependent_data);
    }
    
    public static function update_submission_pdf($submission_id, $pdf_file) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_submissions';
        
        return $wpdb->update(
            $table,
            array('pdf_file' => $pdf_file),
            array('id' => $submission_id)
        );
    }
}
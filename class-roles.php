<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Roles {
    
    public static function create_roles() {
        // Agent role
        add_role('africa_life_agent', 'Africa Life Agent', array(
            'read' => true,
            'africa_life_agent_access' => true,
            'africa_life_submit_form' => true,
            'africa_life_view_own_submissions' => true
        ));
        
        // QA role
        add_role('africa_life_qa', 'Africa Life QA', array(
            'read' => true,
            'africa_life_qa_access' => true,
            'africa_life_view_assigned_submissions' => true,
            'africa_life_update_submission_status' => true
        ));
    }
    
    public static function remove_roles() {
        remove_role('africa_life_agent');
        remove_role('africa_life_qa');
    }
    
    public static function user_has_agent_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('africa_life_agent', $user->roles) || user_can($user_id, 'manage_options');
    }
    
    public static function user_has_admin_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_options');
    }
    
    public static function user_has_qa_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('africa_life_qa', $user->roles);
    }
    
    public static function user_has_dashboard_access($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return self::user_has_admin_access($user_id) || self::user_has_qa_access($user_id);
    }
    
    public static function create_agent($username, $email, $password, $first_name = '', $last_name = '') {
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name) ?: $username,
            'role' => 'africa_life_agent'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        
        return $user_id;
    }
    
    public static function create_qa($username, $email, $password, $first_name = '', $last_name = '') {
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name) ?: $username,
            'role' => 'africa_life_qa'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        
        return $user_id;
    }
    
    public static function get_agents() {
        $users = get_users(array(
            'role' => 'africa_life_agent',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        return $users;
    }
    
    public static function get_qa_users() {
        $users = get_users(array(
            'role' => 'africa_life_qa',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        return $users;
    }
    
    public static function delete_agent($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user || !in_array('africa_life_agent', $user->roles)) {
            return false;
        }
        
        return wp_delete_user($user_id);
    }
    
    public static function delete_qa($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user || !in_array('africa_life_qa', $user->roles)) {
            return false;
        }
        
        return wp_delete_user($user_id);
    }
    
    public static function assign_agent_to_qa($qa_user_id, $agent_id, $assigned_by) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_qa_assignments';
        
        return $wpdb->insert($table, array(
            'qa_user_id' => $qa_user_id,
            'agent_id' => $agent_id,
            'assigned_by' => $assigned_by
        ));
    }
    
    public static function remove_agent_from_qa($qa_user_id, $agent_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_qa_assignments';
        
        return $wpdb->delete($table, array(
            'qa_user_id' => $qa_user_id,
            'agent_id' => $agent_id
        ));
    }
    
    public static function get_qa_assigned_agents($qa_user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_qa_assignments';
        
        $agent_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT agent_id FROM $table WHERE qa_user_id = %d",
            $qa_user_id
        ));
        
        if (empty($agent_ids)) {
            return array();
        }
        
        return get_users(array(
            'include' => $agent_ids,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
    }
    
    public static function get_agent_assignments($agent_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'africa_life_qa_assignments';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT qa.*, u.display_name as qa_name 
            FROM $table qa 
            LEFT JOIN {$wpdb->users} u ON qa.qa_user_id = u.ID 
            WHERE qa.agent_id = %d",
            $agent_id
        ));
    }
}

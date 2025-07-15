<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Roles {
    
    public static function create_roles() {
        add_role('africa_life_agent', 'Africa Life Agent', array(
            'read' => true,
            'africa_life_agent_access' => true,
            'africa_life_submit_form' => true,
            'africa_life_view_own_submissions' => true
        ));
    }
    
    public static function remove_roles() {
        remove_role('africa_life_agent');
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
    
    public static function create_agent($username, $email, $password, $first_name = '', $last_name = '') {
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'africa_life_agent'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
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
    
    public static function delete_agent($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user || !in_array('africa_life_agent', $user->roles)) {
            return false;
        }
        
        return wp_delete_user($user_id);
    }
}
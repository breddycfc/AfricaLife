<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Email_Handler {
    
    public function send_submission_emails($submission_id) {
        // Get submission data
        $submission = AfricaLife_Database::get_submission($submission_id);
        if (!$submission) {
            error_log('Africa Life Email: Submission not found - ID: ' . $submission_id);
            return false;
        }
        
        // Get plan data
        $plan = AfricaLife_Database::get_plan($submission->plan_id);
        
        // Get agent data
        $agent = get_userdata($submission->agent_id);
        
        // Get email template
        $email_template = AfricaLife_Database::get_template('email');
        if (!$email_template) {
            error_log('Africa Life: Email template not found');
            return false;
        }
        
        $template_data = $email_template->template_content;
        
        // Prepare placeholders
        $placeholders = array(
            '{customer_name}' => $submission->main_full_names . ' ' . $submission->main_surname,
            '{customer_email}' => $submission->main_email,
            '{application_number}' => $submission->application_number,
            '{member_number}' => $submission->member_number,
            '{plan_name}' => $plan ? $plan->plan_name : 'Unknown Plan',
            '{plan_details}' => $this->format_plan_details($plan),
            '{total_premium}' => number_format($submission->total_premium, 2),
            '{entry_date}' => date('d/m/Y', strtotime($submission->entry_date)),
            '{cover_date}' => date('d/m/Y', strtotime($submission->cover_date)),
            '{agent_name}' => $agent ? $agent->display_name : 'Unknown Agent',
            '{submission_date}' => date('Y-m-d H:i:s', strtotime($submission->submission_date)),
            '{id_number}' => $submission->main_id_number,
            '{contact_number}' => $submission->main_contact_numbers,
            '{account_last4}' => substr($submission->bank_account_number, -4)
        );
        
        // Get PDF path
        $pdf_path = '';
        if ($submission->pdf_file) {
            $upload_dir = wp_upload_dir();
            $pdf_path = $upload_dir['basedir'] . '/africa-life/' . $submission->pdf_file;
        }
        
        // Send emails
        $customer_sent = $this->send_customer_email($submission->main_email, $template_data, $placeholders, $pdf_path);
        $admin_sent = $this->send_admin_email($template_data, $placeholders, $pdf_path);
        
        return $customer_sent && $admin_sent;
    }
    
    private function send_customer_email($customer_email, $template_data, $placeholders, $pdf_path) {
        $subject = $this->replace_placeholders($template_data['customer_subject'], $placeholders);
        $message = $this->replace_placeholders($template_data['customer_body'], $placeholders);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Africa Life <noreply@africalife.co.za>'
        );
        
        $attachments = array();
        if (file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }
        
        $sent = wp_mail($customer_email, $subject, $this->format_email_content($message), $headers, $attachments);
        
        if (!$sent) {
            error_log('Africa Life: Failed to send customer email to ' . $customer_email);
        }
        
        return $sent;
    }
    
    private function send_admin_email($template_data, $placeholders, $pdf_path) {
        $admin_email = get_option('admin_email');
        $africa_life_admin_email = get_option('africa_life_admin_email', $admin_email);
        
        $subject = $this->replace_placeholders($template_data['admin_subject'], $placeholders);
        $message = $this->replace_placeholders($template_data['admin_body'], $placeholders);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Africa Life <noreply@africalife.co.za>'
        );
        
        $attachments = array();
        if (file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }
        
        $sent = wp_mail($africa_life_admin_email, $subject, $this->format_email_content($message), $headers, $attachments);
        
        if (!$sent) {
            error_log('Africa Life: Failed to send admin email to ' . $africa_life_admin_email);
        }
        
        return $sent;
    }
    
    public function send_status_notification($submission_id, $new_status) {
        $submission = AfricaLife_Database::get_submission($submission_id);
        if (!$submission) {
            return false;
        }
        
        $agent = get_userdata($submission->agent_id);
        if (!$agent) {
            return false;
        }
        
        $subject = 'Application Status Update - ' . $submission->application_number;
        
        $status_color = '';
        switch($new_status) {
            case 'Approved':
                $status_color = '#4caf50';
                break;
            case 'Declined':
                $status_color = '#f44336';
                break;
            default:
                $status_color = '#ff9800';
        }
        
        $message = '
        <p>Dear ' . esc_html($agent->display_name) . ',</p>
        
        <p>The status of the following application has been updated:</p>
        
        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="width: 30%; font-weight: bold;">Application Number:</td>
                <td>' . esc_html($submission->application_number) . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Customer Name:</td>
                <td>' . esc_html($submission->main_full_names . ' ' . $submission->main_surname) . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Submission Date:</td>
                <td>' . date('Y-m-d H:i:s', strtotime($submission->submission_date)) . '</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">New Status:</td>
                <td><span style="background: ' . $status_color . '; color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold;">' . esc_html($new_status) . '</span></td>
            </tr>
        </table>
        
        <p>Best regards,<br>Africa Life Team</p>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Africa Life <noreply@africalife.co.za>'
        );
        
        return wp_mail($agent->user_email, $subject, $this->format_email_content($message), $headers);
    }
    
    private function replace_placeholders($content, $placeholders) {
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
    
    private function format_email_content($message) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Africa Life</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: #2e7d32;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 36px;
        }
        .header p {
            margin: 0;
            font-size: 18px;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 8px 0;
        }
        a {
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AFRICA LIFE</h1>
            <p>Funeral Cover</p>
        </div>
        <div class="content">
            ' . nl2br($message) . '
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Africa Life. All rights reserved.</p>
            <p>Contact us: 031 035 0625 | <a href="mailto:info@africalife.co.za">info@africalife.co.za</a></p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private function format_plan_details($plan) {
        if (!$plan || empty($plan->categories)) {
            return 'Plan details not available.';
        }
        
        $details = '';
        foreach ($plan->categories as $category) {
            $details .= sprintf(
                "• %s (%s): R%s premium, R%s cover\n",
                $category['name'],
                $category['age_range'],
                number_format($category['rate'], 2),
                number_format($category['cover_amount'], 2)
            );
        }
        
        return $details;
    }
}
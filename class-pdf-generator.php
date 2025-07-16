<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_PDF_Generator {
    
    public function generate_pdf($submission_id) {
        try {
            // Get submission data
            $submission = AfricaLife_Database::get_submission($submission_id);
            if (!$submission) {
                error_log('Africa Life PDF: Submission not found - ID: ' . $submission_id);
                return false;
            }
            
            // Get dependents
            $dependents = AfricaLife_Database::get_submission_dependents($submission_id);
            
            // Get plan data
            $plan = AfricaLife_Database::get_plan($submission->plan_id);
            
            // Get agent data
            $agent = get_userdata($submission->agent_id);
            
            // Get templates
            $pdf_template = AfricaLife_Database::get_template('pdf');
            $template_settings = $pdf_template ? $pdf_template->template_content : $this->get_default_template_settings();
            
            // Generate HTML content
            $html_content = $this->generate_html_content($submission, $dependents, $plan, $agent, $template_settings);
            
            // Create file
            $upload_dir = wp_upload_dir();
            $africa_life_dir = $upload_dir['basedir'] . '/africa-life/';
            
            if (!file_exists($africa_life_dir)) {
                wp_mkdir_p($africa_life_dir);
            }
            
            $filename = 'application_' . $submission->application_number . '_' . date('Y-m-d_H-i-s') . '.html';
            $file_path = $africa_life_dir . $filename;
            
            file_put_contents($file_path, $html_content);
            
            // Try to convert to PDF if possible
            if ($this->can_generate_pdf()) {
                $pdf_filename = $this->convert_html_to_pdf($file_path, $submission->application_number);
                if ($pdf_filename) {
                    return $pdf_filename;
                }
            }
            
            return $filename;
            
        } catch (Exception $e) {
            error_log('Africa Life PDF Generation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function get_default_template_settings() {
        return array(
            'header' => 'AFRICA LIFE APPLICATION FORM',
            'footer' => 'Africa Life - Funeral Cover Application',
            'declaration' => 'I declare that neither I nor my dependents suffer from any pre-existing health conditions that could lead to an early death. I understand and accept waiting periods, premiums and other conditions in the master policy as explained to me by the Intermediary.',
            'debit_order_declaration' => 'I authorize Africa Life to issue and deliver payment instructions to my Banker for collection against my Bank account on condition that the sum of such payment instruction will never exceed my obligations as agreed in your contract/agreement.',
            'font_family' => 'Arial, sans-serif',
            'font_size' => '10px'
        );
    }
    
    private function generate_html_content($submission, $dependents, $plan, $agent, $template_settings) {
        // Separate children and extended family
        $children = array();
        $extended_family = array();
        
        foreach ($dependents as $dependent) {
            if ($dependent->dependent_type === 'child') {
                $children[] = $dependent;
            } else {
                $extended_family[] = $dependent;
            }
        }
        
        // Generate signature
        $signature = $this->generate_signature($submission->main_full_names . ' ' . $submission->main_surname);
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Africa Life Application Form - ' . esc_html($submission->application_number) . '</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: ' . $template_settings['font_family'] . ';
            font-size: ' . $template_settings['font_size'] . ';
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2e7d32;
            font-size: 24px;
            margin: 0 0 5px 0;
        }
        .header h2 {
            font-size: 18px;
            margin: 0 0 10px 0;
        }
        .header h3 {
            font-size: 16px;
            margin: 0;
        }
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .section-header {
            background: #2e7d32;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 4px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 35%;
        }
        .value {
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }
        .checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
        }
        .checkbox.checked:after {
            content: "✓";
            display: block;
            text-align: center;
            line-height: 15px;
        }
        .signature-box {
            border: 1px solid #000;
            padding: 20px;
            margin: 10px 0;
        }
        .signature {
            font-style: italic;
            font-size: 14px;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding-bottom: 2px;
            margin-top: 20px;
        }
        .declaration-box {
            border: 2px solid #000;
            padding: 10px;
            margin: 10px 0;
            background: #f5f5f5;
        }
        .page-break {
            page-break-before: always;
        }
        .dependent-table {
            width: 100%;
            border: 1px solid #000;
            margin-top: 10px;
        }
        .dependent-table th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 5px;
            font-weight: bold;
            text-align: left;
        }
        .dependent-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .footer {
            text-align: center;
            font-size: 9px;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>AFRICA LIFE</h1>
        <h2>FUNERAL COVER</h2>
        <h3>Application for Membership</h3>
    </div>
    
    <!-- Section A - CONTRACT DETAILS -->
    <div class="section">
        <div class="section-header">A – CONTRACT DETAILS</div>
        <table>
            <tr>
                <td class="label">Scheme Name:</td>
                <td class="value">Africa Life ™</td>
                <td class="label">Member No:</td>
                <td class="value">' . esc_html($submission->member_number) . '</td>
            </tr>
            <tr>
                <td class="label">Category:</td>
                <td class="value">' . esc_html($plan ? $plan->plan_name : '') . '</td>
                <td class="label">Cover Amount:</td>
                <td class="value">R ' . number_format($submission->cover_amount, 2) . '</td>
            </tr>
            <tr>
                <td class="label">Entry Date:</td>
                <td class="value">' . date('d/m/Y', strtotime($submission->entry_date)) . '</td>
                <td class="label">Cover Date:</td>
                <td class="value">' . date('d/m/Y', strtotime($submission->cover_date)) . '</td>
            </tr>
            <tr>
                <td class="label">Total Premium:</td>
                <td class="value">R ' . number_format($submission->total_premium, 2) . '</td>
                <td class="label">Risk Premium:</td>
                <td class="value">R ' . number_format($submission->risk_premium, 2) . '</td>
            </tr>
            <tr>
                <td class="label">Marketing/Admin Fee:</td>
                <td class="value">R ' . number_format($submission->marketing_admin_fee, 2) . '</td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <!-- Section B - MAIN MEMBER DETAILS -->
    <div class="section">
        <div class="section-header">B – MAIN MEMBER DETAILS</div>
        <table>
            <tr>
                <td class="label">Surname:</td>
                <td class="value">' . esc_html($submission->main_surname) . '</td>
                <td class="label">Full Names:</td>
                <td class="value">' . esc_html($submission->main_full_names) . '</td>
            </tr>
            <tr>
                <td class="label">Title:</td>
                <td class="value">' . esc_html($submission->main_title) . '</td>
                <td class="label">ID:</td>
                <td class="value">' . esc_html($submission->main_id_number) . '</td>
            </tr>
            <tr>
                <td class="label">Date of Birth:</td>
                <td class="value">' . date('d/m/Y', strtotime($submission->main_date_of_birth)) . '</td>
                <td class="label">Email:</td>
                <td class="value">' . esc_html($submission->main_email) . '</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td class="value" colspan="3">' . nl2br(esc_html($submission->main_address)) . '</td>
            </tr>
            <tr>
                <td class="label">Code:</td>
                <td class="value">' . esc_html($submission->main_postal_code) . '</td>
                <td class="label">Contact Numbers:</td>
                <td class="value">' . esc_html($submission->main_contact_numbers) . '</td>
            </tr>
            <tr>
                <td class="label">Preferred Language:</td>
                <td colspan="3">
                    <span class="checkbox ' . ($submission->main_preferred_language === 'English' ? 'checked' : '') . '"></span> English
                    <span style="margin-left: 20px;"><span class="checkbox ' . ($submission->main_preferred_language === 'Afrikaans' ? 'checked' : '') . '"></span> Afrikaans</span>
                    <span style="margin-left: 20px;"><span class="checkbox ' . ($submission->main_preferred_language === 'Xhosa' ? 'checked' : '') . '"></span> Xhosa</span>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Section C - SPOUSE DETAILS -->
    <div class="section">
        <div class="section-header">C – SPOUSE DETAILS</div>
        <table>
            <tr>
                <td class="label">Surname:</td>
                <td class="value">' . esc_html($submission->spouse_surname ?: '') . '</td>
                <td class="label">Full Names:</td>
                <td class="value">' . esc_html($submission->spouse_full_names ?: '') . '</td>
            </tr>
            <tr>
                <td class="label">ID:</td>
                <td class="value">' . esc_html($submission->spouse_id_number ?: '') . '</td>
                <td class="label">Date of Birth:</td>
                <td class="value">' . ($submission->spouse_date_of_birth ? date('d/m/Y', strtotime($submission->spouse_date_of_birth)) : '') . '</td>
            </tr>
            <tr>
                <td class="label">Relationship:</td>
                <td colspan="3">
                    <span class="checkbox ' . ($submission->spouse_relationship === 'Married' ? 'checked' : '') . '"></span> Married
                    <span style="margin-left: 20px;"><span class="checkbox ' . ($submission->spouse_relationship === 'Living Together' ? 'checked' : '') . '"></span> Living Together</span>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Section D - CHILDREN DETAILS -->
    <div class="section">
        <div class="section-header">D – CHILDREN DETAILS</div>
        <table class="dependent-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Surname</th>
                    <th style="width: 30%;">Full Names</th>
                    <th style="width: 20%;">Relationship</th>
                    <th style="width: 20%;">ID Number</th>
                </tr>
            </thead>
            <tbody>';
        
        // Add children rows (always show 5 rows)
        for ($i = 0; $i < 5; $i++) {
            $child = isset($children[$i]) ? $children[$i] : null;
            $html .= '<tr>
                <td>' . ($i + 1) . '</td>
                <td>' . ($child ? esc_html($child->surname) : '') . '</td>
                <td>' . ($child ? esc_html($child->full_names) : '') . '</td>
                <td>' . ($child ? esc_html($child->relationship) : '') . '</td>
                <td>' . ($child ? esc_html($child->id_number) : '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>
    </div>
    
    <!-- Section E - EXTENDED FAMILY -->
    <div class="section">
        <div class="section-header">E – EXTENDED FAMILY</div>
        <table class="dependent-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Surname</th>
                    <th style="width: 30%;">Full Names</th>
                    <th style="width: 20%;">Relationship</th>
                    <th style="width: 20%;">ID Number</th>
                </tr>
            </thead>
            <tbody>';
        
        // Add extended family rows (always show 2 rows)
        for ($i = 0; $i < 2; $i++) {
            $extended = isset($extended_family[$i]) ? $extended_family[$i] : null;
            $html .= '<tr>
                <td>' . ($i + 1) . '</td>
                <td>' . ($extended ? esc_html($extended->surname) : '') . '</td>
                <td>' . ($extended ? esc_html($extended->full_names) : '') . '</td>
                <td>' . ($extended ? esc_html($extended->relationship) : '') . '</td>
                <td>' . ($extended ? esc_html($extended->id_number) : '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>
    </div>
    
    <!-- Section F - BENEFICIARY DETAILS -->
    <div class="section">
        <div class="section-header">F – BENEFICIARY DETAILS</div>
        <table>
            <tr>
                <td class="label">Name and Surname:</td>
                <td class="value">' . esc_html($submission->beneficiary_name_surname) . '</td>
                <td class="label">Relationship:</td>
                <td class="value">' . esc_html($submission->beneficiary_relationship) . '</td>
            </tr>
            <tr>
                <td class="label">Identity Number:</td>
                <td class="value">' . esc_html($submission->beneficiary_id_number) . '</td>
                <td class="label">Telephone:</td>
                <td class="value">' . esc_html($submission->beneficiary_telephone) . '</td>
            </tr>
        </table>
    </div>
    
    <!-- Section G - DECLARATION BY APPLICANT -->
    <div class="section">
        <div class="section-header">G – DECLARATION BY APPLICANT</div>
        <div class="declaration-box">
            <p>' . esc_html($template_settings['declaration']) . '</p>
        </div>
        <div style="margin-top: 20px;">
            <table>
                <tr>
                    <td class="label">Applicant\'s Name:</td>
                    <td class="value">' . esc_html($submission->main_full_names . ' ' . $submission->main_surname) . '</td>
                    <td class="label">Date:</td>
                    <td class="value">' . date('d/m/Y', strtotime($submission->declaration_date)) . '</td>
                </tr>
                <tr>
                    <td class="label">Signature:</td>
                    <td><div class="signature">' . esc_html($signature) . '</div></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label">Representative:</td>
                    <td class="value">' . esc_html($agent ? $agent->display_name : '') . '</td>
                    <td class="label">Signature:</td>
                    <td><div class="signature" style="margin-top: 0;">' . esc_html($agent ? $this->generate_signature($agent->display_name) : '') . '</div></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div style="border-top: 1px solid #000; margin-top: 20px; padding-top: 10px;">
        <p style="font-weight: bold;">FOR OFFICE USE ONLY:</p>
        <div style="height: 100px; border: 1px solid #ccc; background: #f9f9f9;"></div>
    </div>
    
    <!-- Page 2 - Payment Mandate -->
    <div class="page-break"></div>
    
    <div class="header">
        <h1>AFRICA LIFE</h1>
        <h2>FUNERAL COVER</h2>
    </div>
    
    <h3 style="text-align: center; margin: 20px 0;">Authority and Mandate for payments Instruction: Electronic and Written Mandates</h3>
    
    <table style="margin-bottom: 20px;">
        <tr>
            <td class="label">Given by (name of Accountholder):</td>
            <td class="value">' . esc_html($submission->bank_account_holder) . '</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td class="value">' . nl2br(esc_html($submission->bank_address)) . '</td>
        </tr>
        <tr>
            <td class="label">Bank:</td>
            <td class="value">' . esc_html($submission->bank_name) . '</td>
        </tr>
        <tr>
            <td class="label">Branch and Code:</td>
            <td class="value">' . esc_html($submission->bank_branch_code) . '</td>
        </tr>
        <tr>
            <td class="label">Account Number:</td>
            <td class="value">' . esc_html($submission->bank_account_number) . '</td>
        </tr>
        <tr>
            <td class="label">Type of Account:</td>
            <td class="value">' . esc_html($submission->bank_account_type) . '</td>
        </tr>
        <tr>
            <td class="label">Amount:</td>
            <td class="value">R ' . number_format($submission->total_premium, 2) . '</td>
        </tr>
        <tr>
            <td class="label">Date:</td>
            <td class="value">' . date('d/m/Y') . '</td>
        </tr>
        <tr>
            <td class="label">Contact Number:</td>
            <td class="value">' . esc_html($submission->bank_contact_number) . '</td>
        </tr>
        <tr>
            <td class="label">Abbreviated Name as Registered with the Bank:</td>
            <td class="value">' . esc_html($submission->bank_abbreviated_name) . '</td>
        </tr>
    </table>
    
    <div class="section">
        <div class="section-header">Declaration</div>
        <div class="declaration-box">
            <p style="font-weight: bold;">Declaration</p>
            <p>' . esc_html($template_settings['debit_order_declaration']) . '</p>
            <p>This method will commence (effective date) and will continue monthly, thereafter until your obligation has ended or the Authority and Mandate is terminated by yourself by giving us notice of not less than one month.</p>
            <p>In the event that the payment day falls on a Sunday, or recognized South African public holiday, the payment day will automatically be the preceding ordinary business day.</p>
            <p>Payment instructions due in December may be debited against my account on a date earlier or on (date as agreed by you)</p>
            <p>If there are insufficient funds in the nominated account to meet the obligation, you are entitled to track my account and re-present the instruction for payment as soon as sufficient funds are available in my account.</p>
            <p>This Authority and Mandate may be cancelled by me/us; however, such cancellation will not cancel the Agreement. I/We shall not be entitled to any refund of amounts which you may have withdrawn while this Authority was in force, if such amounts were legally owing to you.</p>
            <p>The Authority and Mandate may be ceded or assigned to a third party only if the Agreement is also ceded or assigned to the third party.</p>
        </div>
        
        <p style="margin-top: 20px;">Mr/Mrs/Miss we will confirm your Authority and Mandate in writing prior to processing the debit order against your account.</p>
        <p>Mr/Mrs/Miss do you understand and accept what I have read to you? <strong>(Yes/No)</strong> <span style="text-decoration: underline; margin-left: 10px;">YES - Verbal Consent Given</span></p>
        <p>If you have any questions or complaints, please contact on <strong>031 035 0625</strong></p>
        <p><strong>Lastly, let me be the first to welcome you as a valued member of AFRILIFE</strong></p>
        
        <div style="margin-top: 30px;">
            <p>Signed at <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px;"></span> on this <span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;">' . date('d') . '</span> day of <span style="border-bottom: 1px solid #000; display: inline-block; width: 100px;">' . date('F') . '</span> 20<span style="border-bottom: 1px solid #000; display: inline-block; width: 50px;">' . date('y') . '</span></p>
            
            <table style="margin-top: 30px;">
                <tr>
                    <td style="width: 45%;">
                        <p style="margin-bottom: 40px;">Client Signature</p>
                        <div style="border-bottom: 1px solid #000; width: 100%;"></div>
                        <p style="font-size: 9px; margin-top: 5px;">(Signature as used for operating on the account)</p>
                    </td>
                    <td style="width: 10%;"></td>
                    <td style="width: 45%;">
                        <p style="margin-bottom: 40px;">(Assisted by)</p>
                        <div style="border-bottom: 1px solid #000; width: 100%;"></div>
                    </td>
                </tr>
            </table>
            
            <p style="margin-top: 30px;">Agreement reference number is <span style="border-bottom: 1px solid #000; display: inline-block; width: 200px; font-weight: bold;">' . esc_html($submission->application_number) . '</span></p>
            
            <p style="margin-top: 40px; font-weight: bold; text-align: center; background: yellow; padding: 10px;">NB: PLEASE ATTACH FICA DOCUMENT</p>
        </div>
    </div>
    
    <div class="footer">
        <p>' . esc_html($template_settings['footer']) . '</p>
        <p>Application Number: ' . esc_html($submission->application_number) . ' | Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    private function generate_signature($name) {
        $name_parts = explode(' ', trim($name));
        
        if (count($name_parts) >= 2) {
            $first_initial = substr($name_parts[0], 0, 1);
            $last_name = end($name_parts);
            return $first_initial . '. ' . $last_name;
        } else {
            return $name;
        }
    }
    
    private function can_generate_pdf() {
        // Check if wkhtmltopdf is available
        if (function_exists('shell_exec')) {
            $output = shell_exec('which wkhtmltopdf 2>&1');
            return !empty($output);
        }
        return false;
    }
    
    private function convert_html_to_pdf($html_file, $application_number) {
        $upload_dir = wp_upload_dir();
        $africa_life_dir = $upload_dir['basedir'] . '/africa-life/';
        
        $pdf_filename = 'application_' . $application_number . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdf_path = $africa_life_dir . $pdf_filename;
        
        $command = sprintf(
            'wkhtmltopdf --page-size A4 --margin-top 15mm --margin-right 15mm --margin-bottom 15mm --margin-left 15mm --encoding UTF-8 --quiet %s %s',
            escapeshellarg($html_file),
            escapeshellarg($pdf_path)
        );
        
        $output = shell_exec($command . ' 2>&1');
        
        if (file_exists($pdf_path) && filesize($pdf_path) > 0) {
            // Delete the HTML file
            unlink($html_file);
            return $pdf_filename;
        } else {
            error_log('Africa Life PDF conversion failed: ' . $output);
        }
        
        return false;
    }
}

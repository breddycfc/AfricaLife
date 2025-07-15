<?php
if (!defined('ABSPATH')) {
    exit;
}

class AfricaLife_Agent_Interface {
    
    public function render_form() {
        if (!is_user_logged_in() || !AfricaLife_Roles::user_has_agent_access()) {
            return $this->render_login_prompt();
        }
        
        $plans = AfricaLife_Database::get_plans();
        
        ob_start();
        ?>
        <div class="africa-life-application-container">
            <style>
                .africa-life-application-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                    font-family: Arial, sans-serif;
                }
                .form-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .form-header h1 {
                    color: #2e7d32;
                    font-size: 36px;
                    margin-bottom: 10px;
                }
                .form-section {
                    background: #f5f5f5;
                    padding: 20px;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    border: 1px solid #ddd;
                }
                .form-section h3 {
                    color: #fff;
                    background: #2e7d32;
                    padding: 10px 15px;
                    margin: -20px -20px 20px -20px;
                    border-radius: 8px 8px 0 0;
                    font-size: 18px;
                }
                .form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 15px;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                    color: #333;
                }
                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    font-size: 14px;
                }
                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: #2e7d32;
                    box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.1);
                }
                .required {
                    color: #d32f2f;
                }
                .dependent-row {
                    display: grid;
                    grid-template-columns: 1fr 1fr 1fr 1fr;
                    gap: 10px;
                    padding: 10px;
                    background: #fff;
                    margin-bottom: 10px;
                    border-radius: 4px;
                }
                .radio-group {
                    display: flex;
                    gap: 20px;
                    margin-top: 5px;
                }
                .radio-group label {
                    display: flex;
                    align-items: center;
                    font-weight: normal;
                }
                .radio-group input[type="radio"] {
                    margin-right: 5px;
                    width: auto;
                }
                .submit-button {
                    background: #2e7d32;
                    color: white;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 4px;
                    font-size: 18px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: background 0.3s;
                }
                .submit-button:hover {
                    background: #1b5e20;
                }
                .submit-button:disabled {
                    background: #ccc;
                    cursor: not-allowed;
                }
                .plan-selector {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                .plan-card {
                    background: white;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    padding: 20px;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                .plan-card:hover {
                    border-color: #2e7d32;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .plan-card.selected {
                    border-color: #2e7d32;
                    background: #e8f5e9;
                }
                .plan-card h4 {
                    color: #2e7d32;
                    margin-bottom: 10px;
                }
                .plan-details {
                    font-size: 14px;
                    color: #666;
                }
                .declaration-box {
                    background: #fff;
                    border: 2px solid #2e7d32;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .agent-dashboard {
                    margin-top: 40px;
                    padding-top: 40px;
                    border-top: 2px solid #ddd;
                }
            </style>
            
            <!-- Header -->
            <div class="form-header">
                <h1>AFRICA LIFE</h1>
                <h2 style="color: #666; font-size: 24px;">FUNERAL COVER</h2>
                <h3 style="color: #333; font-size: 20px; margin-top: 20px;">Application for Membership</h3>
            </div>
            
            <!-- Plan Selection -->
            <div class="form-section">
                <h3>Select a Funeral Cover Plan</h3>
                <div class="plan-selector">
                    <?php foreach ($plans as $plan): ?>
                        <?php
                        $categories = json_decode($plan->categories, true);
                        $total_premium = 0;
                        foreach ($categories as $category) {
                            $total_premium += floatval($category['rate']);
                        }
                        ?>
                        <div class="plan-card" data-plan-id="<?php echo $plan->id; ?>">
                            <h4><?php echo esc_html($plan->plan_name); ?></h4>
                            <div class="plan-details">
                                <?php foreach ($categories as $category): ?>
                                    <p><strong><?php echo esc_html($category['name']); ?>:</strong> R<?php echo number_format($category['rate'], 2); ?>/month</p>
                                <?php endforeach; ?>
                                <hr style="margin: 10px 0;">
                                <p><strong>Total Premium: R<?php echo number_format($total_premium, 2); ?>/month</strong></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Application Form -->
            <form id="africa-life-application-form" style="display: none;">
                <?php wp_nonce_field('africa_life_submit', 'africa_life_nonce'); ?>
                <input type="hidden" name="plan_id" id="selected_plan_id" value="">
                <input type="hidden" name="agent_id" value="<?php echo get_current_user_id(); ?>">
                
                <!-- Section A - CONTRACT DETAILS -->
                <div class="form-section">
                    <h3>A – CONTRACT DETAILS</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Scheme Name:</label>
                            <input type="text" value="Africa Life ™" readonly style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Member No:</label>
                            <input type="text" name="member_number" readonly placeholder="Will be generated" style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Category: <span class="required">*</span></label>
                            <input type="text" name="category" id="plan_category" readonly required style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Cover Amount: R</label>
                            <input type="number" name="cover_amount" id="cover_amount" readonly style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Entry Date: <span class="required">*</span></label>
                            <input type="date" name="entry_date" required>
                        </div>
                        <div class="form-group">
                            <label>Cover Date: <span class="required">*</span></label>
                            <input type="date" name="cover_date" required>
                        </div>
                        <div class="form-group">
                            <label>Total Premium: R</label>
                            <input type="number" name="total_premium" id="total_premium" readonly style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Risk Premium: R</label>
                            <input type="number" name="risk_premium" id="risk_premium" readonly style="background: #eee;">
                        </div>
                        <div class="form-group">
                            <label>Marketing/Admin Fee: R</label>
                            <input type="number" name="marketing_admin_fee" id="marketing_admin_fee" readonly style="background: #eee;">
                        </div>
                    </div>
                </div>
                
                <!-- Section B - MAIN MEMBER DETAILS -->
                <div class="form-section">
                    <h3>B – MAIN MEMBER DETAILS</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Surname: <span class="required">*</span></label>
                            <input type="text" name="main_surname" required>
                        </div>
                        <div class="form-group">
                            <label>Full Names: <span class="required">*</span></label>
                            <input type="text" name="main_full_names" required>
                        </div>
                        <div class="form-group">
                            <label>Title: <span class="required">*</span></label>
                            <select name="main_title" required>
                                <option value="">Select</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                <option value="Ms">Ms</option>
                                <option value="Dr">Dr</option>
                                <option value="Prof">Prof</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ID Number: <span class="required">*</span></label>
                            <input type="text" name="main_id_number" pattern="[0-9]{13}" maxlength="13" required placeholder="13 digits">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth: <span class="required">*</span></label>
                            <input type="date" name="main_date_of_birth" required>
                        </div>
                        <div class="form-group">
                            <label>Email: <span class="required">*</span></label>
                            <input type="email" name="main_email" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Address: <span class="required">*</span></label>
                            <textarea name="main_address" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Postal Code: <span class="required">*</span></label>
                            <input type="text" name="main_postal_code" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Numbers: <span class="required">*</span></label>
                            <input type="text" name="main_contact_numbers" required placeholder="e.g., 0123456789">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Preferred Language: <span class="required">*</span></label>
                            <div class="radio-group">
                                <label><input type="radio" name="main_preferred_language" value="English" checked> English</label>
                                <label><input type="radio" name="main_preferred_language" value="Afrikaans"> Afrikaans</label>
                                <label><input type="radio" name="main_preferred_language" value="Xhosa"> Xhosa</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section C - SPOUSE DETAILS -->
                <div class="form-section">
                    <h3>C – SPOUSE DETAILS</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Surname:</label>
                            <input type="text" name="spouse_surname">
                        </div>
                        <div class="form-group">
                            <label>Full Names:</label>
                            <input type="text" name="spouse_full_names">
                        </div>
                        <div class="form-group">
                            <label>ID Number:</label>
                            <input type="text" name="spouse_id_number" pattern="[0-9]{13}" maxlength="13" placeholder="13 digits">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth:</label>
                            <input type="date" name="spouse_date_of_birth">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Relationship:</label>
                            <div class="radio-group">
                                <label><input type="radio" name="spouse_relationship" value="Married"> Married</label>
                                <label><input type="radio" name="spouse_relationship" value="Living Together"> Living Together</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section D - CHILDREN DETAILS -->
                <div class="form-section">
                    <h3>D – CHILDREN DETAILS</h3>
                    <div id="children-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="dependent-row">
                            <div class="form-group">
                                <label><?php echo $i; ?>. Surname:</label>
                                <input type="text" name="child_surname[]">
                            </div>
                            <div class="form-group">
                                <label>Full Names:</label>
                                <input type="text" name="child_full_names[]">
                            </div>
                            <div class="form-group">
                                <label>Relationship:</label>
                                <input type="text" name="child_relationship[]" placeholder="e.g., Son, Daughter">
                            </div>
                            <div class="form-group">
                                <label>ID Number:</label>
                                <input type="text" name="child_id_number[]" pattern="[0-9]{13}" maxlength="13">
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Section E - EXTENDED FAMILY -->
                <div class="form-section">
                    <h3>E – EXTENDED FAMILY</h3>
                    <div id="extended-family-container">
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                        <div class="dependent-row">
                            <div class="form-group">
                                <label><?php echo $i; ?>. Surname:</label>
                                <input type="text" name="extended_surname[]">
                            </div>
                            <div class="form-group">
                                <label>Full Names:</label>
                                <input type="text" name="extended_full_names[]">
                            </div>
                            <div class="form-group">
                                <label>Relationship:</label>
                                <input type="text" name="extended_relationship[]" placeholder="e.g., Parent, Sibling">
                            </div>
                            <div class="form-group">
                                <label>ID Number:</label>
                                <input type="text" name="extended_id_number[]" pattern="[0-9]{13}" maxlength="13">
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Section F - BENEFICIARY DETAILS -->
                <div class="form-section">
                    <h3>F – BENEFICIARY DETAILS</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Name and Surname: <span class="required">*</span></label>
                            <input type="text" name="beneficiary_name_surname" required>
                        </div>
                        <div class="form-group">
                            <label>Relationship: <span class="required">*</span></label>
                            <input type="text" name="beneficiary_relationship" required placeholder="e.g., Spouse, Child, Parent">
                        </div>
                        <div class="form-group">
                            <label>Identity Number: <span class="required">*</span></label>
                            <input type="text" name="beneficiary_id_number" pattern="[0-9]{13}" maxlength="13" required>
                        </div>
                        <div class="form-group">
                            <label>Telephone: <span class="required">*</span></label>
                            <input type="tel" name="beneficiary_telephone" required placeholder="e.g., 0123456789">
                        </div>
                    </div>
                </div>
                
                <!-- Debit Order Details -->
                <div class="form-section">
                    <h3>DEBIT ORDER DETAILS</h3>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Account Holder Name: <span class="required">*</span></label>
                            <input type="text" name="bank_account_holder" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Address: <span class="required">*</span></label>
                            <textarea name="bank_address" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Bank: <span class="required">*</span></label>
                            <select name="bank_name" required>
                                <option value="">Select Bank</option>
                                <option value="ABSA">ABSA</option>
                                <option value="FNB">FNB</option>
                                <option value="Standard Bank">Standard Bank</option>
                                <option value="Nedbank">Nedbank</option>
                                <option value="Capitec">Capitec</option>
                                <option value="African Bank">African Bank</option>
                                <option value="TymeBank">TymeBank</option>
                                <option value="Discovery Bank">Discovery Bank</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Branch and Code: <span class="required">*</span></label>
                            <input type="text" name="bank_branch_code" required placeholder="6 digits">
                        </div>
                        <div class="form-group">
                            <label>Account Number: <span class="required">*</span></label>
                            <input type="text" name="bank_account_number" required>
                        </div>
                        <div class="form-group">
                            <label>Type of Account: <span class="required">*</span></label>
                            <select name="bank_account_type" required>
                                <option value="">Select Type</option>
                                <option value="Current">Current (Cheque)</option>
                                <option value="Savings">Savings</option>
                                <option value="Transmission">Transmission</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Contact Number: <span class="required">*</span></label>
                            <input type="tel" name="bank_contact_number" required>
                        </div>
                        <div class="form-group">
                            <label>Abbreviated Name (as registered with the Bank): <span class="required">*</span></label>
                            <input type="text" name="bank_abbreviated_name" required>
                        </div>
                    </div>
                </div>
                
                <!-- Section G - DECLARATION BY APPLICANT -->
                <div class="form-section">
                    <h3>G – DECLARATION BY APPLICANT</h3>
                    <div class="declaration-box">
                        <p>I declare that neither I nor my dependents suffer from any pre-existing health conditions that could lead to an early death. 
                        I understand and accept waiting periods, premiums and other conditions in the master policy as explained to me by the Intermediary.</p>
                        
                        <p style="margin-top: 15px;">I authorize Africa Life to issue and deliver payment instructions to my Banker for collection against my Bank account on 
                        condition that the sum of such payment instruction will never exceed my obligations as agreed in this contract/agreement.</p>
                        
                        <div style="margin-top: 20px;">
                            <label>
                                <input type="checkbox" name="declaration_accepted" required style="width: auto; margin-right: 10px;">
                                <strong>I have read, understood and accept the declaration above</strong> <span class="required">*</span>
                            </label>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <label>
                                <input type="checkbox" name="verbal_consent" required style="width: auto; margin-right: 10px;">
                                <strong>The customer has provided verbal consent</strong> <span class="required">*</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="submit-button" id="submit-application">
                        Submit Application
                    </button>
                </div>
            </form>
            
            <!-- Agent Dashboard -->
            <div class="agent-dashboard">
                <h2 style="color: #2e7d32; margin-bottom: 20px;">My Recent Submissions</h2>
                <?php $this->render_agent_submissions(); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var selectedPlan = null;
            
            // Plan selection
            $('.plan-card').click(function() {
                $('.plan-card').removeClass('selected');
                $(this).addClass('selected');
                
                var planId = $(this).data('plan-id');
                $('#selected_plan_id').val(planId);
                
                // Get plan details and populate form
                var planName = $(this).find('h4').text();
                $('#plan_category').val(planName);
                
                // Calculate totals
                var totalPremium = 0;
                $(this).find('.plan-details p').each(function() {
                    var text = $(this).text();
                    if (text.includes('R') && !text.includes('Total')) {
                        var amount = parseFloat(text.split('R')[1]);
                        if (!isNaN(amount)) {
                            totalPremium += amount;
                        }
                    }
                });
                
                $('#total_premium').val(totalPremium.toFixed(2));
                $('#risk_premium').val((totalPremium * 0.85).toFixed(2)); // 85% risk premium
                $('#marketing_admin_fee').val((totalPremium * 0.15).toFixed(2)); // 15% admin fee
                
                // Show form
                $('#africa-life-application-form').slideDown();
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#africa-life-application-form').offset().top - 100
                }, 500);
            });
            
            // Auto-populate DOB from ID number
            $('input[name$="_id_number"]').on('blur', function() {
                var idNumber = $(this).val();
                if (idNumber.length === 13) {
                    var year = idNumber.substring(0, 2);
                    var month = idNumber.substring(2, 4);
                    var day = idNumber.substring(4, 6);
                    
                    // Determine century
                    var currentYear = new Date().getFullYear();
                    var century = parseInt(year) > (currentYear % 100) ? '19' : '20';
                    var fullYear = century + year;
                    
                    var dob = fullYear + '-' + month + '-' + day;
                    
                    // Find corresponding DOB field
                    var fieldName = $(this).attr('name');
                    if (fieldName.includes('main_')) {
                        $('input[name="main_date_of_birth"]').val(dob);
                    } else if (fieldName.includes('spouse_')) {
                        $('input[name="spouse_date_of_birth"]').val(dob);
                    }
                }
            });
            
            // Form submission
            $('#africa-life-application-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $submitBtn = $('#submit-application');
                
                // Validate
                if (!$form[0].checkValidity()) {
                    $form[0].reportValidity();
                    return;
                }
                
                // Confirm
                if (!confirm('Are you sure you want to submit this application?')) {
                    return;
                }
                
                // Disable button
                $submitBtn.prop('disabled', true).text('Submitting...');
                
                // Prepare form data
                var formData = new FormData($form[0]);
                formData.append('action', 'africa_life_submit_form');
                
                // Submit
                $.ajax({
                    url: africa_life_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('Application submitted successfully! PDF has been generated and emails have been sent.');
                            $form[0].reset();
                            $('.plan-card').removeClass('selected');
                            $('#africa-life-application-form').slideUp();
                            location.reload(); // Reload to show new submission
                        } else {
                            alert('Error: ' + (response.data || 'Unknown error occurred'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred while submitting the application. Please try again.');
                        console.error('AJAX error:', error);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false).text('Submit Application');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    private function render_login_prompt() {
        return '<div style="text-align: center; padding: 50px; background: #f5f5f5; border-radius: 8px;">
            <h2 style="color: #2e7d32;">Agent Access Required</h2>
            <p>Please log in with your agent credentials to access the application form.</p>
            <a href="' . home_url('/agent-login/') . '" style="display: inline-block; margin-top: 20px; padding: 10px 30px; background: #2e7d32; color: white; text-decoration: none; border-radius: 4px;">
                Login as Agent
            </a>
        </div>';
    }
    
    private function render_agent_submissions() {
        $agent_id = get_current_user_id();
        $submissions = AfricaLife_Database::get_submissions($agent_id);
        
        if (empty($submissions)) {
            echo '<p style="text-align: center; color: #666; padding: 40px;">No submissions found.</p>';
            return;
        }
        
        ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; background: white;">
                <thead>
                    <tr style="background: #2e7d32; color: white;">
                        <th style="padding: 12px; text-align: left;">Application #</th>
                        <th style="padding: 12px; text-align: left;">Member #</th>
                        <th style="padding: 12px; text-align: left;">Customer Name</th>
                        <th style="padding: 12px; text-align: left;">Date</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 12px;"><?php echo esc_html($submission->application_number); ?></td>
                            <td style="padding: 12px;"><?php echo esc_html($submission->member_number); ?></td>
                            <td style="padding: 12px;"><?php echo esc_html($submission->main_full_names . ' ' . $submission->main_surname); ?></td>
                            <td style="padding: 12px;"><?php echo date('Y-m-d', strtotime($submission->submission_date)); ?></td>
                            <td style="padding: 12px;">
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; 
                                    <?php echo $submission->status === 'Approved' ? 'background: #4caf50; color: white;' : 
                                              ($submission->status === 'Declined' ? 'background: #f44336; color: white;' : 'background: #ff9800; color: white;'); ?>">
                                    <?php echo esc_html($submission->status); ?>
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <?php if ($submission->pdf_file): ?>
                                    <a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/africa-life/' . $submission->pdf_file); ?>" 
                                       target="_blank" style="color: #2e7d32; text-decoration: none;">
                                        View PDF
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
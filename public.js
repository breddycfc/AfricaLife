/* Africa Life Plugin Public JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Form validation
    function validateForm() {
        var isValid = true;
        var requiredFields = $('#africa-life-form').find('[required]');
        
        requiredFields.each(function() {
            var field = $(this);
            var value = field.val().trim();
            
            if (!value) {
                field.addClass('border-red-500');
                isValid = false;
            } else {
                field.removeClass('border-red-500');
            }
        });
        
        // Email validation
        var emailField = $('input[name="customer_email"]');
        var emailValue = emailField.val().trim();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (emailValue && !emailRegex.test(emailValue)) {
            emailField.addClass('border-red-500');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Real-time field validation
    $('#africa-life-form input, #africa-life-form select, #africa-life-form textarea').on('blur', function() {
        var field = $(this);
        var value = field.val().trim();
        
        if (field.prop('required') && !value) {
            field.addClass('border-red-500');
            showFieldError(field, 'This field is required');
        } else {
            field.removeClass('border-red-500');
            hideFieldError(field);
        }
        
        // Email specific validation
        if (field.attr('type') === 'email' && value) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                field.addClass('border-red-500');
                showFieldError(field, 'Please enter a valid email address');
            }
        }
    });
    
    function showFieldError(field, message) {
        var errorId = field.attr('name') + '-error';
        var existingError = $('#' + errorId);
        
        if (existingError.length) {
            existingError.text(message);
        } else {
            field.after('<div id="' + errorId + '" class="text-red-400 text-xs mt-1">' + message + '</div>');
        }
    }
    
    function hideFieldError(field) {
        var errorId = field.attr('name') + '-error';
        $('#' + errorId).remove();
    }
    
    // Plan selection handling with enhanced UI
    $('#plan_selection').change(function() {
        var selectedOption = $(this).find('option:selected');
        var categories = selectedOption.data('categories');
        
        if (categories) {
            try {
                var categoryData = JSON.parse(categories);
                displayCategories(categoryData);
                $('#plan-details').removeClass('hidden').hide().fadeIn(300);
            } catch (e) {
                console.error('Error parsing category data:', e);
                showNotification('Error loading plan details', 'error');
            }
        } else {
            $('#plan-details').fadeOut(300, function() {
                $(this).addClass('hidden');
            });
        }
    });
    
    function displayCategories(categories) {
        var categoriesList = $('#categories-list');
        categoriesList.empty();
        
        var totalPremium = 0;
        
        $.each(categories, function(index, category) {
            var categoryHtml = $('<div class="bg-gray-700 p-4 rounded-lg border-l-4 border-yellow-400 transform hover:scale-105 transition-transform duration-200">' +
                '<h5 class="font-semibold text-yellow-300 mb-2">' + escapeHtml(category.name) + '</h5>' +
                '<div class="grid grid-cols-2 gap-2 text-sm text-gray-300">' +
                    '<p><span class="font-medium">Age Range:</span> ' + escapeHtml(category.age_range) + '</p>' +
                    '<p><span class="font-medium">Premium:</span> R' + parseFloat(category.rate).toFixed(2) + '</p>' +
                    '<p><span class="font-medium">Cover:</span> R' + parseFloat(category.cover_amount).toFixed(2) + '</p>' +
                    '<p><span class="font-medium">Terms:</span> ' + escapeHtml(category.terms || 'Standard terms apply') + '</p>' +
                '</div>' +
                '</div>');
            
            categoriesList.append(categoryHtml);
            totalPremium += parseFloat(category.rate);
        });
        
        $('#premium_amount').val(totalPremium.toFixed(2));
        
        // Animate premium amount update
        var premiumField = $('#premium_amount');
        premiumField.addClass('ring-2 ring-yellow-400');
        setTimeout(function() {
            premiumField.removeClass('ring-2 ring-yellow-400');
        }, 1000);
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Enhanced form submission with progress indicator
    $('#africa-life-form').submit(function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            showNotification('Please fill in all required fields correctly', 'error');
            return;
        }
        
        var submitBtn = $('#submit-btn');
        var originalText = submitBtn.text();
        
        // Disable form and show loading state
        submitBtn.prop('disabled', true).html('<div class="loading-spinner inline-block mr-2"></div>Submitting...');
        $('#africa-life-form input, #africa-life-form select, #africa-life-form textarea').prop('disabled', true);
        
        var formData = new FormData(this);
        formData.append('action', 'africa_life_submit_form');
        
        $.ajax({
            url: africa_life_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification('Application submitted successfully! PDF has been generated and emails sent.', 'success');
                    
                    // Reset form after successful submission
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error: ' + (response.data || 'Unknown error occurred'), 'error');
                }
            },
            error: function(xhr, status, error) {
                var message = 'An error occurred while submitting the form.';
                if (status === 'timeout') {
                    message = 'Request timed out. Please try again.';
                }
                showNotification(message, 'error');
            },
            complete: function() {
                // Re-enable form
                submitBtn.prop('disabled', false).text(originalText);
                $('#africa-life-form input, #africa-life-form select, #africa-life-form textarea').prop('disabled', false);
            }
        });
    });
    
    // Notification system
    function showNotification(message, type) {
        var notificationClass = '';
        var icon = '';
        
        switch(type) {
            case 'success':
                notificationClass = 'bg-green-900 border-green-500 text-green-300';
                icon = '✓';
                break;
            case 'error':
                notificationClass = 'bg-red-900 border-red-500 text-red-300';
                icon = '✗';
                break;
            case 'warning':
                notificationClass = 'bg-yellow-900 border-yellow-500 text-yellow-300';
                icon = '⚠';
                break;
            default:
                notificationClass = 'bg-blue-900 border-blue-500 text-blue-300';
                icon = 'ℹ';
        }
        
        var notification = $('<div class="fixed top-4 right-4 z-50 p-4 border-l-4 rounded-md shadow-lg ' + notificationClass + ' transform translate-x-full transition-transform duration-300">' +
            '<div class="flex items-center">' +
                '<span class="text-lg mr-3">' + icon + '</span>' +
                '<span>' + escapeHtml(message) + '</span>' +
                '<button class="ml-4 text-xl leading-none" onclick="$(this).parent().parent().fadeOut(300, function(){ $(this).remove(); })">&times;</button>' +
            '</div>' +
        '</div>');
        
        $('body').append(notification);
        
        // Animate in
        setTimeout(function() {
            notification.removeClass('translate-x-full');
        }, 100);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            notification.addClass('translate-x-full');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 5000);
    }
    
    // Script toggle with improved animation
    $('#toggle-script').click(function() {
        var button = $(this);
        var content = $('#script-content');
        
        content.slideToggle(300, function() {
            var isVisible = content.is(':visible');
            button.text(isVisible ? 'Minimize' : 'Maximize');
            button.toggleClass('bg-gray-600', isVisible).toggleClass('bg-gray-700', !isVisible);
        });
    });
    
    // Auto-save form data to localStorage
    var formFields = $('#africa-life-form input, #africa-life-form select, #africa-life-form textarea');
    
    formFields.on('change input', function() {
        var formData = {};
        formFields.each(function() {
            var field = $(this);
            if (field.attr('type') !== 'password' && field.attr('name') !== 'africa_life_nonce') {
                formData[field.attr('name')] = field.val();
            }
        });
        localStorage.setItem('africa_life_form_data', JSON.stringify(formData));
    });
    
    // Restore form data from localStorage
    var savedData = localStorage.getItem('africa_life_form_data');
    if (savedData) {
        try {
            var formData = JSON.parse(savedData);
            $.each(formData, function(name, value) {
                var field = $('[name="' + name + '"]');
                if (field.length && field.attr('type') !== 'password') {
                    field.val(value);
                }
            });
        } catch (e) {
            console.error('Error restoring form data:', e);
        }
    }
    
    // Clear saved data on successful submission
    $(document).on('form-submitted-successfully', function() {
        localStorage.removeItem('africa_life_form_data');
    });
    
    // Enhanced table interactions
    $('.submissions-table tr').hover(
        function() {
            $(this).addClass('bg-gray-700');
        },
        function() {
            $(this).removeClass('bg-gray-700');
        }
    );
});
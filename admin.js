/* Africa Life Plugin Admin JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Enhanced notification system
    function showNotification(message, type, duration) {
        duration = duration || 5000;
        
        var notificationClass = '';
        var icon = '';
        
        switch(type) {
            case 'success':
                notificationClass = 'admin-notification success';
                icon = '✓';
                break;
            case 'error':
                notificationClass = 'admin-notification error';
                icon = '✗';
                break;
            case 'warning':
                notificationClass = 'admin-notification warning';
                icon = '⚠';
                break;
            default:
                notificationClass = 'admin-notification';
                icon = 'ℹ';
        }
        
        var notification = $('<div class="' + notificationClass + ' fixed top-4 right-4 z-50 max-w-md transform translate-x-full transition-transform duration-300">' +
            '<div class="flex items-center">' +
                '<span class="text-lg mr-3">' + icon + '</span>' +
                '<span class="flex-1">' + escapeHtml(message) + '</span>' +
                '<button class="ml-4 text-xl leading-none opacity-50 hover:opacity-100" onclick="$(this).parent().parent().fadeOut(300, function(){ $(this).remove(); })">&times;</button>' +
            '</div>' +
        '</div>');
        
        $('body').append(notification);
        
        // Animate in
        setTimeout(function() {
            notification.removeClass('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(function() {
            notification.addClass('translate-x-full');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, duration);
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
    
    // Enhanced status update handling
    $('.status-select').change(function() {
        var submissionId = $(this).data('submission-id');
        var newStatus = $(this).val();
        var selectElement = $(this);
        var originalValue = selectElement.data('original-value') || selectElement.val();
        
        // Store original value if not already stored
        if (!selectElement.data('original-value')) {
            selectElement.data('original-value', originalValue);
        }
        
        // Show loading state
        selectElement.prop('disabled', true).addClass('opacity-50');
        
        $.ajax({
            url: africa_life_ajax.ajax_url,
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
                    selectElement.addClass('ring-2 ring-green-400');
                    
                    // Update status badge if exists
                    var statusBadge = selectElement.closest('tr').find('.status-badge');
                    if (statusBadge.length) {
                        statusBadge.removeClass('status-pending status-approved status-declined')
                                  .addClass('status-' + newStatus.toLowerCase())
                                  .text(newStatus);
                    }
                    
                    showNotification('Status updated successfully', 'success');
                    
                    setTimeout(function() {
                        selectElement.removeClass('ring-2 ring-green-400');
                    }, 2000);
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
    
    // Enhanced table interactions
    $('.admin-table tbody tr').hover(
        function() {
            $(this).addClass('bg-gray-700');
        },
        function() {
            $(this).removeClass('bg-gray-700');
        }
    );
    
    // Modal handling
    window.showModal = function(title, content, actions) {
        var modal = $('<div class="admin-modal fixed inset-0 z-50 flex items-center justify-center p-4">' +
            '<div class="admin-modal-content max-w-lg w-full max-h-screen overflow-y-auto">' +
                '<div class="p-6">' +
                    '<div class="flex justify-between items-center mb-4">' +
                        '<h3 class="text-lg font-semibold text-yellow-400">' + escapeHtml(title) + '</h3>' +
                        '<button class="modal-close text-gray-400 hover:text-white text-xl">&times;</button>' +
                    '</div>' +
                    '<div class="modal-content mb-6">' + content + '</div>' +
                    '<div class="modal-actions flex justify-end space-x-3">' + (actions || '') + '</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(modal);
        
        // Close modal handlers
        modal.find('.modal-close').click(function() {
            closeModal(modal);
        });
        
        modal.click(function(e) {
            if (e.target === this) {
                closeModal(modal);
            }
        });
        
        // Animate in
        modal.hide().fadeIn(300);
        
        return modal;
    }
    
    window.closeModal = function(modal) {
        if (!modal) {
            $('.admin-modal').fadeOut(300, function() {
                $(this).remove();
            });
        } else {
            modal.fadeOut(300, function() {
                modal.remove();
            });
        }
    }
    
    // Global showNotification function
    window.showNotification = showNotification;
    
    // Form handling with validation
    function handleFormSubmission(form, action, successMessage, onSuccess) {
        form.submit(function(e) {
            e.preventDefault();
            
            var submitBtn = form.find('button[type="submit"]');
            var originalText = submitBtn.text();
            
            // Basic validation
            var isValid = true;
            form.find('[required]').each(function() {
                var field = $(this);
                if (!field.val().trim()) {
                    field.addClass('border-red-500');
                    isValid = false;
                } else {
                    field.removeClass('border-red-500');
                }
            });
            
            if (!isValid) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }
            
            // Show loading state
            submitBtn.prop('disabled', true).html('<div class="loading-spinner inline-block mr-2"></div>Submitting...');
            
            var formData = new FormData(form[0]);
            formData.append('action', action);
            formData.append('nonce', africa_life_ajax.nonce);
            
            $.ajax({
                url: africa_life_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showNotification(successMessage, 'success');
                        if (onSuccess) {
                            onSuccess(response);
                        }
                    } else {
                        showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred while processing the request.', 'error');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    // Enhanced chart configurations
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = '#d1d5db';
        Chart.defaults.backgroundColor = 'rgba(251, 191, 36, 0.1)';
        Chart.defaults.borderColor = '#fbbf24';
    }
    
    // Data table enhancements
    function enhanceDataTable(table) {
        // Add search functionality
        var searchInput = $('<input type="text" placeholder="Search..." class="admin-form-input mb-4">');
        table.before(searchInput);
        
        searchInput.on('keyup', function() {
            var value = $(this).val().toLowerCase();
            table.find('tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Add sorting functionality
        table.find('th').addClass('cursor-pointer hover:bg-gray-600').click(function() {
            var column = $(this).index();
            var table = $(this).closest('table');
            var tbody = table.find('tbody');
            var rows = tbody.find('tr').toArray();
            
            var isAsc = $(this).hasClass('sort-asc');
            
            // Remove all sorting classes
            table.find('th').removeClass('sort-asc sort-desc');
            
            // Add appropriate class
            $(this).addClass(isAsc ? 'sort-desc' : 'sort-asc');
            
            rows.sort(function(a, b) {
                var aVal = $(a).find('td').eq(column).text().trim();
                var bVal = $(b).find('td').eq(column).text().trim();
                
                // Try to parse as numbers
                var aNum = parseFloat(aVal);
                var bNum = parseFloat(bVal);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? bNum - aNum : aNum - bNum;
                } else {
                    return isAsc ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
                }
            });
            
            tbody.empty().append(rows);
        });
    }
    
    // Initialize enhancements for existing tables
    $('.admin-table').each(function() {
        enhanceDataTable($(this));
    });
    
    // Auto-refresh functionality for dashboard
    function setupAutoRefresh() {
        var refreshInterval = 30000; // 30 seconds
        
        if ($('.stats-dashboard').length) {
            setInterval(function() {
                // Only refresh if tab is visible
                if (!document.hidden) {
                    location.reload();
                }
            }, refreshInterval);
        }
    }
    
    setupAutoRefresh();
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl/Cmd + R: Refresh current tab
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 82) {
            e.preventDefault();
            location.reload();
        }
        
        // Escape: Close modals
        if (e.keyCode === 27) {
            $('.admin-modal').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
    
    // Enhanced error handling
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
        showNotification('A JavaScript error occurred. Please refresh the page.', 'error');
    });
    
    // AJAX error handling
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.error('AJAX Error:', xhr.status, thrownError);
        console.error('Response:', xhr.responseText);
        
        if (xhr.status === 403) {
            showNotification('Access denied. Please refresh the page and try again.', 'error');
        } else if (xhr.status === 500) {
            showNotification('Server error occurred. Please try again later.', 'error');
        }
    });
});

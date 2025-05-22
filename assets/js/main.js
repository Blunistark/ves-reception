// School Admin System - Main JavaScript File

// Global utility functions
const Utils = {
    // Show success message
    showSuccess: function(message) {
        this.showMessage(message, 'success');
    },

    // Show error message
    showError: function(message) {
        this.showMessage(message, 'error');
    },

    // Show warning message
    showWarning: function(message) {
        this.showMessage(message, 'warning');
    },

    // Generic message display
    showMessage: function(message, type = 'success') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.message');
        existingMessages.forEach(msg => msg.remove());

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type} fade-in`;
        messageDiv.innerHTML = message;

        // Insert at top of content container
        const contentContainer = document.querySelector('.content-container');
        if (contentContainer) {
            contentContainer.insertBefore(messageDiv, contentContainer.firstChild);
        } else {
            document.body.insertBefore(messageDiv, document.body.firstChild);
        }

        // Auto-hide after 5 seconds
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 300);
        }, 5000);
    },

    // Format phone number
    formatPhone: function(phone) {
        const cleaned = phone.replace(/\D/g, '');
        if (cleaned.length >= 10) {
            return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
        }
        return phone;
    },

    // Validate email
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    // Validate phone
    isValidPhone: function(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10;
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Loading state for buttons
    setButtonLoading: function(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="loading-spinner"></span>Loading...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    },

    // AJAX helper
    ajax: function(url, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        };

        const config = Object.assign(defaultOptions, options);

        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                throw error;
            });
    }
};

// Form validation
const FormValidator = {
    // Validate admission inquiry form
    validateAdmissionForm: function(formData) {
        const errors = {};

        if (!formData.child_name || formData.child_name.trim().length < 2) {
            errors.child_name = 'Child name must be at least 2 characters long';
        }

        if (!formData.parent_name || formData.parent_name.trim().length < 2) {
            errors.parent_name = 'Parent name must be at least 2 characters long';
        }

        if (!formData.phone_number || !Utils.isValidPhone(formData.phone_number)) {
            errors.phone_number = 'Please enter a valid phone number';
        }

        if (formData.parent_email && !Utils.isValidEmail(formData.parent_email)) {
            errors.parent_email = 'Please enter a valid email address';
        }

        if (!formData.desired_class) {
            errors.desired_class = 'Please select a desired class';
        }

        return errors;
    },

    // Validate visitor form
    validateVisitorForm: function(formData) {
        const errors = {};

        if (!formData.visitor_name || formData.visitor_name.trim().length < 2) {
            errors.visitor_name = 'Visitor name must be at least 2 characters long';
        }

        if (!formData.phone_number || !Utils.isValidPhone(formData.phone_number)) {
            errors.phone_number = 'Please enter a valid phone number';
        }

        if (formData.email && !Utils.isValidEmail(formData.email)) {
            errors.email = 'Please enter a valid email address';
        }

        if (!formData.purpose) {
            errors.purpose = 'Please select a purpose for the visit';
        }

        if (!formData.visit_date) {
            errors.visit_date = 'Please select a visit date';
        } else {
            const visitDate = new Date(formData.visit_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (visitDate < today) {
                const confirmPastDate = confirm('The selected date is in the past. Do you want to continue?');
                if (!confirmPastDate) {
                    errors.visit_date = 'Please select a future date or confirm past date';
                }
            }
        }

        return errors;
    },

    // Display form errors
    showFormErrors: function(errors) {
        // Clear existing errors
        document.querySelectorAll('.form-error').forEach(error => error.remove());
        document.querySelectorAll('.form-input.error').forEach(input => {
            input.classList.remove('error');
        });

        // Show new errors
        Object.keys(errors).forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.style.color = 'var(--error-text)';
                errorDiv.style.fontSize = '14px';
                errorDiv.style.marginTop = '4px';
                errorDiv.textContent = errors[fieldName];
                
                field.parentNode.appendChild(errorDiv);
            }
        });
    }
};

// Modal functionality
const Modal = {
    show: function(title, content, options = {}) {
        // Remove existing modal
        const existingModal = document.getElementById('modal');
        if (existingModal) {
            existingModal.remove();
        }

        // Create modal
        const modal = document.createElement('div');
        modal.id = 'modal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                    <button class="modal-close" onclick="Modal.hide()">&times;</button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${options.showFooter !== false ? `
                <div class="modal-footer">
                    ${options.footerButtons || '<button class="btn-secondary" onclick="Modal.hide()">Close</button>'}
                </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                Modal.hide();
            }
        });

        // Add CSS if not already present
        if (!document.getElementById('modal-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-styles';
            style.textContent = `
                .modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                    animation: fadeIn 0.3s ease-out;
                }
                .modal-content {
                    background: var(--secondary-bg);
                    border-radius: 12px;
                    max-width: 500px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    border: 1px solid var(--border-color);
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                }
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px 24px;
                    border-bottom: 1px solid var(--border-color);
                }
                .modal-title {
                    margin: 0;
                    font-size: 18px;
                    font-weight: 600;
                    color: var(--text-primary);
                }
                .modal-close {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: var(--text-secondary);
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                }
                .modal-close:hover {
                    background-color: var(--accent-bg);
                    color: var(--text-primary);
                }
                .modal-body {
                    padding: 24px;
                }
                .modal-footer {
                    padding: 16px 24px;
                    border-top: 1px solid var(--border-color);
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
            `;
            document.head.appendChild(style);
        }

        return modal;
    },

    hide: function() {
        const modal = document.getElementById('modal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 300);
        }
    }
};

// Detail view functions
function showAdmissionDetails(id) {
    Utils.ajax('../ajax_handler.php', {
        method: 'POST',
        body: `action=get_admission_details&id=${id}`
    })
    .then(data => {
        if (data.success) {
            const admission = data.data;
            const content = `
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Child Name:</strong> ${admission.child_name}
                    </div>
                    <div class="detail-item">
                        <strong>Parent Name:</strong> ${admission.parent_name}
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong> ${admission.parent_email || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Phone:</strong> ${admission.phone_number}
                    </div>
                    <div class="detail-item">
                        <strong>Desired Class:</strong> ${admission.desired_class}
                    </div>
                    <div class="detail-item">
                        <strong>Address:</strong> ${admission.address || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Requirements:</strong> ${admission.specific_requirements || 'None'}
                    </div>
                    <div class="detail-item">
                        <strong>Notes:</strong> ${admission.parent_notes || 'None'}
                    </div>
                    <div class="detail-item">
                        <strong>Inquiry Date:</strong> ${new Date(admission.inquiry_date).toLocaleDateString()}
                    </div>
                    <div class="detail-item">
                        <strong>Status:</strong> ${admission.status.charAt(0).toUpperCase() + admission.status.slice(1)}
                    </div>
                </div>
                <style>
                    .details-grid {
                        display: grid;
                        gap: 12px;
                    }
                    .detail-item {
                        padding: 8px 0;
                        border-bottom: 1px solid var(--accent-bg);
                    }
                    .detail-item:last-child {
                        border-bottom: none;
                    }
                </style>
            `;
            
            Modal.show('Admission Inquiry Details', content);
        } else {
            Utils.showError(data.message || 'Error loading admission details');
        }
    })
    .catch(error => {
        Utils.showError('Error loading admission details');
        console.error('Error:', error);
    });
}

function showVisitorDetails(id) {
    Utils.ajax('../ajax_handler.php', {
        method: 'POST',
        body: `action=get_visitor_details&id=${id}`
    })
    .then(data => {
        if (data.success) {
            const visitor = data.data;
            const content = `
                <div class="details-grid">
                    <div class="detail-item">
                        <strong>Name:</strong> ${visitor.visitor_name}
                    </div>
                    <div class="detail-item">
                        <strong>Email:</strong> ${visitor.email || 'N/A'}
                    </div>
                    <div class="detail-item">
                        <strong>Phone:</strong> ${visitor.phone_number}
                    </div>
                    <div class="detail-item">
                        <strong>Purpose:</strong> ${visitor.purpose}
                    </div>
                    <div class="detail-item">
                        <strong>Visit Date:</strong> ${new Date(visitor.visit_date).toLocaleDateString()}
                    </div>
                    <div class="detail-item">
                        <strong>Status:</strong> ${visitor.status.charAt(0).toUpperCase() + visitor.status.slice(1)}
                    </div>
                    <div class="detail-item">
                        <strong>Created:</strong> ${new Date(visitor.created_at).toLocaleString()}
                    </div>
                </div>
            `;
            
            Modal.show('Visitor Details', content);
        } else {
            Utils.showError(data.message || 'Error loading visitor details');
        }
    })
    .catch(error => {
        Utils.showError('Error loading visitor details');
        console.error('Error:', error);
    });
}

// Phone number formatting
function setupPhoneFormatting() {
    document.querySelectorAll('input[name="phone_number"], input[name="contact_number"]').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 10) {
                value = value.substring(0, 10);
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            } else if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})/, '$1-$2');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})/, '$1-');
            }
            e.target.value = value;
        });
    });
}

// Auto-hide messages
function setupAutoHideMessages() {
    document.querySelectorAll('.message').forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
}

// Search functionality
function setupSearchForms() {
    document.querySelectorAll('input[name*="_search"]').forEach(input => {
        const debouncedSubmit = Utils.debounce(() => {
            input.form.submit();
        }, 500);
        
        input.addEventListener('input', debouncedSubmit);
    });
}

// Character counter for textareas
function setupCharacterCounters() {
    document.querySelectorAll('textarea[name="parent_notes"]').forEach(textarea => {
        const maxLength = 500;
        
        // Create counter element
        const counter = document.createElement('div');
        counter.className = 'character-counter';
        counter.style.textAlign = 'right';
        counter.style.fontSize = '12px';
        counter.style.color = 'var(--text-secondary)';
        counter.style.marginTop = '4px';
        
        textarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length}/${maxLength} characters`;
            
            if (remaining < 0) {
                textarea.value = textarea.value.substring(0, maxLength);
                counter.style.color = 'var(--error-text)';
                Utils.showWarning('Parent notes cannot exceed 500 characters.');
            } else if (remaining < 50) {
                counter.style.color = 'var(--warning-text)';
            } else {
                counter.style.color = 'var(--text-secondary)';
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
    });
}

// Date input restrictions
function setupDateRestrictions() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelectorAll('input[name="visit_date"]').forEach(input => {
        input.setAttribute('min', today);
    });
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupAutoHideMessages();
    setupPhoneFormatting();
    setupSearchForms();
    setupCharacterCounters();
    setupDateRestrictions();
    
    // Handle keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            Modal.hide();
        }
    });
});

// Export functions for global access
window.Utils = Utils;
window.FormValidator = FormValidator;
window.Modal = Modal;
window.showAdmissionDetails = showAdmissionDetails;
window.showVisitorDetails = showVisitorDetails;
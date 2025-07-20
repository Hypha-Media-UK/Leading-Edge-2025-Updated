// Shared form validation utilities
export class FormValidation {
    
    // Validate a single field
    static validateField(field, rules = {}) {
        const value = field.value.trim();
        const fieldName = field.name || field.id;
        
        // Required validation
        if (rules.required && !value) {
            return `${this.capitalizeFirst(fieldName)} is required`;
        }
        
        // Email validation
        if (rules.email && value && !this.isValidEmail(value)) {
            return 'Please enter a valid email address';
        }
        
        // Min length validation
        if (rules.minLength && value && value.length < rules.minLength) {
            return `${this.capitalizeFirst(fieldName)} must be at least ${rules.minLength} characters`;
        }
        
        return null; // No error
    }
    
    // Validate entire form
    static validateForm(form, fieldRules = {}) {
        let isValid = true;
        const errors = {};
        
        // Get all form fields
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            const fieldName = field.name || field.id;
            const rules = fieldRules[fieldName] || {};
            
            // Skip validation if no rules defined
            if (Object.keys(rules).length === 0) return;
            
            const error = this.validateField(field, rules);
            if (error) {
                errors[fieldName] = error;
                isValid = false;
                this.showFieldError(field, error);
            } else {
                this.hideFieldError(field);
            }
        });
        
        return { isValid, errors };
    }
    
    // Show field error
    static showFieldError(field, message) {
        const errorElement = this.getErrorElement(field);
        
        field.style.borderColor = '#e53e3e';
        field.classList.add('error');
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }
    
    // Hide field error
    static hideFieldError(field) {
        const errorElement = this.getErrorElement(field);
        
        field.style.borderColor = '';
        field.classList.remove('error');
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('show');
        }
    }
    
    // Get or create error element for field
    static getErrorElement(field) {
        const fieldContainer = field.closest('.form-group, .form-field');
        if (!fieldContainer) return null;
        
        let errorElement = fieldContainer.querySelector('.error-message');
        
        // Create error element if it doesn't exist
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            fieldContainer.appendChild(errorElement);
        }
        
        return errorElement;
    }
    
    // Clear all form errors
    static clearFormErrors(form) {
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => this.hideFieldError(field));
    }
    
    // Email validation helper
    static isValidEmail(email) {
        return /^\S+@\S+\.\S+$/.test(email);
    }
    
    // Capitalize first letter helper
    static capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // Setup real-time validation
    static setupRealTimeValidation(form, fieldRules = {}) {
        const fields = form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            const fieldName = field.name || field.id;
            const rules = fieldRules[fieldName];
            
            if (!rules) return;
            
            // Validate on blur
            field.addEventListener('blur', () => {
                const error = this.validateField(field, rules);
                if (error) {
                    this.showFieldError(field, error);
                } else {
                    this.hideFieldError(field);
                }
            });
            
            // Clear error on input
            field.addEventListener('input', () => {
                if (field.classList.contains('error')) {
                    this.hideFieldError(field);
                }
            });
        });
    }
}

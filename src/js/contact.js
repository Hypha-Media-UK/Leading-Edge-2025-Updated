// Contact page specific JavaScript

// Contact page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Form validation and submission
    const contactForm = document.querySelector('.contact-form');
    const formSuccess = document.querySelector('.form-success');
    const formAlert = document.querySelector('.form-alert');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (validateContactForm()) {
                // Simulate form submission
                setTimeout(() => {
                    contactForm.style.display = 'none';
                    formSuccess.style.display = 'block';
                }, 1000);
            } else {
                formAlert.style.display = 'block';
                setTimeout(() => {
                    formAlert.style.display = 'none';
                }, 3000);
            }
        });
    }
    
    // Reset form functionality
    const resetBtn = document.querySelector('.reset-form-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetContactForm();
        });
    }
    
    // Form validation function
    function validateContactForm() {
        let isValid = true;
        const requiredFields = ['name', 'email', 'phone', 'service', 'date', 'time'];
        
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const errorMessage = field.parentNode.querySelector('.error-message');
            
            if (!field.value.trim()) {
                showFieldError(field, errorMessage, `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`);
                isValid = false;
            } else if (fieldName === 'email' && !isValidEmail(field.value)) {
                showFieldError(field, errorMessage, 'Please enter a valid email address');
                isValid = false;
            } else {
                hideFieldError(field, errorMessage);
            }
        });
        
        return isValid;
    }
    
    // Show field error
    function showFieldError(field, errorElement, message) {
        field.style.borderColor = '#C62828';
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
    
    // Hide field error
    function hideFieldError(field, errorElement) {
        field.style.borderColor = '';
        errorElement.textContent = '';
        errorElement.classList.remove('show');
    }
    
    // Email validation
    function isValidEmail(email) {
        return /^\S+@\S+\.\S+$/.test(email);
    }
    
    // Reset form function
    function resetContactForm() {
        if (contactForm) {
            contactForm.reset();
            contactForm.style.display = 'block';
        }
        if (formSuccess) {
            formSuccess.style.display = 'none';
        }
        if (formAlert) {
            formAlert.style.display = 'none';
        }
        
        // Clear all error states
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(error => {
            error.classList.remove('show');
            error.textContent = '';
        });
        
        const formFields = document.querySelectorAll('.form-field input, .form-field select, .form-field textarea');
        formFields.forEach(field => {
            field.style.borderColor = '';
        });
    }
    
    // GSAP animations for contact page
    if (typeof gsap !== 'undefined') {
        
        // Animate contact cards
        gsap.utils.toArray('.contact-card').forEach((card, index) => {
            gsap.fromTo(card,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.2,
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate FAQ items
        gsap.utils.toArray('.faq-item').forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate form container
        const formContainer = document.querySelector('.contact-form-container');
        if (formContainer) {
            gsap.fromTo(formContainer,
                { opacity: 0, x: -50 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: formContainer,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }
});

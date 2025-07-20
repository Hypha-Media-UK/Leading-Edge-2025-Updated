// Careers page specific JavaScript - Essential only

// Careers page functionality - static version
document.addEventListener('DOMContentLoaded', function() {
    
    // Form submission handling - keep essential functionality
    const careersForm = document.getElementById('careers-form');
    if (careersForm) {
        careersForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic form validation
            const requiredFields = careersForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (isValid) {
                // Here you would typically send the form data to your server
                alert('Thank you for your application! We will be in touch soon.');
                careersForm.reset();
            } else {
                alert('Please fill in all required fields.');
            }
        });
    }
    
    // All modal interactions and animations removed for optimized static version
    // Ready for future interaction layer implementation
});

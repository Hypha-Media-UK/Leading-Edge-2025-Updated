// Single JavaScript Entry Point - Optimized Architecture
import { FormValidation } from './utils/form-validation.js';

// Component initialization system
class ComponentManager {
    constructor() {
        this.components = new Map();
        this.initialized = false;
    }

    // Register a component
    register(selector, initFunction) {
        this.components.set(selector, initFunction);
    }

    // Initialize all components found on the page
    init() {
        if (this.initialized) return;

        this.components.forEach((initFunction, selector) => {
            const elements = document.querySelectorAll(selector);
            if (elements.length > 0) {
                console.log(`Initializing component: ${selector}`);
                initFunction(elements);
            }
        });

        this.initialized = true;
    }
}

// Create global component manager
const componentManager = new ComponentManager();

// Register all components
componentManager.register('.contact-form', (elements) => {
    elements.forEach(form => {
        const formSuccess = document.querySelector('.form-success');
        const formAlert = document.querySelector('.form-alert');
        
        const validationRules = {
            name: { required: true },
            email: { required: true, email: true },
            phone: { required: true },
            service: { required: true },
            date: { required: true },
            time: { required: true }
        };

        FormValidation.setupRealTimeValidation(form, validationRules);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const { isValid } = FormValidation.validateForm(form, validationRules);
            
            if (isValid) {
                setTimeout(() => {
                    form.style.display = 'none';
                    if (formSuccess) formSuccess.style.display = 'block';
                }, 1000);
            } else {
                if (formAlert) {
                    formAlert.style.display = 'block';
                    setTimeout(() => {
                        formAlert.style.display = 'none';
                    }, 3000);
                }
            }
        });

        // Reset form functionality
        const resetBtn = document.querySelector('.reset-form-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.reset();
                form.style.display = 'block';
                FormValidation.clearFormErrors(form);
                if (formSuccess) formSuccess.style.display = 'none';
                if (formAlert) formAlert.style.display = 'none';
            });
        }
    });
});

componentManager.register('#careers-form', (elements) => {
    elements.forEach(form => {
        const validationRules = {
            name: { required: true },
            email: { required: true, email: true },
            phone: { required: true },
            position: { required: true },
            experience: { required: true },
            message: { required: true, minLength: 10 }
        };

        FormValidation.setupRealTimeValidation(form, validationRules);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const { isValid } = FormValidation.validateForm(form, validationRules);
            
            if (isValid) {
                alert('Thank you for your application! We will be in touch soon.');
                form.reset();
                FormValidation.clearFormErrors(form);
            } else {
                alert('Please fill in all required fields correctly.');
            }
        });
    });
});

componentManager.register('.testimonials-slider', (elements) => {
    elements.forEach(slider => {
        const testimonialWrappers = slider.querySelectorAll('.testimonial-wrapper');
        const prevButton = slider.querySelector('.arrow.prev');
        const nextButton = slider.querySelector('.arrow.next');
        
        if (testimonialWrappers.length <= 1) return;
        
        let currentTestimonialIndex = 0;
        let testimonialInterval = null;
        const rotationInterval = 6000;
        let isTransitioning = false;
        
        function showTestimonial(index) {
            if (isTransitioning || testimonialWrappers.length === 0) return;
            
            isTransitioning = true;
            
            const currentWrapper = testimonialWrappers[currentTestimonialIndex];
            currentWrapper.style.opacity = '0';
            currentWrapper.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                currentTestimonialIndex = index;
                const newWrapper = testimonialWrappers[currentTestimonialIndex];
                newWrapper.style.transform = 'translateY(20px)';
                newWrapper.style.opacity = '1';
                
                setTimeout(() => {
                    newWrapper.style.transform = 'translateY(0)';
                    isTransitioning = false;
                }, 50);
            }, 500);
        }
        
        function nextTestimonial() {
            if (isTransitioning) return;
            const nextIndex = (currentTestimonialIndex + 1) % testimonialWrappers.length;
            showTestimonial(nextIndex);
        }
        
        function previousTestimonial() {
            if (isTransitioning) return;
            const prevIndex = (currentTestimonialIndex - 1 + testimonialWrappers.length) % testimonialWrappers.length;
            showTestimonial(prevIndex);
        }
        
        function startAutoRotation() {
            if (testimonialInterval) clearInterval(testimonialInterval);
            testimonialInterval = setInterval(nextTestimonial, rotationInterval);
        }
        
        function stopAutoRotation() {
            if (testimonialInterval) {
                clearInterval(testimonialInterval);
                testimonialInterval = null;
            }
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', () => {
                stopAutoRotation();
                nextTestimonial();
                setTimeout(startAutoRotation, 3000);
            });
        }
        
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                stopAutoRotation();
                previousTestimonial();
                setTimeout(startAutoRotation, 3000);
            });
        }
        
        slider.addEventListener('mouseenter', stopAutoRotation);
        slider.addEventListener('mouseleave', startAutoRotation);
        
        startAutoRotation();
        
        window.addEventListener('beforeunload', stopAutoRotation);
    });
});

// Header functionality
componentManager.register('#header', (elements) => {
    const header = elements[0];
    const menuToggle = document.getElementById('menu-toggle');
    const hamburger = document.getElementById('hamburger');
    const nav = document.getElementById('nav');

    // Header scroll effect with throttling
    let ticking = false;
    
    function updateHeader() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        ticking = false;
    }
    
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    });

    // Mobile menu toggle
    if (menuToggle && nav && hamburger) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close menu when clicking on nav links
        const navLinks = nav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                nav.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!nav.contains(event.target) && !menuToggle.contains(event.target)) {
                nav.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    }
});

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing application components...');
    componentManager.init();
});

// Export for potential external use
export { componentManager, FormValidation };

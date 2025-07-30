// Single JavaScript Entry Point - Optimized Architecture
import { FormValidation } from './utils/form-validation.js';
import { scrollTriggerManager } from './animations/core/scroll-trigger.js';
import { globalAnimations } from './animations/global-animations.js';

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

// Team member modal functionality
componentManager.register('.team-member', (elements) => {
    elements.forEach(member => {
        const modalTarget = member.getAttribute('data-modal-target');
        
        member.addEventListener('click', function() {
            const modal = document.getElementById(modalTarget);
            if (modal) {
                openModal(modal);
            }
        });
    });
});

// Modal management functionality
componentManager.register('.modal-overlay', (elements) => {
    elements.forEach(modal => {
        // Close button functionality
        const closeButton = modal.querySelector('.modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                closeModal(modal);
            });
        }
        
        // Overlay click to close (but not when clicking modal content)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
});

// Modal utility functions
function openModal(modal) {
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    
    // Trigger reflow to ensure display change is applied
    modal.offsetHeight;
    
    // Add active class for animation
    modal.classList.add('active');
    
    // Focus management for accessibility
    const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
        firstFocusable.focus();
    }
    
    // Store the previously focused element (store the actual element, not as a selector)
    modal.previousFocusElement = document.activeElement;
}

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.classList.remove('modal-open');
    
    // Wait for animation to complete before hiding
    setTimeout(() => {
        modal.style.display = 'none';
        
        // Restore focus to previously focused element
        if (modal.previousFocusElement && modal.previousFocusElement.focus) {
            modal.previousFocusElement.focus();
        }
    }, 300);
}

// Note: Flip card functionality is now handled inline in the offers-grid.twig template
// to ensure compatibility and avoid module loading issues

// Global ESC key handler for modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            closeModal(activeModal);
        }
    }
});

// Header functionality
// Services tabs functionality - Simplified and reliable
componentManager.register('.services-tabs', (elements) => {
    elements.forEach(servicesSection => {
        const mainTabs = servicesSection.querySelectorAll('.tabs-container .tab');
        const subTabsWrappers = servicesSection.querySelectorAll('.sub-tabs-wrapper');
        const subTabs = servicesSection.querySelectorAll('.sub-tab');
        const contentSections = servicesSection.querySelectorAll('.service-content-section');

        // Initialize - show first main tab's content
        function initializeTabs() {
            // Hide all sub-tabs wrappers and content sections
            subTabsWrappers.forEach(wrapper => {
                wrapper.classList.add('hidden');
                wrapper.classList.remove('visible');
            });
            
            contentSections.forEach(section => {
                section.classList.add('hidden');
                section.classList.remove('visible');
            });

            // Show first main tab's sub-tabs and first content
            if (mainTabs.length > 0) {
                const firstMainTab = mainTabs[0];
                const firstMainTabId = firstMainTab.getAttribute('data-tab');
                
                // Show first sub-tabs wrapper
                const firstSubTabsWrapper = servicesSection.querySelector(`[data-main-tab="${firstMainTabId}"]`);
                if (firstSubTabsWrapper) {
                    firstSubTabsWrapper.classList.remove('hidden');
                    firstSubTabsWrapper.classList.add('visible');
                    
                    // Show first content section
                    const firstSubTab = firstSubTabsWrapper.querySelector('.sub-tab');
                    if (firstSubTab) {
                        const firstContentId = firstSubTab.getAttribute('data-tab');
                        const firstContent = servicesSection.querySelector(`[data-tab="${firstContentId}"].service-content-section`);
                        if (firstContent) {
                            firstContent.classList.remove('hidden');
                            firstContent.classList.add('visible');
                        }
                    }
                }
            }
        }

        // Show content section
        function showContentSection(targetTabId) {
            contentSections.forEach(section => {
                if (section.getAttribute('data-tab') === targetTabId) {
                    section.classList.remove('hidden');
                    section.classList.add('visible');
                } else {
                    section.classList.add('hidden');
                    section.classList.remove('visible');
                }
            });
        }

        // Show sub-tabs wrapper
        function showSubTabsWrapper(targetMainTabId) {
            subTabsWrappers.forEach(wrapper => {
                if (wrapper.getAttribute('data-main-tab') === targetMainTabId) {
                    wrapper.classList.remove('hidden');
                    wrapper.classList.add('visible');
                } else {
                    wrapper.classList.add('hidden');
                    wrapper.classList.remove('visible');
                }
            });
            
            return servicesSection.querySelector(`[data-main-tab="${targetMainTabId}"]`);
        }

        // Main tab click handlers
        mainTabs.forEach(mainTab => {
            mainTab.addEventListener('click', function() {
                const targetTabId = this.getAttribute('data-tab');
                
                // Update main tab active states
                mainTabs.forEach(tab => tab.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding sub-tabs wrapper
                const targetSubTabsWrapper = showSubTabsWrapper(targetTabId);
                if (targetSubTabsWrapper) {
                    // Reset sub-tabs - activate first one
                    const subTabsInWrapper = targetSubTabsWrapper.querySelectorAll('.sub-tab');
                    subTabsInWrapper.forEach((subTab, index) => {
                        if (index === 0) {
                            subTab.classList.add('active');
                            // Show first content section for this main tab
                            const firstContentId = subTab.getAttribute('data-tab');
                            showContentSection(firstContentId);
                        } else {
                            subTab.classList.remove('active');
                        }
                    });
                }
            });
        });

        // Sub-tab click handlers
        subTabs.forEach(subTab => {
            subTab.addEventListener('click', function() {
                const targetTabId = this.getAttribute('data-tab');
                const parentWrapper = this.closest('.sub-tabs-wrapper');
                
                // Update sub-tab active states within this wrapper
                if (parentWrapper) {
                    const siblingSubTabs = parentWrapper.querySelectorAll('.sub-tab');
                    siblingSubTabs.forEach(tab => tab.classList.remove('active'));
                }
                this.classList.add('active');
                
                // Show corresponding content section
                showContentSection(targetTabId);
            });
        });

        // Initialize the tabs
        initializeTabs();
    });
});

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
    
    // Check if we're on the services page
    const isServicesPage = document.body.classList.contains('services-detail') || 
                          document.querySelector('.services-detail-page') !== null;
    
    if (!isServicesPage) {
        // Initialize ScrollTrigger and animations only if NOT on services page
        scrollTriggerManager.init();
        globalAnimations.init();
    } else {
        console.log('Services page detected - GSAP animations disabled to prevent conflicts');
    }
    
    // Always initialize components
    componentManager.init();
});

// Export for potential external use
export { componentManager, FormValidation, scrollTriggerManager, globalAnimations };

// Careers page specific JavaScript

// Careers page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Position modal functionality
    const positionCards = document.querySelectorAll('.position-card');
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    let currentModal = null;
    
    console.log('Position cards found:', positionCards.length);
    console.log('View Details buttons found:', viewDetailsButtons.length);
    
    // Open modal when "View Details" is clicked
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('View Details button clicked!', this);
            const positionCard = this.closest('.position-card');
            console.log('Position card found:', positionCard);
            const modalTarget = positionCard.getAttribute('data-modal-target');
            console.log('Modal target:', modalTarget);
            showPositionModal(modalTarget);
        });
    });
    
    // Also handle clicks on the position card itself
    positionCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Only trigger if not clicking the button
            if (!e.target.closest('.view-details-btn')) {
                e.preventDefault();
                const modalTarget = this.getAttribute('data-modal-target');
                showPositionModal(modalTarget);
            }
        });
    });
    
    // Close modal functionality for all modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close')) {
            closeModal();
        }
        
        if (e.target.classList.contains('modal-overlay')) {
            closeModal();
        }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeModal();
        }
    });
    
    function showPositionModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }

        console.log('Showing modal:', modalId);
        
        // Hide any currently open modal
        if (currentModal) {
            currentModal.style.display = 'none';
            currentModal.classList.remove('active');
        }

        // Show the new modal
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.classList.add('modal-open');
        
        // Store reference to current modal
        currentModal = modal;
        
        // Handle apply button click
        const applyBtn = modal.querySelector('.apply-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal();
                
                // Scroll to application form
                const applicationForm = document.getElementById('application-form');
                if (applicationForm) {
                    applicationForm.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
        
        // Focus management for accessibility
        modal.focus();
    }
    
    function closeModal() {
        if (currentModal) {
            currentModal.style.display = 'none';
            currentModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            currentModal = null;
        }
    }
    
    // Form submission handling
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
    
    // GSAP animations for careers page
    if (typeof gsap !== 'undefined') {
        
        // Animate page header
        const pageHeader = document.querySelector('.page-header');
        if (pageHeader) {
            gsap.fromTo(pageHeader.querySelector('h1'),
                { opacity: 0, y: 30 },
                { opacity: 1, y: 0, duration: 1, delay: 0.2 }
            );
            
            gsap.fromTo(pageHeader.querySelector('.separator'),
                { opacity: 0, scaleX: 0 },
                { opacity: 1, scaleX: 1, duration: 0.8, delay: 0.5 }
            );
            
            gsap.fromTo(pageHeader.querySelector('p'),
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.8, delay: 0.7 }
            );
        }

        // Animate benefit items
        gsap.utils.toArray('.benefit-item').forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, y: 50 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    delay: index * 0.2,
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate position cards
        gsap.utils.toArray('.position-card').forEach((card, index) => {
            gsap.fromTo(card,
                { opacity: 0, y: 30, scale: 0.95 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    delay: index * 0.15,
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate application form
        const applicationForm = document.querySelector('.application-form-container');
        if (applicationForm) {
            gsap.fromTo(applicationForm,
                { opacity: 0, y: 40 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: applicationForm,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }

        // Animate culture section
        const cultureContent = document.querySelector('.culture-content');
        if (cultureContent) {
            gsap.fromTo(cultureContent.querySelector('.culture-text'),
                { opacity: 0, x: -50 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: cultureContent,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
            
            gsap.fromTo(cultureContent.querySelector('.culture-image'),
                { opacity: 0, x: 50 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    delay: 0.3,
                    scrollTrigger: {
                        trigger: cultureContent,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }

        // Add hover animations for position cards
        gsap.utils.toArray('.position-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    y: -5,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });

            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    y: 0,
                    duration: 0.3,
                    ease: "power2.out"
                });
            });
        });
    }
});

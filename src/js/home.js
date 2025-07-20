// Home page specific JavaScript
import { AnimationUtils } from './utils/animations.js';

// Home page specific functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Testimonials slider functionality
    const testimonialsSlider = document.querySelector('.testimonials-slider');
    if (testimonialsSlider) {
        const testimonialWrappers = testimonialsSlider.querySelectorAll('.testimonial-wrapper');
        const prevButton = testimonialsSlider.querySelector('.arrow.prev');
        const nextButton = testimonialsSlider.querySelector('.arrow.next');
        
        let currentTestimonialIndex = 0;
        let testimonialInterval = null;
        const rotationInterval = 6000; // 6 seconds
        let isTransitioning = false;
        
        // Function to show testimonial at specific index
        function showTestimonial(index) {
            if (isTransitioning || testimonialWrappers.length === 0) return;
            
            isTransitioning = true;
            
            // Hide current testimonial
            const currentWrapper = testimonialWrappers[currentTestimonialIndex];
            currentWrapper.style.opacity = '0';
            currentWrapper.style.transform = 'translateY(-20px)';
            
            // After fade out, show new testimonial
            setTimeout(() => {
                currentTestimonialIndex = index;
                const newWrapper = testimonialWrappers[currentTestimonialIndex];
                newWrapper.style.transform = 'translateY(20px)';
                newWrapper.style.opacity = '1';
                
                // Animate in
                setTimeout(() => {
                    newWrapper.style.transform = 'translateY(0)';
                    isTransitioning = false;
                }, 50);
            }, 500);
        }
        
        // Function to go to next testimonial
        function nextTestimonial() {
            if (isTransitioning) return;
            const nextIndex = (currentTestimonialIndex + 1) % testimonialWrappers.length;
            showTestimonial(nextIndex);
        }
        
        // Function to go to previous testimonial
        function previousTestimonial() {
            if (isTransitioning) return;
            const prevIndex = (currentTestimonialIndex - 1 + testimonialWrappers.length) % testimonialWrappers.length;
            showTestimonial(prevIndex);
        }
        
        // Start auto-rotation
        function startAutoRotation() {
            if (testimonialInterval) clearInterval(testimonialInterval);
            testimonialInterval = setInterval(nextTestimonial, rotationInterval);
        }
        
        // Stop auto-rotation
        function stopAutoRotation() {
            if (testimonialInterval) {
                clearInterval(testimonialInterval);
                testimonialInterval = null;
            }
        }
        
        // Event listeners for navigation buttons
        if (nextButton) {
            nextButton.addEventListener('click', () => {
                stopAutoRotation();
                nextTestimonial();
                // Restart auto-rotation after user interaction
                setTimeout(startAutoRotation, 3000);
            });
        }
        
        if (prevButton) {
            prevButton.addEventListener('click', () => {
                stopAutoRotation();
                previousTestimonial();
                // Restart auto-rotation after user interaction
                setTimeout(startAutoRotation, 3000);
            });
        }
        
        // Pause auto-rotation on hover
        testimonialsSlider.addEventListener('mouseenter', stopAutoRotation);
        testimonialsSlider.addEventListener('mouseleave', startAutoRotation);
        
        // Initialize auto-rotation
        if (testimonialWrappers.length > 1) {
            startAutoRotation();
        }
        
        // Clean up on page unload
        window.addEventListener('beforeunload', stopAutoRotation);
    }

    // Use shared animation utilities
    AnimationUtils.animateProductGrid('.product-item');
    AnimationUtils.animateCards('.instagram-item', { 
        y: 20, 
        duration: 0.5, 
        stagger: 0.05 
    });
    AnimationUtils.animateSection('.call-to-action .cta-content', { 
        trigger: 'top 80%' 
    });
});

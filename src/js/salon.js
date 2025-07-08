// Salon page specific JavaScript

// Salon page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // GSAP animations for salon page
    if (typeof gsap !== 'undefined') {
        
        // Animate philosophy cards
        gsap.utils.toArray('.feature-card').forEach((card, index) => {
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

        // Animate gallery items
        gsap.utils.toArray('.gallery-item').forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, scale: 0.8 },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.8,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate about section
        const aboutImage = document.querySelector('.about-image');
        const aboutText = document.querySelector('.about-text');
        
        if (aboutImage) {
            gsap.fromTo(aboutImage,
                { opacity: 0, x: -50 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: aboutImage,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
        
        if (aboutText) {
            gsap.fromTo(aboutText,
                { opacity: 0, x: 50 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    delay: 0.3,
                    scrollTrigger: {
                        trigger: aboutText,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }
});

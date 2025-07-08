// Offers page specific JavaScript

// Offers page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Flip card functionality
    const flipButtons = document.querySelectorAll('[data-flip-card]');
    
    flipButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const flipCard = this.closest('.flip-card');
            const flipCardInner = flipCard.querySelector('.flip-card-inner');
            
            flipCardInner.classList.toggle('flipped');
        });
    });
    
    // GSAP animations for offers page
    if (typeof gsap !== 'undefined') {
        
        // Animate promotion cards
        gsap.utils.toArray('.promotion-item').forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, y: 50, scale: 0.9 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.8,
                    delay: index * 0.2,
                    ease: "back.out(1.7)",
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate gift voucher section
        const giftVoucherSection = document.querySelector('.gift-voucher-section');
        if (giftVoucherSection) {
            gsap.fromTo(giftVoucherSection.querySelector('.gift-voucher-content'),
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: giftVoucherSection,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }

        // Animate gift voucher CTA in header
        const giftVoucherCTA = document.querySelector('.gift-voucher-cta');
        if (giftVoucherCTA) {
            gsap.fromTo(giftVoucherCTA,
                { opacity: 0, scale: 0.8 },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.6,
                    delay: 0.5,
                    ease: "back.out(1.7)"
                }
            );
        }

        // Add hover animations for promotion cards
        gsap.utils.toArray('.promotion-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    y: -10,
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

        // Animate terms toggle icon rotation
        gsap.utils.toArray('.terms-toggle').forEach(button => {
            const icon = button.querySelector('i');
            
            button.addEventListener('click', () => {
                gsap.to(icon, {
                    rotation: "+=180",
                    duration: 0.3,
                    ease: "power2.inOut"
                });
            });
        });

        // Animate flip back button
        gsap.utils.toArray('.flip-back-btn').forEach(button => {
            const icon = button.querySelector('i');
            
            button.addEventListener('mouseenter', () => {
                gsap.to(icon, {
                    x: -5,
                    duration: 0.2,
                    ease: "power2.out"
                });
            });

            button.addEventListener('mouseleave', () => {
                gsap.to(icon, {
                    x: 0,
                    duration: 0.2,
                    ease: "power2.out"
                });
            });
        });
    }
});

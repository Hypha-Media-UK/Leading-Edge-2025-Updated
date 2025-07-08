// Main JavaScript functionality

// Header scroll effect and mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('header');
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

// GSAP animations (if GSAP is loaded)
if (typeof gsap !== 'undefined') {
    // Register ScrollTrigger plugin
    gsap.registerPlugin(ScrollTrigger);

    // Check if user prefers reduced motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    
    if (!prefersReducedMotion) {
        // Optimize ScrollTrigger settings for better performance
        ScrollTrigger.config({
            limitCallbacks: true,
            syncInterval: 150
        });

        // Hero text animation (no ScrollTrigger needed)
        const heroContent = document.querySelector('.hero-content');
        if (heroContent) {
            const tl = gsap.timeline();
            
            if (heroContent.querySelector('h1')) {
                tl.fromTo(heroContent.querySelector('h1'),
                    { opacity: 0, y: 30 },
                    { opacity: 1, y: 0, duration: 1, delay: 0.5 }
                );
            }
            
            if (heroContent.querySelector('p')) {
                tl.fromTo(heroContent.querySelector('p'),
                    { opacity: 0, y: 20 },
                    { opacity: 1, y: 0, duration: 1, delay: 0.7 }, "-=0.8"
                );
            }
            
            if (heroContent.querySelector('.hero-buttons')) {
                tl.fromTo(heroContent.querySelector('.hero-buttons'),
                    { opacity: 0, y: 20 },
                    { opacity: 1, y: 0, duration: 1, delay: 0.9 }, "-=0.8"
                );
            }
        }

        // Combine similar animations into batches to reduce ScrollTrigger instances
        const animateCards = (selector, animationProps) => {
            const cards = gsap.utils.toArray(selector);
            if (cards.length > 0) {
                gsap.set(cards, animationProps.from);
                
                ScrollTrigger.batch(cards, {
                    onEnter: (elements) => {
                        gsap.to(elements, {
                            ...animationProps.to,
                            stagger: 0.15,
                            overwrite: true
                        });
                    },
                    start: "top 85%",
                    once: true
                });
            }
        };

        // Feature cards animation
        animateCards('.feature-card', {
            from: { opacity: 0, y: 30 },
            to: { opacity: 1, y: 0, duration: 0.6, ease: "power2.out" }
        });

        // Service cards animation
        animateCards('.service-card', {
            from: { opacity: 0, scale: 0.95 },
            to: { opacity: 1, scale: 1, duration: 0.6, ease: "power2.out" }
        });

        // Testimonial cards animation
        animateCards('.testimonial-card', {
            from: { opacity: 0, y: 20 },
            to: { opacity: 1, y: 0, duration: 0.6, ease: "power2.out" }
        });

        // Basic section fade-in (reduced from all sections to key sections only)
        const keySections = gsap.utils.toArray('.hero, .services-section, .testimonials-section');
        if (keySections.length > 0) {
            ScrollTrigger.batch(keySections, {
                onEnter: (elements) => {
                    gsap.fromTo(elements, 
                        { opacity: 0, y: 30 },
                        { 
                            opacity: 1, 
                            y: 0, 
                            duration: 0.8, 
                            stagger: 0.2,
                            ease: "power2.out",
                            overwrite: true
                        }
                    );
                },
                start: "top 80%",
                once: true
            });
        }
    }
}

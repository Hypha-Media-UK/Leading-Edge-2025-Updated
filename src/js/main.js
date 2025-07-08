// Main JavaScript functionality

// Header scroll effect and mobile menu
document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('header');
    const menuToggle = document.getElementById('menu-toggle');
    const hamburger = document.getElementById('hamburger');
    const nav = document.getElementById('nav');

    // Header scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
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

    // Basic fade-in animations for sections
    gsap.utils.toArray('section').forEach(section => {
        gsap.fromTo(section, 
            { opacity: 0, y: 50 },
            {
                opacity: 1,
                y: 0,
                duration: 1,
                scrollTrigger: {
                    trigger: section,
                    start: 'top 80%',
                    end: 'bottom 20%',
                    toggleActions: 'play none none reverse'
                }
            }
        );
    });

    // Hero text animation
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        gsap.fromTo(heroContent.querySelector('h1'),
            { opacity: 0, y: 30 },
            { opacity: 1, y: 0, duration: 1, delay: 0.5 }
        );
        
        gsap.fromTo(heroContent.querySelector('p'),
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 1, delay: 0.7 }
        );
        
        gsap.fromTo(heroContent.querySelector('.hero-buttons'),
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 1, delay: 0.9 }
        );
    }

    // Feature cards animation
    gsap.utils.toArray('.feature-card').forEach((card, index) => {
        gsap.fromTo(card,
            { opacity: 0, y: 30 },
            {
                opacity: 1,
                y: 0,
                duration: 0.8,
                delay: index * 0.2,
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%',
                    toggleActions: 'play none none reverse'
                }
            }
        );
    });

    // Service cards animation
    gsap.utils.toArray('.service-card').forEach((card, index) => {
        gsap.fromTo(card,
            { opacity: 0, scale: 0.9 },
            {
                opacity: 1,
                scale: 1,
                duration: 0.8,
                delay: index * 0.2,
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%',
                    toggleActions: 'play none none reverse'
                }
            }
        );
    });

    // Testimonial cards animation
    gsap.utils.toArray('.testimonial-card').forEach((card, index) => {
        gsap.fromTo(card,
            { opacity: 0, x: index % 2 === 0 ? -30 : 30 },
            {
                opacity: 1,
                x: 0,
                duration: 0.8,
                delay: index * 0.1,
                scrollTrigger: {
                    trigger: card,
                    start: 'top 85%',
                    toggleActions: 'play none none reverse'
                }
            }
        );
    });
}

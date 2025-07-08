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

// Modern CSS + Intersection Observer Animation System
class ModernAnimations {
    constructor() {
        this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.supportsScrollTimeline = CSS.supports('animation-timeline', 'view()');
        
        if (!this.prefersReducedMotion) {
            this.init();
        }
    }
    
    init() {
        // Initialize hero animations immediately
        this.initHeroAnimations();
        
        // Initialize scroll-based animations
        if (this.supportsScrollTimeline) {
            this.initModernScrollAnimations();
        } else {
            this.initIntersectionObserver();
        }
    }
    
    initHeroAnimations() {
        const heroContent = document.querySelector('.hero-content');
        if (!heroContent) return;
        
        // Add stagger delays to hero elements
        const h1 = heroContent.querySelector('h1');
        const p = heroContent.querySelector('p');
        const buttons = heroContent.querySelector('.hero-buttons');
        
        if (h1) {
            h1.style.setProperty('--animation-delay', '0.5s');
            h1.classList.add('hero-animate');
        }
        
        if (p) {
            p.style.setProperty('--animation-delay', '0.7s');
            p.classList.add('hero-animate');
        }
        
        if (buttons) {
            buttons.style.setProperty('--animation-delay', '0.9s');
            buttons.classList.add('hero-animate');
        }
    }
    
    initModernScrollAnimations() {
        // For browsers that support CSS scroll-driven animations
        const animatedElements = document.querySelectorAll(
            '.service-card, .testimonial-wrapper, .hero, .services-preview, .testimonials, .virtues, .products-showcase'
        );
        
        animatedElements.forEach(element => {
            element.classList.add('scroll-reveal-modern');
        });
        
        // Add stagger delays for card groups
        this.addStaggerDelays('.service-card', 100);
        this.addStaggerDelays('.testimonial-wrapper', 150);
    }
    
    initIntersectionObserver() {
        // Fallback for browsers without scroll-driven animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateElement(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe all animatable elements
        const elements = document.querySelectorAll(
            '.service-card, .testimonial-wrapper, .hero, .services-preview, .testimonials, .virtues, .products-showcase'
        );
        
        elements.forEach(element => {
            element.classList.add('scroll-reveal-fallback');
            observer.observe(element);
        });
        
        // Set up staggered animations for card groups
        this.setupStaggeredGroups();
    }
    
    animateElement(element) {
        if (element.classList.contains('service-card') || 
            element.classList.contains('testimonial-wrapper')) {
            this.animateCardGroup(element);
        } else {
            element.classList.add('visible');
        }
    }
    
    animateCardGroup(triggerElement) {
        // Find the parent container and animate all cards within it
        const container = triggerElement.closest('.services-grid, .testimonial-container, section');
        if (!container) {
            triggerElement.classList.add('visible');
            return;
        }
        
        const cards = container.querySelectorAll('.service-card, .testimonial-wrapper');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, index * 100);
        });
    }
    
    setupStaggeredGroups() {
        // Group cards by their container for staggered animation
        const cardContainers = document.querySelectorAll('.services-grid, .testimonial-container');
        
        cardContainers.forEach(container => {
            const cards = container.querySelectorAll('.service-card, .testimonial-wrapper');
            cards.forEach((card, index) => {
                card.style.setProperty('--stagger-delay', `${index * 100}ms`);
            });
        });
    }
    
    addStaggerDelays(selector, delayIncrement) {
        const elements = document.querySelectorAll(selector);
        elements.forEach((element, index) => {
            element.style.setProperty('--stagger-delay', `${index * delayIncrement}ms`);
        });
    }
}

// Initialize the animation system
document.addEventListener('DOMContentLoaded', () => {
    new ModernAnimations();
});

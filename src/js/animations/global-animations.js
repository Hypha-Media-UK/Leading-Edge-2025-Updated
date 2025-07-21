// Global Scroll Animations
import { scrollTriggerManager, gsap, ScrollTrigger } from './core/scroll-trigger.js';

// Global Animations Manager
class GlobalAnimations {
    constructor() {
        this.initialized = false;
    }

    // Initialize all global animations
    init() {
        if (this.initialized) return;

        this.initHeroLoadAnimation();
        this.initUniversalStaggerAnimations();
        this.initTabWaveAnimations();
        this.initSummaryTextAnimations();
        this.initTextImageAnimations();
        this.initCallToActionAnimation();
        
        this.initialized = true;
        console.log('Global animations initialized');
    }

    // Hero Page Load Animation (not scroll-triggered)
    initHeroLoadAnimation() {
        // Find hero section
        const heroSection = document.querySelector('.hero');
        
        if (!heroSection) return;

        const heroContent = heroSection.querySelector('.hero-content');
        const textContent = heroSection.querySelector('.text-content');
        const heroButtons = heroSection.querySelector('.hero-buttons');
        const heroTitle = heroSection.querySelector('h1');
        const heroSubText = heroSection.querySelector('p');
        const buttons = heroSection.querySelectorAll('.btn');

        // Set initial states - everything invisible
        gsap.set([heroTitle, heroSubText, buttons], {
            opacity: 0,
            y: 30
        });

        gsap.set(heroSection, {
            opacity: 0
        });

        // Create timeline for hero load animation
        const heroTimeline = gsap.timeline({ delay: 0.2 });

        // Fade in hero section first
        heroTimeline.to(heroSection, {
            opacity: 1,
            duration: 0.5,
            ease: "power2.out"
        });

        // Animate title
        if (heroTitle) {
            heroTimeline.to(heroTitle, {
                opacity: 1,
                y: 0,
                duration: 0.8,
                ease: "power2.out"
            }, "-=0.2");
        }

        // Animate subtitle
        if (heroSubText) {
            heroTimeline.to(heroSubText, {
                opacity: 1,
                y: 0,
                duration: 0.6,
                ease: "power2.out"
            }, "-=0.4");
        }

        // Animate buttons with stagger
        if (buttons.length > 0) {
            heroTimeline.to(buttons, {
                opacity: 1,
                y: 0,
                duration: 0.5,
                stagger: 0.1,
                ease: "power2.out"
            }, "-=0.2");
        }

        console.log('Hero load animation initialized');
    }

    // Universal Stagger Animations - handles all grid-based components
    initUniversalStaggerAnimations() {
        // Find all grids with the standardized class
        const staggerGrids = document.querySelectorAll('.js-stagger-grid');
        
        staggerGrids.forEach(grid => {
            const items = grid.querySelectorAll('.js-stagger-item');
            
            if (items.length === 0) return;

            // Set initial state - items invisible and slightly below
            gsap.set(items, {
                opacity: 0,
                y: 30,
                scale: 0.95
            });

            // Create stagger animation
            gsap.to(items, {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: 0.6,
                stagger: 0.15, // 0.15 second delay between each item
                ease: "power2.out",
                scrollTrigger: {
                    trigger: grid,
                    start: "top 80%",
                    toggleActions: "play none none reverse",
                    markers: true // Show ScrollTrigger markers for debugging
                }
            });
        });

        console.log(`Universal stagger animations initialized for ${staggerGrids.length} grids`);
    }

    // Call to Action Parallax Animation
    initCallToActionAnimation() {
        // Find all call-to-action sections
        const ctaSections = document.querySelectorAll('.cta-section');
        
        ctaSections.forEach(section => {
            const ctaContent = section.querySelector('.cta-content');
            
            if (!ctaContent) return;

            // Parallax effect for the background
            gsap.to(section, {
                backgroundPosition: "50% 100%",
                ease: "none",
                scrollTrigger: {
                    trigger: section,
                    start: "top bottom",
                    end: "bottom top",
                    scrub: true,
                    markers: true // Show ScrollTrigger markers for debugging
                }
            });

            // Content fade-in and slide-up animation
            gsap.set(ctaContent, {
                opacity: 0,
                y: 50
            });

            gsap.to(ctaContent, {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: "power2.out",
                scrollTrigger: {
                    trigger: section,
                    start: "top 80%",
                    toggleActions: "play none none reverse",
                    markers: true
                }
            });
        });
    }

    // Tab Wave Animations - creates ripple effect from center outward
    initTabWaveAnimations() {
        // Main tabs wave animation
        const mainTabsContainer = document.querySelector('.tabs-container');
        if (mainTabsContainer) {
            const mainTabs = mainTabsContainer.querySelectorAll('.tab');
            if (mainTabs.length > 0) {
                this.createWaveAnimation(mainTabs, mainTabsContainer, 'main-tabs');
            }
        }

        // Sub tabs wave animation
        const subTabsContainers = document.querySelectorAll('.sub-tabs-container');
        subTabsContainers.forEach((container, index) => {
            const subTabs = container.querySelectorAll('.sub-tab');
            if (subTabs.length > 0) {
                this.createWaveAnimation(subTabs, container, `sub-tabs-${index}`, 0.3); // Delay after main tabs
            }
        });

        console.log(`Tab wave animations initialized for main tabs and ${subTabsContainers.length} sub-tab containers`);
    }

    // Helper method to create wave animation
    createWaveAnimation(tabs, container, animationId, baseDelay = 0) {
        if (tabs.length === 0) return;

        // Set initial state - tabs invisible and scaled down
        gsap.set(tabs, {
            opacity: 0,
            scale: 0,
            y: 20
        });

        // Calculate center index
        const centerIndex = Math.floor(tabs.length / 2);

        // Create stagger array based on distance from center
        const staggerArray = Array.from(tabs).map((tab, index) => {
            const distanceFromCenter = Math.abs(index - centerIndex);
            return distanceFromCenter * 0.1; // 0.1s delay per step from center
        });

        // Create wave animation
        gsap.to(tabs, {
            opacity: 1,
            scale: 1,
            y: 0,
            duration: 0.6,
            stagger: {
                each: staggerArray,
                from: centerIndex
            },
            ease: "back.out(1.7)", // Bounce effect
            scrollTrigger: {
                trigger: container,
                start: "top 80%",
                toggleActions: "play none none reverse",
                markers: true // Show ScrollTrigger markers for debugging
            },
            delay: baseDelay
        });
    }

    // Summary Section Text Animations - animates headers and description text
    initSummaryTextAnimations() {
        // Find all summary sections
        const summarySections = document.querySelectorAll('.interior-section');
        
        summarySections.forEach(section => {
            const sectionHeader = section.querySelector('.section-header');
            const description = section.querySelector('.interior-description');
            
            // Animate section header (title and subtitle)
            if (sectionHeader) {
                const title = sectionHeader.querySelector('h2');
                const subtitle = sectionHeader.querySelector('p');
                
                // Set initial state
                gsap.set([title, subtitle].filter(Boolean), {
                    opacity: 0,
                    y: 30
                });
                
                // Create timeline for header animation
                const headerTimeline = gsap.timeline({
                    scrollTrigger: {
                        trigger: sectionHeader,
                        start: "top 85%",
                        toggleActions: "play none none reverse",
                        markers: true
                    }
                });
                
                // Animate title first
                if (title) {
                    headerTimeline.to(title, {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        ease: "power2.out"
                    });
                }
                
                // Animate subtitle with slight overlap
                if (subtitle) {
                    headerTimeline.to(subtitle, {
                        opacity: 1,
                        y: 0,
                        duration: 0.6,
                        ease: "power2.out"
                    }, "-=0.4");
                }
            }
            
            // Animate description text separately
            if (description) {
                gsap.set(description, {
                    opacity: 0,
                    y: 20
                });
                
                gsap.to(description, {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    ease: "power2.out",
                    scrollTrigger: {
                        trigger: description,
                        start: "top 85%",
                        toggleActions: "play none none reverse",
                        markers: true
                    }
                });
            }
        });

        console.log(`Summary text animations initialized for ${summarySections.length} sections`);
    }

    // Text Image Section Animations - subtle animations for image and text
    initTextImageAnimations() {
        // Find all text-image sections
        const textImageSections = document.querySelectorAll('.about-section');
        
        textImageSections.forEach(section => {
            const aboutContent = section.querySelector('.about-content');
            const aboutImage = section.querySelector('.about-image');
            const aboutText = section.querySelector('.about-text');
            
            if (!aboutContent) return;
            
            // Set initial states
            if (aboutImage) {
                gsap.set(aboutImage, {
                    opacity: 0,
                    x: -30,
                    scale: 0.95
                });
            }
            
            if (aboutText) {
                gsap.set(aboutText, {
                    opacity: 0,
                    x: 30
                });
            }
            
            // Create timeline for subtle staggered animation
            const textImageTimeline = gsap.timeline({
                scrollTrigger: {
                    trigger: aboutContent,
                    start: "top 80%",
                    toggleActions: "play none none reverse",
                    markers: true
                }
            });
            
            // Animate image first (slides in from left)
            if (aboutImage) {
                textImageTimeline.to(aboutImage, {
                    opacity: 1,
                    x: 0,
                    scale: 1,
                    duration: 0.8,
                    ease: "power2.out"
                });
            }
            
            // Animate text with slight overlap (slides in from right)
            if (aboutText) {
                textImageTimeline.to(aboutText, {
                    opacity: 1,
                    x: 0,
                    duration: 0.8,
                    ease: "power2.out"
                }, "-=0.4");
            }
        });

        console.log(`Text-image animations initialized for ${textImageSections.length} sections`);
    }

    // Method to add more animations in the future
    // Example: initHeroAnimation() { ... }
    // Example: initTestimonialsAnimation() { ... }
}

// Create and export singleton instance
const globalAnimations = new GlobalAnimations();

export { globalAnimations };

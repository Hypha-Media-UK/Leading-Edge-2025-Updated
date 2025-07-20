// Shared animation utilities using GSAP
export class AnimationUtils {
    
    // Generic card animation
    static animateCards(selector, options = {}) {
        if (typeof gsap === 'undefined') return;
        
        const defaults = {
            opacity: 0,
            y: 30,
            duration: 0.6,
            stagger: 0.1,
            trigger: 'top 90%'
        };
        
        const config = { ...defaults, ...options };
        
        gsap.utils.toArray(selector).forEach((element, index) => {
            gsap.fromTo(element,
                { opacity: config.opacity, y: config.y },
                {
                    opacity: 1,
                    y: 0,
                    duration: config.duration,
                    delay: index * config.stagger,
                    scrollTrigger: {
                        trigger: element,
                        start: config.trigger,
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });
    }
    
    // Generic section animation
    static animateSection(selector, options = {}) {
        if (typeof gsap === 'undefined') return;
        
        const defaults = {
            opacity: 0,
            y: 50,
            duration: 1,
            trigger: 'top 85%'
        };
        
        const config = { ...defaults, ...options };
        const element = document.querySelector(selector);
        
        if (element) {
            gsap.fromTo(element,
                { opacity: config.opacity, y: config.y },
                {
                    opacity: 1,
                    y: 0,
                    duration: config.duration,
                    scrollTrigger: {
                        trigger: element,
                        start: config.trigger,
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }
    
    // Product grid specific animation
    static animateProductGrid(selector = '.product-item') {
        if (typeof gsap === 'undefined') return;
        
        gsap.utils.toArray(selector).forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, scale: 0.8 },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.6,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });
    }
}

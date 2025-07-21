// GSAP ScrollTrigger Setup
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Register ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

// ScrollTrigger Manager Class
class ScrollTriggerManager {
    constructor() {
        this.initialized = false;
        this.triggers = [];
    }

    // Initialize ScrollTrigger
    init() {
        if (this.initialized) return;

        // Basic ScrollTrigger configuration
        ScrollTrigger.config({
            autoRefreshEvents: "visibilitychange,DOMContentLoaded,load"
        });

        // Refresh ScrollTrigger on window resize
        window.addEventListener('resize', () => {
            ScrollTrigger.refresh();
        });

        this.initialized = true;
        console.log('ScrollTrigger initialized');
    }

    // Create a new ScrollTrigger
    create(options) {
        const trigger = ScrollTrigger.create(options);
        this.triggers.push(trigger);
        return trigger;
    }

    // Refresh all triggers
    refresh() {
        ScrollTrigger.refresh();
    }

    // Kill all triggers
    killAll() {
        this.triggers.forEach(trigger => trigger.kill());
        this.triggers = [];
        ScrollTrigger.killAll();
    }

    // Get ScrollTrigger instance for external use
    getScrollTrigger() {
        return ScrollTrigger;
    }

    // Get GSAP instance for external use
    getGSAP() {
        return gsap;
    }
}

// Create and export singleton instance
const scrollTriggerManager = new ScrollTriggerManager();

export { scrollTriggerManager, gsap, ScrollTrigger };

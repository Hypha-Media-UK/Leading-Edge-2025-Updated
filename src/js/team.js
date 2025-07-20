// Team page specific JavaScript
import { AnimationUtils } from './utils/animations.js';
import { ModalUtils } from './utils/modals.js';

// Team page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize modal functionality
    ModalUtils.init();
    
    // Setup team member modal triggers
    ModalUtils.setupModalTriggers('.team-member');
    
    // Use shared animation utilities
    AnimationUtils.animateCards('.team-member');
    AnimationUtils.animateCards('.philosophy-section .feature-card', { 
        stagger: 0.2 
    });
    AnimationUtils.animateSection('.team-intro');
});

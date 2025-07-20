// Shared modal utilities
export class ModalUtils {
    
    static currentModal = null;
    
    // Initialize modal functionality
    static init() {
        // Close modal functionality for all modals
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-close')) {
                ModalUtils.closeModal();
            }
            
            if (e.target.classList.contains('modal-overlay')) {
                ModalUtils.closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && ModalUtils.currentModal) {
                ModalUtils.closeModal();
            }
        });
    }
    
    // Show modal by ID
    static showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }
        
        // Hide any currently open modal
        if (ModalUtils.currentModal) {
            ModalUtils.currentModal.style.display = 'none';
            ModalUtils.currentModal.classList.remove('active');
        }

        // Show the new modal
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.classList.add('modal-open');
        
        // Store reference to current modal
        ModalUtils.currentModal = modal;
        
        // Focus management for accessibility
        modal.focus();
    }
    
    // Close current modal
    static closeModal() {
        if (ModalUtils.currentModal) {
            ModalUtils.currentModal.style.display = 'none';
            ModalUtils.currentModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            ModalUtils.currentModal = null;
        }
    }
    
    // Setup modal triggers
    static setupModalTriggers(triggerSelector, modalTargetAttribute = 'data-modal-target') {
        const triggers = document.querySelectorAll(triggerSelector);
        
        triggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const modalTarget = this.getAttribute(modalTargetAttribute);
                if (modalTarget) {
                    ModalUtils.showModal(modalTarget);
                }
            });
        });
    }
}

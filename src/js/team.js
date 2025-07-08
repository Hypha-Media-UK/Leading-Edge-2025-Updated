// Team page specific JavaScript

// Team page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // GSAP animations for team page
    if (typeof gsap !== 'undefined') {
        
        // Animate team member cards
        gsap.utils.toArray('.team-member').forEach((member, index) => {
            gsap.fromTo(member,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: member,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate team values cards
        gsap.utils.toArray('.philosophy-section .feature-card').forEach((card, index) => {
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

        // Animate team intro section
        const teamIntro = document.querySelector('.team-intro');
        if (teamIntro) {
            gsap.fromTo(teamIntro,
                { opacity: 0, y: 50 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: teamIntro,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }

    // Team member modal functionality
    const teamMembers = document.querySelectorAll('.team-member');
    let currentModal = null;
    
    console.log('Team members found:', teamMembers.length);

    // Open modal when team member is clicked
    teamMembers.forEach(member => {
        member.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Team member clicked!', this);
            const modalTarget = this.getAttribute('data-modal-target');
            console.log('Modal target:', modalTarget);
            showTeamMemberModal(modalTarget);
        });
    });

    // Close modal functionality for all modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close')) {
            closeTeamModal();
        }
        
        if (e.target.classList.contains('modal-overlay')) {
            closeTeamModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeTeamModal();
        }
    });

    function showTeamMemberModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal not found:', modalId);
            return;
        }

        console.log('Showing modal:', modalId);
        
        // Hide any currently open modal
        if (currentModal) {
            currentModal.style.display = 'none';
            currentModal.classList.remove('active');
        }

        // Show the new modal
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.classList.add('modal-open');
        
        // Store reference to current modal
        currentModal = modal;
        
        // Focus management for accessibility
        modal.focus();
    }

    function closeTeamModal() {
        if (currentModal) {
            currentModal.style.display = 'none';
            currentModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            currentModal = null;
        }
    }
});

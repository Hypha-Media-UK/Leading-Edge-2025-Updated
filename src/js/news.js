// News page specific JavaScript

// News page functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Category filtering functionality
    const filterItems = document.querySelectorAll('.filter-item');
    const newsCards = document.querySelectorAll('.news-card');
    
    filterItems.forEach(filter => {
        filter.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Update active filter
            filterItems.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            
            // Filter news cards
            filterNewsCards(category);
        });
    });
    
    function filterNewsCards(category) {
        newsCards.forEach(card => {
            const cardCategory = card.querySelector('.news-category');
            const categoryText = cardCategory ? cardCategory.textContent.trim().toLowerCase() : '';
            
            if (category === 'all' || categoryText === category.replace('-', ' ')) {
                card.style.display = 'block';
                // Re-trigger animation
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // News article modal functionality
    const readMoreButtons = document.querySelectorAll('.read-more-btn');
    let currentModal = null;
    
    console.log('News cards found:', newsCards.length);

    // Article click functionality
    const featuredNews = document.querySelector('.featured-news');
    if (featuredNews) {
        featuredNews.addEventListener('click', function(e) {
            e.preventDefault();
            const modalTarget = this.getAttribute('data-modal-target');
            showNewsModal(modalTarget);
        });
    }
    
    newsCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const modalTarget = this.getAttribute('data-modal-target');
            showNewsModal(modalTarget);
        });
    });

    // Also handle "Read More" button clicks specifically
    readMoreButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const card = this.closest('.news-card');
            if (card) {
                const modalTarget = card.getAttribute('data-modal-target');
                showNewsModal(modalTarget);
            }
        });
    });

    // Close modal functionality for all modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close')) {
            closeNewsModal();
        }
        
        if (e.target.classList.contains('modal-overlay')) {
            closeNewsModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeNewsModal();
        }
    });

    function showNewsModal(modalId) {
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

    function closeNewsModal() {
        if (currentModal) {
            currentModal.style.display = 'none';
            currentModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            currentModal = null;
        }
    }
    
    // GSAP animations for news page
    if (typeof gsap !== 'undefined') {
        
        // Animate featured news
        const featuredNewsElement = document.querySelector('.featured-news');
        if (featuredNewsElement) {
            gsap.fromTo(featuredNewsElement,
                { opacity: 0, y: 50 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 1,
                    scrollTrigger: {
                        trigger: featuredNewsElement,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }

        // Animate news cards
        gsap.utils.toArray('.news-card').forEach((card, index) => {
            gsap.fromTo(card,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate filter items
        gsap.utils.toArray('.filter-item').forEach((filter, index) => {
            gsap.fromTo(filter,
                { opacity: 0, scale: 0.8 },
                {
                    opacity: 1,
                    scale: 1,
                    duration: 0.4,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: '.news-filters',
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });

        // Animate section header
        const sectionHeader = document.querySelector('.news-section .section-header');
        if (sectionHeader) {
            gsap.fromTo(sectionHeader,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    scrollTrigger: {
                        trigger: sectionHeader,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }
});

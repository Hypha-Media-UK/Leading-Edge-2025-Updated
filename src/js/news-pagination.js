/**
 * News Pagination Scroll Enhancement
 * Automatically scrolls to the news-grid section when pagination links are clicked
 * or when landing on a paginated news page
 */

class NewsPagination {
    constructor() {
        this.newsGridSelector = '#news-grid';
        this.paginationSelector = '.pagination-link';
        this.scrollOffset = 100; // Offset for fixed header
        
        this.init();
    }

    init() {
        // Check if we're on a news page
        if (!this.isNewsPage()) {
            return;
        }

        // Scroll to news grid if we're on a paginated page
        this.handlePageLoad();
        
        // Add click handlers to pagination links
        this.bindPaginationClicks();
    }

    isNewsPage() {
        return window.location.pathname.startsWith('/news');
    }

    isPaginatedPage() {
        return window.location.pathname.includes('/page/');
    }

    handlePageLoad() {
        // If we're on a paginated page, scroll to news grid after page load
        if (this.isPaginatedPage()) {
            // Wait for page to fully load, then scroll
            window.addEventListener('load', () => {
                setTimeout(() => {
                    this.scrollToNewsGrid();
                }, 100); // Small delay to ensure everything is rendered
            });
        }
    }

    bindPaginationClicks() {
        // Add click handlers to pagination links
        document.addEventListener('click', (e) => {
            const paginationLink = e.target.closest(this.paginationSelector);
            
            if (paginationLink && this.isPaginationLink(paginationLink)) {
                // Store scroll intent in sessionStorage
                // This will be used after the page reloads
                sessionStorage.setItem('scrollToNewsGrid', 'true');
            }
        });

        // Check if we should scroll after page load
        if (sessionStorage.getItem('scrollToNewsGrid') === 'true') {
            sessionStorage.removeItem('scrollToNewsGrid');
            
            // Wait for page to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => this.scrollToNewsGrid(), 100);
                });
            } else {
                setTimeout(() => this.scrollToNewsGrid(), 100);
            }
        }
    }

    isPaginationLink(link) {
        // Check if the link is a pagination link (not disabled)
        return !link.classList.contains('disabled') && 
               link.href && 
               link.href.includes('/news');
    }

    scrollToNewsGrid() {
        const newsGrid = document.querySelector(this.newsGridSelector);
        
        if (!newsGrid) {
            return;
        }

        // Calculate scroll position with offset for fixed header
        const elementTop = newsGrid.getBoundingClientRect().top + window.pageYOffset;
        const scrollTo = elementTop - this.scrollOffset;

        // Smooth scroll to the news grid
        window.scrollTo({
            top: scrollTo,
            behavior: 'smooth'
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new NewsPagination();
    });
} else {
    new NewsPagination();
}

// Export for potential use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NewsPagination;
}

/**
 * FOUC Prevention and Loading State Management
 * Enhances the critical CSS approach with JavaScript optimizations
 */

// Immediately add loading class to prevent any flash
document.documentElement.classList.add('loading');

// Font loading optimization
if ('fonts' in document) {
  // Preload critical fonts
  const criticalFonts = [
    new FontFace('Montserrat', 'url(https://fonts.gstatic.com/s/montserrat/v25/JTUSjIg1_i6t8kCHKm459Wlhyw.woff2)', {
      weight: '400',
      display: 'swap'
    }),
    new FontFace('Playfair Display', 'url(https://fonts.gstatic.com/s/playfairdisplay/v30/nuFvD-vYSZviVYUb_rj3ij__anPXJzDwcbmjWBN2PKdFvXDXbtXK-F2qO0isEw.woff2)', {
      weight: '400',
      display: 'swap'
    })
  ];

  // Load critical fonts
  Promise.all(criticalFonts.map(font => {
    document.fonts.add(font);
    return font.load();
  })).then(() => {
    document.documentElement.classList.add('fonts-loaded');
  }).catch(err => {
    console.warn('Font loading failed:', err);
    // Continue without custom fonts
    document.documentElement.classList.add('fonts-loaded');
  });
}

// DOM ready state management
function handleDOMReady() {
  // Remove loading class once DOM is ready
  document.documentElement.classList.remove('loading');
  document.documentElement.classList.add('loaded');
  
  // Add smooth transitions after initial load
  setTimeout(() => {
    document.documentElement.classList.add('transitions-enabled');
  }, 100);
}

// Handle different loading states
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', handleDOMReady);
} else {
  // DOM is already ready
  handleDOMReady();
}

// Handle window load for complete resource loading
window.addEventListener('load', () => {
  document.documentElement.classList.add('fully-loaded');
  
  // Enable advanced animations after full load
  setTimeout(() => {
    document.documentElement.classList.add('animations-enabled');
  }, 200);
});

// Intersection Observer for progressive loading
if ('IntersectionObserver' in window) {
  const lazyElements = document.querySelectorAll('[data-lazy]');
  
  if (lazyElements.length > 0) {
    const lazyObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const element = entry.target;
          element.classList.add('in-view');
          lazyObserver.unobserve(element);
        }
      });
    }, {
      rootMargin: '50px'
    });
    
    lazyElements.forEach(element => {
      lazyObserver.observe(element);
    });
  }
}

// Performance monitoring
if ('performance' in window && 'measure' in performance) {
  window.addEventListener('load', () => {
    // Measure critical metrics
    const navigation = performance.getEntriesByType('navigation')[0];
    const paintEntries = performance.getEntriesByType('paint');
    
    const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint');
    const lcp = paintEntries.find(entry => entry.name === 'largest-contentful-paint');
    
    // Log performance metrics (remove in production)
    if (process.env.NODE_ENV === 'development') {
      console.group('Performance Metrics');
      console.log('DOM Content Loaded:', navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart, 'ms');
      console.log('First Contentful Paint:', fcp ? fcp.startTime : 'N/A', 'ms');
      console.log('Largest Contentful Paint:', lcp ? lcp.startTime : 'N/A', 'ms');
      console.groupEnd();
    }
  });
}

export { handleDOMReady };

// Services page specific JavaScript

// Services page tab functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // Tab switching functionality
    const mainTabs = document.querySelectorAll('.tabs-container .tab');
    const subTabsWrappers = document.querySelectorAll('.sub-tabs-wrapper');
    const serviceContentSections = document.querySelectorAll('.service-content-section');
    
    let activeMainTab = 'allHair';
    let activeSubTab = 'cuttingStyling';
    
    // Main tab click handlers
    mainTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            setActiveMainTab(tabId);
        });
    });
    
    // Sub tab click handlers
    document.addEventListener('click', function(e) {
        if (e.target.closest('.sub-tabs-container .sub-tab')) {
            const subTab = e.target.closest('.sub-tab');
            const subTabId = subTab.getAttribute('data-tab');
            setActiveSubTab(subTabId);
        }
    });
    
    function setActiveMainTab(tabId) {
        activeMainTab = tabId;
        
        // Update main tab active states
        mainTabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.getAttribute('data-tab') === tabId) {
                tab.classList.add('active');
            }
        });
        
        // Show/hide sub tabs containers
        subTabsWrappers.forEach(wrapper => {
            const mainTabId = wrapper.getAttribute('data-main-tab');
            if (mainTabId === tabId) {
                wrapper.style.display = 'block';
                // Set default sub tab for this main tab
                if (tabId === 'allHair') {
                    activeSubTab = 'cuttingStyling';
                } else if (tabId === 'beautyNails') {
                    activeSubTab = 'bodyWaxing';
                } else if (tabId === 'massageAesthetics') {
                    activeSubTab = 'bodyMassage';
                }
                
                // Update sub tab active states
                const subTabs = wrapper.querySelectorAll('.sub-tab');
                subTabs.forEach(subTab => {
                    subTab.classList.remove('active');
                    if (subTab.getAttribute('data-tab') === activeSubTab) {
                        subTab.classList.add('active');
                    }
                });
            } else {
                wrapper.style.display = 'none';
            }
        });
        
        // Update content sections
        updateContentSections();
    }
    
    function setActiveSubTab(subTabId) {
        activeSubTab = subTabId;
        
        // Update sub tab active states in current wrapper
        const currentWrapper = document.querySelector(`.sub-tabs-wrapper[data-main-tab="${activeMainTab}"]`);
        if (currentWrapper) {
            const subTabs = currentWrapper.querySelectorAll('.sub-tab');
            subTabs.forEach(subTab => {
                subTab.classList.remove('active');
                if (subTab.getAttribute('data-tab') === subTabId) {
                    subTab.classList.add('active');
                }
            });
        }
        
        // Update content sections
        updateContentSections();
    }
    
    function updateContentSections() {
        const targetSection = `${activeMainTab}-${activeSubTab}`;
        
        serviceContentSections.forEach(section => {
            const sectionTab = section.getAttribute('data-tab');
            if (sectionTab === targetSection) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    }
    
    // GSAP animations for services page
    if (typeof gsap !== 'undefined') {
        
        // Animate service items
        gsap.utils.toArray('.service-item').forEach((item, index) => {
            gsap.fromTo(item,
                { opacity: 0, x: -20 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 0.5,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: item,
                        start: 'top 90%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });
        
        // Animate tabs
        gsap.utils.toArray('.tab').forEach((tab, index) => {
            gsap.fromTo(tab,
                { opacity: 0, y: 20 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    delay: index * 0.1,
                    scrollTrigger: {
                        trigger: tab,
                        start: 'top 95%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });
        
        // Animate service content sections
        gsap.utils.toArray('.service-content-section').forEach(section => {
            gsap.fromTo(section,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.8,
                    scrollTrigger: {
                        trigger: section,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        });
    }
});

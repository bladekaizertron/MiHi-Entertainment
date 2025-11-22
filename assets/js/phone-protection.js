/**
 * Phone Number Protection Script
 * Prevents bots from scraping phone numbers while allowing real users to call
 * 
 * Usage: Add data-click-to-call attribute to any button or link
 * Example: <button data-click-to-call data-call-text="Call Us">Call Us</button>
 */

(function() {
    // Obfuscated phone number (base64 encoded: 7205516994)
    // This makes it harder for bots to scrape the number from HTML source
    const encodedPhone = 'NzIwNTUxNjk5NA==';
    
    // Decode function
    function decodePhone() {
        try {
            return atob(encodedPhone);
        } catch(e) {
            return '7205516994'; // Fallback
        }
    }
    
    // Format phone number for display
    function formatPhone(phone) {
        return `(${phone.substring(0, 3)})${phone.substring(3, 6)}-${phone.substring(6)}`;
    }
    
    // Initialize click-to-call buttons
    function initClickToCall() {
        // Find all elements with data-click-to-call attribute
        const callButtons = document.querySelectorAll('[data-click-to-call]');
        
        callButtons.forEach(button => {
            // Skip if already initialized
            if (button.dataset.initialized === 'true') {
                return;
            }
            button.dataset.initialized = 'true';
            
            const phoneNumber = decodePhone();
            const formattedPhone = formatPhone(phoneNumber);
            
            // Set up click handler
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Check if mobile device
                const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                
                if (isMobile) {
                    // Directly initiate call on mobile devices
                    window.location.href = `tel:${phoneNumber}`;
                } else {
                    // On desktop, show number with options
                    const userChoice = confirm(
                        `Call us at ${formattedPhone}\n\n` +
                        `Click OK to copy the number to clipboard.\n` +
                        `Click Cancel to view our contact form.`
                    );
                    
                    if (userChoice) {
                        // Copy to clipboard
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(formattedPhone).then(() => {
                                // Show temporary success message
                                const originalText = button.querySelector('span')?.textContent || button.textContent;
                                if (button.querySelector('span')) {
                                    button.querySelector('span').textContent = 'Copied!';
                                } else {
                                    button.textContent = 'Copied!';
                                }
                                setTimeout(() => {
                                    if (button.querySelector('span')) {
                                        button.querySelector('span').textContent = originalText;
                                    } else {
                                        button.textContent = originalText;
                                    }
                                }, 2000);
                            }).catch(() => {
                                // Fallback: show number in prompt
                                prompt('Phone number (copy manually):', formattedPhone);
                            });
                        } else {
                            // Fallback for older browsers
                            prompt('Phone number (copy manually):', formattedPhone);
                        }
                    } else {
                        // Scroll to contact form
                        const contactSection = document.getElementById('contact');
                        if (contactSection) {
                            contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            // If no contact section, show number anyway
                            prompt('Phone number:', formattedPhone);
                        }
                    }
                }
            });
            
            // Update button text/content if needed
            const buttonText = button.getAttribute('data-call-text') || 'Call Us';
            const spanElement = button.querySelector('span');
            if (spanElement && !spanElement.textContent.trim()) {
                spanElement.textContent = buttonText;
            } else if (!spanElement && !button.textContent.trim()) {
                button.textContent = buttonText;
            }
        });
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClickToCall);
    } else {
        // DOM already loaded, initialize immediately
        initClickToCall();
    }
    
    // Re-initialize after dynamic content loads (for SPAs or AJAX content)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                initClickToCall();
            }
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Expose function for manual initialization if needed
    window.initClickToCall = initClickToCall;
})();


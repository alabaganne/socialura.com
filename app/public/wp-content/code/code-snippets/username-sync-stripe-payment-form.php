<?php
/**
 * Sync username from localStorage to Stripe custom field
 * 
 * This snippet can be added via the Code Snippets plugin
 * It hooks into the Accept Stripe Payments payment popup
 * and populates the custom field with username from localStorage
 */

// Hook into payment popup before closing body tag
add_action( 'asp_ng_pp_output_before_closing_body', 'sync_username_to_stripe_custom_field', 100, 1 );

function sync_username_to_stripe_custom_field( $a ) {
    ?>
    <script>
        // Sync username from localStorage to Stripe custom field
        (function() {
            // Function to set username value
            function setUsernameFromLocalStorage() {
                // Get username from localStorage
                const storedUsername = localStorage.getItem('username');
                
                if (storedUsername) {
                    // Try multiple selectors for the custom field
                    // The field might have different names/IDs based on configuration
                    const selectors = [
                        'input[name="stripeCustomFields[0]"]',
                    ];
                    
                    let fieldFound = false;
                    
                    for (const selector of selectors) {
                        const field = document.querySelector(selector);
                        if (field && field.type === 'text') {
                            field.value = storedUsername;
                            fieldFound = true;
                            
                            // Trigger change event to ensure any validation/handlers run
                            const event = new Event('change', { bubbles: true });
                            field.dispatchEvent(event);
                            
                            console.log('Username synced to Stripe custom field:', storedUsername);
                            break;
                        }
                    }
                    
                    if (!fieldFound) {
                        console.log('Stripe custom field not found, but username is available:', storedUsername);
                    }
                }
            }
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setUsernameFromLocalStorage);
            } else {
                // DOM is already loaded, run immediately
                setUsernameFromLocalStorage();
            }
            
            // Also try after a short delay in case fields are dynamically loaded
            setTimeout(setUsernameFromLocalStorage, 500);
        })();
    </script>
    <?php
}

// Also hook into the regular form display (non-popup context)
add_action( 'wp_footer', 'sync_username_to_stripe_form', 100 );

function sync_username_to_stripe_form() {
    // Only add this on pages that might have Stripe payment forms
    if ( ! is_singular() ) {
        return;
    }
    ?>
    <script>
        // Sync username for regular Stripe forms (non-popup)
        (function() {
            // Check if we're not already in the payment popup
            if (document.getElementById('payment-form') && !document.getElementById('Aligner')) {
                return; // We're in the popup, the other function handles this
            }
            
            function setUsernameInRegularForm() {
                const storedUsername = localStorage.getItem('username');
                
                if (storedUsername) {
                    // Look for ASP form custom fields in regular forms
                    const fields = document.querySelectorAll(
                        '.asp_product_custom_field_input[type="text"], ' +
                        'input[name="stripeCustomField"]'
                    );
                    
                    fields.forEach(field => {
                        if (field.type === 'text' && !field.value) {
                            field.value = storedUsername;
                            
                            // Trigger change event
                            const event = new Event('change', { bubbles: true });
                            field.dispatchEvent(event);
                        }
                    });
                }
            }
            
            // Run on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setUsernameInRegularForm);
            } else {
                setUsernameInRegularForm();
            }
            
            // Also observe for dynamically added forms
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        setUsernameInRegularForm();
                    }
                });
            });
            
            // Start observing
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // Disconnect observer after 10 seconds to prevent performance issues
            setTimeout(() => observer.disconnect(), 10000);
        })();
    </script>
    <?php
}

/**
 * Usage Instructions:
 * 
 * 1. Copy this entire code
 * 2. Go to WordPress Admin > Snippets > Add New
 * 3. Give it a title like "Sync Username to Stripe Custom Field"
 * 4. Paste this code in the code area
 * 5. Set to "Run snippet everywhere" or "Only run on site front-end"
 * 6. Save and Activate
 * 
 * Requirements:
 * - The Stripe product must have a custom field configured
 * - The username must be stored in localStorage with key 'username'
 * - The custom field should be a text type field
 * 
 * The script will automatically populate the custom field with the username
 * from localStorage when the payment form loads.
 */
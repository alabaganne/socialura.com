<?php
/**
 * Stripe Payment Popup Font Fix
 * 
 * This code adds custom CSS to fix the font family issue in the Stripe Payments plugin popup
 * Add this to your theme's functions.php or use a code snippets plugin
 */

add_action('wp_enqueue_scripts', 'fix_stripe_popup_font', 9999);

function fix_stripe_popup_font() {
    // Check if we're on a page that might have Stripe payments
    wp_enqueue_style(
        'stripe-popup-font-fix', 
        content_url('/uploads/stripe-popup-font-fix.css'), 
        array(), 
        '1.0.0'
    );
}
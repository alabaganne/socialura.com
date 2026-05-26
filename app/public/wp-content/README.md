# Socialura.com - Social Media Growth Platform

## Overview
Socialura.com is a WordPress-based e-commerce platform specializing in social media growth services. The website enables customers to purchase followers packages for various social media platforms, primarily Instagram, with a focus on providing organic growth solutions.

## Business Model
The platform offers tiered pricing packages for social media followers, ranging from 500 to 100,000 followers, with special promotional pricing and discounts. All services are designed to comply with social media platform guidelines and use strategic marketing approaches.

## Technical Stack
- **CMS**: WordPress
- **Payment Processing**: Stripe (via custom-modified Stripe Payments plugin)
- **Frontend**: Custom HTML/CSS/JavaScript with jQuery
- **Popup System**: Popup Maker for pricing and promotional displays

## Required WordPress Plugins

### Core Functionality
1. **Stripe Payments** (v2.0.93) - *Modified*
   - Main payment processing system
   - Custom modifications for product handling
   - Integration with pricing popup system

2. **Stripe Payments Additional Custom Fields** (v2.0.9)
   - Extends Stripe payment forms with custom fields
   - Required for collecting additional customer information

3. **Stripe Payments Addons Update Checker** (v2.2)
   - Manages updates for Stripe payment addons
   - Ensures compatibility between addons

### User Interface & Experience
4. **Popup Maker** (v1.20.5)
   - Creates and manages pricing popups
   - Handles promotional overlays
   - Mobile-responsive popup system

5. **Font Awesome** (v5.1.0)
   - Icon library for UI elements
   - Used throughout the site for visual enhancements

### Development & Maintenance
6. **Code Snippets** (v3.6.8)
   - Manages custom PHP/JS/CSS code snippets
   - Stores site-specific functionality

7. **Advance Custom HTML** (v1.0.7)
   - Allows custom HTML blocks in content
   - Used for specialized page sections

### Communication & Forms
8. **WP Mail SMTP** (v4.5.0)
   - Ensures reliable email delivery
   - Handles transactional emails

9. **WPForms Lite** (v1.9.6.2)
   - Contact forms and user input handling
   - Integration with email notifications

### Utilities
10. **Media Sync** (v1.4.8)
    - Synchronizes media library with file system
    - Useful for bulk media imports

11. **WPVivid Backup** (v0.9.117)
    - Backup and restoration functionality
    - Site migration capabilities

## Custom Code Structure
```
wp-content/
├── code/
│   ├── pages/
│   │   └── ebooks.html        # E-books showcase page
│   └── popups/
│       └── pricing-popup.html  # Main pricing popup with product buttons
├── uploads/
│   ├── pum/                   # Popup Maker generated files
│   └── 2025/07/              # Media uploads
└── plugins/
    └── stripe-payments/       # Modified plugin (tracked in git)
```

## Key Features
- **Dynamic Pricing Display**: Interactive pricing cards with discount badges
- **Stripe Integration**: Secure payment processing with custom product attachments
- **Responsive Design**: Mobile-optimized layouts for all devices
- **Multi-language Support**: French language implementation
- **E-book Store**: Digital products section for marketing guides

## Installation Instructions

1. **WordPress Setup**
   - Install WordPress (latest version recommended)
   - Configure database and basic settings

2. **Plugin Installation**
   - Install all plugins listed above with exact versions
   - Note: All plugins except `stripe-payments` should be installed from WordPress repository or official sources
   - The `stripe-payments` plugin contains custom modifications and is included in this repository

3. **Configuration**
   - Configure Stripe API keys in Stripe Payments settings
   - Set up email configuration in WP Mail SMTP
   - Import popup templates in Popup Maker
   - Configure product IDs in stripe-payments to match pricing popup

4. **Custom Code Deployment**
   - Copy the `code/` directory structure
   - Ensure proper file permissions
   - Update product IDs in `pricing-popup.html` if needed

## Important Notes
- The `stripe-payments` plugin has been customized for this specific implementation
- The site uses localStorage for syncing username inputs between forms
- All unmodified plugins are excluded from version control via `.gitignore`

## Maintenance
- Regular plugin updates should be tested in staging environment first
- Custom modifications in `stripe-payments` plugin must be preserved during updates
- Backup site before any major changes using WPVivid

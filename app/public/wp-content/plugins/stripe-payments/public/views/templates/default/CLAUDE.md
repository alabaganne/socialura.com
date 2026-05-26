# Payment Popup Template Documentation

## Overview
This file (`payment-popup.php`) renders the Stripe payment popup modal for the Accept Stripe Payments (ASP) WordPress plugin. It creates a standalone HTML page that displays as a modal/popup for processing payments.

## Structure

### 1. **HTML Head Section** (Lines 1-42)
- Loads inline CSS (production vs development mode)
- Sets page metadata and viewport
- Injects JavaScript variables from PHP
- Includes site favicon
- Provides action hook: `asp_ng_pp_output_before_closing_head`

### 2. **Modal Layout** (Lines 44-516)
- **Test Mode Indicator** (Line 47): Shows "TEST MODE" link when not in production
- **Loading Spinners**: Multiple spinner elements for different states
- **Success Animation**: SVG checkmark animation for successful payments
- **Modal Header** (Lines 63-76):
  - Optional product logo
  - Close button (X icon)
  - Product name and description

### 3. **Payment Form** (Lines 82-512)
Main form with multiple sections:

#### Stock Control (Lines 83-87)
- Shows available quantity if stock control is enabled

#### Variable Pricing (Lines 88-122)
- **Amount Input**: For variable pricing products
- **Currency Selector**: Dropdown for multi-currency support
- **Quantity Input**: For customizable quantities

#### Product Variations (Lines 129-180)
- Supports radio buttons, dropdowns, and checkboxes
- Shows price modifiers for each variation
- Dynamic layout based on variation count

#### Coupon System (Lines 182-197)
- Coupon code input with Apply/Remove buttons
- Error display for invalid coupons

#### Order Summary Table (Lines 198-253)
- Displays item details, quantity, and price
- Shows tax calculations
- Includes shipping costs
- Calculates and displays total

#### Payment Method Selection (Lines 254-283)
- Radio buttons for multiple payment methods
- Supports custom payment method icons

#### Customer Information (Lines 287-343)
- **Name Field**: 
  - Single field or separate first/last name fields (configurable)
  - French label: "Nom et prénom"
- **Email Field**: Required email input with icon

#### Billing/Shipping Address (Lines 344-444)
- Toggle for same billing/shipping address
- Billing address fields: Address, City, Country, State (optional), Postcode
- Shipping address fields (hidden by default if same as billing)
- Country dropdowns populated from utility function

#### Card Payment (Lines 445-450)
- Stripe card element container
- French label: "Carte de crédit ou de débit"
- Error display for card validation

#### Additional Elements
- Custom fields support (above and below card element)
- Terms of Service checkbox (Lines 458-469)
- Submit button (Lines 474-483)
- Security badge display (Lines 485-493)

### 4. **Hidden Fields** (Lines 495-507)
- Payment intent ID
- Button unique ID
- Product ID
- Processing flags
- Thank you page URL
- Token creation flags

### 5. **3D Secure Modal** (Lines 517-521)
- Close button for 3D Secure authentication iframe

### 6. **Scripts and Styles Loading** (Lines 524-545)
- Loads styles and scripts in header/footer as configured

## Key Variables

### `$a` Array
Main data array containing:
- `page_title`: Browser title
- `vars`: JavaScript variables to inject
- `data`: Product and configuration data
- `item_name`: Product name
- `prod_id`: Product ID
- `fatal_error`: Error messages
- `is_live`: Production/test mode flag
- `styles`/`scripts`: Asset arrays

### Important `$a['data']` Properties:
- `is_live`: Production mode flag
- `item_logo`: Product logo URL
- `descr`: Product description
- `stock_control_enabled`: Stock management flag
- `amount_variable`: Variable pricing flag
- `currency_variable`: Multi-currency flag
- `custom_quantity`: Custom quantity flag
- `variations`: Product variations data
- `coupons_enabled`: Coupon support flag
- `billing_address`/`shipping_address`: Address requirement flags
- `payment_methods`: Available payment methods
- `customer_default_country`: Default country selection

## Hooks and Filters

### Action Hooks:
1. `asp_ng_pp_output_before_closing_head` - Add content before `</head>`
2. `asp_ng_pp_output_before_address` - Add content before address fields
3. `asp_ng_pp_output_before_closing_form` - Add content before `</form>`
4. `asp_ng_pp_output_before_closing_body` - Add content before `</body>`

### Filters:
1. `asp_ng_pp_default_country_override` - Override default country
2. `asp_customize_text_msg` - Customize text labels
3. `asp_ng_available_currencies` - Modify available currencies
4. `asp_ng_pp_output_before_buttons` - Add content before submit button
5. `asp_ng_pp_after_button` - Add content after submit button
6. `asp_ng_pp_security_message_content` - Customize security badge
7. `asp_ng_pp_extra_output_before_closing_body` - Additional body content

## Security Features

1. **Input Sanitization**: Uses WordPress escaping functions
   - `esc_html()` for text output
   - `esc_attr()` for attributes
   - `esc_url()` for URLs
   - `wp_kses()` for HTML with allowed tags

2. **Nonce/Security**: Form includes hidden fields for validation

3. **XSS Prevention**: All dynamic content is properly escaped

## Language Customizations

The template includes French translations:
- "Nom et prénom" for customer name
- "Carte de crédit ou de débit" for credit/debit card

Other text uses WordPress translation functions with the 'stripe-payments' text domain.

## CSS Framework
Uses Pure.css for styling with responsive grid classes:
- `pure-g`: Grid container
- `pure-u-*`: Grid units with responsive breakpoints
- `pure-form`: Form styling
- `pure-button`: Button styling

## JavaScript Integration
The template prepares data for client-side JavaScript:
- Injects PHP variables as JavaScript globals
- Provides element IDs for JavaScript manipulation
- Stripe Elements integration via `#card-element`

## Usage Notes

1. This is a standalone page loaded in a modal/iframe
2. The form submits payment data to Stripe
3. Supports both test and production modes
4. Highly customizable through hooks and filters
5. Responsive design with mobile support
6. Accessibility features (ARIA roles, tabindex)

## How to Use Action Hooks

WordPress action hooks allow you to execute custom code at specific points in the payment popup lifecycle. Here's how to use them:

### Basic Syntax

```php
add_action( 'hook_name', 'your_function_name', priority, number_of_parameters );
```

### Payment Popup Hook Examples

#### 1. **Add Custom Scripts/Styles to Head** (`asp_ng_pp_output_before_closing_head`)

```php
// Add custom analytics or tracking scripts
add_action( 'asp_ng_pp_output_before_closing_head', 'my_custom_head_content' );

function my_custom_head_content() {
    ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    
    <!-- Custom CSS -->
    <style>
        #payment-form { background: #f5f5f5; }
        .pure-button-primary { background-color: #007cba; }
    </style>
    <?php
}
```

#### 2. **Add Content Before Address Fields** (`asp_ng_pp_output_before_address`)

```php
// Add a custom message or field before address section
add_action( 'asp_ng_pp_output_before_address', 'my_custom_address_notice', 10, 1 );

function my_custom_address_notice( $data ) {
    ?>
    <div class="pure-u-1 custom-notice">
        <p style="color: #d63638; font-weight: bold;">
            ⚠️ Please ensure your billing address matches your credit card statement
        </p>
    </div>
    <?php
}
```

#### 3. **Add Fields Before Form Closing** (`asp_ng_pp_output_before_closing_form`)

```php
// Add custom hidden fields or additional form elements
add_action( 'asp_ng_pp_output_before_closing_form', 'my_custom_form_fields', 10, 1 );

function my_custom_form_fields( $a ) {
    ?>
    <!-- Add referral tracking -->
    <input type="hidden" name="referral_source" value="<?php echo esc_attr( $_GET['ref'] ?? 'direct' ); ?>">
    
    <!-- Add custom checkbox -->
    <div class="pure-u-1" style="margin-top: 10px;">
        <label class="pure-checkbox">
            <input type="checkbox" name="newsletter_signup" value="1">
            Subscribe to our newsletter for exclusive offers
        </label>
    </div>
    <?php
}
```

#### 4. **Add Content Before Body Closing** (`asp_ng_pp_output_before_closing_body`)

```php
// Add custom JavaScript or tracking pixels
add_action( 'asp_ng_pp_output_before_closing_body', 'my_custom_body_scripts', 10, 1 );

function my_custom_body_scripts( $a ) {
    ?>
    <script>
        // Custom form validation
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            console.log('Payment form submitted for product:', <?php echo json_encode( $a['item_name'] ); ?>);
        });
        
        // Track form interactions
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                // Track field focus events
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_field_focus', {
                        'field_name': this.name
                    });
                }
            });
        });
    </script>
    <?php
}
```

### Real-World Examples from the Plugin

#### Custom CSS Injection (from class-asp-pp-display.php)
```php
// The plugin uses this to add custom CSS from settings
add_action( 'asp_ng_pp_output_before_closing_body', array( $this, 'output_custom_css' ), 1000 );

public function output_custom_css( $a ) {
    $pp_additional_css = $this->asp_main->get_setting( 'pp_additional_css' );
    if ( empty( $pp_additional_css ) ) {
        return;
    }
    echo sprintf( "<style>%s</style>\r\n", wp_kses( str_replace( array( "\t", "\r\n" ), '', $pp_additional_css ), array() ) );
}
```

#### CAPTCHA Integration Examples
```php
// From hCaptcha integration
add_action( 'asp_ng_pp_output_add_scripts', array( $this, 'ng_add_scripts' ) );
add_filter( 'asp_ng_pp_output_before_buttons', array( $this, 'ng_before_buttons' ), 10, 2 );

// Adds hCaptcha before submit button
public function ng_before_buttons( $output, $data ) {
    $output .= '<div class="h-captcha" data-sitekey="' . esc_attr( $this->site_key ) . '"></div>';
    return $output;
}
```

### Hook Execution Order

1. `asp_ng_pp_output_before_closing_head` - In `<head>` section
2. `asp_ng_pp_output_before_address` - Before customer info fields
3. `asp_ng_pp_output_before_buttons` - Before submit button (filter)
4. `asp_ng_pp_output_before_closing_form` - Before `</form>` tag
5. `asp_ng_pp_output_before_closing_body` - Before `</body>` tag

### Best Practices

1. **Use Proper Priority**: Default is 10. Lower numbers execute first.
   ```php
   add_action( 'hook_name', 'early_function', 5 );   // Runs first
   add_action( 'hook_name', 'late_function', 20 );   // Runs later
   ```

2. **Check Data Availability**: Always verify data exists before using:
   ```php
   function my_hook_function( $a ) {
       if ( ! empty( $a['data']['custom_field'] ) ) {
           // Use the data safely
       }
   }
   ```

3. **Escape Output**: Always escape dynamic content:
   ```php
   echo esc_html( $variable );
   echo esc_attr( $attribute );
   echo esc_url( $url );
   ```

4. **Namespace Functions**: Prefix functions to avoid conflicts:
   ```php
   add_action( 'asp_ng_pp_output_before_closing_head', 'mycompany_add_tracking' );
   ```

### Common Use Cases

- **Analytics Integration**: Add tracking codes (Google Analytics, Facebook Pixel)
- **Custom Styling**: Override default styles or add theme-specific CSS
- **Additional Fields**: Add custom form fields for special requirements
- **Validation**: Add client-side validation scripts
- **Third-party Integrations**: Connect with CRM, email marketing, etc.
- **Security Features**: Add CAPTCHA, fraud detection scripts
- **Compliance**: Add GDPR notices, privacy policy links
- **A/B Testing**: Inject testing scripts or variations
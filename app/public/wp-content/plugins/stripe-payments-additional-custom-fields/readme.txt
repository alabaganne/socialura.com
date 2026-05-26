=== Stripe Payments Additional Custom Fields Addon ===
Contributors: alexanderfoxc
Donate link: https://stripe-plugins.com
Tags: stripe
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.0.9

Stripe Payments Additional Custom Fields Addon

== Description ==

== Installation ==

1. Upload `stripe-payments-additional-custom-fields` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
None

== Screenshots ==
None

== Changelog ==

= 2.0.9 =
* A php deprecation issue fixed.
* iDEAL payment addon compatibility issue fixed.

= 2.0.8 =
* Added a new settings option to show the custom fields below the card input fields.

= 2.0.7 =
* The custom fields input values are now read directly by the addon (instead of coming from the core plugin).

= 2.0.6 =
* Added "Checked By Default" option for checkbox field type.
* Fixed rare issue when unchecked checkbox could have value of previous field after payment form submission.
* Made more text strings available for translation.

= 2.0.5 =
* Improved settings page handling.

= 2.0.4 =
* Fixed rare issue when saving settings.
* Improved admin-side buttons display.
* Update is now handled by core plugin.

= 2.0.3 =
* Added Dropdown field type.

= 2.0.2 =
* Fixed duplicated custom fields issue.

= 2.0.1 =
* Fixed {custom_field_N}, {custom_field_name_N}, {custom_field_value_N} email merge tags were processing field ID improperly.

= 2.0.0 =
* Added new Stripe API support.

= 1.1.1 =
* Added support for the upcoming NG payment handling.

= 1.1.0 =
* Added Datepicker field type.
* Fixed fields IDs inconsistency issue.

= 1.0.4 =
* Added notice to addon settings page if Custom Field is disabled in core plugin settings.

= 1.0.3 =
* Added {custom_field_N}, {custom_field_name_N}, {custom_field_value_N} email merge tag support. Can be used put individual custom fields into customers' emails.

= 1.0.2 =
* Added per-product custom fields display settings. Requires core plugin version 1.9.13t2+.

= 1.0.1 =
* Fixed PHP notices during payment processing by core plugin.
* No longer displays custom fields if addon is disabled.

= 1.0.0 =
* First public release.
=== Diller Loyalty ===
Contributors: dillerapp,teixeira1985
Tags: diller, loyalty program, customer club, coupons, members, stamp cards, sms, campaign
Requires at least: 4.7
Tested up to: 6.0.1
Version: 2.3.0
Stable tag: 2.3.0
Requires PHP: 7.3
WC requires at least: 3.8.0
WC tested up to: 6.7.0
License: MIT
License URI: https://choosealicense.com/licenses/mit/

Diller is a loyalty platform for businesses that is easy, affordable and profitable. With Diller Wordpress plugin you will get customers back to your store, where they can access coupons, stamp cards, membership points and benefits.
It integrates with Woocommerce, allowing for Customers to enroll directly from the checkout page, redeem coupons at checkout and get an overview of the points earned for the current order.

**Important**
This plugin completely replaces the old plugin v1.6.1 [here](https://wordpress.org/plugins/diller-app/), and you should only use this one in your store.

Read more about Diller [here](https://diller.io/)

== Description ==

Feature list

1. Membership status (My Account Dashboard)
2. Coupons, stamps and friend referral (My Account Dashboard)
3. Subscription form for joining the loyalty program
4. Multisite compatible
5. Multi-language support (NO, SE, EN, PT, and more to come)
6. GDPR compliance
7. SDK: Contains a developer guide and code samples to help you implementing Diller functionality on your own.

== Installation ==
1. Install the plugin using WordPress [built-in plugin installer](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation) or upload the entire `diller-loyalty` folder to the `/wp-content/plugins/` directory
2. Click the Activate Plugin button
3. Click on **Diller Loyalty** from the navigation menu and connect your store and adjust you store settings.


== Upgrade Notice ==


== Screenshots ==
1. Public enrollment form
2. Checkout: enrollment form (for guests or non-enrolled customers)
3. My Account: Loyalty Program Status
4. My Account: Loyalty Program profile edit screen
5. My Account: Refer a friend
6. My Account: Available coupons
7. My Account: Available stamps
8. Cart: Loyalty Program Coupons
9. Checkout: enrollment form
10. Diller customization and settings page


== Frequently Asked Questions ==

= How to get help if I run into any issue? =
For issues with your WooCommerce installation you should use [Wordpress support forum](https://wordpress.org/support/plugin/woocommerce/).
For other issues, related to the plugin, please contact our awesome support and customer service team [support@diller.no](mailto:support@diller.no).

=== How to obtain the credentials to connect my store ===
1. Take contact with our awesome support and customer service team [support@diller.no](mailto:support@diller.no) and they'll get you the store PIN code and API key to connect your store.


== Changelog ==
= 2.3.0 =
* Orders are sent to Diller API now, only when order status is set to "Completed".
* Adds setting to allow the plugin to run in test mode.
* Adds support for WP-Cli integration (currently only allows).
* Adds support for Danish language.
* Adds Non-binary to gender field options.

= 2.2.4 =
* Updated privacy policy URL.
* Improves dynamic form field ids uniqueness.
* Fixes bug when sending transactions to Diller API.

= 2.2.3 =
* Bug fixing and small tweaks.
* Adds mbstring polyfill extensions for PHP 8.

= 2.2.2 =
* Fixes PHP warning and notices being thrown in some actions.
* Fixes bug when Follower was deleted in Diller Admin panel and wanted to join again later, inside My Account area.
* Removed sslverify = false from API calls.
* Updated languages texts for nb-NO.
* Updated migration script to better handle migration of old settings format.
* Add: New hooks "diller_admin_woocommerce_actions" and "diller_admin_woocommerce_filters" for customization plugin backend behavior.
* Updated SDK files and documentation for newly added hooks.

= 2.2.1 =
* Fixed bug with coupons at checkout.
* Removed description from coupons.

= 2.2.0 =
* Support for Vipps transactions (integration with plugin [Checkout with Vipps for WooCommerce](https://wordpress.org/plugins/woo-vipps/)).
* Added Call to action for joining LP to order details (for non-members).
* Improved coupon styling and info displayed.
* General improvements and bug fixing.
* Updated language file for nb_NO

= 2.1.2 =
* Added Remove button for coupon cards (checkout).
* Improvements and bug fixing

= 2.1.1 =
* Added new field in admin settings to allow "Join Loyalty Program" checkboxes to be displayed after billing form (default) or terms
* Fixes bug with marketing fields logic at checkout
* Fixes bug with displaying points to customers that joined for the first time via checkout

= 2.1.0 =
* New feature: Members can reset their account or fulfil an incomplete registration by SMS code verification
* Bug-fixes and enhancements
* Improved logging

= 2.0.13 =
* Bug-fixes and enhancements
* Consent fields are now displayed before terms rather than billing

= 2.0.12 =
* Bug-fixes and enhancements
* Removed momentjs dependency
* Added Swedish language support

= 2.0.11 =
* Improvements on escaping output and sanitizing input data
* Improved admin settings screen to allow customization of plugin behavior
* Improved support for old shortcode migration on plugin activation
* Syncs enrollment form page permalink changes to Diller API
* Changed licence to MIT

= 2.0.10 =
* Initial public release.

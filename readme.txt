=== WooCommerce JTL-Connector ===
Contributors: ntbyk
Tags: warenwirtschaft, jtl, connector, wms, erp, woocommerce
Requires at least: 4.7
Tested up to: 5.0
Requires PHP: 5.6.4
WC requires at least: 3.4
WC tested up to: 3.5.2
Stable tag: 1.6.3
License: GPLv3
License URI: http://www.gnu.org/licenses/lgpl-3.0.html

Extend your shop software, trough this connector, with an full ERP with many features for marketplaces etc.

== Description ==

Already work with an woocommerce shop and set up your product-catalog? You want to extend your woocommerce-shop with an erp-software?

With this JTL-Connector, you are able to connect your woocommerce to our free erp-software JTL-Wawi and sync for example articles, stock levels, orders.
You can also use the JTL-Connector to import article data into the JTL-Wawi, for later uses in amrketplaces or other online-shops.


== Installation ==

This section describes how to install the plugin and get it working.

1. Do a backup of your shop-database, before you do the following steps.
1. Install the JTL-Connector for WooCommerce and register for an free domain bound licence-key.
1. If the installation was successful the JTL-Connector will be shown in the woocommerce settings.

== Changelog ==

= 1.6.3 =
* Enhancement - CO-397 - Compatibility with wordpress 5.0
* Fix - CO-398 - jtlwcc_deactivate_plugin not found

= 1.6.2 =
* Enhancement -CO-374 - Hide variants attributes
* Fix - CO-373 - Update deactivates plugin

= 1.6.1 =
* Enhancement - CO-302 - optimize plugin update
* Enhancement - CO-365 - submit wcc in wordpress.org plugin store
* Enhancement - CO-369 - improve sync of variants

= 1.6.0 =
* Enhancement - CO-345 - Compatibility with woocommerce 3.5.x
* Enhancement - Refactor main plugin files
* Enhancement - Added copy connector url btn
* Enhancement - New build procedure
* Fix - CO-336 - Alt text is not set correctly
* Enhancement - CO-319 - mark article as virtual/downloadable
* Enhancement - CO-302 - optimize plugin update

= 1.5.7 =
*  Fix - CO-290 - problem with multiple vat in customer orders
*  Fix - Specific mapping issue
*  Fix - missing shipping order item vat
*  Enhancement - shipping class product mapping

= 1.5.6 =
* Fix - CO-271 fix_product_attribute_push
* Fix - CO-284 - VAT is not set correctly on CustomerOrderItem
* Fix - CO-271 - article attributes removal
* Enhancement - removed integrity check

= 1.5.5 =
* Enhancement - use dynamic table prefix in all sql queries.
* Enhancement - version file included into connector build.

= 1.5.4 =
* Fix - cast bug on Customer
* Enhancement - ShippingClass will now be pushed

= 1.5.3 =
* Fix - remove image pull bug
* Fix - remove one integrity check test

= 1.5.2 =
* Fix - Specific value error bug

= 1.5.1 =
* Fix - Specific value language bug

= 1.5.0 =
* Enhancement - Add specific support(Beta)
* Enhancement - Add new attribute handling(Beta)
* Enhancement - IntegrityCheck in install routine

= 1.4.12 =
* Fix - wrong vat on shipping items

= 1.4.11 =
* Fix - wrong status on product pull
* Fix - push didn't update product_variation information

= 1.4.10 =
* Enhancement - CO-170 Added an incompatible list on settings page
* Enhancement - CO-171 Added copy password field on settings page
* Fix - CO-164 setting new name in category push.
* Fix - CO-170 Extend some language file entries
* Fix - double subtraction of coupons

= 1.4.9 =
* Fix - CO-152 Changed method because discount wasn't set correctly on pull.
* Enhancement - Implement fallback for no creation date.
* Enhancement - Remove unclear push method calls
* Fix - CO-136 wrong count of images in stats

= 1.4.8 =
* Fix - CO-135 Fix reduced tax rate not saved
* Fix - Fix categories are duplicated on push
* Fix - Fix German translations
* Enhancement - Refactor plugin settings part

= 1.4.7 =
* Enhancement - Support WC 3.2
* Enhancement - Add customer note field
* Fix - product modified date null pointer
* Fix - Delete all options and tables on uninstall
* Fix - php backwards compatibility to 5.4

= 1.4.6 =
* Fix - CO-112 product id instead of variation id linked in order items
* Fix - CO-113 product creation date updated
* Fix - CO-109 product feedback is deactivated on update

= 1.4.5 =
* Enhancement - CO-108 Take the 'woocommerce_tax_based_on' option to calculate the order item VAT

= 1.4.4 =
* Fix - build on PHP7 error
* Fix - string to boolean parse error
* Fix - constraint creation on installation

= 1.4.3 =
* Fix - product variations not pushed
* Fix - not detected locale
* Fix - category sort table not filled
* Fix - for getting the product of an order item returns a product even it does not exists

= 1.4.2 =
* Enhancement - Update product by matching SKU
* Fix - linking table creation SQL
* Fix - setting updated as timestamp
* Enhancement - Fallback alt text for image
* Enhancement - Update translation

= 1.4.1 =
* Fix - free shipping
* Enhancement - Strip HTML tags from keywords

= 1.4.0 =
* Enhancement - Support of WooCommerce 3
* Enhancement - Only pull double product image once
* Enhancement - Refactoring order item taxes for accurate tax calculation
* Enhancement - Refactor Germanized integration
* Enhancement - Remove minimum oder and packaging quantity validation on push
* Fix - primary key mapper for unsupported types

== Upgrade Notice ==

Upgrade to the latest version to prevent unwanted behavior.
# Changelog

This is the changelog of our "JTL WooCommerce Connector".

## 2.4.1-dev
* Info - Merged state with current release
* Bugfix - Fixed invalid constant reference

## 2.4.0-dev
* Info - Merged state with current release
* Changed compatibility info to WooCommerce 5
* Removed platform version from Connector identify call

## 2.3.0-dev
* Info - Merged state with current release
* Bugfix - CO-1335 - Fixed various bugs related to variable products 
* Bugfix - Fixed problems with quicksync and product stock sync  

## 2.2.0-dev
* Info - Merged state with current release
* Bugfix - Fixed setProductType bug

## 2.1.0-dev
* Info - Merged state with current release

## 2.0.0-dev
* Enhancement - CO-307 - WPML implementation

## 1.19.0
* Bugfix - CO-1372 - fixed customer group update
* Bugfix - CO-1343 - fixed cross-selling pull
* Bugfix - CO-1338 - fixed sanitizing image name on push
* Bugfix - CO-1337 - fixed paypal payment method mapping

## 1.18.0
* Changed compatibility info to WooCommerce 5
* Removed platform version from Connector identify call

## 1.17.0
* Bugfix - CO-1254 - fixed empty values in Cross-Selling pull
* Bugfix - CO-1274 - fixed parent images are attached to children
* Bugfix - CO-1272 - fixed saving category slug when url path is empty
* Bugfix - CO-1241 - fixed saving images with same name
* Bugfix - CO-1304 - partially shiped orders have now status pending
* Bugfix - CO-1261 - German Market: pay by invoice(kauf auf rechnung) orders are now unpaid
* Enhancement - CO-1243 - allow saving html tags in attribute values
* Info - WooCommerce compatibility updated to 4.8, Wordpress compatibility updated to 5.6

## 1.16.1
* Bugfix - fresh installation process missing token

## 1.16.0
* Enhancement - Unified connector config to database config, removed all items except developer logging from config.json file
* Bugfix - CO-1240 - Fixed vat calculation comparing values
* Bugfix - CO-1239 - Fixed price transfer precision, price gross item is now rounded at pull 
* Bugfix - CO-1221 - Fixed 'not all images were sent' error

## 1.15.2
* Bugfix - CO-1213 - Delete product image if not used
* Bugfix - CO-1193 - Fixed tax id import

## 1.15.1
* Enhancement - Fixed tax rate calculation    

## 1.15.0
* Enhancement - Product price refactoring, unified normal and quick sync calls to one method
* Bugfix - Fixed setting bulk prices
* Bugfix - Fixed setting standard price when B2B Market is active
* Bugfix - Reverted transfer priceGross on customer order pull, set item price precision to minimum of 4
* Bugfix - Increased tax rate calculation precision to 4
* Bugfix - CO-1161 - DHL postnumber is now correctly transfered 
* Bugfix - CO-1174 - Customer group can be changed on customer update

## 1.14.2
* Bugfix - Price decimal precision increased to minimum of 4

## 1.14.1
* Bugfix - Price quicksync set price to 0 

## 1.14.0
* Bugfix - CO-1175 - Fixed split tax on shipping when there are two or more tax rates 
* Enhancement - CO-1139 - Product/Category with special in name is now correctly imported
* Enhancement - CO-1045 - Allow to change price import precision. Setting is basing on WooCommerce decimal prices setting for frontend
* Bugfix - CO-982 - RRP/UVP price in B2B market is not set correctly

## 1.13.1
* Bugfix - CO-1160 - Removed deprecated PHP function call get_magic_quotes_gpc()

## 1.13.0
* Bugfix - CO-1134 - Invalid customer group id sent on pull
* Bugfix - CO-1133 delete transients after quicksync

## 1.12.0
* Info - removed setPriceGross in CustomerOrderItem
* Info - removed setTotalSumGross in CustomerOrder
* Info - removed minimum price decimals condition in CustomerOrderItem
* Info - removed price cutting in CustomerOrder
* Enhancement - Vat calculations improvements, it's calculated basing directly on priceNet and priceGross
* Enhancement - Added option to recalculate order before pull when order has coupons
* Enhancement - Added possibility to transfer product type in the attribute 'wc_product_type' but type need to exist in WooCommerce 
  
## 1.11.1
* Bugfix - Paypal Plus PUI auto loading fix

## 1.11.0
* Bugfix - Invalid manufacturer query when deleting image
* Enhancement - CO-984 - Pull payment only if order is linked
* Enhancement - CO-1067 - Added support for Paypal Plus PUI
* Bugfix - CO-898 - Image alt is no more set as tmp filename when not present
* Bugfix - CO-1109 - Added exception when default customer group is not set in connector settings

## 1.10.0  
* Enhancement - CO-1086 - Changed supplier delivery time to handling time method 
* Enhancement - CO-1049 - Added compatibility for plugin name change Perfect Brands for WooCommerce

## 1.9.5 
* Bugfix - Reverted changes related to rounding vat on item from version 1.9.4
* Bugfix - Fixed typo in DeliveryNote controller 
* Enhancement - CO-979 - Added delivery time support for Germanized plugin
* Enhancement - CO-965 - Added fallback when shipping vat rate is 0 then vat rate is the highest rate from products 

## 1.9.4
* Enhancement - CO-991 - If there is exactly one 'tax' order item type in order, rate_percent from it will be used instead of calculating vat rate manually
* Enhancement - Item price gross is now used without rounding for manually vat calculation
* Bugfix - Increased vat value precision to 2 digits when it's manually calculated
* Enhancement - CO-955 - iframe tag in description is now correctly saved  

## 1.9.3
* Enhancement - Added default value for 'dhl_wunschpaket_neighbour_address_addition' attribute in format: {shipping address zipcode} {shipping address city}
* Bugfix - CO-981 - Fixed saving meta fields from B2B market cause variable product type was switched to simple
* Bugfix - CO-975 - DHL Wunschpaket: Added default salutation 'Herr' if no salutation is present
* Bugfix - CO-855 - Fixed connector setting: 'Abgeschlossene Bestellungen importieren' doesnt't work 

## 1.9.2
* Bugfix - Stock level doubled when canceling order, added 'woocommerce_can_restore_order_stock' in status_change.push to prevent
* Bugfix - fixed manufacturer image linking, added missing condition

## 1.9.1
* Enhancement - added backup plugins compatibility: BackupBuddy, UpdraftPlus - Backup/Restore
* Info - marked BackWPup as incompatible plugin
* Enhancement - CO-931 - Added support for VR pay eCommerce - WooCommerce plugin

## 1.9.0
* Enhancement - CO-915 - Added compatibility with WooCommerce 4
* Minor fixes related to code inspections
* Removed stripslashes_deep call on super globals

## 1.8.5
* Increased minimum PHP version to 7.1.3
* Increased versions tested up to WooCommerce: 3.9 and Wordpress: 5.3
* Fix - CO-862 - VAT ID on customer is not always set
* Fix - CO-707 - Unified tax rates in global data pull

## 1.8.4
* Fix - CO-860 - Bulk Prices upper limit "infinite" fix
* Fix - DHL for Woocommerce invalid argument fix
* Fix - CO-820 - Coupon vat rate is not set
* Fix - CO-807 - Variable products are not imported correctly
* Enhancement - CO-750 - Added product Upsells field to synchronization, Crossselling field fixed
* Enhancement - CO-773 - Customer Vat number is now correctly transferred on pull 
* Enhancement - CO-780 - Support for minimum order quantity push (B2B plugin)

## 1.8.3
* Enhancement - CO-628 - Support for shipment tracking numbers (Required plugin: Advanced Shipment Tracking for WooCommerce)
* Enhancement - CO-695 - Support for dhl "wunschpaket" service (Required plugin: DHL for WooCommerce)
* Fix - CO-726 - Regular price import missing (B2B plugin)
* Enhancement - CO-729 - Product as service attribute (Germanized plugin)

## 1.8.2
* Fix - custom property always sent
* Fix - completed orders never included in import
* Enhancement - CO-511 - control sell only one of this item in each order
* Fix - round bug on order item price gross
* Fix - round bug on order item net
* Fix - casting bugs on sevreal germainzed classes
* Enhancement - Prepare for B2B-Market 1.0.4

## 1.8.1
* Fix - CO-507 - variable product seems to be simple product bug 
* Enhancement - Wordpress 5.2 Support
* Enhancement - Adjust recommend settings
* Enhancement - Add info content on settings
* Fix - CO-462 - set correct tax for orders and additional costs
* Enhancement - Change german market ppu handling
* Enhancement - Prevent missing prices bug
* Fix - germanized prices bug
* Fix - German Market free shipping bug
* Fix - Special prices bug
* Enhancement - Update special prices handling
* Fix - Net bugs on unit price germanized
* Fix - special price not removed bug
* Fix - wrong seo post update
* Enhancement - Refactor some code on product push sequence
* Fix - Warning blocks sync bug
* Enchancement - Preparations B2B-Market 1.0.4 CG Pull / Prices Pull/Push / Special Prices Pull/Push

## 1.8.0
* Enhancement - Support of HTML-TEXT in descriptions
* Enhancement - Separate menu for all JTL-Connector settings
* Enhancement - Support of seo for "manufacturer" (Required plugin: Yoast SEO / Beta)
* Enhancement - Support of seo for "categories" (Required plugin: Yoast SEO / Beta)
* Enhancement - Switch to new "SupportedPluginHelper" functions
* Enhancement - Use new meta keys instead of use mapped ones
* Enhancement - Remove unnecessary files
* Enhancement - new install routine
* Enhancement - Change some building logic
* Enhancement - Increase product(attribute/variation/custom_property/specifics/ push performance
* Enhancement - CO-466 - Support of German Market (BETA ! NO EAN)
* Enhancement - CO-483 - German Market: measurement_units (BETA)
* Enhancement - CO-480 - German Market: digital product type (BETA)
* Enhancement - CO-467 - German Market: base prices (BETA)
* Enhancement - CO-483 - German Market: delivery times (BETA)
* Enhancement - CO-474 - German Market: preselect variations (BETA)
* Enhancement - German Market: alt delivery note (BETA)
* Enhancement - German Market: purchased note note (BETA)
* Enhancement - German Market: suppress shipping notice (BETA)
* Enhancement - CO-434 - Support of B2B Market (BETA)
* Enhancement - CO-479 - B2B Market: bulk prices support (BETA)
* Enhancement - CO-468 - B2B Market: rrp support (BETA)
* Enhancement - CO-253 - B2B Market:customer groups (BETA)
* Enhancement - CO-478 - B2B Market:customer groups based prices (BETA)
* Enhancement - B2B Market/JTL-CONNECTOR/WAWI: special price for customer groups
* Fix - special prices
* Fix - custom fields
* Fix - CO-462 - set correct vat for all order items

## 1.7.2
* Enhancement - CO-436 - Support of seo for "products" (Required plugin: Yoast SEO / RC)
* Enhancement - CO-431 - Support of product brand (Required plugin: Perfect WooCommerce Brands / RC)
* Fix - manufacturer push
* Fix - manufacturer i18n meta pull
* Fix - ean & ean i18n pull
* Fix - customer order pull
* Fix - Yoast Seo Premium Bug
* Fix - missing billing information
* Fix - specifc value not exist bug
* Enhancement - some function attr
* Enhancement - some germanized features

## 1.7.1
* Enhancement - CO-436 - Support of seo for products (Required plugin: Yoast SEO / !!! BETA !!!)
* Enhancement - CO-431 - Support of product brand (Required plugin: Perfect WooCommerce Brands)
* Enhancement - CO-424 - delivery time support(RC)
* Enhancement - CO-429 - ean support(RC)
* Enhancement - Update UI texts
* Enhancement - New supported plugin validation
* Enhancement - New information on settings tab
* Enhancement - New functional attributes added
* Enhancement - Add fallback for i18n on specific & specific value pull
* Fix - nosearch && payable attribute bug
* Fix - Delivery time creation bug
* Fix - Return void mismatch bug

## 1.7.0
* Enhancement - CO-385 - Add Developer Logging on connector configuration tab
* Enhancement - CO-385 - New connector configuration
* Enhancement - CO-424 - delivery time support(BETA)
* Enhancement - CO-429 - ean support(BETA)

## 1.6.4
* Fix - Datetime bug on product push
* Fix - Prevent errors in checksum sql statement
* Fix - Checksum sql statement
* Fix - CO-421 - variant specifics will be deleted after next sync
* Fix - CO-413 - special prices will not be removed

## 1.6.3
* Enhancement - CO-397 - Compatibility with wordpress 5.0
* Fix - CO-398 - jtlwcc_deactivate_plugin not found

## 1.6.2
* Enhancement -CO-374 - Hide variants attributes
* Fix - CO-373 - Update deactivates plugin

## 1.6.1
* Enhancement - CO-302 - optimize plugin update
* Enhancement - CO-365 - submit wcc in wordpress.org plugin store
* Enhancement - CO-369 - improve sync of variants

## 1.6.0
* Enhancement - CO-345 - Compatibility with woocommerce 3.5.x 
* Enhancement - Refactor main plugin files
* Enhancement - Added copy connector url btn
* Enhancement - New build procedure
* Fix - CO-336 - Alt text is not set correctly
* Enhancement - CO-319 - mark article as virtual/downloadable
* Enhancement - CO-302 - optimize plugin update

## 1.5.7
*  Fix - CO-290 - problem with multiple vat in customer orders
*  Fix - Specific mapping issue
*  Fix - missing shipping order item vat
*  Enhancement - shipping class product mapping

## 1.5.6
* Fix - CO-271 fix_product_attribute_push
* Fix - CO-284 - VAT is not set correctly on CustomerOrderItem
* Fix - CO-271 - article attributes removal
* Enhancement - removed integrity check

## 1.5.5
* Enhancement - use dynamic table prefix in all sql queries.
* Enhancement - version file included into connector build.

## 1.5.4 
* Fix - cast bug on Customer
* Enhancement - ShippingClass will now be pushed

## 1.5.3
* Fix - remove image pull bug
* Fix - remove one integrity check test

## 1.5.2
* Fix - Specific value error bug

## 1.5.1
* Fix - Specific value language bug

## 1.5.0
* Enhancement - Add specific support(Beta)
* Enhancement - Add new attribute handling(Beta)
* Enhancement - IntegrityCheck in install routine

## 1.4.12
* Fix - wrong vat on shipping items

## 1.4.11
* Fix - wrong status on product pull
* Fix - push didn't update product_variation information

## 1.4.10 
* Enhancement - CO-170 Added an incompatible list on settings page
* Enhancement - CO-171 Added copy password field on settings page
* Fix - CO-164 setting new name in category push.
* Fix - CO-170 Extend some language file entries
* Fix - double subtraction of coupons

## 1.4.9
* Fix - CO-152 Changed method because discount wasn't set correctly on pull.
* Enhancement - Implement fallback for no creation date.
* Enhancement - Remove unclear push method calls
* Fix - CO-136 wrong count of images in stats

## 1.4.8
* Fix - CO-135 Fix reduced tax rate not saved
* Fix - Fix categories are duplicated on push
* Fix - Fix German translations
* Enhancement - Refactor plugin settings part

## 1.4.7
* Enhancement - Support WC 3.2
* Enhancement - Add customer note field
* Fix - product modified date null pointer
* Fix - Delete all options and tables on uninstall
* Fix - php backwards compatibility to 5.4

## 1.4.6
* Fix - CO-112 product id instead of variation id linked in order items
* Fix - CO-113 product creation date updated
* Fix - CO-109 product feedback is deactivated on update

## 1.4.5
* Enhancement - CO-108 Take the 'woocommerce_tax_based_on' option to calculate the order item VAT

## 1.4.4
* Fix - build on PHP7 error
* Fix - string to boolean parse error
* Fix - constraint creation on installation

## 1.4.3
* Fix - product variations not pushed
* Fix - not detected locale
* Fix - category sort table not filled
* Fix - for getting the product of an order item returns a product even it does not exists

## 1.4.2
* Enhancement - Update product by matching SKU
* Fix - linking table creation SQL
* Fix - setting updated as timestamp
* Enhancement - Fallback alt text for image
* Enhancement - Update translation

## 1.4.1
* Fix - free shipping
* Enhancement - Strip HTML tags from keywords

## 1.4.0
* Enhancement - Support of WooCommerce 3
* Enhancement - Only pull double product image once
* Enhancement - Refactoring order item taxes for accurate tax calculation
* Enhancement - Refactor Germanized integration
* Enhancement - Remove minimum oder and packaging quantity validation on push
* Fix - primary key mapper for unsupported types
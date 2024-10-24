# Changelog

This is the changelog of our "JTL WooCommerce Connector".

## Unreleased

## 2.0.6.1 _2024-10-24_
- HOTFIX Update release files

## 2.0.6 _2024-10-08_
- CO-2690 - fix category push NullLogger
- CO-2586 - fix special price without set date

## 2.0.5 _2024-07-22_
- CO-2649 - fix hardcoded database prefix

## 2.0.4 _2024-07-03_
- CO-2632 - fix missing category iso code
- CO-2643 - update image identities once

## 2.0.3 _2024-06-11_
- CO-2591 - cast taxRates to float
- CO-2612 - add updated plugin name for rank math
- CO-2607 - update log level according to config
- CO-2606 - fix error message undefined array key
- CO-2608 - consider variants with similar values

## 2.0.2 _2024-05-22_
- CO-2613 - remove call to specific value method

## 2.0.1 _2024-05-15_
- CO-2603 - add missing import and initialize logger

## 2.0.0 _2024-05-14_
- CO-2514 - prevent duplicate product images 
- CO-2394 - delete removed custom attributes
- CO-2460 - implement compatibility with germanized pro food 
- CO-2582 - merge wpml with wc connector
- CO-2440 - support advanced custom fields plugin for products

## 1.42.2 _2024-02-14_
- CO-2523 - get build version from config
- CO-2527 - pass rank math data as string
- CO-2536 - fix hpos payment

## 1.42.1 _2024-01-19_
- CO-2528 - update operand type

## 1.42.0 _2024-01-16_
- CO-2433 - null safe on get quantity
- CO-2362 - remove space in image title
- CO-2377 - add hpos support
- CO-2494 - disable cache
- CO-2496 - call method dynamically 
- CO-2416 - set germanized option "mark as shipped" true per default 
- CO-2419 - allow start date for special price without end date
- CO-2512 - set id for custom attributes
- CO-2511 - solve image import loop
- CO-2449 - migrate to core 5.2

## 1.41.2 _2023-12-05_
- HOTFIX Category Mapping HostId = 0

## 1.41.1 _2023-11-28_
- CO-2466 - fix undefined method in manufacturer model
- CO-2476 - change image type parameter
- CO-2453 - change json to array
- CO-2365 - update logic category title

## 1.41.0 _2023-11-14_
- CO-2471 - update argument order in image push
- CO-2461 - add clear cache button, add disable cache button
- fix debug log setting

## 1.40.4 _2023-11-06_
- CO-2458 - consider attribute IDs during product pull
- Fix Customer Group Prices

## 1.40.3 _2023-10-25_
- Fix Session is invalid error if session has been started before 
- CO-2443 - add manufacturer number to product

## 1.40.2 _2023-10-19_
- CO-2450 - update property name

## 1.40.1 _2023-10-18_
- fix B2B Market
- fix Additional text encountered after finished reading JSON content

## 1.40.0 _2023-10-17_
- CO-2418 - remove name format options
- CO-2072 - migrate to core 5.1

## 1.39.10 _2023-08-08_
- CO-2263 - fix empty delivery addresses
- CO-2389 - fix delivery status
- CO-2409 - support Additional Variation Images Gallery plugin

## 1.39.9 _2023-06-13_
- CO-2230 - fix Invisibility Type
- CO-2348 - fix cost splitting for shipping

## 1.39.8 _2023-05-10_
- Hotfix - return type

## 1.39.7 _2023-05-09_
- CO-2210 - fix customer import if db prefix is not wp_
- CO-2228 - fix wrong price calculation
- CO-2230 - fix B2B-Market customer group visibility
- CO-2268 - fixed manufacturer rank math meta fields
- CO-2297 - fix perfect brands plugin name
- CO-2346 - fixed next available inflow date

## 1.39.6 _2023-04-11_
- CO-2316 - cast string to float for tax calculation

## 1.39.5 _2023-03-14_
- CO-2242 - added customer group special price support
- CO-2263 - fix empty delivery addresses
- CO-1803 - delivery cost calc fixed

## 1.39.4 _2022-12-13_
- CO-2163 - fix extra product options plugin support
- CO-2150 - fix option to disable zero delivery time
- CO-2089 - add delay option for importing orders
- CO-2136 - remove empty taxonomy string assignment

## 1.39.3 _2022-11-08_
- CO-2188 - add log for missing image
- CO-2189 - Fix missing products in image pull 

## 1.39.2 _2022-09-28_
- CO-2141 - fix getFilter Error with Attribute wc_gm_alt_delivery_note

## 1.39.1 _2022-09-21_
- CO-2135 - fix product Attr error

## 1.39.0 _2022-09-13_
- CO-2097 - required age attribute
- CO-2091 - use delivery Status
- CO-2068 - Fixed default country extracting
- CO-2101 - wrong delivery costs on mixed tax rates
- CO-1758 - Attribute refactoring

## 1.38.0 _2022-08-09_
- Feature - CO-2060 - Better Customer Pull Performance
- Bugfix - CO-2065 - Fixed disabling stock management for variant parent product

## 1.37.1 _2022-07-12_
- fix WP Plugin Version

## 1.37.0 _2022-07-12_
- Bugfix - CO-2039 - Keywords are pulled with wrong seperator
- Bugfix - CO-2025 - UVP not saved in Child Products
- Feature - CO-1499 - Prevent features.json override
- Add allowed Plugin to composer.json

## 1.36.0 _2022-06-14_
- Updated tested WP Version to 6.0
- Updated Connector Core to 3.3.5

## 1.36.0 _2022-06-09_
- Bugfix - CO-2027 - Double Shipping costs if Shipping has multiple Vats
- Bugfix - CO-1959 - Delivery Time not updated
- Bugfix - CO-2013 - Custom Fields not transferred

## 1.35.1 _2022-04-13_
- Upated tested up to 5.9 Wordpress version

## 1.35.0 _2022-04-05_
- Bugfix - CO-1945 - Fixed duplicated slug for categories
- Feature - CO-1946 - Added multisite plugin detection 
- Bugfix - CO-1955 - Do not display function attributes on frontend
- Bugfix - CO-1487 - Fixed reserved slug names for product variants
- Bugfix - include jtl plugins in plugins directory

## 1.34.0 _2022-03-14_
- Feature - CO-1950 - Set standard WooCommerce price same as default customer group if recommended B2B market settings is enabled
- Feature - CO-1907 - Added support for Checkout Field Editor plugin via Connector settings
- Bugfix - CO-1923 - Fixed delivery time for Germanized plugin version greater than 3.7.0 
- Bugfix - CO-1789 - Fixed German Market base price set  

## 1.33.0 _2022-02-16_
- Fixed missing tax_class linking table
- Fixed - CO-1908 - rolled back changes related to setting base price based on customer group CO-1853
- Feature - CO-1915 - added config option to delete unknown attributes

## 1.32.1 _2022-01-24_
- Hotfix - fixed phone number in delivery address incompatible with older WooCommerce versions

## 1.32.0 _2022-01-19_
- Feature - CO-1888 - improved sql payment query
- Bugfix - CO-1887 - fixed error 'Call to a member function is_purchasable() on bool' in custom property attributes
- Bugfix - CO-1892 - added missing phone number in order delivery address
- updated WooCommerce compatibility info to 6.0

## 1.31.0
- Bugfix - CO-1853 - fixed default product price based on default customer group (B2B Market)
- Feature - CO-1848 - do not overwrite is archive property for product attributes
- Updated build process, fixed deprecated parameters order in Primary Key Mapper

## 1.30.0
- Bugfix - fixed manufacturer missing translations bug
- Bugfix - CO-1857 - fixed invalid return type in customer group price (B2B Market)
- Bugfix - CO-1842 - fixed problem with appending values to custom attributes

## 1.29.0
- Bugfix - CO-1835 - fixed DHL post number transfer
- Feature - CO-1808 - added possibility to choose payment types that will be imported only when order is completed (usually manual payment types)
- Feature - CO-1784 - refactored handling of boolean attribute values

## 1.28.1
- Hotfix - added 'invoice' and 'cash_on_delivery' to manual payment methods

## 1.28.0
- Feature - CO-1745 - Added support for Amazon Pay transactions 
- Feature - CO-1744 - Added support for set product visibility by attribute  
- Feature - CO-1268 - Added support for 'Extra Product Options (Product Addons) for WooCommerce' plugin  
- Feature - CO-1223 - Updated translations
- Bugfix - CO-1575 - Split images in push that are used in more than one element
- Bugfix - CO-1793 - B2B Market version 1.0.8.0 customer group prices adjustments  
- Bugfix - Increased minimum decimal precision in vat rate calculation to 2
- Removed inactive contributors

## 1.27.1
- Hotfix - Fixed not linked guest sql method

## 1.27.0
- Feature - CO-1743 - Updated integration with Advanced Shipping Pro plugin
- Bugfix - CO-1587 -  Fixed order status change for invoice payment method
- Bugfix - CO-1534 - Fixed DHL Packstation number transfer   
- Feature - CO-1530 - Added config option to select additional order statuses
- Feature - CO-1349 - Added support for Custom Product Tabs plugin
- Bugfix - CO-1318 - Added config option to disable recommended B2B Market settings 
- Feature - CO-1288 - Added config option to support nextAvailableInflowDate

## 1.26.2
- Bugfix - CO-1722 - bulk prices import

## 1.26.1
- Hotfix detailed shipping gross price
- Updated tested up to versions

## 1.26.0
- Feature - Controllers refactoring to fix PHP8 compatibility

## 1.25.0
- Feature - CO-1461 - Added product tax class guessing on product push

## 1.24.1
- Hotfix taxClassId problem

## 1.24.0
- Feature - CO-1429 - Get full state name if available on customer order pull
- Bugfix - CO-1513 - Tax rate calculation improvements 

## 1.23.2
- Payment pull hotfix

## 1.23.1
- Merged missing CO-1397 functionality 

## 1.23.0
- Bugfix - CO-1485 - Paypal PUI text fix
- Bugfix - CO-1476 - Importing manual orders fix
- Bugfix - CO-1410 - Fixed German Market digital product set
- Bugfix - CO-1397 - Fixed overwriting image description
- Bugfix - CO-1285 - Save manufacturer even without transferred i18ns
- Feature - CO-1277 - Added variation sorting support

## 1.22.0
- Bugfix - CO-1370 - Improved setting shipping vat rate
- Bugfix - CO-1406 - Fixed problem with duplicated variations after initial import
- Bugfix - CO-1452 - Fixed setting invalid variation value
- Bugfix - CO-1484 - Fixed variants preselection

## 1.21.1
- Bugfix - Added missing constant

## 1.21.0
- Bugfix - CO-1237 - Fixed deleting product specifics
- Bugfix - CO-1316 - Fixed importing not existing category images
- Feature - CO-1341 - Added support for "Rank Math SEO" plugin
- Feature - CO-1356 - Added support for "Additional Variation Images Gallery" plugin

## 1.20.0
- Feature - CO-1295 - added support for blacklist products and categories in B2BMarket
- Feature - CO-1251 - added new attribute 'wc_notify_customer_on_overselling' (true/false) to set notify option on oversell
- Feature - CO-1376 - when available get vat rate from product price model in price quick sync   

## 1.19.0
- Bugfix - CO-1372 - fixed customer group update
- Bugfix - CO-1343 - fixed cross-selling pull
- Bugfix - CO-1338 - fixed sanitizing image name on push
- Bugfix - CO-1337 - fixed paypal payment method mapping

## 1.18.0
- Changed compatibility info to WooCommerce 5
- Removed platform version from Connector identify call

## 1.17.0
- Bugfix - CO-1254 - fixed empty values in Cross-Selling pull
- Bugfix - CO-1274 - fixed parent images are attached to children
- Bugfix - CO-1272 - fixed saving category slug when url path is empty
- Bugfix - CO-1241 - fixed saving images with same name
- Bugfix - CO-1304 - partially shiped orders have now status pending
- Bugfix - CO-1261 - German Market: pay by invoice(kauf auf rechnung) orders are now unpaid
- Enhancement - CO-1243 - allow saving html tags in attribute values
- Info - WooCommerce compatibility updated to 4.8, Wordpress compatibility updated to 5.6

## 1.16.1
- Bugfix - fresh installation process missing token

## 1.16.0
- Enhancement - Unified connector config to database config, removed all items except developer logging from config.json file
- Bugfix - CO-1240 - Fixed vat calculation comparing values
- Bugfix - CO-1239 - Fixed price transfer precision, price gross item is now rounded at pull 
- Bugfix - CO-1221 - Fixed 'not all images were sent' error

## 1.15.2
- Bugfix - CO-1213 - Delete product image if not used
- Bugfix - CO-1193 - Fixed tax id import

## 1.15.1
- Enhancement - Fixed tax rate calculation    

## 1.15.0
- Enhancement - Product price refactoring, unified normal and quick sync calls to one method
- Bugfix - Fixed setting bulk prices
- Bugfix - Fixed setting standard price when B2B Market is active
- Bugfix - Reverted transfer priceGross on customer order pull, set item price precision to minimum of 4
- Bugfix - Increased tax rate calculation precision to 4
- Bugfix - CO-1161 - DHL postnumber is now correctly transfered 
- Bugfix - CO-1174 - Customer group can be changed on customer update

## 1.14.2
- Bugfix - Price decimal precision increased to minimum of 4

## 1.14.1
- Bugfix - Price quicksync set price to 0 

## 1.14.0
- Bugfix - CO-1175 - Fixed split tax on shipping when there are two or more tax rates 
- Enhancement - CO-1139 - Product/Category with special in name is now correctly imported
- Enhancement - CO-1045 - Allow to change price import precision. Setting is basing on WooCommerce decimal prices setting for frontend
- Bugfix - CO-982 - RRP/UVP price in B2B market is not set correctly

## 1.13.1
- Bugfix - CO-1160 - Removed deprecated PHP function call get_magic_quotes_gpc()

## 1.13.0
- Bugfix - CO-1134 - Invalid customer group id sent on pull
- Bugfix - CO-1133 delete transients after quicksync

## 1.12.0
- Info - removed setPriceGross in CustomerOrderItem
- Info - removed setTotalSumGross in CustomerOrder
- Info - removed minimum price decimals condition in CustomerOrderItem
- Info - removed price cutting in CustomerOrder
- Enhancement - Vat calculations improvements, it's calculated basing directly on priceNet and priceGross
- Enhancement - Added option to recalculate order before pull when order has coupons
- Enhancement - Added possibility to transfer product type in the attribute 'wc_product_type' but type need to exist in WooCommerce 
  
## 1.11.1
- Bugfix - Paypal Plus PUI auto loading fix

## 1.11.0
- Bugfix - Invalid manufacturer query when deleting image
- Enhancement - CO-984 - Pull payment only if order is linked
- Enhancement - CO-1067 - Added support for Paypal Plus PUI
- Bugfix - CO-898 - Image alt is no more set as tmp filename when not present
- Bugfix - CO-1109 - Added exception when default customer group is not set in connector settings

## 1.10.0  
- Enhancement - CO-1086 - Changed supplier delivery time to handling time method 
- Enhancement - CO-1049 - Added compatibility for plugin name change Perfect Brands for WooCommerce

## 1.9.5 
- Bugfix - Reverted changes related to rounding vat on item from version 1.9.4
- Bugfix - Fixed typo in DeliveryNote controller 
- Enhancement - CO-979 - Added delivery time support for Germanized plugin
- Enhancement - CO-965 - Added fallback when shipping vat rate is 0 then vat rate is the highest rate from products 

## 1.9.4
- Enhancement - CO-991 - If there is exactly one 'tax' order item type in order, rate_percent from it will be used instead of calculating vat rate manually
- Enhancement - Item price gross is now used without rounding for manually vat calculation
- Bugfix - Increased vat value precision to 2 digits when it's manually calculated
- Enhancement - CO-955 - iframe tag in description is now correctly saved  

## 1.9.3
- Enhancement - Added default value for 'dhl_wunschpaket_neighbour_address_addition' attribute in format: {shipping address zipcode} {shipping address city}
- Bugfix - CO-981 - Fixed saving meta fields from B2B market cause variable product type was switched to simple
- Bugfix - CO-975 - DHL Wunschpaket: Added default salutation 'Herr' if no salutation is present
- Bugfix - CO-855 - Fixed connector setting: 'Abgeschlossene Bestellungen importieren' doesnt't work 

## 1.9.2
- Bugfix - Stock level doubled when canceling order, added 'woocommerce_can_restore_order_stock' in status_change.push to prevent
- Bugfix - fixed manufacturer image linking, added missing condition

## 1.9.1
- Enhancement - added backup plugins compatibility: BackupBuddy, UpdraftPlus - Backup/Restore
- Info - marked BackWPup as incompatible plugin
- Enhancement - CO-931 - Added support for VR pay eCommerce - WooCommerce plugin

## 1.9.0
- Enhancement - CO-915 - Added compatibility with WooCommerce 4
- Minor fixes related to code inspections
- Removed stripslashes_deep call on super globals

## 1.8.5
- Increased minimum PHP version to 7.1.3
- Increased versions tested up to WooCommerce: 3.9 and Wordpress: 5.3
- Fix - CO-862 - VAT ID on customer is not always set
- Fix - CO-707 - Unified tax rates in global data pull

## 1.8.4
- Fix - CO-860 - Bulk Prices upper limit "infinite" fix
- Fix - DHL for Woocommerce invalid argument fix
- Fix - CO-820 - Coupon vat rate is not set
- Fix - CO-807 - Variable products are not imported correctly
- Enhancement - CO-750 - Added product Upsells field to synchronization, Crossselling field fixed
- Enhancement - CO-773 - Customer Vat number is now correctly transferred on pull 
- Enhancement - CO-780 - Support for minimum order quantity push (B2B plugin)

## 1.8.3
- Enhancement - CO-628 - Support for shipment tracking numbers (Required plugin: Advanced Shipment Tracking for WooCommerce)
- Enhancement - CO-695 - Support for dhl "wunschpaket" service (Required plugin: DHL for WooCommerce)
- Fix - CO-726 - Regular price import missing (B2B plugin)
- Enhancement - CO-729 - Product as service attribute (Germanized plugin)

## 1.8.2
- Fix - custom property always sent
- Fix - completed orders never included in import
- Enhancement - CO-511 - control sell only one of this item in each order
- Fix - round bug on order item price gross
- Fix - round bug on order item net
- Fix - casting bugs on sevreal germainzed classes
- Enhancement - Prepare for B2B-Market 1.0.4

## 1.8.1
- Fix - CO-507 - variable product seems to be simple product bug 
- Enhancement - Wordpress 5.2 Support
- Enhancement - Adjust recommend settings
- Enhancement - Add info content on settings
- Fix - CO-462 - set correct tax for orders and additional costs
- Enhancement - Change german market ppu handling
- Enhancement - Prevent missing prices bug
- Fix - germanized prices bug
- Fix - German Market free shipping bug
- Fix - Special prices bug
- Enhancement - Update special prices handling
- Fix - Net bugs on unit price germanized
- Fix - special price not removed bug
- Fix - wrong seo post update
- Enhancement - Refactor some code on product push sequence
- Fix - Warning blocks sync bug
- Enchancement - Preparations B2B-Market 1.0.4 CG Pull / Prices Pull/Push / Special Prices Pull/Push

## 1.8.0
- Enhancement - Support of HTML-TEXT in descriptions
- Enhancement - Separate menu for all JTL-Connector settings
- Enhancement - Support of seo for "manufacturer" (Required plugin: Yoast SEO / Beta)
- Enhancement - Support of seo for "categories" (Required plugin: Yoast SEO / Beta)
- Enhancement - Switch to new "SupportedPluginHelper" functions
- Enhancement - Use new meta keys instead of use mapped ones
- Enhancement - Remove unnecessary files
- Enhancement - new install routine
- Enhancement - Change some building logic
- Enhancement - Increase product(attribute/variation/custom_property/specifics/ push performance
- Enhancement - CO-466 - Support of German Market (BETA ! NO EAN)
- Enhancement - CO-483 - German Market: measurement_units (BETA)
- Enhancement - CO-480 - German Market: digital product type (BETA)
- Enhancement - CO-467 - German Market: base prices (BETA)
- Enhancement - CO-483 - German Market: delivery times (BETA)
- Enhancement - CO-474 - German Market: preselect variations (BETA)
- Enhancement - German Market: alt delivery note (BETA)
- Enhancement - German Market: purchased note note (BETA)
- Enhancement - German Market: suppress shipping notice (BETA)
- Enhancement - CO-434 - Support of B2B Market (BETA)
- Enhancement - CO-479 - B2B Market: bulk prices support (BETA)
- Enhancement - CO-468 - B2B Market: rrp support (BETA)
- Enhancement - CO-253 - B2B Market:customer groups (BETA)
- Enhancement - CO-478 - B2B Market:customer groups based prices (BETA)
- Enhancement - B2B Market/JTL-CONNECTOR/WAWI: special price for customer groups
- Fix - special prices
- Fix - custom fields
- Fix - CO-462 - set correct vat for all order items

## 1.7.2
- Enhancement - CO-436 - Support of seo for "products" (Required plugin: Yoast SEO / RC)
- Enhancement - CO-431 - Support of product brand (Required plugin: Perfect WooCommerce Brands / RC)
- Fix - manufacturer push
- Fix - manufacturer i18n meta pull
- Fix - ean & ean i18n pull
- Fix - customer order pull
- Fix - Yoast Seo Premium Bug
- Fix - missing billing information
- Fix - specifc value not exist bug
- Enhancement - some function attr
- Enhancement - some germanized features

## 1.7.1
- Enhancement - CO-436 - Support of seo for products (Required plugin: Yoast SEO / !!! BETA !!!)
- Enhancement - CO-431 - Support of product brand (Required plugin: Perfect WooCommerce Brands)
- Enhancement - CO-424 - delivery time support(RC)
- Enhancement - CO-429 - ean support(RC)
- Enhancement - Update UI texts
- Enhancement - New supported plugin validation
- Enhancement - New information on settings tab
- Enhancement - New functional attributes added
- Enhancement - Add fallback for i18n on specific & specific value pull
- Fix - nosearch && payable attribute bug
- Fix - Delivery time creation bug
- Fix - Return void mismatch bug

## 1.7.0
- Enhancement - CO-385 - Add Developer Logging on connector configuration tab
- Enhancement - CO-385 - New connector configuration
- Enhancement - CO-424 - delivery time support(BETA)
- Enhancement - CO-429 - ean support(BETA)

## 1.6.4
- Fix - Datetime bug on product push
- Fix - Prevent errors in checksum sql statement
- Fix - Checksum sql statement
- Fix - CO-421 - variant specifics will be deleted after next sync
- Fix - CO-413 - special prices will not be removed

## 1.6.3
- Enhancement - CO-397 - Compatibility with wordpress 5.0
- Fix - CO-398 - jtlwcc_deactivate_plugin not found

## 1.6.2
- Enhancement -CO-374 - Hide variants attributes
- Fix - CO-373 - Update deactivates plugin

## 1.6.1
- Enhancement - CO-302 - optimize plugin update
- Enhancement - CO-365 - submit wcc in wordpress.org plugin store
- Enhancement - CO-369 - improve sync of variants

## 1.6.0
- Enhancement - CO-345 - Compatibility with woocommerce 3.5.x 
- Enhancement - Refactor main plugin files
- Enhancement - Added copy connector url btn
- Enhancement - New build procedure
- Fix - CO-336 - Alt text is not set correctly
- Enhancement - CO-319 - mark article as virtual/downloadable
- Enhancement - CO-302 - optimize plugin update

## 1.5.7
-  Fix - CO-290 - problem with multiple vat in customer orders
-  Fix - Specific mapping issue
-  Fix - missing shipping order item vat
-  Enhancement - shipping class product mapping

## 1.5.6
- Fix - CO-271 fix_product_attribute_push
- Fix - CO-284 - VAT is not set correctly on CustomerOrderItem
- Fix - CO-271 - article attributes removal
- Enhancement - removed integrity check

## 1.5.5
- Enhancement - use dynamic table prefix in all sql queries.
- Enhancement - version file included into connector build.

## 1.5.4 
- Fix - cast bug on Customer
- Enhancement - ShippingClass will now be pushed

## 1.5.3
- Fix - remove image pull bug
- Fix - remove one integrity check test

## 1.5.2
- Fix - Specific value error bug

## 1.5.1
- Fix - Specific value language bug

## 1.5.0
- Enhancement - Add specific support(Beta)
- Enhancement - Add new attribute handling(Beta)
- Enhancement - IntegrityCheck in install routine

## 1.4.12
- Fix - wrong vat on shipping items

## 1.4.11
- Fix - wrong status on product pull
- Fix - push didn't update product_variation information

## 1.4.10 
- Enhancement - CO-170 Added an incompatible list on settings page
- Enhancement - CO-171 Added copy password field on settings page
- Fix - CO-164 setting new name in category push.
- Fix - CO-170 Extend some language file entries
- Fix - double subtraction of coupons

## 1.4.9
- Fix - CO-152 Changed method because discount wasn't set correctly on pull.
- Enhancement - Implement fallback for no creation date.
- Enhancement - Remove unclear push method calls
- Fix - CO-136 wrong count of images in stats

## 1.4.8
- Fix - CO-135 Fix reduced tax rate not saved
- Fix - Fix categories are duplicated on push
- Fix - Fix German translations
- Enhancement - Refactor plugin settings part

## 1.4.7
- Enhancement - Support WC 3.2
- Enhancement - Add customer note field
- Fix - product modified date null pointer
- Fix - Delete all options and tables on uninstall
- Fix - php backwards compatibility to 5.4

## 1.4.6
- Fix - CO-112 product id instead of variation id linked in order items
- Fix - CO-113 product creation date updated
- Fix - CO-109 product feedback is deactivated on update

## 1.4.5
- Enhancement - CO-108 Take the 'woocommerce_tax_based_on' option to calculate the order item VAT

## 1.4.4
- Fix - build on PHP7 error
- Fix - string to boolean parse error
- Fix - constraint creation on installation

## 1.4.3
- Fix - product variations not pushed
- Fix - not detected locale
- Fix - category sort table not filled
- Fix - for getting the product of an order item returns a product even it does not exists

## 1.4.2
- Enhancement - Update product by matching SKU
- Fix - linking table creation SQL
- Fix - setting updated as timestamp
- Enhancement - Fallback alt text for image
- Enhancement - Update translation

## 1.4.1
- Fix - free shipping
- Enhancement - Strip HTML tags from keywords

## 1.4.0
- Enhancement - Support of WooCommerce 3
- Enhancement - Only pull double product image once
- Enhancement - Refactoring order item taxes for accurate tax calculation
- Enhancement - Refactor Germanized integration
- Enhancement - Remove minimum oder and packaging quantity validation on push
- Fix - primary key mapper for unsupported types
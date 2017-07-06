1.4.0
-----
- Support of WooCommerce 3
- Only pull double product image once
- Refactoring order item taxes for accurate tax calculation
- Refactor Germanized integration
- Remove mimimum oder and packaging quantity validation on push
- Fix primary key mapper for unsupported types

1.3.6
-----
- Add option for defining the start date orders are pulled
- Fix account information of direct debit
- Parse order total returning string to float
- Skip product images if product not found
- Add fix for formal/informal locales
- Fix methods invoked on false (data not found)
- Fix WordPress locale function not found
- Remove widget

1.3.5
-----
- Add fallback for temp folder
- Fix inactive product boolean/string attribute value
- Fix currency and language global data
- Language English and German name supported now

1.3.4
-----
- Optimize logging 
- Fix product special price without date limit
- Change is_active product flag to an attribute
- Customer and stock level fixes
- Fix Germanized error
- Refactoring

1.3.3
-----
- Fix include completed orders
- Fix shipping positions not pulled
- Fix regular price
- Fix shipping weight
- Fix product regular price pull
- Fix product special price not shown as special
- Add price decimals
- Admin error shows warning

1.3.2
-----
- Fix autoloading in admin backend and frontend
- Fix \wc_variation_attribute_name used for older versions
- Use integers as endpoint ids for linking tables where possible
- Database refactoring
- Fix images with names
- Fix image data update

1.3.1
-----
- Add payment method fallback
- Fix image pull duplicated entries

1.3.0
-----
- Split up linking table to dedicated tables
- Add option to specify the child product name format

1.2.3
-----
- Fix stock level update error

1.2.2
-----
- Fix product variation not transferred correctly
- Do not include allowed backorders in availability
- Fix divided shipping costs of Germanized Plugin

1.2.1
-----
- Fix customer model php doc
- Set indices within table creation

1.2.0
-----
- Add support for WooCommerce Version 2.6 ("woocommerce_termmeta" table is removed)
- Based on woocommerce the processing status means paid and is included now not only for PayPal
- Add "JTL-Connector" before the error message in the plugins list
- Fasten customer pull queries 
 
1.1.0
-----
- Add option for not pulling completed orders based on high amount of data (this also includes Customers and Payments)
- Add option to (un)hide the dashboard widget
- Refactor settings page and use Options API
- Add attributes and variations to customer order item child articles
- Divide payment query to completed and paypal
- Add Germanized fee call for multiple MwSt.
- Add tax rate and tax class cache for customer order item
- Refactor taxes in "normal" customer order item
- Optimize customer and order queries
- Set default customer group
- Only update category structure on changes
- Better pagination for images
- Update product units based on Germanized versions greater or equal 1.6
- Fix product stock level when changing from not manging to managing stock
- Delete line breaks in SQL log

1.0.0
-----
- Add i18n for Image
- Optimize master products to sync logic
- Filter products which cannot be represented in WooCommerce e.g. "Abnahmeintervalle"
- Write own term count mechanism
- Round weight to third decimal point
- Fix image parameter type
- Fix product stock level for parent products
- Write logging disabled config as default

0.9.0
-----
- Add support fur custom attributes
- Add support for defined attributes (payable, nosearch)
- Add new customer order item types (coupon, surcharge)
- Do not pull child products where the parent does not exists

0.8.0
-----
- Transfer coupons as free position
- Check empty transaction id
- Set product variation combination name
- Sync only stock status on product stock level
- Do not pull stock level if product does not manage stock
- Product variation id without "pa_" for taxonomies

0.7.0
-----
- Add fees as order item
- Make discount price positive
- Add WpErrorLogger
- Add connector widget for dashboard
- Create constraints only if table engine is innodb
- Refactor image stats
- Do not save connector version

0.6.1
-----
- Fix unit products pull
- Fix division by zero on unit price
- Save master products to sync for finish call in database
- Only sync variable products on quick sync (100% fix)
- Set regular price to price on sale

0.6.0
-----
- Add gross price in orders
- Add invoice payment type
- Add pui to invoice orders
- Add plugin namespace loading dynamically
- Remove WordPressTrait and log errors
- Don't pull attachments without file
- Check permalink setting is not basic
- Fix image SQLs
- Fix image linking functions
- Fetch linked images only on construction
- Refactor image master delete
- Fix product base price with option

0.5.0
-----
- Add capture request check for redirected servers
- Add support for canHandle and handle events in order to add complete main entities
- Fix global attributes
- Change order status mapping
- Fix paid orders and payments
- Extract several util classes from one class
- Fix typo in translation
- Fix customer order total price with quantity
- Fix shipping items on germanized entered with vat included
- Extract methods
- Extract identity linking
- Refactor for higher quality

0.4.0
-----
- Support shipping class push
- Add delivery time for germanized
- Fix cross selling overwrite
- Fix product base price in germanized
- Fix customer order shipping items with germanized
- Fix multiple gallery images just working on one transport
- Refactor coding style
- Get category sorting in one SQL
- Fix customer pull query
- Change status mapping
- Sort categories after each push
- Use English as default and add German translations
- Define constants in image controller

0.3.1
-----
- Fix import
- Fix quotations
- Fix return cross selling on push
- Exclude product variation as cross selling
- Refactor Germanized implementation

0.3.0
-----
- Support Germanized Plugin out of the box
- Add file deletion for images nor referenced by other posts
- Fix constraints creation on plugin activation
- Fix variation values which are global attributes
- Fix price just updated on second transfer
- Fix payment SQL transferring too much
- Fix category delete query
- Fix using WooCommerce method only available since 2.5
- Remove not needed classes
- Do not double log linking
- Refactor structure of code

0.2.3
-----
- Refactor WordPress Plugin part
- Other version check without including WooCommerce
- Product Stock Level change for not existing products
- Refactor customer order item to fit regular WooCommerce behavior and put sharable taxes in germanized plugin
- Remove Date Util and put method in Util
- Fix product price push
- Fix product tags

0.2.2
-----
- Add VAT for shipping
- Add product meta url
- Refactor naming
- Refactor price updates
- Fix image stats
- Use query for variation combination pull
- Fix query for variation combination pull
- Fix price not transferred
- Fix product special price time frame
- Fix DateTimeZone
- Product Attribute also for child products
- Add missing VAT calculation on special prices
- Include check on suhosing extension for phar
- Change allowed post status for paypal transactions

0.2.1
-----
- Respect WooCommerce option of entering prices with or without VAT
- Transfer guest as customer
- Run category preorder only on changes
- Optimize customer order

0.2.0
-----
- Add missing limit on customer order pull  
- Fix problem with different params for connector url  
- Support all WordPress users for making an order
- Change language from WooCommerce Shop country to WordPress language
- Fix shipping method name
- Fix customer order address country
- Fix category level tree order
- Fix minimum php version conflicts
- Fix invoice to bank transfer
- Fix orders marked as paid

0.1.2
-----
- Fix WorPress trait file name
- Change plugin documentation URL

0.1.1
-----
- Fix magic quotes check
- Fix pagename or name key for site name
- Fix customer order item product not found
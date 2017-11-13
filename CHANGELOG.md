1.4.8
-----
- CO-135 Fix reduced tax rate not saved
- Fix categories are duplicated on push
- Fix German translations
- Refactor plugin settings part

1.4.7
-----
- Support WC 3.2
- Add customer note field
- Fix product modified date null pointer
- Delete all options and tables on uninstall
- Fix php backwards compatibility to 5.4

1.4.6
-----
- CO-112 Fix product id instead of variation id linked in order items
- CO-113 Fix product creation date updated
- CO-109 Fix product feedback is deactivated on update

1.4.5
-----
- CO-108 Take the 'woocommerce_tax_based_on' option to calculate the order item VAT

1.4.4
-----
- Fix build on PHP7 error
- Fix string to boolean parse error
- Fix constraint creation on installation

1.4.3
-----
- Fix product variations not pushed
- Fix not detected locale
- Fix category sort table not filled
- Fix for getting the product of an order item returns a product even it does not exists

1.4.2
-----
- Update product by matching SKU
- Fix linking table creation SQL
- Fix setting updated as timestamp 
- Fallback alt text for image
- Update translation

1.4.1
-----
- Fix free shipping
- Strip HTML tags from keywords

1.4.0
-----
- Support of WooCommerce 3
- Only pull double product image once
- Refactoring order item taxes for accurate tax calculation
- Refactor Germanized integration
- Remove minimum oder and packaging quantity validation on push
- Fix primary key mapper for unsupported types
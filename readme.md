=== WooCommerce JTL-Connector ===
Contributors: ntbyk
Donate link: http://example.com/
Tags: warenwirtschaft, jtl, connector, wms, erp
Requires at least: 3.0.1
Tested up to: 4.9.8
Requires PHP: 5.6.4
WC requires at least: 3.4
WC tested up to: 3.5.1
Stable tag: 1.6.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Durch die Schnittstelle können Sie den Funktionsumfang Ihrer Shop-Software um die Vorzüge einer leistungsstarken ERP-Software erweitern.
 
== Description ==
 
Mit diesem JTL-Connector binden Sie WooCommerce an JTL-Wawi an und synchronisieren z.B. Artikel, Bestände und Aufträge. 
Sie können den Connector auch nutzen, um Daten aus einen WooCommerce-Shop nach JTL-Wawi zu transferieren, um dann einen weiteren Onlineshop einzusetzen.
 
Sie arbeiten mit einem WooCommerce-Shop und haben dort bereits einen Datenbestand aufgebaut. 
Jetzt möchten Sie ein Warenwirtschaftssystem an Ihren WooCommerce-Shop anschließen. 
Dazu setzen Sie eine leere JTL-Wawi auf und verbinden diese Wawi über den JTL-Connector mit Ihrem WooCommerce-Shop. 
Im Anschluss möchten Sie den gesamten Datenbestand Ihres WooCommerce-Shops automatisiert in Ihre JTL-Wawi übertragen.
 
== Installation ==
 
This section describes how to install the plugin and get it working.
 
1. Sichern Sie die Datenbank Ihres aktiven WooCommerce-Shops, bevor Sie mit den folgenden Schritten fortfahren.
1. Laden Sie sich einen aktuellen JTL-Connector für WooCommerce herunter und besorgen Sie sich eine gültigen Lizenzschlüssel für Ihre Shop-Domain.
1. Bei erfolgreicher Installation des Connectors wird Ihnen jetzt der Connector innerhalb des Plugins WooCommerce aufgelistet.
 
== Changelog ==
 
= 1.6.0 = 
* CO-345 - Connector mit WooCommerce 3.5.x kompatibel machen
* Refactor main plugin files 
* Added copy connector url btn
* New build procedure
* CO-336 - Alternativtext wird nicht gesetzt
* CO-319 - Artikel als virtuell markieren
* CO-302 - Plugin Update optimieren

= 1.5.7 =
* Fixed CO-290 - "Probleme bei der gleichzeitigen Übernahme von Aufträgen mit unterschiedlichem MwSt-Satz"
* Fixed Specific mapping issue
* Fixed missing shipping order item vat
* Fixed shipping class product mapping

= 1.5.6 =
* CO-271 fix_product_attribute_push
* Fixed CO-284 - VAT is not set correctly on CustomerOrderItem
* Fixed CO-271 - Artikelattribute werden nicht entfernt
* removed integrity check 

= 1.5.5 =
* use dynamic table prefix in all sql queries.
* version file included into connector build.

= 1.5.4 =
* fix cast bug on Customer
* ShippingClass will now be pushed

= 1.5.3 =
* remove image pull bug
* remove one integrity check test

= 1.5.2 =
* Specific value error bug

= 1.5.1 =
* Specific value language bug

= 1.5.0 =
* Add specific support(Beta)
* Add new attribute handling(Beta)
* IntegrityCheck in install routine

= 1.4.12 =
* Fixed wrong vat on shipping items

= 1.4.11 =
* Fixed wrong status on product pull
* Fixed push didn't update product_variation information

= 1.4.10 =
* CO-170 Added an incompatible list on settings page
* CO-171 Added copy password field on settings page
* CO-164 Fixed setting new name in category push.
* CO-170 Extend some language file entries
* Fixed double subtraction of coupons

= 1.4.9 =
* CO-152 Changed method because discount wasn't set correctly on pull.
* Implement fallback for no creation date.
* Remove unclear push method calls
* CO-136 Fixed wrong count of images in stats

= 1.4.8 =
* CO-135 Fix reduced tax rate not saved
* Fix categories are duplicated on push
* Fix German translations
* Refactor plugin settings part

= 1.4.7 =
* Support WC 3.2
* Add customer note field
* Fix product modified date null pointer
* Delete all options and tables on uninstall
* Fix php backwards compatibility to 5.4

= 1.4.6 =
* CO-112 Fix product id instead of variation id linked in order items
* CO-113 Fix product creation date updated
* CO-109 Fix product feedback is deactivated on update

= 1.4.5 =
* CO-108 Take the 'woocommerce_tax_based_on' option to calculate the order item VAT

= 1.4.4 =
* Fix build on PHP7 error
* Fix string to boolean parse error
* Fix constraint creation on installation

= 1.4.3 =
* Fix product variations not pushed
* Fix not detected locale
* Fix category sort table not filled
* Fix for getting the product of an order item returns a product even it does not exists

= 1.4.2 =
* Update product by matching SKU
* Fix linking table creation SQL
* Fix setting updated as timestamp 
* Fallback alt text for image
* Update translation

= 1.4.1 =
* Fix free shipping
* Strip HTML tags from keywords

= 1.4.0 =
* Support of WooCommerce 3
* Only pull double product image once
* Refactoring order item taxes for accurate tax calculation
* Refactor Germanized integration
* Remove minimum oder and packaging quantity validation on push
* Fix primary key mapper for unsupported types

== Upgrade Notice ==
 
= 1.6.0-dev = 
* CO-345 - Connector mit WooCommerce 3.5.x kompatibel machen
* Refactor main plugin files 
* Added copy connector url btn
* New build procedure
* CO-336 - Alternativtext wird nicht gesetzt
* CO-319 - Artikel als virtuell markieren
 
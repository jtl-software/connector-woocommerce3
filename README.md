#Dokumentation
Wird im folgenden die Abkürzung **Ger** verwendet, bedeutet dies, dass die Funktionalität nur mit dem Germanized Plugin erhätlich ist. Dieses Plugin wird von fast allen unserer Kunden genutzt, damit nötige Funktionen wie Grundpreis oder genaue Steuerberechnung bei Bestellungen verfügbar sind. Im Connector wird daher standardmäßig die kostenlose Funktion dieses Plugins unterstützt.

##Global Data

###Currency
Es gibt nur eine Währung, welche in den WooCommerce Einstellungen beabreitet werden kann. Die Einstellungen werden in der options Tabelle gespeichert.

###Customer Group
WooCommerce fügt den WP Roles eine *customer* Rolle hinzu. Die Kundengruppe hat keine Auswirking auf den Shop und regelt nur die Rechte des Benutzer. Diese ist die einzige Kundengruppe.

###Language
Die Sprache des Shops richtet sich nach der Sprache der WordPress Frontend Einstellung. Für eine Mehrsprachigkeit wird ein Plugin benötigt.

###Measurement Unit (Ger)
Können mit dem Extra Menüpunkt *Einheiten* verwaltet werden.

###Product Type
Hier handelt es sich um die vier mit WooCommerce kommenden Produkttypen von denen nur zwei von JTL-Wawi unterstützt werden können. Die Stücklisten könnten als *grouped* Produkttyp abgebildet werden jedoch sind die Artikel immer nur mit einer Anzahl von eins in der Liste, sodass aus Verständnisgründen die Funktionen nicht gemappt werden.

###Shipping Class
Können in den Einstellungen unter *Versand* > *Versandklassen* verwaltet werden.

###Shipping Methods
Sind versteckt unter *Versand* > *Versandzonen* für die jeweiligen Zonen bzw. eine für die "übrigen Länder" Zone

###Tax Rates
In den Einstellungen einfach unter *Mehrwertsteuer* zu finden. Hier ist zu beachten, dass Germanzied im Einrichtungsdialog es ermöglicht für alle EU Länder die MwSt. anzulegen. Für diese müssten dann in der Wawi auch alle Sätze existieren.

##Categories
Im Storefront Theme sind keine Meta Tags für Description und Keywords vorhanden. Desweiteren fehlen attributes, customer groups und invisibilities. Eine Kategorie ist im Frontend unsichtbar, wenn Sie keine Produkte enthält. Um einen Kategoriebaum zu bilden und die Kategorien in der richtigen Reihenfolge zu ziehen wird die Tabelle *jtl_connector_category_level* gefüllt.

##Products
Produkte werden mit dem post_type *product* oder *product_variation* in der posts Tabelle gespeichert. Alles Zusatzinformationen werden als key value Pairs in der postmeta Tabelle gespeichert. Häufiger Fehler bei nicht sichtbaren Produkten ist, dass in den Einstellungen unter *Produkt* > *Darstellung* nur Kategorien ausgewählt sind.

Order Status WooCommerce
------------------------
- **Pending** payment – Order received (unpaid)
- **Failed** – Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (i.e., PayPal)
- **Processing** – Payment received and stock has been reduced – the order is awaiting fulfillment. All product orders require processing, except those that are Digital and Downloadable.
- **Completed** – Order fulfilled and complete – requires no further action
- **On-Hold** – Awaiting payment – stock is reduced, but you need to confirm payment
- **Cancelled** – Cancelled by an admin or the customer – no further action required (Cancelled orders do not reduce stock by default)
- **Refunded** – Refunded by an admin – no further action required

Not supported by WooCommerce
----------------------------
- CrossSellingGroup
- Unit
- Warehouse
- Customer: account credit, birthday, delivery instructions, discount, fax, newsletter subscription, mobile, 
salutation, title, origin, VAT number, attributes
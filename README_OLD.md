Für die Entwicklung 
1. In das wp-content/plugins Verzeichnis wechseln
2. git clone git@gitlab.jtl-software.de:woo-jtl-connector/jtl-woocommerce-3-connector.git woo-jtl-connector
3. cd woo-jtl-connector
4. composer install

Zum releasen
1. composer update --no-dev
2. Version in version Datei, woo-jtl-connector.php Header und globale Variable anpassen
3. phing release
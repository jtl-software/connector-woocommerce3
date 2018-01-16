Für die Entwicklung 
1. In das wp-content/plugins Verzeichnis wechseln
2. git clone git@gitlab.jtl-software.de:jtlconnector/jtl-woocommerce-3-connector.git jtlconnector
3. cd jtlconnector
4. composer install

Zum releasen
1. composer update --no-dev
2. Version in version Datei, jtlconnector.php Header und globale Variable anpassen
3. phing release
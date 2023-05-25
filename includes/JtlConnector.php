<?php

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Config\ConfigSchema;
use JtlWooCommerceConnector\Connector;

final class JtlConnector //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @return void
     */
    public static function capture_request(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $wp;

        if (!empty($wp->request) && ($wp->request === 'jtlconnector' || $wp->request === 'index.php/jtlconnector')) {
            self::unslash_gpc();

            $connector   = new Connector();
            $application = new Application(CONNECTOR_DIR);

            $features = $application->getConfig()->get(ConfigSchema::FEATURES_PATH);
            if (!file_exists($features))
            {
                copy(sprintf('%s.example', $features), $features);
            }

            $application->run($connector);
        }
    }

    /**
     * @return void
     */
    private static function unslash_gpc(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $_GET     = array_map('stripslashes_deep', $_GET);
        $_POST    = array_map('stripslashes_deep', $_POST);
        $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
        $_SERVER  = array_map('stripslashes_deep', $_SERVER);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
}

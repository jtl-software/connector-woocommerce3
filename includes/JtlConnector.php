<?php

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Config\ConfigSchema;
use JtlWooCommerceConnector\Connector;

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */
final class JtlConnector
{
    public static function capture_request()
    {
        global $wp;

        if (!empty($wp->request) && ($wp->request === 'jtlconnector' || $wp->request === 'index.php/jtlconnector')) {
            self::unslash_gpc();

            $connector = new Connector();
            $application = new Application(CONNECTOR_DIR);

            $features = $application->getConfig()->get(ConfigSchema::FEATURES_PATH);
            if(!file_exists($features)){
                copy(sprintf('%s.example', $features), $features);
            }

            $application->run($connector);

        }
    }

    private static function unslash_gpc()
    {
        $_GET = array_map('stripslashes_deep', $_GET);
        $_POST = array_map('stripslashes_deep', $_POST);
        $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_SERVER = array_map('stripslashes_deep', $_SERVER);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
}

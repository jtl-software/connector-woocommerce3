<?php

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Config\ConfigSchema;
use Jtl\Connector\Core\Config\FileConfig;
use JtlWooCommerceConnector\Connector;
use Psr\Log\LogLevel;

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

            $config = new FileConfig(\sprintf('%s/config/config.json', CONNECTOR_DIR));
            $config->set(ConfigSchema::SERIALIZER_ENABLE_CACHE, false);

            $connector   = new Connector();
            $application = new Application(CONNECTOR_DIR, $config);

            // abort existing session
            if (\session_status() === PHP_SESSION_ACTIVE) {
                \session_abort();
            }

            if ($config->get(ConfigSchema::DEBUG) === true) {
                $application->getConfig()->set(ConfigSchema::LOG_LEVEL, LogLevel::DEBUG);
                $application->getLoggerService()->setLogLevel(LogLevel::DEBUG);
            }

            $features = $application->getConfig()->get(ConfigSchema::FEATURES_PATH);
            if (!file_exists($features)) {
                copy(sprintf('%s.example', $features), $features);
            }

            $application->run($connector);
            exit();
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

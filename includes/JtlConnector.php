<?php

/**
 * @author    Sven Mäurer <sven.maeurer@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */
final class JtlConnector
{
    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function capture_request()
    {
        global $wp;
        if (!empty($wp->request) && ($wp->request === 'jtlconnector' || $wp->request === 'index.php/jtlconnector')) {
            $application = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            if (!get_magic_quotes_gpc()) {
                self::unslash_gpc();
            }
            try {
                if (file_exists(CONNECTOR_DIR . '/connector.phar')) {
                    if (is_writable(sys_get_temp_dir())) {
                        include_once('phar://' . CONNECTOR_DIR . '/connector.phar/src/bootstrap.php');
                    } else {
                        _e(sprintf('Directory %s has no write access.', sys_get_temp_dir()), TEXT_DOMAIN);
                    }
                } else {
                    include_once(CONNECTOR_DIR . '/src/bootstrap.php');
                }
            } catch (\Exception $e) {
                if (is_object($application)) {
                    $handler = $application->getErrorHandler()->getExceptionHandler();
                    $handler($e);
                }
            }
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

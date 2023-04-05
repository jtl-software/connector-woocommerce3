<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */
final class JtlConnector
{
    protected static $_instance = null;

    /**
     * @return JtlConnector|null
     */
    public static function instance(): ?JtlConnector
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return void
     */
    public static function capture_request(): void
    {
        global $wp;

        if (!empty($wp->request) && ($wp->request === 'jtlconnector' || $wp->request === 'index.php/jtlconnector')) {
            $application = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }

            self::unslash_gpc();

            try {
                require(JTLWCC_CONNECTOR_DIR . '/src/bootstrap.php');
            } catch (\Exception $e) {
                if (is_object($application)) {
                    $handler = $application->getErrorHandler()->getExceptionHandler();
                    $handler($e);
                }
            }
        }
    }

    /**
     * @return void
     */
    private static function unslash_gpc(): void
    {
        $_GET     = array_map('stripslashes_deep', $_GET);
        $_POST    = array_map('stripslashes_deep', $_POST);
        $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
        $_SERVER  = array_map('stripslashes_deep', $_SERVER);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
}

<?php

final class JtlConnector //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected static $_instance = null; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

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
    public static function capture_request(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
    private static function unslash_gpc(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $_GET     = array_map('stripslashes_deep', $_GET);
        $_POST    = array_map('stripslashes_deep', $_POST);
        $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
        $_SERVER  = array_map('stripslashes_deep', $_SERVER);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    }
}

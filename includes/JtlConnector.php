<?php

declare(strict_types=1);

use Jtl\Connector\Core\Application\Application;
use Jtl\Connector\Core\Config\ConfigSchema;
use Jtl\Connector\Core\Config\FileConfig;
use Jtl\Connector\Core\Logger\LoggerService;
use Jtl\Connector\Core\Utilities\Validator\Validate;
use JtlWooCommerceConnector\Authentication\RequestAuthenticator;
use JtlWooCommerceConnector\Connector;
use JtlWooCommerceConnector\Session\AuthenticatedSqliteSessionHandler;
use Psr\Log\LogLevel;

final class JtlConnector //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * @return void
     * @throws \Noodlehaus\Exception\EmptyDirectoryException
     * @throws \InvalidArgumentException
     */
    public static function capture_request(): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        global $wp;

        if (!empty($wp->request) && ($wp->request === 'jtlconnector' || $wp->request === 'index.php/jtlconnector')) {
            self::unslash_gpc();

            $config = new FileConfig(\sprintf('%s/config/config.json', CONNECTOR_DIR));
            $config->set(ConfigSchema::SERIALIZER_ENABLE_CACHE, false);

            $connector      = new Connector();
            $application    = new Application(CONNECTOR_DIR, $config);
            $sessionHandler = new AuthenticatedSqliteSessionHandler(\sprintf('%s/var', CONNECTOR_DIR));
            Connector::registerErrorSanitizer(
                $application->getEventDispatcher(),
                $application->getLoggerService()->get(LoggerService::CHANNEL_ERROR)
            );
            $application->setSessionHandler($sessionHandler);

            // Authentication runs first: nothing else is handled for unauthenticated requests
            // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Intentional: token-based auth, values are only compared, never output
            $jtlrpc  = $_GET['jtlrpc'] ?? $_POST['jtlrpc'] ?? '';
            $jtlauth = $_GET['jtlauth'] ?? $_POST['jtlauth'] ?? null;
            // phpcs:enable
            $authError = RequestAuthenticator::check(
                \is_string($jtlrpc) ? $jtlrpc : '',
                $jtlauth,
                static fn (string $sessionId): bool => $sessionHandler->validateId($sessionId)
            );
            if ($authError !== null) {
                \wp_send_json(
                    RequestAuthenticator::createErrorResponse(\is_string($jtlrpc) ? $jtlrpc : '', $authError)
                );
            }

            // abort existing session
            if (\session_status() === PHP_SESSION_ACTIVE) {
                \session_abort();
            }

            if ($config->get(ConfigSchema::DEBUG) === true) {
                $application->getConfig()->set(ConfigSchema::LOG_LEVEL, LogLevel::DEBUG);
                $application->getLoggerService()->setLogLevel(LogLevel::DEBUG);
            }

            $features = $application->getConfig()->get(ConfigSchema::FEATURES_PATH);

            if (!\is_string($features)) {
                throw new \InvalidArgumentException(
                    \esc_html("Expected features to be a string but got " . \gettype($features) . " instead.")
                );
            }

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
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Intentional: reverse WordPress magic quotes for JTL Connector request processing; connector uses token-based auth, not WP nonces
        $_GET     = array_map('stripslashes_deep', $_GET);
        $_POST    = array_map('stripslashes_deep', $_POST);
        $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
        $_SERVER  = array_map('stripslashes_deep', $_SERVER);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        // phpcs:enable
    }
}

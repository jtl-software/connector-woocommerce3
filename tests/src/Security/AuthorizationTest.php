<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use JtlWooCommerceConnector\Tests\TestCase;

/**
 * Tests that authorization checks are present in security-critical functions.
 *
 * These tests verify the code structure rather than runtime behavior because
 * the vulnerable functions (JtlConnectorAdmin::save, downloadJTLLogs,
 * clearJTLLogs) are tightly coupled to WordPress and cannot be loaded in
 * isolation without a full WordPress bootstrap.
 *
 * @covers \JtlConnectorAdmin
 */
class AuthorizationTest extends TestCase
{
    private const string ADMIN_FILE  = __DIR__ . '/../../../includes/JtlConnectorAdmin.php';
    private const string PLUGIN_FILE = __DIR__ . '/../../../woo-jtl-connector.php';

    /**
     * @return void
     */
    public function testSaveMethodContainsCapabilityCheck(): void
    {
        $source = $this->extractMethodSource(self::ADMIN_FILE, 'public static function save');

        $this->assertStringContainsString(
            "current_user_can('manage_woocommerce')",
            $source,
            'JtlConnectorAdmin::save() must check for manage_woocommerce capability'
        );
    }

    /**
     * @return void
     */
    public function testSaveMethodContainsNonceVerification(): void
    {
        $source = $this->extractMethodSource(self::ADMIN_FILE, 'public static function save');

        $this->assertStringContainsString(
            "check_admin_referer('settings_save_woo-jtl-connector')",
            $source,
            'JtlConnectorAdmin::save() must verify the admin referer nonce'
        );
    }

    /**
     * @return void
     */
    public function testSettingsFormContainsNonceField(): void
    {
        $source = (string)\file_get_contents(self::ADMIN_FILE);

        $this->assertStringContainsString(
            "wp_nonce_field('settings_save_woo-jtl-connector')",
            $source,
            'The settings form must include a wp_nonce_field for CSRF protection'
        );
    }

    /**
     * @return void
     */
    public function testDownloadJTLLogsContainsCapabilityCheck(): void
    {
        $source = $this->extractFunctionSource(self::PLUGIN_FILE, 'function downloadJTLLogs');

        $this->assertStringContainsString(
            "current_user_can('manage_woocommerce')",
            $source,
            'downloadJTLLogs() must check for manage_woocommerce capability'
        );
    }

    /**
     * @return void
     */
    public function testDownloadJTLLogsContainsNonceVerification(): void
    {
        $source = $this->extractFunctionSource(self::PLUGIN_FILE, 'function downloadJTLLogs');

        $this->assertStringContainsString(
            "check_ajax_referer('jtl_logs_nonce')",
            $source,
            'downloadJTLLogs() must verify the AJAX nonce'
        );
    }

    /**
     * @return void
     */
    public function testClearJTLLogsContainsCapabilityCheck(): void
    {
        $source = $this->extractFunctionSource(self::PLUGIN_FILE, 'function clearJTLLogs');

        $this->assertStringContainsString(
            "current_user_can('manage_woocommerce')",
            $source,
            'clearJTLLogs() must check for manage_woocommerce capability'
        );
    }

    /**
     * @return void
     */
    public function testClearJTLLogsContainsNonceVerification(): void
    {
        $source = $this->extractFunctionSource(self::PLUGIN_FILE, 'function clearJTLLogs');

        $this->assertStringContainsString(
            "check_ajax_referer('jtl_logs_nonce')",
            $source,
            'clearJTLLogs() must verify the AJAX nonce'
        );
    }

    /**
     * @return void
     */
    public function testDownloadJTLLogsDoesNotWriteToPublicPath(): void
    {
        $source = $this->extractFunctionSource(self::PLUGIN_FILE, 'function downloadJTLLogs');

        $this->assertStringNotContainsString(
            'wp-content/plugins',
            $source,
            'downloadJTLLogs() must not write ZIP files to a publicly accessible path'
        );
    }

    /**
     * @return void
     */
    public function testJavascriptSendsNonceWithAjaxCalls(): void
    {
        $source = $this->extractFunctionSource(
            self::PLUGIN_FILE,
            'function woo_jtl_connector_settings_javascript'
        );

        $this->assertStringContainsString(
            '_ajax_nonce',
            $source,
            'AJAX calls must include a nonce parameter'
        );

        $this->assertStringContainsString(
            "wp_create_nonce('jtl_logs_nonce')",
            $source,
            'The JavaScript must use a nonce created with wp_create_nonce'
        );
    }

    /**
     * Extracts a function body from a file by searching for the function signature.
     *
     * @param string $filePath
     * @param string $signature
     * @return string
     */
    private function extractFunctionSource(string $filePath, string $signature): string
    {
        return $this->extractCodeBlock($filePath, $signature);
    }

    /**
     * Extracts a method body from a file by searching for the method signature.
     *
     * @param string $filePath
     * @param string $signature
     * @return string
     */
    private function extractMethodSource(string $filePath, string $signature): string
    {
        return $this->extractCodeBlock($filePath, $signature);
    }

    /**
     * Extracts a code block (function/method) from source by matching braces.
     *
     * @param string $filePath
     * @param string $signature
     * @return string
     */
    private function extractCodeBlock(string $filePath, string $signature): string
    {
        $content = (string)\file_get_contents($filePath);
        $pos     = \strpos($content, $signature);

        $this->assertNotFalse($pos, "Signature '{$signature}' not found in {$filePath}");

        $braceStart = \strpos($content, '{', $pos);
        $this->assertNotFalse($braceStart, "Opening brace not found after '{$signature}'");

        $depth  = 0;
        $length = \strlen($content);

        for ($i = $braceStart; $i < $length; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return \substr($content, $pos, $i - $pos + 1);
                }
            }
        }

        $this->fail("Could not find closing brace for '{$signature}'");
    }
}

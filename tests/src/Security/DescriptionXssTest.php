<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Tests\Security;

use JtlWooCommerceConnector\Tests\TestCase;

/**
 * Regression tests for the stored XSS via unfiltered product, category and manufacturer descriptions (CO-3668).
 * The WordPress kses filters must stay active for all description writes and the raw variation meta fields
 * must be sanitized explicitly.
 *
 * @covers \JtlWooCommerceConnector\Controllers\ProductController
 * @covers \JtlWooCommerceConnector\Controllers\CategoryController
 * @covers \JtlWooCommerceConnector\Controllers\ManufacturerController
 */
class DescriptionXssTest extends TestCase
{
    private const string SRC_DIR = __DIR__ . '/../../../src';

    /**
     * @return void
     */
    public function testKsesFiltersAreNeverRemoved(): void
    {
        $offenders = [];
        $iterator  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::SRC_DIR));
        foreach ($iterator as $file) {
            if (
                !$file instanceof \SplFileInfo
                || $file->getExtension() !== 'php'
                || $file->getFilename() === 'TermDescriptionFilter.php'
            ) {
                continue;
            }
            $source  = (string)\file_get_contents($file->getPathname());
            $pattern = '/remove_filter\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"]wp_filter(_post)?_kses[\'"]/';
            if (\preg_match($pattern, $source) === 1) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'kses filters must not be removed in: ' . \implode(', ', $offenders));
    }

    /**
     * @return void
     */
    public function testTermDescriptionsUsePostAllowlistInsteadOfBeingUnfiltered(): void
    {
        $filter = (string)\file_get_contents(self::SRC_DIR . '/Utilities/TermDescriptionFilter.php');
        $this->assertStringContainsString("add_filter('pre_term_description', 'wp_filter_post_kses')", $filter);

        $connector = (string)\file_get_contents(self::SRC_DIR . '/Connector.php');
        $this->assertStringContainsString('TermDescriptionFilter::register();', $connector);
    }

    /**
     * @return void
     */
    public function testVariationDescriptionMetaIsSanitized(): void
    {
        $source = (string)\file_get_contents(self::SRC_DIR . '/Controllers/ProductController.php');

        $this->assertMatchesRegularExpression(
            '/update_post_meta\(\$productId, \'_variation_description\', \\\\wp_kses_post\(/',
            $source
        );
        $this->assertMatchesRegularExpression(
            '/update_post_meta\(\$productId, \'_mini_dec\', \\\\wp_kses_post\(/',
            $source
        );
    }

    /**
     * @return void
     */
    public function testWpmlVariationDescriptionMetaIsSanitized(): void
    {
        $source = (string)\file_get_contents(self::SRC_DIR . '/Integrations/Plugins/Wpml/WpmlProductVariation.php');

        $this->assertStringContainsString('\wp_kses_post($productI18n->getDescription())', $source);
        $this->assertStringContainsString('\wp_kses_post($productI18n->getShortDescription())', $source);
    }
}
